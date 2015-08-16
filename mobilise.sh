#!/usr/bin/env bash

# Mobilise will mobilise all the dependencies of this project in one go!
# Everything will be installed relative to this file (this repo) and then moved/symlinked to the correct place
# This file should appear as /www/snapsearch/mobilise.sh
# Only run this after the server has already been configured, so it should have all the necessary generic web server stuff

# Ask for sudo
if [[ $UID != 0 ]]; then
	echo "Please run this script with sudo:"
	echo "sudo $0 $*"
	exit 1
fi

# Checking for necessary global components
hash git 2>/dev/null || { echo >&2 "git needs to be installed, so aborting"; exit 1; }
hash curl 2>/dev/null || { echo >&2 "curl needs to be installed, so aborting"; exit 1; }
hash perl 2>/dev/null || { echo >&2 "perl needs to be installed, so aborting"; exit 1; }
hash nginx 2>/dev/null || { echo >&2 "nginx needs to be installed, so aborting"; exit 1; }
hash php 2>/dev/null || { echo >&2 "php-cli (php) needs to be installed, so aborting"; exit 1; }
hash php5-fpm 2>/dev/null || { echo >&2 "php-fpm needs to be installed, so aborting"; exit 1; }
hash python 2>/dev/null || { echo >&2 "python needs to be installed, so aborting"; exit 1; }
hash mysql 2>/dev/null || { echo >&2 "mysql needs to be installed, so aborting"; exit 1; }
hash composer 2>/dev/null || { echo >&2 "composer needs to be installed, so aborting"; exit 1; }
hash npm 2>/dev/null || { echo >&2 "npm needs to be installed, so aborting"; exit 1; }
hash bower 2>/dev/null || { echo >&2 "bower needs to be installed, so aborting"; exit 1; }
hash grunt 2>/dev/null || { echo >&2 "grunt-cli (grunt) needs to be installed, so aborting"; exit 1; }

# Find the project's directory from this file
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Change to the project's directory
cd $PROJECT_DIR

# Need something to run change index.php's environment to production
# perl -pi -e "s/((?:[a-z][a-z]+)\(\'ENVIRONMENT\', isset\(\$_SERVER\[\'CI_ENV\'\]\) \? \$_SERVER\[\'CI_ENV\'\] : \'development\'\).)/define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');/g" index.php
# Perhaps it's better to use NGINX or the server module to provide this?
# Eventually mobilise.sh needs to run grunt in order to actually build the final product

# Install all the dependencies
# Composer and NPM should already be available on PATH
# Bower and Grunt-Cli will be required however
# Their STDIN is redirected to /dev/null, this is because it can affect the STDIN of this bash script
echo "Installing dependencies from Composer, NPM and Bower"
composer install --no-dev </dev/null
npm install --production </dev/null
# this has an issue it might request for confirmation (make sure to resolve bower beforehand)
bower install --production --allow-root </dev/null

# Optimising Composer
composer dump-autoload --optimize </dev/null

# Download SlimerJS
read -p "$(tput bold)$(tput setaf 2)Setup SlimerJS? [Y/n]: $(tput sgr0)" -n 1 -r DOWNLOAD_SLIMERJS
echo
if [[ $DOWNLOAD_SLIMERJS =~ ^[Y]$ ]]; then
	echo "Downloading SlimerJS 0.9.0"
	curl http://download.slimerjs.org/releases/0.9.1/slimerjs-0.9.1-linux-i686.tar.bz2 -o slimerjs.tar.bz2
	# If you want to use 64bit make sure to download http://download.slimerjs.org/releases/0.9.1/slimerjs-0.9.1-linux-x86_64.tar.bz2
	echo "Uncompressing and extracting SlimerJS into ./slimerjs"
	mkdir -p slimerjs && tar xjvf slimerjs.tar.bz2 -C slimerjs --strip-components 1
	rm slimerjs.tar.bz2
	echo "Adding SlimerJS to the PATH"
	sudo ln -sf `pwd`/slimerjs/slimerjs /usr/local/bin/slimerjs
fi

# Switch on globbing extension. It cannot be part of an If block
shopt -s extglob

