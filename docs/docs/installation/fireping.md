---
title: "Fireping - Installation"
permalink: /docs/getting-started/fireping/
key: docs-getting-started-fireping
---

# Installation

In this section we'll explain how to setup the fireping application server. Start by installing the necessary software.

## Debian 9

First, install the necessary system dependencies.

```bash
sudo apt-get update
sudo apt-get install -y php7.4 php7.4-mysql php7.4-mbsting php7.4-zip php7.4-curl php7.4-rrd git
```

```bash
cd /opt
sudo git clone https://github.com/jimmycleuren/fireping.git
cd fireping
```

Now [install Composer](https://getcomposer.org/download/) and fetch the vendor dependencies.

```bash
# in /opt/fireping/
composer install --verbose --prefer-dist --no-dev --optimize-autoloader --no-suggest
```

# Configuration

Fireping's configuration file is called `.env.local` and is stored in the installation directory.

## APP_SECRET

This is a secret key used to generate CSRF tokens. You must change it. It should be a mix of alphanumeric characters, at least 32 characters in length.

Example:

```bash
# in /opt/fireping/
php -r "echo 'APP_SECRET=' . sha1(random_bytes(50)) . PHP_EOL;" >> .env.local
```

## DATABASE_URL

This is the connection string to connect to the database with. It is dissected as:

`driver://db_user:db_password@db_host:db_port/db_name?connectionParameters`

Example:

```bash
# Connect to a MySQL database. (driver)
# Connect as user "fireping" with password "my_secret". (db_user:db_password)
# Connect to localhost on port 3306. (db_host:db_port)
# Connect to the fireping database. (db_name)
# The database is running version 5.7. (serverVersion=5.7)
DATABASE_URL=mysql://fireping:my_secret@127.0.0.1:3306/fireping?serverVersion=5.7
```

## REDIS_URL

This is the connection string used to connect to the redis server. It must be prefixed by `redis://` or `rediss://`

Example:

```bash
# Connect to a redis server running on the localhost.
REDIS_URL=redis://localhost
```

## MAILER_URL

This is the connection string of the SMTP server to use when sending out alerts.

Example:

```bash
# Connect to an SMTP server running on localhost.
MAILER_URL=null://localhost # disable e-mail delivery.
# MAILER_URL=smtp://localhost:465?encryption=ssl&auth_mode=login&username=&password= # SMTP example.
```

## MAILER_FROM

Configure this to set the e-mail address to use when sending out alerts.

```bash
MAILER_FROM=fireping@organization.example
```