#!/bin/bash

# Mobilise will mobilise all the dependencies of this project in one go!
# Everything will be installed relative to this file (this repo) and then moved/symlinked to the correct place
# This file should appear as /www/snapsearch/mobilise.sh

# Ask for sudo
if [[ $UID != 0 ]]; then
	echo "Please run this script with sudo:"
	echo "sudo $0 $*"
	exit 1
fi

# Find the project's directory from this file
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
# Change to the project's directory
cd $PROJECT_DIR

# Download SlimerJS
echo "Downloading SlimerJS 0.9.0rc1"
curl http://download.slimerjs.org/v0.9/0.9.0rc1/slimerjs-0.9.0rc1-linux-i686.tar.bz2 -o slimerjs.tar.bz2
echo "Uncompressing and extracting SlimerJS into ./slimerjs"
mkdir slimerjs && tar xjvf slimerjs.tar.bz2 -C slimerjs --strip-components 1
echo "Adding SlimerJS to the PATH"
sudo ln -s `pwd`/slimerjs/slimerjs /usr/local/bin/slimerjs

# Bring in Secret Keys





# Bring in the keys from Github (secrets) (ask for it on the cli initialise, we cant save the keys)