1. PHP app + Front end (along with composer dependencies)
2. API test cases with Codeception with Travis.yml

https://www.digitalocean.com/community/articles/how-to-set-up-a-firewall-using-ip-tables-on-ubuntu-12-04
https://www.digitalocean.com/community/articles/how-to-use-nmap-to-scan-for-open-ports-on-your-vps

Required:

apt-get install python2.7.5 or greater...
apt-get install xvfb
apt-get install git
apt-get install python-setuptools
apt-get install python-pip
easy_install supervisor
apt-get install curl

Follow this as well: https://www.digitalocean.com/community/articles/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-12-04
Also install PHP extensions -> http://arstechnica.com/information-technology/2012/12/web-served-part-3-bolting-on-php-with-php-fpm/ (Check mcrypt bug! http://askubuntu.com/a/360657)
sudo apt-get install mysql-server php5-mysql
sudo apt-get install php5-fpm
sudo apt-get install nginx

Robot Service (can be modified inside supervisord.conf in robot_scripts, but make sure to update the NGINX configuration to load balance more than 4 robot services):

8499 - LOAD BALANCER
8500 - First Robot
8501 - Second Robot
8502 - Third Robot
8503 - Fourth Robot

Configure snapsearch.io in /etc/hosts: 127.0.0.1 snapsearch.io www.snapsearch.io

Scale up: https://www.digitalocean.com/community/articles/how-to-scale-your-infrastructure-with-digitalocean

Migrate to using Monit instead of Supervisord eventually!


FOR PHP:

1. Wrap Valitron + Pimple/DI container + Respect\Validation to create something freaking awesome for validation...
2. Clean up templating structure, something simpler, it's mostly use JS templates for the front end. However what about default_layout? Or JSON layout?
3. Use Monolog instead of CI's logger.
4. Disable CSRF, you're going to use Localstorage + Oauth anyway
5. No need for MY_Input, use HTTP request/response object
6. Swap out CI Migrations for Phinx Migration (then plugin the CLI route)
7. Migrate to Slim, you're swapping out everything for Composer anyway!

NODEJS:

sudo apt-get install python-software-properties python g++ make
sudo add-apt-repository ppa:chris-lea/node.js
sudo apt-get update
sudo apt-get install nodejs




1. CI application is not working correctly (404 on controllers from NGINX)
2. Slimer0.9.0rc1 is resolving the redirections automatically for the onResourceReceived, this is wrong and needs to be fixed, or we need to go back to 0.9pre...etc