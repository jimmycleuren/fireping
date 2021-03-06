---
title: "Fireping"
permalink: /docs/installation/slave/fireping/
key: docs-installation-slave-fireping
---

This section details how to install and configure a slave instance for Fireping. Within this setup, a slave is responsible for fetching its configuration from the master, performing the requested work and publishing its results back to the master. Periodically, it will also report some stats on itself to the master.

A slave is installed from the same code base as the master. In fact, it's possible to run a slave from the same machine if so desired.

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

Then install the required system dependencies.

```bash
$ sudo apt-get update
$ sudo apt-get install -y php7.4 php7.4-xml php7.4-mysql php7.4-mbstring php7.4-zip php7.4-curl php-rrd rrdtool git zip supervisor fping
```

Clone the repository to a location of your choosing.

```bash
$ sudo mkdir /opt/fireping-slave && cd /opt/fireping-slave
$ sudo git clone https://github.com/jimmycleuren/fireping.git .
```

Now [install Composer](https://getcomposer.org/download/) and fetch the vendor dependencies.

```bash
$ ./composer.phar install --verbose --prefer-dist --no-dev --optimize-autoloader --no-scripts --no-suggest
```

Then, run some post-install scripts.

```bash
$ php bin/console cache:clear --env=slave
$ php bin/console cache:warmup --env=slave
```

# Certificates

Remember to load the necessary CAs into your certificate store if your master's isn't trusted by default.

```bash
$ mv certificate.crt /usr/local/share/certificate.crt
$ sudo update-ca-certificate
``` 

# Configuration

Fireping's configuration file is called `.env.local` and is stored in the installation directory.

## APP_ENV

This changes the running application's environment.

```bash
APP_ENV=slave
```

## SLAVE_URL

This is the URL of your Fireping master installation.

Example:

```bash
SLAVE_URL=https://fireping.corp.example
```  

## SLAVE_NAME

This is the username of the API user that will be used to interact with the Fireping API. Every slave needs a unique username.

Example:

```bash
SLAVE_NAME=foobar
```

## SLAVE_PASSWORD

This is the password of the user specified in SLAVE_NAME. It is used to interact with the Fireping API hosted at the instance specified in SLAVE_URL.

Example:

```bash
SLAVE_PASSWORD=MySecretPassword
```
