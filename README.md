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
curl -O http://download.slimerjs.org/v0.9/0.9.0rc1/slimerjs-0.9.0rc1-linux-i686.tar.bz2
pip install --upgrade httpie

Slimerjs binary needs to be softlink, so that the directory of execution works
sudo ln -s /absolute/path/to/slimerjs /usr/local/bin/slimerjs

move startup script into /etc/init/supervisord.conf (make sure to change the chdir to where the robot_scripts is, currently at /www/snapsearch/robot_scripts)

Make sure line endings are based on LF for those shell scripts and especially the startup script. Use dos2unix to convert.

Follow this as well: https://www.digitalocean.com/community/articles/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-12-04
Also install PHP extensions -> http://arstechnica.com/information-technology/2012/12/web-served-part-3-bolting-on-php-with-php-fpm/ (Check mcrypt bug!)
sudo apt-get install mysql-server php5-mysql
sudo apt-get install php5-fpm
sudo apt-get install nginx

Server Configuration:

Get https://github.com/Polycademy/server-configs-nginx do the global config.

Robot Service (can be modified inside supervisord.conf in robot_scripts, but make sure to update the NGINX configuration to load balance more than 4 robot services):

8499 - LOAD BALANCER
8500 - First Robot
8501 - Second Robot
8502 - Third Robot
8503 - Fourth Robot

Configure snapsearch.io in /etc/hosts: 127.0.0.1 snapsearch.io www.snapsearch.io

Scale up: https://www.digitalocean.com/community/articles/how-to-scale-your-infrastructure-with-digitalocean