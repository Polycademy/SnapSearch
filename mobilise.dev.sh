#!/bin/bash

# Mobilise will mobilise all the dependencies of this project in one go!
# Everything will be installed relative to this file (this repo) and then moved/symlinked to the correct place
# This file should appear as /www/snapsearch/mobilise.sh
# Only run this after the server has already been configured, so it should have all the necessary generic web server stuff

# Checking for necessary global components
hash git 2>/dev/null || { echo >&2 "git needs to be installed, so aborting"; exit 1; }
hash curl 2>/dev/null || { echo >&2 "curl needs to be installed, so aborting"; exit 1; }
hash perl 2>/dev/null || { echo >&2 "perl needs to be installed, so aborting"; exit 1; }
hash nginx 2>/dev/null || { echo >&2 "nginx needs to be installed, so aborting"; exit 1; }
hash php 2>/dev/null || { echo >&2 "php-cli (php) needs to be installed, so aborting"; exit 1; }
hash python 2>/dev/null || { echo >&2 "python needs to be installed, so aborting"; exit 1; }
hash mysql 2>/dev/null || { echo >&2 "mysql needs to be installed, so aborting"; exit 1; }
hash composer 2>/dev/null || { echo >&2 "composer needs to be installed, so aborting"; exit 1; }
hash npm 2>/dev/null || { echo >&2 "npm needs to be installed, so aborting"; exit 1; }
hash bower 2>/dev/null || { echo >&2 "bower needs to be installed, so aborting"; exit 1; }

# Find the project's directory from this file
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Change to the project's directory
cd $PROJECT_DIR

# Install all the dependencies
# Composer and NPM should already be available on PATH
# Bower and Grunt-Cli will be required however
# Their STDIN is redirected to /dev/null, this is because it can affect the STDIN of this bash script
echo "Installing dependencies from Composer, NPM and Bower"
composer install </dev/null
npm install </dev/null
# this has an issue it might request for confirmation (make sure to resolve bower beforehand)
bower install --allow-root </dev/null

# Download SlimerJS
read -p "$(tput bold)$(tput setaf 2)Setup SlimerJS? [Y/n]: $(tput sgr0)" -n 1 -r DOWNLOAD_SLIMERJS
echo
if [[ $DOWNLOAD_SLIMERJS =~ ^[Y]$ ]]; then
    echo "Downloading SlimerJS 0.9.0"
    curl http://download.slimerjs.org/v0.9/0.9.0/slimerjs-0.9.0-linux-i686.tar.bz2 -o slimerjs.tar.bz2
    echo "Uncompressing and extracting SlimerJS into ./slimerjs"
    mkdir -p slimerjs && tar xjvf slimerjs.tar.bz2 -C slimerjs --strip-components 1
    rm slimerjs.tar.bz2
    echo "Adding slimerjs to the PATH"
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
    curl -L -k -u 'CMCDragonkai' https://github.com/CMCDragonkai/keys/tarball/master | tar xzv -C secrets --strip-components 1
    rm -r secrets/!(snapsearch)
    cp -r secrets/snapsearch/* secrets
    rm -r secrets/snapsearch
fi

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

# Setting up NGINX server configuration
echo "Setting up NGINX configuration"
ESCAPED_PROJECT_DIR="${PROJECT_DIR//\//\\/}"
echo "Copying snapsearch.io site config to NGINX sites-enabled"
sudo cp server_config/snapsearch.io /etc/nginx/sites-enabled/snapsearch.io
echo "Confirming root path in snapsearch.io site config"
sudo perl -pi -e "s/root .*/root $ESCAPED_PROJECT_DIR;/g" /etc/nginx/sites-enabled/snapsearch.io
echo "Copying SSL certificate and key to NGINX ssl directory"
sudo mkdir -p /etc/nginx/ssl
sudo cp secrets/snapsearch.io.pem /etc/nginx/ssl/snapsearch.io.pem
sudo cp secrets/snapsearch.io.key /etc/nginx/ssl/snapsearch.io.key
sudo cp secrets/dev.snapsearch.io.crt /etc/nginx/ssl/dev.snapsearch.io.crt
sudo cp secrets/dev.snapsearch.io.key /etc/nginx/ssl/dev.snapsearch.io.key
sudo service nginx restart

# Changing owner to www-data
# echo "Changing owner of downloaded files to www-data"
# chown -R www-data:www-data $PROJECT_DIR

# Synchronising clock time
echo "Synchronising clock time to prevent synchronisation problems with external services such as Amazon"
sudo ntpdate ntp.ubuntu.com
echo 'ntpdate ntp.ubuntu.com' | sudo tee /etc/cron.daily/ntpdate
sudo chmod 755 /etc/cron.daily/ntpdate

echo "All done!"