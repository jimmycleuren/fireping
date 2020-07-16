---
title: "Upgrading Master"
permalink: /docs/how-to/upgrade-slave/
key: docs-how-to-upgrade-slave
---

# Upgrading Slave

## Docker

- Pull the latest image.

```bash
$ docker image pull jimmycleuren/fireping
```

- Restart the container with the same settings as when you originally started it. You can do this easily with management tools like portainer.

## Manual Install

If you did a manual installation, the simplest way to upgrade is this:

- Pull a new release

```bash
$ git pull
```

- Install new dependencies

```bash
$ ./composer.phar install --verbose --prefer-dist --no-dev --optimize-autoloader --no-scripts --no-suggest
```

- Clear your cache

```bash
$ APP_ENV=slave php bin/console cache:clear
```

- Restart the slave process

```bash
$ supervisorctl
fireping-slave:0                 RUNNING   pid 2598, uptime 47 days, 21:28:16
supervisor> restart fireping-slave:0
```