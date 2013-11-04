Todo:

So this would result in respository containing:

4. PHP app + Front end (along with composer dependencies)
5. API test cases with Codeception with Travis.yml

https://www.digitalocean.com/community/articles/how-to-set-up-a-firewall-using-ip-tables-on-ubuntu-12-04
https://www.digitalocean.com/community/articles/how-to-use-nmap-to-scan-for-open-ports-on-your-vps
https://www.digitalocean.com/community/articles/how-to-setup-a-basic-ip-tables-configuration-on-centos-6
http://www.sitepoint.com/setting-up-php-behind-nginx-with-fastcgi/
http://stackoverflow.com/questions/12571052/have-supervisord-periodically-restart-child-processes

32bit linux
XVFB: 7mb - 15mb
XVFB-RUN: 1mb
XURL Runner: 37MB - 50MB
Website memory usage: 80mb

=> 130mb

Required:

apt-get install python2.7.5 or greater...
apt-get install xvfb
apt-get install git
apt-get install python-setuptools
apt-get install python-pip
easy_install supervisor
apt-get install curl
curl -O http://download.slimerjs.org/nightlies/0.8.5pre/slimerjs-0.8.5pre-linux-i686.tar.bz2
pip install --upgrade httpie

SlimerJS 0.8.5pre => application.ini => MaxVersion=25.*

Slimerjs binary needs to be softlink, so that the directory of execution works
sudo ln -s /absolute/path/to/slimerjs /usr/local/bin/slimerjs

move startup script into /etc/init/supervisord.conf (make sure to change the chdir to where the robot_scripts is)

Make sure line endings are based on LF for those shell scripts and especially the startup script. Use dos2unix to convert.

Follow this as well: https://www.digitalocean.com/community/articles/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-12-04
sudo apt-get install mysql-server libapache2-mod-auth-mysql php5-mysql
sudo apt-get install php5-fpm
sudo apt-get install nginx

Server Configuration:

Get https://github.com/Polycademy/server-configs-nginx do the global config.

Symlink sites-available