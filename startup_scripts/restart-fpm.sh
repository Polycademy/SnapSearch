#!/usr/bin/env bash

# php5-fpm keeps failing and the logs are not showing why
# here we are just going to keep restarting php5-fpm until the end of time!
# it needs to run as root
# repeat this every minute from cron!

if ! curl -sSfI https://snapsearch.io/ > /dev/null 2>&1 ; then        
    # server has failed
    echo "PHP-FPM failed at $(date)"
    service php5-fpm restart
fi

# -sfI is enough, since we don't actually want any output at all even if we get errors