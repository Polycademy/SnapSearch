1. PHP app + Front end (along with composer dependencies)
2. API test cases with Codeception with Travis.yml

https://www.digitalocean.com/community/articles/how-to-set-up-a-firewall-using-ip-tables-on-ubuntu-12-04
https://www.digitalocean.com/community/articles/how-to-use-nmap-to-scan-for-open-ports-on-your-vps

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

8499 - LOAD BALANCER
8500 - First Robot
8501 - Second Robot
8502 - Third Robot

FOR PHP:

1. Wrap Valitron + Pimple/DI container + Respect\Validation to create something freaking awesome for validation...
2. Clean up templating structure, something simpler, it's mostly use JS templates for the front end. However what about default_layout? Or JSON layout?
3. Use Monolog instead of CI's logger.
4. Disable CSRF, you're going to use Localstorage + Oauth anyway
5. No need for MY_Input, use HTTP request/response object
6. Swap out CI Migrations for Phinx Migration (then plugin the CLI route)
7. Migrate to Slim, you're swapping out everything for Composer anyway!
6. Combine onCallback with the custom callback to allow users to call back to SlimerJS. Perhaps a wait timer is good too! This needs to be resolved automatically if the user forgot to do so. So if no custom callback, this will not be set. If there is a custom callback, they need to window.callPhantom(). Which would resolve to true, so this just delays the async further if need be. https://github.com/ariya/phantomjs/wiki/API-Reference-WebPage#wiki-webpage-onCallback On SlimerJS is it window.callSlimer()?
7. Wait for navigationLocked to be fixed!
8. Also make sure pages aren't created using _blank.. that will screw up the headers

Market here: http://backbonetutorials.com/seo-for-single-page-apps/

Setup firewall

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