#!/bin/sh

# If you would like to do some extra provisioning you may
# add any commands you wish to this file and they will
# be run after the Homestead machine is provisioned.

# run apt-get update first, without it I was getting errors not finding the extensions
sudo DEBIAN_FRONTEND=noninteractive apt-get --assume-yes update

# load any extensions you like here
sudo DEBIAN_FRONTEND=noninteractive apt-get --assume-yes install php-mcrypt php-rrd

sudo service php7.1-fpm restart

mysql -u homestead -e "CREATE USER 'fireping'@'localhost' IDENTIFIED BY 'fireping';"
mysql -u homestead -e "GRANT ALL PRIVILEGES ON fireping.* TO 'fireping'@'localhost';"
mysql -u homestead -e "FLUSH PRIVILEGES;"

cd fireping
composer install
php bin/console doctrine:migrations:migrate -n