SnapSearch
==========

Installing:

```
sudo apt-get install -y python-software-properties python g++
sudo add-apt-repository ppa:chris-lea/node.js
sudo apt-get update
sudo apt-get install -y make libc6 libstdc++6 libgcc1 xvfb git python-setuptools python-pip curl nginx php5-fpm mysql-server php5-mysql php5-json php5-mcrypt php5-cli php5-curl nodejs cron
```

MySQL config: 
https://www.digitalocean.com/community/articles/how-to-install-linux-nginx-mysql-php-lemp-stack-on-ubuntu-12-04
PHP FPM config:
http://arstechnica.com/information-technology/2012/12/web-served-part-3-bolting-on-php-with-php-fpm/

```
; inside /etc/php5/fpm/pool.d/www.conf
listen.owner = www-data
listen.group = www-data
listen.mode = 0660
```

PHP FPM mcrypt fix:

In < 14.04 -> http://askubuntu.com/a/360657

In > 14.04 -> `sudo php5enmod mcrypt && sudo service php5-fpm restart`

Make sure the `session_save_path()` is writable!

If SlimerJS doesn't start, make sure firefox is installed: `sudo apt-get install firefox`.

```
easy_install supervisor
easy_install httpie
```

The easy_install of httpie seems to stop working once you install pip and awscli. Workaround, `sudo apt-get install httpie`.

```
npm install -g bower
npm install -g grunt-cli
```

COMPOSER:

```
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

Robot Service (can be modified inside supervisord.conf in robot_scripts, but make sure to update the NGINX configuration to load balance the robot services):

Change index.php development to production if necessary.

Change the the SlimerJS download to either 32bit or 64bit depending on your architecture.

You need WebServerConfiguration. And setup NGINX from there.

Setup firewall:

```
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
```

Building App.js and Common.js (swapping for cache busting):

```
./node_modules/.bin/browserify -t debowerify -t deglobalify -t brfs -e js/app/App.js -o js/compiled/App.js
./node_modules/.bin/browserify -t debowerify -t deglobalify -t brfs -e js/app/Common.js -o js/compiled/Common.js
./node_modules/.bin/minify js/compiled/App.js
./node_modules/.bin/minify js/compiled/Common.js
```

Change to `rlimit_files = 4096` for `/etc/php5/fpm/pool.d/www.conf`, this allows 4096 open lock files for each PHP worker process if there's 3 workers, that's 12288 lock files that can be open at the same time. This one of the upper limits for concurrent connections to SnapSearch. The real limit is much higher, because concurrent connections don't all open lock files at the same time. And there's probably a smaller limit due to other architectural parts of SnapSearch.

See all upstart jobs: `initctl list`

Recommendations
---------------

How to deal with 404s:

1. 404s for random URLs:

Server side routing needs to copy the rules of client side routing. So that when a URL that doesn't match the client and server side routing, it's treated as a 404 page from the server side. This can be done like this. The server side returns a 404 status code, but leaves the routing to the client side. The client side has a other routing rule, and in this case it will return a 404 page from the client side.
This problem is a problem for machine and human clients. So this solves it for the machine and human clients.
This is easy to keep synchronised.

2. 404s for missing objects

This is a problem for mainly machine clients.
When an object is missing based on a dynamic page that uses ids. The server side cannot know if the object is missing unless it implements object checking logic. This becomes harder to synchronise.
On the client side, your object checking logic will check if the object exists, and if it doesn't it'll show a dynamic 404 state on the page. However by this time, a 200 status code has already been returned from the server side.
In this case, a human client will see a 404 page easily and not be bothered by the 200 status code. However for machine clients such as search engines, this is a big problem. The solution to this, is to implement the object checking logic, OR use snapsearch's meta tag commands.

Planning Documents
------------------

Here we need to discuss SnapSearch's core scripting technology in terms of:

 Core R&D activities

Records of core R&D activities should document: 

    the state of knowledge or technology that existed when the R&D was undertaken
    the new knowledge or information concerning the creation of new or improved materials, products, devices, processes or services that was sought through the R&D
    that the knowledge or information was not publicly available. For example, this might include:

a) literature reviews
b) patent or other searches
c) scientific or technological reviews and articles
d) trade journals

    the proposed hypothesis (that is, the idea, theory or possible solution) being tested
    the systematic progression of work to test the hypothesis based on the principles of established science, that is, the 'scientific method’ that was employed
    documents detailing the experiments undertaken, the experiments’ results, the analysis of the results, and the subsequent changes implemented to the experiments.

Regular progress reports against the planned milestones are important records. They provide evidence of your R&D project’s progression and record decision points.

Any material changes to the purpose of your R&D project, or the hypothesis being investigated, should be documented.

Development first began on mhas's repository: https://github.com/mhas16/ajax_seo/commit/0fba85b81005bad72d01cc069b03877c62f23067

Testing
-------

```
http --verbose POST https://snapsearch.io/api/v1/robot url=http://localhost:1337/ --auth EMAIL:KEY
```

Concurrent Testing:

Set X to number of jobs. Y to number of concurrent jobs. Parallel jobs is still limited to your number of cores.

```
seq X | parallel -n0 -jY "curl -s -k -H 'Content-Type: application/json' -X POST -d '{\"url\":\"http://httpbin.org/ip\"}' -u EMAIL:KEY https://snapsearch.io/api/v1/robot"
```