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
hash nginx 2>/dev/null || { echo >&2 "nginx needs to be installed, so aborting"; exit 1; }
hash php-fpm 2>/dev/null || { echo >&2 "php-fpm needs to be installed, so aborting"; exit 1; }
hash python 2>/dev/null || { echo >&2 "python needs to be installed, so aborting"; exit 1; }
hash mysql 2>/dev/null || { echo >&2 "mysql needs to be installed, so aborting"; exit 1; }
hash composer 2>/dev/null || { echo >&2 "composer needs to be installed, so aborting"; exit 1; }
hash npm 2>/dev/null || { echo >&2 "npm needs to be installed, so aborting"; exit 1; }
hash bower 2>/dev/null || { echo >&2 "bower needs to be installed, so aborting"; exit 1; }
hash grunt-cli 2>/dev/null || { echo >&2 "grunt-cli needs to be installed, so aborting"; exit 1; }

# Find the project's directory from this file
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Change to the project's directory
cd $PROJECT_DIR

# Install all the dependencies
# Composer and NPM should already be available on PATH
# Bower and Grunt-Cli will be required however
echo "Installing dependencies from Composer, NPM and Bower"
composer install --dev
npm install
bower install

# Download SlimerJS
echo "Downloading SlimerJS 0.9.0rc1"
curl http://download.slimerjs.org/v0.9/0.9.0rc1/slimerjs-0.9.0rc1-linux-i686.tar.bz2 -o slimerjs.tar.bz2
echo "Uncompressing and extracting SlimerJS into ./slimerjs"
mkdir slimerjs && tar xjvf slimerjs.tar.bz2 -C slimerjs --strip-components 1
echo "Adding SlimerJS to the PATH"
sudo ln -s `pwd`/slimerjs/slimerjs /usr/local/bin/slimerjs

# Bring in Secret Keys
echo "Downloading secret keys relevant to Snapsearch"
mkdir -p secrets
curl -u 'CMCDragonkai' -L https://raw.github.com/CMCDragonkai/keys/master/snapsearch/keys.php > secrets/keys.php

# How many robots do you want to start?
# Prompt for robot numbers, note that port starts at 8500
# Along with port numbers
# Also then subsequently modify sites-available on its upstream

# Setting up supervisor upstart script to run this project's robots
echo "Setting up Supervisor Upstart Script"
ROBOT_PATH="`pwd`/robot_scripts"
perl -pi -e 's/chdir .*/chdir $ROBOT_PATH/g' startup_scripts/supervisord.conf
echo "Moving Supervisor startup script to /etc/init"
sudo cp startup_scripts/supervisord.conf /etc/init/supervisord.conf
echo "Starting Supervisord"
sudo service supervisord restart

# Setting up NGINX server configuration
echo "Establishing a symlink from snapsearch.io to NGINX sites-enabled"
sudo ln-s `pwd`/server_config/sites-available/snapsearch.io /etc/nginx/sites-enabled/snapsearch.io
sudo service nginx restart

echo "All done!"