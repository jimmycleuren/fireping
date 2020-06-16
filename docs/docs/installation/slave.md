---
title: "Slave - Installation"
permalink: /docs/getting-started/slave/
key: docs-getting-started-slave
---

This section details how to install and configure a slave instance for Fireping. Within this setup, a slave is responsible for fetching its configuration from the master, performing the requested work and publishing its results back to the master. Periodically, it will also report some stats on itself to the master.

A slave is installed from the same code base as the master. In fact, it's possible to run a slave from the same machine if so desired.

## Debian 9

Start by adding a new package repository so that we can add the PHP7.4 binaries.

```bash
sudo apt-get install -y wget gnupg ca-certificates apt-transport-https
wget -q https://packages.sury.org/php/apt.gpg -O- | sudo apt-key add -

echo "deb https://packages.sury.org/php/ stretch main" | sudo tee /etc/apt/sources.list.d/php.list
```

Then install the required system dependencies.

```bash
sudo apt-get update
sudo apt-get install -y php7.4 php7.4-mysql php7.4-mbstring php7.4-zip php7.4-curl php-rrd rrdtool git zip supervisor fping
```

Clone the repository to a location of your choosing.

```bash
mkdir /opt/fireping-slave
git clone https://github.com/jimmycleuren/fireping.git .
cd fireping
```

Now [install Composer](https://getcomposer.org/download/) and fetch the vendor dependencies.

```bash
# in /opt/fireping-slave/
composer install --verbose --prefer-dist --no-dev --optimize-autoloader --no-suggest
```

# Configuration

Fireping's configuration file is called `.env.local` and is stored in the installation directory.

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

# Slave Registration

A slave user need to be registered with the master. This can be done via the website or via the master CLI.

## Website

Head to the admin page which can be found in the top right corner after logging in.

![Where to find the Admin Page](/fireping/assets/images/admin_where.png)

![Add Slave](/fireping/assets/images/adding_slave_user.png)

## CLI

```bash
root@fc436c8b9973:/opt/fireping# php bin/console fireping:create:user
Please enter the username (defaults to admin): foobar
Please enter the password:
Please enter the email address: foobar@org.example
Please choose roles for this user
  [0] ROLE_ADMIN
  [1] ROLE_API
 > 1
root@fc436c8b9973:/opt/fireping#
```
