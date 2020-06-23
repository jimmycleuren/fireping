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
command=php bin/console app:probe:dispatcher --env=slave
user=fireping
directory=/opt/fireping-slave
stderr_logfile=/var/log/supervisor/fireping-slave.error.log
stdout_logfile=/var/log/supervisor/fireping-slave.out.log
```

Reload the supervisord configuration.

```bash
$ supervisorctl
supervisor> reread
fireping-slave: available
supervisor> add fireping-slave
fireping-slave: added process group
supervisor> status
fireping-slave                  RUNNING   pid 21278, uptime 0:00:06
supervisor> exit
```