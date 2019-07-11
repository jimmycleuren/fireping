---
title: "download instructions"
---

# Installation process Linux
## Installating OS dependencies
run these commands in a terminal
```bash
sudo apt-get install ca-certificates apt-transport-https
wget -q https://packages.sury.org/php/apt.gpg -O- | sudo apt-key add -

echo "deb https://packages.sury.org/php/ stretch main" | sudo tee /etc/apt/sources.list.d/php.list

sudo apt-get update

sudo apt-get install fping php-cli php-xml php-mbstring php-zip php-curl php-rrd git supervisor
```

## Installing composer
Install [composer](https://getcomposer.org/download/) by clicking on the link and following the instructions or by the following command
```bash
sudo apt-get install composer
```

## Installing fireping
run the folowing commands
```bash
cd /opt
sudo git clone https://github.com/jimmycleuren/fireping.git
cd fireping
sudo composer install
```

Now you can either setup your `Master`, this let's the program recieve data from the `Slaves` and is the host for the fireping dashboard.
Or setup a `Slave` which will send data to the master to use.