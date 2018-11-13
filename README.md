# wr_pha_sre
Generate weekly report from phabricator for SRE.

# installation

## install libphutil

```bash
git clone https://github.com/phacility/libphutil.git
```

## install wr_pha_sre

```bash
git clone git@github.com:haw-haw/Pha_WR_SRE.git
```

# usage

```bash
cd wr_pha_sre;
cp config.ini.example config.ini;
vim config.ini;
# modify config.ini as your env
php phawrsre.php;
```

Thanks to https://github.com/DONSA/phabriport.git
