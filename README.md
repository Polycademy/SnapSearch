# SnapSearch #

## Installation Process ##

Run all of the below as root user. Setup SSH first, to login as root. Assume OS is 64bit Ubuntu 14.04, choose the default kernel.

```
# if not root already
sudo -i
```

---

Setup SSH keys first, DO will set the key for you. And SSHD should already be ready to go.

Re-ssh into the machine to verify that it works.

---

Create project folder:

```
mkdir -p /www
cd /www
```

---

First bring in the dependencies.

```sh
apt-get update
apt-get upgrade -y
apt-get dist-upgrade -y
apt-get install -y \
    python-software-properties \
    python \
    g++ \
    make \
    libc6 \
    libstdc++6 \
    libgcc1 \
    xvfb \
    git \
    python-setuptools \
    python-pip \
    curl \
    nginx \
    php5-fpm \
    mysql-server \
    php5-mysql \
    php5-json \
    php5-mcrypt \
    php5-cli \
    php5-curl \
    nodejs \
    node \
    cron \
    libmysqlclient-dev \
    libpcre3-dev \
    libtool \
    automake \
    autoconf \
    autogen \
    firefox \
    ufw \
    htop \
    tree \
    multitail \
    npm \
    nodejs-legacy \
    parallel

easy_install supervisor
easy_install httpie

pip install awscli

npm install -g bower

cd /www

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod 776 /usr/local/bin/composer

cd - 
```

Restart to confirm the machine is still working:

```
shutdown -r now
```

---

Setup the firewall:

```sh
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

---

Now we configure MysQL, make sure to enter `root` for username, and for password, see your `keys.php`. Make sure to have no anonymous user, allow remote root login, remove test database, reload privilege tables.

```
mysql_install_db
/usr/bin/mysql_secure_installation
service mysql restart
```

---

Now we configure awscli, set the key and password to your values in `keys.php`, and leave the rest default (setup on any users requiring AWS access, but especially root).

```
# Configure your AWS
aws configure
```

If you then `cat /root/.aws/credentials`, you should see:

```
[default]
aws_access_key_id = ....
aws_secret_access_key = ....
```

---

Now we configure PHP-FPM:

Go into `/etc/php5/fpm/php.ini`, make these changes:

* post_max_size = 8M
* max_execution_time = 90
* default_socket_timeout = 80
* curl.cainfo = /etc/ssl/certs/ca-certificates.crt

Go into `/etc/php5/cli/php.ini`, make these changes:

* curl.cainfo = /etc/ssl/certs/ca-certificates.crt

Go into `/etc/php5/fpm/pool.d/www.conf`, make these changes:

* user = www-data
* group = www-data
* listen.owner = www-data
* listen.group = www-data
* listen.mode = 0660
* listen = /var/run/php5-fpm.sock
* pm = dynamic
* pm.max_children = 12
* pm.start_servers = 12
* pm.min_spare_servers = 12
* pm.max_spare_servers = 12
* rlimit_files = 4096
* chdir = /
* catch_workers_output = yes
* pm.max_requests = 100
* request_terminate_timeout = 100s
* pm.process_idle_timeout = 10s;

The `rlimit_files = 4096` for `/etc/php5/fpm/pool.d/www.conf`, allows 4096 open lock files for each PHP worker process if there's 3 workers, that's 12288 lock files that can be open at the same time. This one of the upper limits for concurrent connections to SnapSearch. The real limit is much higher, because concurrent connections don't all open lock files at the same time. And there's probably a smaller limit due to other architectural parts of SnapSearch.

Go into `/etc/php5/fpm/php-fpm.conf`, make these changes:

* error_log = /var/log/php5-fpm.log
* log_level = debug

Run `sudo service php5-fpm restart`.

---

Now we configure the certificates.

```
cd /usr/local/share/ca-certificates
curl http://curl.haxx.se/ca/cacert.pem -o cacert.crt
update-ca-certificates
update-ca-certificates --fresh
cd -
```

---

Now we configure `curl` (also enter for any particular user that will be using curl too).

```
echo "cacert = /etc/ssl/certs/ca-certificates.crt" > /root/.curlrc
```

---

Now we compile and install the mysql preg plugin:

```sh
cd /www

