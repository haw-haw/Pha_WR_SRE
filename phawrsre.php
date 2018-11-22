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

$api_parameters = [
    'ownerPHIDs'    => [$user_phid],
    'order'         => 'order-modified',
];

$client = (new ConduitClient($config['url']))->setConduitToken($config['api_token']);
$tasks = $client->callMethodSynchronous('maniphest.query', $api_parameters);

$offset = $config['weekday_start'];
$days = $config['days'];
$week_offset = $config['week_before'];
$currentDate = new DateTime();
$year = $currentDate->format("Y");
$week = $currentDate->format("W") - $week_offset;

$start = (new DateTime())->setISODate($year, $week, $offset)->setTime(0, 0, 0);
$end = (new DateTime())->setISODate($year, $week, $offset + 6)->setTime(23, 59, 59);

$subject = sprintf(
    'Workly report of Week %s, %s (%s ~ %s) for %s',
    $week,
    $year,
    $start->format('m.d'),
    $end->format('m.d'),
    $user_realName
);

$email_subject = sprintf(
    'Week %s, %s (%s ~ %s)',
    $week,
    $year,
    $start->format('m.d'),
    $end->format('m.d')
);

$output = [
    'subject'   => $subject,
    'ongoing'   => [],
    'completed' => [],
    'other'       => []
];

foreach ($tasks as $key => $task)
{
    $dateModified = new DateTime(date("Y-m-d H:i:s", $task['dateModified']));

    if ($dateModified > $start and $dateModified < $end)
    {
        $outputString = sprintf(
            "# %s(T%s)\n",
            $task['title'],
            $task['id']
        );

        switch ($task['status'])
        {
            case "open":
                $output['ongoing'][] = $outputString;
                break;

            case "resolved":
                $output['completed'][] = $outputString;
                break;

            default:
                $output['other'][] = $outputString.$task['status'];
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

$email_subject = "=?UTF-8?B?". base64_encode("$email_subject"). "?=";
$from_email = $config['email'];
$headers = "From: $from_email\r\n". 
        "MIME-Version: 1.0". "\r\n". 
        "Content-type: text/plain; charset=UTF-8". "\r\n";

if ($config['send2on']) {
    $re = mail($config['email4on'], $email_subject, $message, $headers, " -f $from_email");
    if (!$re) {
        echo error_get_last()['message'];
    }
}

if ($config['send2en']) {
    $re = mail($config['email4en'], $email_subject, $message, $headers, " -f $from_email");
    if (!$re) {
        echo error_get_last()['message'];
    }
}
