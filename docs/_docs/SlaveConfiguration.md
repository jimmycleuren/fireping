---
title: "slave config"
---

# Slaves
## What is a Slave?
The `Slave` is the program that actually pings to servers and generates the data. It will constantly send data to the Master you setup for it.

## Slave configuration
If you'd like to configure the machine as a slave you have to define the folowing environment variables to `/opt/fireping/.env`
- SLAVE_NAME=(give a name to this slave)
- SLAVE_URL=(the base url for the fireping master)

## Supervisor
Supervisor will be the process manager used to start your slave and to keep it running.

- `/etc/supervisor/supervisord.conf` is the main configuration file used for supervisor.
- make sure that the `[include]` directive is configured to include the `.conf` files from the `conf.d/` directory:
```conf
[include]
files = /etc/supervisor/conf.d/*.conf
```
- copy the `fireping-slave.conf` file from `/opt/fireping/config/supervisord/fireping-slave.conf` to `/etc/supervisor/conf.d/` by running
```bash
sudo cp /opt/fireping/config/supervisord/fireping-slave.conf /etc/supervisor/conf.d/
```

- `sudo supervisorctl` to start the supervisor front-end interface
- `reread` in supervisorctl to reread the configuration file (will not restart any existing processes)
```
supervisor> reread
fireping-slave: available
```
- `add fireping-slave` to add it to the manager
```
supervisor> add fireping-slave
fireping-slave: added process group
```
- your slave will now start.

- for more information on supervisor run `help` in the `supervisorctl` interface

## Adding slave to master
In your master make another user with

```bash
php bin/console fos:user:create
```
and give it the credentials you inserted in the `.env` file and then set the role to `role_api` using

```bash
php bin/console fos:user:promote
```

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
``` bash
sudo logrotate --force /etc/logrotate.d/fireping-slave
```
