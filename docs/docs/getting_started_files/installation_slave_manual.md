---
layout: doc
title: "Manual installation guide for the Slaves"
nav_order: 2
parent: Installation guide for the Slaves
grand_parent: Getting started
permalink: /getting-started/slaves/manual
---

# Download fireping
Before you can setup a Slave, you need to download some dependencies and the Fireping source. After that you can configure the Slave

## Installing OS dependencies
First open a terminal and run these commands

```bash
sudo apt-get install ca-certificates apt-transport-https
wget -q https://packages.sury.org/php/apt.gpg -O- | sudo apt-key add -

echo "deb https://packages.sury.org/php/ stretch main" | sudo tee /etc/apt/sources.list.d/php.list

sudo apt-get update

sudo apt-get install fping php-cli php-xml php-mbstring php-zip php-curl php-rrd git supervisor composer
```

## Download & Install Fireping
To install Fireping on the system run these commands in a terminal

```bash
cd /opt
sudo git clone https://github.com/jimmycleuren/fireping.git
cd fireping
sudo composer install
```

---

# Setting up the Slave
Define the folowing environment variables to /opt/fireping/.env
* `SLAVE_NAME=(give a name to this slave)`
* `SLAVE_URL=(the base url for the fireping master)`

## Supervisor
Supervisor will be the process manager used to start your slave and to keep it running.

* `/etc/supervisor/supervisord.conf` is the main configuration file used for supervisor.
* make sure that the `[include]` directive is configured to include the `.conf` files from the conf.d/ directory:
```
[include]
files = /etc/supervisor/conf.d/*.conf
```

* copy the `fireping-slave.conf` file from `/opt/fireping/config/supervisord/fireping-slave.conf` to `/etc/supervisor/conf.d/` by running
```bash
sudo cp /opt/fireping/config/supervisord/fireping-slave.conf /etc/supervisor/conf.d/
```

* `sudo supervisorctl` to start the supervisor front-end interface
* `reread` in supervisorctl to reread the configuration file (will not restart any existing processes)
```
supervisor> reread
fireping-slave: available
```

* `add fireping-slave` to add it to the manager
```
supervisor> add fireping-slave
fireping-slave: added process group
```
* your slave will now start.
* for more information on supervisor run `help` in the `supervisorctl` interface

Adding slave to master
Now open your fireping dashboard, go to `Admin > User` and create a new user with these credentials, role as `role_api` and check the `enabled` box.

![Screenshot of adding a Slave](/fireping/assets/img/adding_slave_user.png)

You should now see the slave if you go back to `fireping.develop/slaves`. (it can take a few seconds)

![Screenshot of adding a Slave](/fireping/assets/img/slaves_added_list.png)

## Logrotate
Slaves will generate a significant amount of logging. In order to mitigate disk usage somewhat, do the following:

```bash
sudo cp /opt/fireping/config/logrotate/fireping-slave /etc/logrotate.d/
```
Then edit the file and adjust the path if necessary

```bash
sudo vim /etc/logrotate.d/fireping-slave
```
Then, if you're doing this retroactively, you can force the logrotate to run now:

```bash
sudo logrotate --force /etc/logrotate.d/fireping-slave
```