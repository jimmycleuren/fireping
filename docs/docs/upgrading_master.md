---
title: "Upgrading Master"
permalink: /docs/how-to/upgrade-master/
key: docs-how-to-upgrade-master
---

# Upgrade Master

## Docker

- Pull the latest image.

```bash
$ docker image pull jimmycleuren/fireping
```

- Restart the container with the same settings as when you originally started it. You can do this easily with management tools like portainer.

## Manual Installation

If you did a manual installation, the simplest way to upgrade is this:

- Stop your reverse proxy to prevent new connections.

```bash
$ service nginx stop
```

- Pull a new release

```bash
$ git pull
```

- Install new dependencies

```bash
$ ./composer.phar install --verbose --prefer-dist --no-dev --optimize-autoloader --no-scripts --no-suggest
```

- Apply new migrations

```bash
$ php bin/console doctrine:migrations:migrate
```

- Clear your cache

```bash
$ APP_ENV=prod php bin/console cache:clear
```