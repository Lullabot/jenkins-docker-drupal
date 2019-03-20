#!/bin/bash

apt-add-repository ppa:ondrej/php -y

apt-get install -y --allow-downgrades --allow-remove-essential --allow-change-held-packages \
  php7.3-cli php7.3-dev \
  php7.3-pgsql php7.3-sqlite3 php7.3-gd \
  php7.3-curl \
  php7.3-imap php7.3-mysql php7.3-mbstring \
  php7.3-xml php7.3-zip php7.3-bcmath php7.3-soap \
  php7.3-intl php7.3-readline

apt-get install -y apache2 \
  libapache2-mod-php

a2enmod headers rewrite

# Run apache as vagrant to simplify permissions. On production
# environments code should not be writable by the web server.
sed -i "s/www-data/vagrant/" /etc/apache2/envvars

VHOST="<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName drupal.local
    DocumentRoot /home/vagrant/code/web

    <Directory "/home/vagrant/code/web">
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog \${APACHE_LOG_DIR}/error.log
    CustomLog \${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
"

echo "$VHOST" > "/etc/apache2/sites-available/drupal.conf"

a2ensite drupal

systemctl restart apache2

apt-get install -y mariadb-server-10.1 \
  mariadb-client

# debconf no longer allows setting a password.
# https://salsa.debian.org/mariadb-team/mariadb-10.0/blob/jessie/debian/mariadb-server-10.0.README.Debian
mysql -e "CREATE USER 'drupal'@'localhost' IDENTIFIED BY 'secret';"
mysql -e "GRANT ALL ON drupal.* TO 'drupal'@'localhost' IDENTIFIED BY 'secret' WITH GRANT OPTION;"
mysql -e "FLUSH PRIVILEGES;"
mysql -e "CREATE DATABASE drupal;"
zcat /vagrant/installed.sql.gz | mysql drupal

