## Requirements

An Ubuntu server (18.04/20.04 should both work fine) - though other linux installations should work too.
- apache2 (or nginx), mysql/mariadb, sqlite need to be installed and running
- php extensions for intl, mysql, sqlite3, mbstring, xml need to be installed and running
- php extention for curl (not required but makes composer run a little faster)
- composer

## Network requirements

Cerebrate communicates via HTTPS so in order to be able to connect to other cerebrate nodes, requiring the following ports to be open:
- port 443 needs to be open for outbound connections to be able to pull contactdb / sharing group information in
- Cerebrate also needs to be accessible (via port 443) from the outside if:
    - you wish to pull interconnect local tools with remote cerebrate instances
    - you wish to act as a hub node for a community where members are expected to pull data from your node


## Cerebrate installation instructions

It should be sufficient to issue the following command to install the dependencies:

- for apache

```bash
sudo apt install apache2 mariadb-server git composer php-intl php-mbstring php-dom php-xml unzip php-ldap php-sqlite3 php-curl sqlite libapache2-mod-php php-mysql
```

- for nginx
```bash
sudo apt install nginx mariadb-server git composer php-intl php-mbstring php-dom php-xml unzip php-ldap php-sqlite3 sqlite php-fpm php-curl php-mysql
```

Clone this repository (for example into /var/www/cerebrate/app)

```bash
sudo mkdir /var/www/cerebrate/app
sudo chown www-data:www-data /var/www/cerebrate/app
sudo -u www-data git clone https://github.com/cerebrate-project/cerebrate.git /var/www/cerebrate/app
```

Run composer

```bash
sudo mkdir -p /var/www/.composer
sudo chown www-data:www-data /var/www/.composer
cd /var/www/cerebrate/app
sudo -H -u www-data composer install
```

Create a database for cerebrate

With a fresh install of Ubuntu sudo to the (system) root user before logging in as the mysql root
```Bash
sudo -i mysql -u root
```

From SQL shell:
```mysql
mysql
CREATE DATABASE cerebrate;
CREATE USER 'cerebrate'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT USAGE ON *.* to cerebrate@localhost;
GRANT ALL PRIVILEGES ON cerebrate.* to cerebrate@localhost;
FLUSH PRIVILEGES;
QUIT;
```

Or from Bash:
```bash
sudo mysql -e "CREATE DATABASE cerebrate;"
sudo mysql -e "CREATE USER 'cerebrate'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';"
sudo mysql -e "GRANT USAGE ON *.* to cerebrate@localhost;"
sudo mysql -e "GRANT ALL PRIVILEGES ON cerebrate.* to cerebrate@localhost;"
sudo mysql -e "FLUSH PRIVILEGES;"
```

Load the default table structure into the database

```bash
sudo mysql -u cerebrate -p cerebrate < /var/www/cerebrate/app/INSTALL/mysql.sql
```

create your local configuration and set the db credentials

```bash
sudo -u www-data cp -a /var/www/cerebrate/app/config/app_local.example.php /var/www/cerebrate/app/config/app_local.php
sudo -u www-data cp -a /var/www/cerebrate/app/config/config.example.json /var/www/cerebrate/app/config/config.json
sudo -u www-data vim /var/www/cerebrate/app/config/app_local.php
```

mod_rewrite needs to be enabled if __using apache__:

```bash
sudo a2enmod rewrite
```

Simply modify the Datasource -> default array's username, password, database fields
This would be, when following the steps above:

```php
    'Datasources' => [
        'default' => [
            'host' => 'localhost',
            'username' => 'cerebrate',
            'password' => 'YOUR_PASSWORD',
            'database' => 'cerebrate',
```

Run the database schema migrations
```bash
sudo -u www-data /var/www/cerebrate/app/bin/cake migrations migrate
sudo -u www-data /var/www/cerebrate/app/bin/cake migrations migrate -p tags
sudo -u www-data /var/www/cerebrate/app/bin/cake migrations migrate -p ADmad/SocialAuth
```

Clean cakephp caches
```bash
sudo rm /var/www/cerebrate/app/tmp/cache/models/*
sudo rm /var/www/cerebrate/app/tmp/cache/persistent/*
```

Create an apache config file for cerebrate / ssh key and point the document root to /var/www/cerebrate/app/webroot and you're good to go

For development installs the following can be done for either apache or nginx:

```bash
# Apache
# This configuration is purely meant for local installations for development / testing
# Using HTTP on an unhardened apache is by no means meant to be used in any production environment
sudo cp /var/www/cerebrate/app/INSTALL/cerebrate_apache_dev.conf /etc/apache2/sites-available/
sudo ln -s /etc/apache2/sites-available/cerebrate_apache_dev.conf /etc/apache2/sites-enabled/
sudo service apache2 restart
```

OR

```bash
# NGINX
# This configuration is purely meant for local installations for development / testing
# Using HTTP on an unhardened apache is by no means meant to be used in any production environment
sudo cp /var/www/cerebrate/app/INSTALL/cerebrate_nginx.conf /etc/nginx/sites-available/
sudo ln -s /etc/nginx/sites-available/cerebrate_nginx.conf /etc/nginx/sites-enabled/
sudo systemctl disable apache2 # may be required if apache is using port
sudo service nginx restart
sudo systemctl enable nginx

```

Now you can point your browser to: http://localhost:8000

To log in use the default credentials below:

- Username: admin
- Password: Password1234
