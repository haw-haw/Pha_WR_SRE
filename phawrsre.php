<?php

require_once '../libphutil/src/__phutil_library_init__.php';

date_default_timezone_set('Asia/Shanghai');

$config = parse_ini_file('config.ini');

$api_parameters_user = [
    'usernames'    => [$config['username']],
];

$client = (new ConduitClient($config['url']))->setConduitToken($config['api_token']);
$user = $client->callMethodSynchronous('user.query', $api_parameters_user);
$user_phid = $user[0]['phid'];
$user_realName = $user[0]['realName'];

$offset = $config['weekday_start'];
$days = $config['days'];
$week_offset = $config['week_before'];
$currentDate = new DateTime();
$year = $currentDate->format("Y");
$week = $currentDate->format("W") - $week_offset;

$start = (new DateTime())->setISODate($year, $week, $offset)->setTime(0, 0, 0);
$end = (new DateTime())->setISODate($year, $week, $offset + 6)->setTime(23, 59, 59);
$start_int = $start->getTimestamp();
$end_int = $end->getTimestamp();

$subject = sprintf(
    '%s的工作周报（%s 年第 %s 周，%s ~ %s）',
    $user_realName,
    $year,
    $week,
    $start->format('m.d'),
    $end->format('m.d')
);

$email_subject = sprintf(
    '%s Week %s(%s ~ %s)',
    $year,
    $week,
    $start->format('m.d'),
    $end->format('m.d')
);

$output = [
    'subject'   => $subject,
    'ongoing'   => [],
    'completed' => [],
    'other'       => []
];

$api_parameters_task_closed = [
    'constraints'   =>  [
        "assigned"  =>  [$user_phid],
        "statuses"  =>  [
            "resolved"
        ],
        "closedStart"   =>  $start_int,
        "closedEnd" =>  $end_int
    ]
];
$tasks = $client->callMethodSynchronous('maniphest.search', $api_parameters_task_closed);
foreach ($tasks['data'] as $key => $task)
{
    $dateModified = new DateTime(date("Y-m-d H:i:s", $task['dateModified']));

    $outputString = sprintf(
        "# %s(T%s)\n",
        $task['fields']['name'],
        $task['id']
    );

    $output['completed'][] = $outputString;
}

$api_parameters_task_open = [
    'constraints'   =>  [
        "assigned"  =>  [$user_phid],
        "statuses"  =>  ["open"],
        "modifiedStart" =>  $start_int,
        "createdEnd"    =>  $end_int
    ]
];

$tasks = $client->callMethodSynchronous('maniphest.search', $api_parameters_task_open);
$taskopen = array();
foreach ($tasks['data'] as $key => $task)
{
    $taskopen[$task['id']] = $task['fields']['name'];
}

$api_parameters_tran = [
    'ids'   => array_keys($taskopen),
];
$trans = $client->callMethodSynchronous('maniphest.gettasktransactions', $api_parameters_tran);

foreach ($taskopen as $task_id => $task_title) {
    foreach ($trans[$task_id] as $key => $tran) {
        if ($tran['dateCreated'] > $start_int and $tran['dateCreated'] < $end_int) {
            $output['ongoing'][] = sprintf(
                "# %s(T%s)\n",
                $task_title,
                $task_id
            );
            break;
        }
    }
}

$message = sprintf(
    "%s\n\nCompleted:\n%s\nOngoing:\n%s\n",
    $output['subject'],
    implode("", $output['completed']),
    implode("", $output['ongoing'])
);

print $message;

$email_subject_encode = "=?UTF-8?B?". base64_encode("$email_subject"). "?=";
$from_email = $config['email'];
$headers = "From: $from_email\r\n". 
        "MIME-Version: 1.0". "\r\n". 
        "Content-type: text/plain; charset=UTF-8". "\r\n";

if ($config['send2on']) {
    $re = mail($config['email4on'], $email_subject_encode, $message, $headers, " -f $from_email");
    if (!$re) {
        echo error_get_last()['message'];
    }
}

if ($config['send2en']) {
    $re = mail($config['email4en'], $email_subject_encode, $message, $headers, " -f $from_email");
    if (!$re) {
        echo error_get_last()['message'];
    }
}

if ($config['write2phame']) {
    $blog_phid = $config['phid_blog'];
    $api_parameters_blog = [
        'transactions'  => [
            [
                "type"  => "blog",
                "value" => "$blog_phid"
            ],
            [
                "type"  =>  "title",
                "value" =>  "$subject"
            ],
            [
                "type"  =>  "subtitle",
                "value" =>  "$email_subject"
            ],
            [
                "type"  =>  "body",
                "value" =>  "$message"
            ],
        ],
    ];
    $result = $client->callMethodSynchronous('phame.post.edit', $api_parameters_blog);
}
