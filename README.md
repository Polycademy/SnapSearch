Running Robot.js:

xvfb-run path/slimerjs path/robot.js



Todo:

1. Convert Supervisord to Monit conf file, because memmon process tree tracking is not working. Delete the superlance repo on your Polycademy group
2. Nginx configuration file (this is loaded at runtime)
One single Nginx will be used by Slimer and the PHP runtime. The port that nginx listens to rout to slimerjs will be blocked using iptables

Nginx can listen on 2 different ports and establish 2 different servers.

http://nginx.org/en/docs/http/server_names.html

Link to devenv for the vagrantup configuration that installs Nginx and SlimerJS and Supervisord at a basic level.

Which could work for production environments as well!

So this would result in respository containing:

1. NGINX configuration (which needs to be loaded at startup) (Use H5BP's style!)
2. Supervisord config (which needs to be manually started)
3. Robot.js which gets started by SlimerJS with Supervisord
4. PHP app + Front end (along with composer dependencies)
5. API test cases with Codeception with Travis.yml

URLs to Supervisor + SlimerJS (requirements.txt using pip to install Supervisor: http://stackoverflow.com/questions/13537901/python-equivalent-of-npm-or-rubygems) And also Superlance and memmon

https://www.digitalocean.com/community/articles/how-to-set-up-a-firewall-using-ip-tables-on-ubuntu-12-04
https://www.digitalocean.com/community/articles/how-to-use-nmap-to-scan-for-open-ports-on-your-vps
https://www.digitalocean.com/community/articles/how-to-setup-a-basic-ip-tables-configuration-on-centos-6
https://github.com/h5bp/server-configs-nginx

http://www.sitepoint.com/setting-up-php-behind-nginx-with-fastcgi/

http://reustle.io/blog/managing-long-running-processes-with-supervisor

http://stackoverflow.com/questions/12571052/have-supervisord-periodically-restart-child-processes

http://superlance.readthedocs.org/en/latest/memmon.html

http://plope.com/Members/chrism/memmon_sample

Need to change logging system dependent on whether supervisord is used to start the process. Check the memory usage of each xulrunner process!

You also need apt-get xvfb

also httpok plugin is a good choice too!

32bit linux
XVFB: 7mb
XVFB-RUN: 1mb
XURL Runner: 37MB
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