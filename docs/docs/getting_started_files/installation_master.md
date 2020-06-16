---
title: "Installation guide for the master"
permalink: /docs/getting-started/master
key: docs-getting-started-master
---

# Download fireping
Before you can setup a master, you need to download some dependencies and the Fireping source. After that you can configure the master

## Install OS dependencies
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

# Setting up the master
## Configure the database
First install MySQL and other dependencies

``` bash
sudo apt-get install mariadb-server php-mysql nginx php-fpm redis-server acl rrdtool php-rrd
```

Then make the database and configure it by entering a MySQL REPL
```bash
sudo mysql
```

and execute these SQL statements (you can chose whatever user credentials you want)

```sql
CREATE DATABASE fireping; -- don't change
CREATE USER 'fireping'@'localhost' IDENTIFIED BY 'password'; -- change username (but leave @'localhost') and password here
GRANT ALL PRIVILEGES ON fireping . * TO 'fireping'@'localhost'; -- remember to use the right username
FLUSH PRIVILEGES;
```

Then go into the `.env` file using `sudo vim .env` or any other text editor you like and edit the `DATABASE_URL` variable

* **db_user** is the username you created (`fireping` in the example on `CREATE USER`)
* **db_password** is the password you created (`fireping` in the example on `IDENTIFIED BY`)
* **db_name** is fireping

![env vim](/fireping/assets/images/env_edit.png)

Make sure you are in the directory `/opt/fireping/` and run

```bash
sudo php bin/console doctrine:migrations:migrate
```

## Configure the dashboard server
Copy the file `docker/nginx/symfony.conf` to `/etc/nginx/sites-enabled` and edit the file
```bash
sudo cp docker/nginx/symfony.conf /etc/nginx/sites-enabled
sudo vim /etc/nginx/sites-enabled/symfony.conf
```

On the line showing
```
server_name fireping.develop;
```
the `fireping.develop` can be changed to any name you want. If you want to host a server you should change the name on this line to that of the server.

Change `/app/` to `/opt/fireping/` on line 3 and line 47 
![Vim of symfony.conf](/fireping/assets/images/symfony_conf_edit.png)

and then change line 11 to
```
fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
```

Now restart nginx
```bash
sudo systemctl restart nginx
```

Create a default admin user

```bash
root@2e4107ae411f:/app# php bin/console fireping:create:user
Please enter the username (defaults to admin):
Please enter the password:
Please enter the email address: admin@localhost.local
Please choose roles for this user
  [0] ROLE_ADMIN
  [1] ROLE_API
 > 0
root@2e4107ae411f:/app#
```

Create a slave user:

```
root@2e4107ae411f:/app# php bin/console fireping:create:user
Please enter the username (defaults to admin): slave
Please enter the password:
Please enter the email address: slave@localhost.local
Please choose roles for this user
  [0] ROLE_ADMIN
  [1] ROLE_API
 > 1
root@2e4107ae411f:/app#
```

Now you are done with the setup of the master and if you go to `http://fireping.develop`, you should see the dashboard. 

If needed, open the hosts file and add your localhost as `fireping.develop` or the server name you chose in it
```bash
sudo vim /etc/hosts
```
```
...
127.0.0.1   fireping.develop
...
```

![Dashboard](/fireping/assets/images/dashboard_main_page.png) 

Next step is to create [slave nodes](/fireping/docs/getting-started/slaves)
