---
title: "Logrotate"
permalink: /docs/installation/slave/logrotate/
key: docs-installation-master-logrotate
---

# Installation

Because the software generates quite a bit of logs it's recommended to setup logrotate.

## Debian

Install logrotate.

```bash
$ apt-get update
$ apt-get install -y logrotate
```

Configure it:

```bash
$ vi /etc/logrotate.d/fireping-slave
```

Example config:

```bash
/opt/fireping-slave/var/log/slave.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    su www-data www-data
}
```