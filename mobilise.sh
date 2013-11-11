#!/bin/bash

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
composer install --dev </dev/null
npm install </dev/null
# this has an issue it might request for confirmation (make sure to resolve bower beforehand)
bower install --allow-root </dev/null

# Download SlimerJS
echo "Downloading SlimerJS 0.9.0rc1"
curl http://download.slimerjs.org/v0.9/0.9.0rc1/slimerjs-0.9.0rc1-linux-i686.tar.bz2 -o slimerjs.tar.bz2
echo "Uncompressing and extracting SlimerJS into ./slimerjs"
mkdir -p slimerjs && tar xjvf slimerjs.tar.bz2 -C slimerjs --strip-components 1
rm slimerjs.tar.bz2
echo "Adding SlimerJS to the PATH"
sudo ln -sf `pwd`/slimerjs/slimerjs /usr/local/bin/slimerjs

# Bring in Secret Keys
echo "Downloading secret keys relevant to Snapsearch"
mkdir -p secrets
curl -u 'CMCDragonkai' -L https://raw.github.com/CMCDragonkai/keys/master/snapsearch/keys.php > secrets/keys.php

# How many robots do you want to start?
# Prompt for robot numbers, note that port starts at 8500
# Along with port numbers

# Should create the database if it's not available
echo "Creating database for snapsearch"
read -p "$(tput bold)$(tput setaf 2)Enter username for mysql, followed by enter: $(tput sgr0)" -r MYSQL_USER
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

# Setup hosts redirection for snapsearch.io and www.snapsearch.io
read -p "$(tput bold)$(tput setaf 2)Setup /etc/hosts redirection for snapsearch.io?. [Y/n]: $(tput sgr0)" -n 1 -r HOST_REDIRECTION
echo
if [[ $HOST_REDIRECTION =~ ^[Y]$ ]]; then
	# This will not overwrite the previous clone
	git clone https://github.com/Polycademy/add-etc-hosts startup_scripts/add-etc-hosts
	echo "Backing up /etc/hosts to startup_scripts/add-etc-hosts/hosts.backup in case of screwup!"
	cp /etc/hosts startup_scripts/add-etc-hosts/hosts.backup
	echo "Adding snapsearch.io and www.snapsearch.io to /etc/hosts"
	sudo startup_scripts/add-etc-hosts/add-etc-hosts snapsearch.io
	sudo startup_scripts/add-etc-hosts/add-etc-hosts www.snapsearch.io
fi

# Setting up supervisor upstart script to run this project's robots
echo "Setting up Supervisor Upstart Script"
ROBOT_PATH="`pwd`/robot_scripts"
ESCAPED_ROBOT_PATH="${ROBOT_PATH//\//\\/}"
perl -pi -e "s/chdir .*/chdir $ESCAPED_ROBOT_PATH/g" startup_scripts/supervisord.conf
echo "Moving Supervisor startup script to /etc/init"
sudo cp startup_scripts/supervisord.conf /etc/init/supervisord.conf
echo "Starting Supervisord"
sudo service supervisord restart

# Setting up NGINX server configuration
echo "Setting up NGINX configuration"
ESCAPED_PROJECT_DIR="${PROJECT_DIR//\//\\/}"
perl -pi -e "s/root .*/root $ESCAPED_PROJECT_DIR;/g" server_config/snapsearch.io
echo "Establishing a symlink from snapsearch.io to NGINX sites-enabled"
sudo ln -sf `pwd`/server_config/snapsearch.io /etc/nginx/sites-enabled/snapsearch.io
sudo service nginx restart

# Changing owner to www-data
echo "Changing owner of downloaded files to www-data"
chown -R www-data:www-data $PROJECT_DIR

echo "All done!"