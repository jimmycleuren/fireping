---
layout: doc
title: "Installation guide for the Master"
nav_order: 1
parent: Getting started
permalink: /getting-started/master
---

# Download fireping
Before you can setup a Master, you need to download some dependencies and the Fireping source. After that you can configure the Master

## Installing OS dependencies
First open a terminal and run these commands

```bash
sudo apt-get install ca-certificates apt-transport-https
wget -q https://packages.sury.org/php/apt.gpg -O- | sudo apt-key add -

echo "deb https://packages.sury.org/php/ stretch main" | sudo tee /etc/apt/sources.list.d/php.list

sudo apt-get update

sudo apt-get install fping php-cli php-xml php-mbstring php-zip php-curl php-rrd git supervisor composer
```

## Download & Install Fireping
To install Fireping on the system run these commands in a terminal

```bash
cd /opt
sudo git clone https://github.com/jimmycleuren/fireping.git
cd fireping
sudo composer install
```

---

# Setting up the Master
## Configuring the database
First install MySQL and other dependencies

``` bash
sudo apt-get install mariadb-server php-mysql nginx php-fpm redis-server acl rrdtool php-rrd
```

Then make the database and configure it by entering a MySQL REPL
```bash
sudo mysql
```

and executing these SQL statements (you can chose whatever user credentials you want)

```SQL
CREATE DATABASE fireping; -- don't change
CREATE USER 'fireping'@'localhost' IDENTIFIED BY 'password'; -- change username (but leave @'localhost') and password here
GRANT ALL PRIVILEGES ON fireping . * TO 'fireping'@'localhost'; -- remember to use the right username
FLUSH PRIVILEGES;
```

Then go into the `.env` file using `sudo vim .env` or any other text editor you like and edit the `DATABASE_URL` variable

* db_user is the username you created (fireping in the example)
* db_password is the password you created (fireping in the example)
* db_name is fireping

Make sure u are in the directory `/opt/fireping/` and run

```bash
sudo php bin/console doctrine:migrations:migrate
```

## Configuring the dashboard server
Copy the file `docker/nginx/symfony.conf` to `/etc/nginx/sites-enabled` and edit the file
```bash
sudo vim /etc/nginx/sites-enabled/symfony.conf
```

change `/app/` to `/opt/fireping/` on line 3 and line 47 and then change line 11 to
```SQL
fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
```

Now restart nginx by typing
```bash
sudo systemctl restart nginx
```

Create a user for admin by running
```bash
php bin/console fos:user:create
```

and follow the steps. When the user is created, you only need to give it admin rights by running
```bash
php bin/console fos:user:promote
```

if needed open the hosts file and add your localhost as `fireping.develop in it`
```bash
sudo vim /etc/hosts
```
```
...
127.0.0.1   fireping.develop
...
```

Enter the username you created in the previous step and give it the role `role_admin`.
Now you are done with the setup of the master and if you go to `fireping.develop`, you should see the dashboard. 

![Dashboard](/assets/img/dashboard_main_page.png) 

Next step is to create [Slave nodes](/getting-started/slaves)