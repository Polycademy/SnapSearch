Required:

sudo apt-get install -y python-software-properties python g++
sudo add-apt-repository ppa:chris-lea/node.js
sudo apt-get update
sudo apt-get install -y make libc6 libstdc++6 libgcc1 xvfb git python-setuptools python-pip curl nginx php5-fpm mysql-server php5-mysql php5-json php5-mcrypt php5-cli php5-curl nodejs
(MySQL config: https://www.digitalocean.com/community/articles/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-12-04)
(Note http://arstechnica.com/information-technology/2012/12/web-served-part-3-bolting-on-php-with-php-fpm/)
(Fix up mcrypt http://askubuntu.com/a/360657 | sudo ln -s /etc/php5/conf.d/mcrypt.ini /etc/php5/mods-available/mcrypt.ini | sudo php5enmod mcrypt | sudo service php5-fpm restart)

If SlimerJS doesn't start, make sure firefox is installed

easy_install supervisor
easy_install httpie

npm install -g bower
npm install -g grunt-cli

COMPOSER:
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

Robot Service (can be modified inside supervisord.conf in robot_scripts, but make sure to update the NGINX configuration to load balance more than 4 robot services):

Change index.php development to production if necessary.

FOR PHP:
Setup firewall once server is up!

apt-get install ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow ssh
ufw allow www
ufw allow https
ufw allow imap
ufw allow imaps
ufw allow smtp
ufw allow ftp
ufw allow sftp
ufw allow git
echo "y" | ufw enable

SSH tunnelling, then you can access the database!

ssh -L LOCALPORT:127.0.0.1:REMOTEPORT USER@IP


Improvements
------------

You need to integrate CSRF later on. Since you'll need a signup page. Or use PolyAuth to support this.

Load Balancer: Use http://zef.me/4502/message-queue-based-load-balancing and https://developer.mozilla.org/en-US/docs/WebAPI/TCP_Socket
Server Admin: Use Dokku and Vagrant
Process Management: Test out memmon.py, write a corresponding one for HTTPok in PHP
SlimerJS: Modularise and cleanup the code. Also need some core improvements for some bugs (navigationLocked)
Validation: Remote filesize and mimetype (technology not available)
Framework: Transition to Slim and custom framework
Logging: Federate the logging systems, actually a PHP binary that accepts logging details would be good
Statuspage: Similar to the logging, but testing the API aswell. Specifically it shows the limits.
Auth: Use PolyAuth + Mashape + Pin.net instead of Mashape? Disable CSRF
Mobilise: Something to set the number of robot servers to setup AND set index.php to production/development, integrate with Grunt build
Tests: API tests functional tests using Codeception

Combine onCallback with the custom callback to allow users to call back to SlimerJS. Perhaps a wait timer is good too! This needs to be resolved automatically if the user forgot to do so. So if no custom callback, this will not be set. If there is a custom callback, they need to window.callPhantom(). Which would resolve to true, so this just delays the async further if need be. https://github.com/ariya/phantomjs/wiki/API-Reference-WebPage#wiki-webpage-onCallback On SlimerJS is it window.callSlimer()?

CRON payments: http://www.unixgeeks.org/security/newbie/unix/cron-1.html

HTML minifier: https://github.com/mrclay/minify/issues/80#issuecomment-28946276
(no comments, only whitespace can be collapsed, but also remember about badly formed html, malformed!)

use: https://github.com/noetix/pin-php

The Billing Model and Controller is used to create the customer object via the Pin service and thus associate credit card information to a user account. This needs to be done. This is combined with the Pin_model to contact the Pin service. Therefore the Billing controller needs the Billing_model and Pin_model. Furthermore to actually charge the customers, this code is in the Cron controller which will use the monthly_billing which uses the Accounts_model, Billing_model, Pin_model, Usage_model, Payments_model and Email_model