# Bring in Secret Keys
read -p "$(tput bold)$(tput setaf 2)Setup Secrets? [Y/n]: $(tput sgr0)" -n 1 -r DOWNLOAD_SECRETS
echo
if [[ $DOWNLOAD_SECRETS =~ ^[Y]$ ]]; then
	echo "Downloading secret keys relevant to Snapsearch"
	mkdir -p secrets
	curl -L -k -u 'CMCDragonkai' https://bitbucket.org/CMCDragonkai/keys/get/master.tar.gz | tar xzv -C secrets --strip-components 1
	rm -r secrets/!(snapsearch)
	cp -r secrets/snapsearch/* secrets
	rm -r secrets/snapsearch
fi

# How many robots do you want to start?
# Prompt for robot numbers, note that port starts at 8500
# Along with port numbers

# Should create the database if it's not available
echo "Creating database for snapsearch"
read -p "$(tput bold)$(tput setaf 2)Enter username for mysql to setup database, followed by enter, or just hit enter to ignore: $(tput sgr0)" -r MYSQL_USER
# Check if the username has been set
if [ ! -z "$MYSQL_USER" ]; then
	mysql -u $MYSQL_USER -p -e "CREATE DATABASE IF NOT EXISTS snapsearch; show databases;"
fi

# Migrate all tables
echo "Migrating the database relies on a proper configuration of the database in Codeigniter"
read -p "$(tput bold)$(tput setaf 2)Migrate the database to latest?. [Y/n]: $(tput sgr0)" -n 1 -r DATABASE_MIGRATION
echo
if [[ $DATABASE_MIGRATION =~ ^[Y]$ ]]; then
	php index.php cli migrate latest	
fi

# Setting up supervisor upstart script to run this project's robots
echo "Setting up Supervisor Upstart Script"
ROBOT_PATH="`pwd`/robot_scripts"
ESCAPED_ROBOT_PATH="${ROBOT_PATH//\//\\/}"
echo "Copying Supervisor startup script to /etc/init"
sudo cp startup_scripts/supervisord.conf /etc/init/supervisord.conf
echo "Confirming robot script path in the Supervisor startup script"
sudo perl -pi -e "s/chdir .*/chdir $ESCAPED_ROBOT_PATH/g" /etc/init/supervisord.conf
echo "Restarting Supervisord"
sudo service supervisord restart

# Setting up the lockfile directory
mkdir -p snapshots/lockfiles
if ! mountpoint -q "snapshots/lockfiles"; then
	mount -t tmpfs -o size=1M,nr_inodes=400k,mode=755,nodev,nosuid,noexec tmpfs snapshots/lockfiles
	MOUNTING_PATH=$(readlink -f snapshots/lockfiles)
	ESCAPED_MOUNTING_PATH="${MOUNTING_PATH//\//\\/}"
	MOUNTING=$(cat /etc/mtab | grep "$MOUNTING_PATH" | head -n 1)
	sed -i "/$ESCAPED_MOUNTING_PATH/d" /etc/fstab
	echo $MOUNTING >> /etc/fstab
fi

# Setting up NGINX server configuration
echo "Setting up NGINX configuration"
ESCAPED_PROJECT_DIR="${PROJECT_DIR//\//\\/}"
echo "Copying snapsearch.io site config to NGINX sites-enabled"
sudo cp server_config/snapsearch.io /etc/nginx/sites-enabled/snapsearch.io
echo "Confirming root path in snapsearch.io site config"
sudo perl -pi -e "s/root .*/root $ESCAPED_PROJECT_DIR;/g" /etc/nginx/sites-enabled/snapsearch.io
echo "Copying SSL certificate and key to NGINX ssl directory"
sudo mkdir -p /etc/nginx/ssl
sudo cp -f --remove-destination secrets/snapsearch.io.pem /etc/nginx/ssl/snapsearch.io.pem
sudo cp -f --remove-destination secrets/snapsearch.io.key /etc/nginx/ssl/snapsearch.io.key
sudo cp -f --remove-destination secrets/dev.snapsearch.io.crt /etc/nginx/ssl/dev.snapsearch.io.crt
sudo cp -f --remove-destination secrets/dev.snapsearch.io.key /etc/nginx/ssl/dev.snapsearch.io.key
sudo service nginx reload

# Setting up Cron Tasks
echo "Setting up SnapSearch billing as a crontab"
ESCAPED_PROJECT_DIR="${PROJECT_DIR//\//\\/}"
echo "Copying startup_scripts/snapsearch to /etc/cron.d/snapsearch"
sudo cp `pwd`/startup_scripts/snapsearch /etc/cron.d/snapsearch
echo "Confirming project directory in cron scripts"
perl -pi -e "s/PROJECT_DIR/$ESCAPED_PROJECT_DIR/g" /etc/cron.d/snapsearch
sudo service cron restart

# Changing owner to www-data
echo "Changing owner of downloaded files to www-data"
chown -R www-data:www-data $PROJECT_DIR

# Synchronising clock time
echo "Synchronising clock time to prevent synchronisation problems with external services such as Amazon"
ntpdate ntp.ubuntu.com
echo "ntpdate ntp.ubuntu.com" > /etc/cron.daily/ntpdate
chmod 755 /etc/cron.daily/ntpdate

echo "All done!"