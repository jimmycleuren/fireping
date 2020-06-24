---
title: "Fireping - Installation"
permalink: /docs/installation/master/fireping/
key: docs-installation-master-fireping
---

# Installation

In this section we'll explain how to setup the fireping application server. Start by installing the necessary software.

## Debian

Start by adding a new package repository so that we can add the PHP7.4 binaries.

```bash
$ sudo apt-get install -y wget gnupg ca-certificates apt-transport-https
$ wget -q https://packages.sury.org/php/apt.gpg -O- | sudo apt-key add -

# Automatically add for your Debian release. Make sure that https://packages.sury.org/php/dists/ has an entry for your release.
$ env -i bash -c '. /etc/os-release; echo "deb https://packages.sury.org/php/ $VERSION_CODENAME main"' | sudo tee /etc/apt/sources.list.d/php.list
# Alternatively, just do it manually for these known supported releases:
# Debian 9: echo "deb https://packages.sury.org/php/ stretch main" | sudo tee /etc/apt/sources.list.d/php.list
# Debian 10: echo "deb https://packages.sury.org/php/ buster main" | sudo tee /etc/apt/sources.list.d/php.list
```

Then install the necessary system dependencies.

```bash
$ sudo apt-get update
$ sudo apt-get install -y php7.4 php7.4-xml php7.4-fpm php7.4-mysql php7.4-mbstring php7.4-zip php7.4-curl php-rrd rrdtool git zip
```

Make sure that php7.4-fpm is up and running.

```bash
$ sudo service php7.4-fpm status
[FAIL] php-fpm7.4 is not running ... failed!
$ sudo service php7.4-fpm start
$ sudo service php7.4-fpm status
[ ok ] php-fpm7.4 is running.
```

Create a fireping system user.

```bash
$ sudo addgroup --system fireping
Adding group `fireping' (GID 101) ...
Done.
```

```bash
$ sudo adduser --system --home /opt/fireping --shell /bin/bash --ingroup fireping --disabled-password --disabled-login fireping
Adding system user `fireping' (UID 101) ...
Adding new user `fireping' (UID 101) with group `fireping' ...
Creating home directory `/opt/fireping' ...
$ su - fireping
$ whoami
fireping
```

Clone the repository to the system user's home directory.

```bash
$ cd && pwd
/opt/fireping
$ git clone https://github.com/jimmycleuren/fireping.git .
```

Now [install Composer](https://getcomposer.org/download/) and fetch the vendor dependencies.

```bash
$ ./composer.phar install --verbose --prefer-dist --no-dev --optimize-autoloader --no-scripts --no-suggest
```

Then, run some post-install scripts.

```bash
$ php bin/console cache:clear --env=prod
$ php bin/console cache:warmup --env=prod
$ php bin/console assets:install --symlink --relative public
```

Make sure the log file is writable by the user running the webserver. The example below lists one way to do this, however note that this will make the logs readable for any user on the system.

```bash
$ touch ~/var/log/prod.log
$ chmod 777 ~/var/log/prod.log
```

Make sure the `~/var/rrd/` and `~/var/cache/` directories are writable by the webserver user.

```bash
$ chmod -R 777 ~/var/rrd/
$ chmod -R 777 ~/var/cache/
```

# Configuration

Fireping's configuration file is called `.env.local` and is stored in the installation directory. It is not created by default.

## APP_SECRET

This is a secret key used to generate CSRF tokens. You must change it. It should be a mix of alphanumeric characters, at least 32 characters in length.

Example:

```bash
$ php -r "echo 'APP_SECRET=' . sha1(random_bytes(50)) . PHP_EOL;" | sudo tee -a .env.local
```

## DATABASE_URL

This is the connection string to connect to the database with. It is dissected as:

```bash
driver://db_user:db_password@db_host:db_port/db_name?connectionParameters
```

Example:

```bash
# Connect to a MySQL database. (driver)
# Connect as user "fireping" with password "my_secret". (db_user:db_password)
# Connect to localhost on port 3306. (db_host:db_port)
# Connect to the fireping database. (db_name)
# The database is running version MariaDB 10.3.22. (serverVersion=mariadb-10.3.22)
# For MySQL, just use the version number without a mysql prefix.
DATABASE_URL=mysql://fireping:my_secret@127.0.0.1:3306/fireping?serverVersion=mariadb-10.3.22
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

## STORAGE

Configure this to select the storage driver you'd like to use. See [RRD Storage](/docs/installation/master/rrd-storage/) for more information.

Example:

```bash
STORAGE=rrd
```

# Initial Setup

## Execute Migrations

After having installed and configured the Fireping master instance, do the following to complete your setup.

```bash
$ php bin/console doctrine:migrations:status
+----------------------+----------------------+------------------------------------------------------------------------+
| Configuration                                                                                                        |
+----------------------+----------------------+------------------------------------------------------------------------+
| Storage              | Type                 | Doctrine\Migrations\Metadata\Storage\TableMetadataStorageConfiguration |
|                      | Table Name           | migration_versions                                                     |
|                      | Column Name          | version                                                                |
|----------------------------------------------------------------------------------------------------------------------|
| Database             | Driver               | Doctrine\DBAL\Driver\PDOMySql\Driver                                   |
|                      | Name                 | fireping                                                               |
|----------------------------------------------------------------------------------------------------------------------|
| Versions             | Previous             | 0                                                                      |
|                      | Current              | 0                                                                      |
|                      | Next                 | DoctrineMigrations\Version20170508115339                               |
|                      | Latest               | DoctrineMigrations\Version20200430091035                               |
|----------------------------------------------------------------------------------------------------------------------|
| Migrations           | Executed             | 0                                                                      |
|                      | Executed Unavailable | 0                                                                      |
|                      | Available            | 21                                                                     |
|                      | New                  | 21                                                                     |
|----------------------------------------------------------------------------------------------------------------------|
| Migration Namespaces | DoctrineMigrations   | /opt/fireping/src/Migrations                                           |
+----------------------+----------------------+------------------------------------------------------------------------+
$ php bin/console doctrine:migrations:migrate

 WARNING! You are about to execute a database migration that could result in schema changes and data loss. Are you sure you wish to continue? (yes/no) [yes]:
 > yes

[notice] Migrating up to DoctrineMigrations\Version20200430091035
[notice] finished in 5020.8ms, used 24M memory, 21 migrations executed, 96 sql queries
$ 
```

## Create Admin User 

```bash
$ php bin/console fireping:create:user
Please enter the username (defaults to admin): admin
Please enter the password:
Please enter the email address: admin@org.example
Please choose roles for this user
  [0] ROLE_ADMIN
  [1] ROLE_API
 > 0
```

## Slave Registration
   
A slave user need to be registered with the master. This can be done via the website or via the master CLI.

### Website

Head to the admin page which can be found in the top right corner after logging in.

![Where to find the Admin Page](/assets/images/admin_where.png)

![Add Slave](/assets/images/adding_slave_user.png)

### CLI

```bash
$ php bin/console fireping:create:user
Please enter the username (defaults to admin): foobar
Please enter the password:
Please enter the email address: foobar@org.example
Please choose roles for this user
 [0] ROLE_ADMIN
 [1] ROLE_API
> 1
$ 
```