git clone https://github.com/mysqludf/lib_mysqludf_preg.git
cd lib_mysqludf_preg
aclocal
automake --add-missing
./configure 
make
make install
make MYSQL="mysql -p" installdb
make test

sudo service mysql restart

cd -
```

---

Now we fix PHP mcrypt:

```
sudo php5enmod mcrypt && sudo service php5-fpm restart
php -m | grep mcrypt
```

---

Now we bring in the application.

```
cd /www
git clone https://CMCDragonkai@bitbucket.org/snapsearch/snapsearch.git
# setup the remote?
```

---

Now we configure NGINX.

```
{
    cd /www

    git clone https://github.com/Polycademy/WebserverConfiguration.git
    NGINX_ROOT="/etc/nginx"
    cp WebserverConfiguration/servers/nginx/configuration/nginx.conf $NGINX_ROOT/nginx.conf
    cp WebserverConfiguration/servers/nginx/configuration/mime.types $NGINX_ROOT/mime.types
    cp WebserverConfiguration/servers/nginx/configuration/fastcgi_params $NGINX_ROOT/fastcgi_params
    cp -r WebserverConfiguration/servers/nginx/configuration/conf.d/* $NGINX_ROOT/conf.d/

    cd -

    service nginx restart
}
```

---

Change index.php to production. Go to `/www/snapsearch/index.php`, and change the constant. Do this before hitting `./mobilise.sh`!

Now we mobilise (hit yes to everything!):

```
cd /www/snapsearch
./mobilise.sh
```

Note that here is where `bower` might break, so you need to run the command manually. Also composer too.

---

Prepare to migrate MySQL data if necessary. Export old mysql data, and import into the new MySQL data.

---

Final checks:

* Make sure the `session_save_path()` is writable! It should be in `/var/lib/php5`.
* If SlimerJS doesn't start, make sure you have the right bit architecture.
* If you have 2 CPUs, make 2 robots and 2 PHP workers, then change the NGINX configuration to use the newest robots.
* Check if you switched DNS to the current IP.
* See all upstart jobs: `initctl list`

---

Reboot.

```
sudo shutdown -r now
```

---

Test if the system works, see testing.

## Development ##

Building App.js and Common.js (swapping for cache busting):

```
./node_modules/.bin/browserify -t debowerify -t deglobalify -t brfs -e js/app/App.js -o js/compiled/App.js
./node_modules/.bin/browserify -t debowerify -t deglobalify -t brfs -e js/app/Common.js -o js/compiled/Common.js
./node_modules/.bin/minify js/compiled/App.js
./node_modules/.bin/minify js/compiled/Common.js
```

## Recommendations ##

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

## Planning Documents ##

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

## Testing ##

```sh
# set the testing url
testing_url="https://snapsearch-demo.herokuapp.com/"
email=""
key=""
njobs="" # number of jobs
npjobs="" # number of parallel jobs executed

# simple test
http --verbose POST https://snapsearch.io/api/v1/robot url=${testing_url} --auth ${email}:${key}

# local refresh test
curl -k -H 'Content-Type: application/json' -X POST -d '{"url":"${testing_url}", "refresh": true}' -u ${email}:${key} https://snapsearch.io/api/v1/robot --resolve 'snapsearch.io:443:127.0.0.1'

# remote refresh test
curl -k -H 'Content-Type: application/json' -X POST -d '{"url":"${testing_url}", "refresh": true}' -u ${email}:${key} https://snapsearch.io/api/v1/robot

# parallel local refresh test
seq ${njobs} | parallel -n0 -j${npjobs} "curl -k -H 'Content-Type: application/json' -X POST -d '{\"url\":\"${testing_url}\", \"refresh\": true}' -u ${email}:${key} https://snapsearch.io/api/v1/robot --resolve 'snapsearch.io:443:127.0.0.1'"

# parallel remote refresh test
seq ${njobs} | parallel -n0 -j${npjobs} "curl -k -H 'Content-Type: application/json' -X POST -d '{\"url\":\"${testing_url}\", \"refresh\": true}' -u ${email}:${key} https://snapsearch.io/api/v1/robot"
```

Test cron commands:

```
# choose root or www-data, <> is mandatory, [] is optional, <> and [] are meta syntax, not bash syntax
sudo -u <root/www-data> [command]
```