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
3. DB needs to be migrated... created if not exists...
4. Actually SlimerJS is following the correct behaviour now. In order to not follow redirects, one must get the headers and status code of the (FIRST) request. Then have an optional toggle to follow in redirects. In you get redirected you get the final page's status code and headers, if not, you get the first one.
5. How do you deal with JAVAScript based location redirects? Will the status code and headers be correct? No. How does search engines get that? Perhaps the next page to be opened is the main one. (So we will need to follow the redirection, however we will also need to get the headers and status code of the main page that is being redirected)
6. Combine onCallback with the custom callback to allow users to call back to SlimerJS. Perhaps a wait timer is good too! This needs to be resolved automatically if the user forgot to do so. So if no custom callback, this will not be set. If there is a custom callback, they need to window.callPhantom(). Which would resolve to true, so this just delays the async further if need be. https://github.com/ariya/phantomjs/wiki/API-Reference-WebPage#wiki-webpage-onCallback On SlimerJS is it window.callSlimer()?

    Header redirect using 3XX series
    Javascript redirect that could happen synchronously and asynchronously
    User actions such as form/link... etc.
    How does meta refresh tags (html redirect) work if they are expecting 60s?


FIRST is to toggle webpage.navigationLocked depending on options.
First iteration, check on onResourceReceived and the first resource received for status and headers. This resolves any header redirect problems.
onPageOpen resolves synchronous JS redirects (may involve location.href)
still to resolve is async js redirects, user actions and meta refresh redirects (however _blank needs to be watched out for)

Then use onNavigationRequested

I need to know if redirects actually change the page content. 

Market here: http://backbonetutorials.com/seo-for-single-page-apps/

Prevent redirection by tracking the number of infinite redirects, dont know how to do that with header redirects however. Probably should have overall timeout for the overall task...

Client side redirection can be tracked from the onNavigationRequested and limited.
Header redirections hit an error onResourceError, however slimerJS just hangs in this case.