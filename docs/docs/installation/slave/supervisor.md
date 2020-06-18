---
title: "Supervisord"
permalink: /docs/installation/slave/supervisord/
key: docs-installation-slave-supervisor
---

# Installation

## Debian

```bash
$ apt-get update
$ apt-get install supervisor
```

Make sure it's running

```bash
$ sudo service supervisor status
supervisord is  not running.
$ sudo service supervisor start
Starting supervisor: supervisord.
$ sudo service supervisor status
supervisord is running
```

Add a configuration file for fireping-slave.

```bash
$ vi /etc/supervisor/conf.d/fireping-slave.conf
```

Example:

```bash
[program:fireping-slave]
command=php /opt/fireping-slave/bin/console app:probe:dispatcher --env=slave
process_name=%(process_num)d
numprocs=1
directory=/tmp
autostart=true
autorestart=true
startsecs=5
startretries=20
redirect_stderr=false
stdout_logfile=/var/log/supervisor/fireping-slave.out.log
stdout_capture_maxbytes=1MB
stdout_logfile_backups=3
stderr_logfile=/var/log/supervisor/fireping-slave.error.log
stderr_capture_maxbytes=1MB
stderr_logfile_backups=3
```

Reload the supervisord configuration.

```bash
$ supervisorctl
supervisor> reread
fireping-slave: available
supervisor> add fireping-slave
fireping-slave: added process group
supervisor> status
fireping-slave:0                 RUNNING   pid 21278, uptime 0:00:06
supervisor> exit
```