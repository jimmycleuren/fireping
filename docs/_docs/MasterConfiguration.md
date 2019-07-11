---
title: "master config"
---

# Master
## What is te Master?
The `Master` collects the ping, traceroute,... data from the `Slaves` and can be set up to show it on graphs.

## Install MySQL and other dependencies
```bash
sudo apt-get install mariadb-server php-mysql nginx php-fpm redis-server acl rrdtool php-rrd
```

## Configuring the database
Enter a `MySQL` REPL
```bash
sudo mysql
```

and create the database and user (where `password` is the password)
```mysql
CREATE DATABASE fireping;
CREATE USER 'fireping'@'localhost' IDENTIFIED BY 'password';
GRANT ALL PRIVILEGES ON fireping . * TO 'fireping'@'localhost';
FLUSH PRIVILEGES;
```

Now go into the `.env` file,
```bash
sudo vim .env
```
and edit the `DATABASE_URL` variable.
Change `db_user` to the username, `db_password` to the password and `db_name` to the database name. 
For example: 
```conf
DATABASE_URL=mysql://fireping:password@127.0.0.1:3306/fireping
```

Make sure u are in the directory `/opt/fireping/` and run
```bash
sudo php bin/console doctrine:migrations:migrate
```

Now copy the file `docker/nginx/symfony.conf` from your fireping folder to `/etc/nginx/sites-enabled/`.

Edit the file

```bash
sudo vim /etc/nginx/sites-enabled/symfony.conf
```

and change `/app/` to `/opt/fireping/` on line 3 and line 47.

Then change line 11 to 
```
fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
```

and run

```bash
sudo systemctl restart nginx
```

Now create a user to log in
```bash
php bin/console fos:user:create
```

and follow the steps.

Give the user admin rights

```bash
php bin/console fos:user:promote
```

Give in the user name and give the role `role_admin`

If needed, make sure you update the `/etc/hosts` file. (i.e. master & slave on same device).