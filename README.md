# Pha_WR_SRE
Generate weekly report from phabricator for SRE.

# installation

## install libphutil

```bash
git clone https://github.com/phacility/libphutil.git
```

## install Pha_WR_SRE

```bash
git clone git@github.com:haw-haw/Pha_WR_SRE.git
```
## onenote setting(optional)
goto http://www.onenote.com/EmailSettings to setting which section to save the note and which email address can send email.

# usage

```bash
cd Pha_WR_SRE;
cp config.ini.example config.ini;
vim config.ini;
# modify config.ini as your env
php phawrsre.php;
```

Thanks to https://github.com/DONSA/phabriport.git
