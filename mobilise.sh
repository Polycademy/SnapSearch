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

# Find the project's directory from this file
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Change to the project's directory
cd $PROJECT_DIR

# Need something to run change index.php's environment to production
# perl -pi -e "s/((?:[a-z][a-z]+)\(\'ENVIRONMENT\', isset\(\$_SERVER\[\'CI_ENV\'\]\) \? \$_SERVER\[\'CI_ENV\'\] : \'development\'\).)/define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'production');/g" index.php
# Perhaps it's better to use NGINX or the server module to provide this?

# Install all the dependencies
# Composer and NPM should already be available on PATH
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
	echo "Downloading SlimerJS 0.9.6"
	if [ $(uname -m) == 'x86_64' ]; then
		# 64 bit
		curl http://download.slimerjs.org/releases/0.9.6/slimerjs-0.9.6-linux-x86_64.tar.bz2 -o slimerjs.tar.bz2
	else
		# 32 bit
		curl http://download.slimerjs.org/releases/0.9.6/slimerjs-0.9.6-linux-i686.tar.bz2 -o slimerjs.tar.bz2
	fi
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

# Setting up the lockfile directory for synchronising cache refreshes
mkdir -p snapshots/lockfiles
if ! mountpoint -q "snapshots/lockfiles"; then
	mount -t tmpfs -o size=1M,nr_inodes=400k,mode=755,nodev,nosuid,noexec,uid=www-data,gid=www-data tmpfs snapshots/lockfiles
	MOUNTING_PATH=$(readlink -f snapshots/lockfiles)
	ESCAPED_MOUNTING_PATH="${MOUNTING_PATH//\//\\/}"
	MOUNTING=$(cat /etc/mtab | grep "$MOUNTING_PATH" | head -n 1)
	sed -i "/$ESCAPED_MOUNTING_PATH/d" /etc/fstab
	echo $MOUNTING >> /etc/fstab
fi

# IP Configuration for Robots

ROBOT_SUBNET="10.0.0.0"
ROBOT_CIDR="/24"
ROBOT_VETH0="10.0.0.1"
ROBOT_VETH1="10.0.0.2" 
ROBOT_GATEWAY=$ROBOT_VETH0

# Setup the network namespace for the robots
ESCAPED_ROBOT_CIDR="${ROBOT_CIDR//\//\\/}"
ROBOT_NAMESPACE_LOG_FILE="${PROJECT_DIR}/startup_scripts/robots-namespace.log"
ESCAPED_ROBOT_NAMESPACE_LOG_FILE="${ROBOT_NAMESPACE_LOG_FILE//\//\\/}"
echo "Setting up Network Namespaces"
echo "Copying Robot Namespace startup script to /etc/init"
sudo cp startup_scripts/robots-namespace.conf /etc/init/robots-namespace.conf
echo "Confirming IP configuration for network namespace"
sudo perl -pi -e \
	"s/env SUBNET=.*/env SUBNET=\"${ROBOT_SUBNET}${ESCAPED_ROBOT_CIDR}\"/g" \
	/etc/init/robots-namespace.conf
sudo perl -pi -e \
	"s/env VETH0_AND_SUBNET=.*/env VETH0_AND_SUBNET=\"${ROBOT_VETH0}${ESCAPED_ROBOT_CIDR}\"/g" \
	/etc/init/robots-namespace.conf
sudo perl -pi -e \
	"s/env VETH1_AND_SUBNET=.*/env VETH1_AND_SUBNET=\"${ROBOT_VETH1}${ESCAPED_ROBOT_CIDR}\"/g" \
	/etc/init/robots-namespace.conf
sudo perl -pi -e \
	"s/env GATEWAY=.*/env GATEWAY=\"${ROBOT_GATEWAY}\"/g" \
	/etc/init/robots-namespace.conf
sudo perl -pi -e \
	"s/ROBOTS_NAMESPACE_LOG_FILE/${ESCAPED_ROBOT_NAMESPACE_LOG_FILE}/g" \
	/etc/init/robots-namespace.conf
# allowing access to the configured domain name servers
# we're not using network namespace specific nameservers, so we just use the global one
# load command output into nameservers array
nameservers=($(cat /etc/resolv.conf | grep nameserver | sed -e 's/nameserver \(.*\)/\1/'))
for i in "${nameservers[@]}"; do 
	# we need to add the $i to the DELETE portion and the ADD portion of robots-namespace.conf
	sed -i "/# add DNS delete below/aip netns exec robots iptables -D OUTPUT -d ${i} -j ACCEPT 2>\/dev\/null || true" /etc/init/robots-namespace.conf
	sed -i "/# add DNS append below/aip netns exec robots iptables -A OUTPUT -d ${i} -j ACCEPT" /etc/init/robots-namespace.conf
done
sudo service robots-namespace start

# Setting up supervisor upstart script to run the robots
echo "Setting up Supervisor Upstart Script for supervising the Robots"
ROBOT_PATH="`pwd`/robot_scripts"
ESCAPED_ROBOT_PATH="${ROBOT_PATH//\//\\/}"
echo "Copying Supervisor startup script to /etc/init"
sudo cp startup_scripts/supervisord.conf /etc/init/supervisord.conf
echo "Confirming robot script path in the Supervisor startup script"
sudo perl -pi -e \
	"s/chdir .*/chdir ${ESCAPED_ROBOT_PATH}/g" \
	/etc/init/supervisord.conf
echo "Confirming robot ip address in the Supervisor startup script"
sudo perl -pi -e \
	"s/env ROBOT_IP=.*/env ROBOT_IP=\"${ROBOT_VETH1}\"/g" \
	/etc/init/supervisord.conf
echo "Restarting Supervisord"
sudo service supervisord restart

# Setting up NGINX server configuration
echo "Setting up NGINX configuration"
ESCAPED_PROJECT_DIR="${PROJECT_DIR//\//\\/}"
echo "Copying snapsearch.io site config to NGINX sites-enabled"
sudo cp server_config/snapsearch.io /etc/nginx/sites-enabled/snapsearch.io
echo "Confirming root path in snapsearch.io site config"
sudo perl -pi -e \
	"s/root .*/root ${ESCAPED_PROJECT_DIR};/g" \
	/etc/nginx/sites-enabled/snapsearch.io
echo "Confirming upstream robot ip address in snapsearch.io site config"
sudo perl -pi -e \
	"s/server ROBOT_IP:(\d+);/server ${ROBOT_VETH1}:\1;/g" \
	/etc/nginx/sites-enabled/snapsearch.io
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