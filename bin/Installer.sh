#!/bin/bash

echo -e "\e[32m=== Welcome To Shieldfy Installer ==="
cat <<EOF


   _____ _     _      _     _  __       
  / ____| |   (_)    | |   | |/ _|      
 | (___ | |__  _  ___| | __| | |_ _   _ 
  \___ \| '_ \| |/ _ \ |/ _  |  _| | | |
  ____) | | | | |  __/ | (_| | | | |_| |
 |_____/|_| |_|_|\___|_|\__,_|_|  \__, |
                                   __/ |
                                  |___/ 

EOF

echo -e "\e[0m"


# Test if PHP is installed
php -v > /dev/null 2>&1
PHP_IS_INSTALLED=$?

[[ $PHP_IS_INSTALLED -ne 0 ]] && { echo -e "\e[41m\e[97m!!! PHP is not installed.\n    Installing Shieldfy aborted!\n\e[0m"; exit 0; }

echo ">>> Checking PHP Version ... OK"

# get shieldfy key & secret
key=$(php -r "echo @explode(':','$1')[0];")
secret=$(php -r "echo @explode(':','$1')[1];")
[[ ! $key || ! $secret ]] && { printf "Missing Key & Secret"; exit 0; }


# Test if Composer is installed
composer -v > /dev/null 2>&1
COMPOSER_IS_INSTALLED=$?

# Test if Apache or Nginx is installed
nginx -v > /dev/null 2>&1
NGINX_IS_INSTALLED=$?

apache2 -v > /dev/null 2>&1
APACHE_IS_INSTALLED=$?


# Test if UOPZ is installed
UOPZ_IS_INSTALLED=$(php -r "echo (extension_loaded('uopz'))?'ok':'no';")


# ============ functions ===========

InstallUOPZ (){

	echo ">>> UOPZ extention not installed , Installing UOPZ "

	# get PHP version
	phpv=$(php -v | grep -m 1 PHP | cut -s -d" " -f2 | cut -d"." -f1,2)

	# install php development package depends on php version

	PHPDevPackage='php-dev'

	if [ $phpv = '7.0' ] 
	then
		PHPDevPackage='php7.0-dev'
	fi

	if [ $phpv = '7.1' ] 
	then
		PHPDevPackage='php7.1-dev'
	fi


	apt-get update
	apt-get install -y $PHPDevPackage
	apt-get install -y php-pear
	pecl install uopz

	#check for confirguration files directorly 
	PHPConfDir="/etc/php/$phpv/mods-available"
	PHPCliConfDir="/etc/php/$phpv/cli/conf.d"

	if [ $phpv = '' ]
	then
		PHPConfDir="/etc/php/mods-available"
		PHPCliConfDir="/etc/php/cli/conf.d"
	fi

	if [ -d $PHPConfDir ]; then
		echo "$PHPConfDir Exists"
	fi

	if [ -d $PHPCliConfDir ]; then
		echo "$PHPCliConfDir Exists"
	fi

	# add configurations

	cat > $PHPConfDir/uopz.ini <<EOF
	; configuration for php uopz module
	; priority=5
	extension=uopz.so
EOF

	cat > $PHPCliConfDir/uopz.ini <<EOF
	; configuration for php uopz module
	; priority=5
	extension=uopz.so
EOF

}


InstallComposer ()
{
	echo ">>> Composer not installed , Installing Composer "
	php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
	php composer-setup.php
	php -r "unlink('composer-setup.php');"	

	# check if index.php has vendor autoload or not if not add it
	VendorExist=$(cat index.php | grep vendor/autoload.php)
	if [ $VendorExist = '' ]; then
		echo  "+++ Please Add this line at the top of your Index.php to load composer \n require_once('vendor/autoload.php'); "
	fi

}


AddShieldfy ()
{
	echo ">>> Adding Shieldfy SDK"
	# require shieldfy sdk
	composer require shieldfy/shieldfy-php:dev-master || { echo ">>> Composer failed , Make sure your are connected to internet and try again" ; exit 1; }

	echo ">>> Exporting shieldfy configurations (shieldfy.json)"
	# add shieldfy.json with keys in it
	cat > shieldfy.json << EOF
{
    "endpoint"          :"https://endpoint.shieldfy.io",
    "app_key"           :"$key",
    "app_secret"        :"$secret",
    "debug"             : false, 
    "action"            : "block", 
    "cache"             : "default",
    "headers"           : { 
        "X-XSS-Protection"       :  "1; mode=block",
        "X-Content-Type-Options" :  "nosniff",
        "X-Frame-Options"        :  "SAMEORIGIN"
    },
    "disable"           :  []
}

EOF
	

	#ping the api server
	#echo ">>> Run the internal PHP installer"
	#php -f "./vendor/shieldfy/shieldfy-php/bin/Installer.php"
	echo ">>> Shieldfy adding done"
}

RestartService ()
{
	# restart services
	# service --status-all | grep php | sed 's/\+//g' | sed 's/\[//g' | sed 's/\]//g' | sed 's/\s\+//g'
	# service --status-all | grep php | sed 's/[^a-zA-Z0-9\.\-]//g'
	# service --status-all | grep -o 'php.*'
	PHPFPM=$(service --status-all | grep -o 'php.*')
	if [[ $PHPFPM ]]; then
		echo ">>> Restaring PHP fpm "
		service $PHPFPM restart
	else
		echo '>>>  PHP fpm not exists restarting the webserver instead'
		if [[ $NGINX_IS_INSTALLED ]]; then
			echo ">>> Restaring Nginx "
			service nginx restart
		fi
		if [[ $APACHE_IS_INSTALLED ]]; then
			echo ">>> Restaring Apache "
			service apache2 restart
		fi
		
	fi

}

echo ">>> Begin Installation Process"

# ========== run ===========
if [[ $COMPOSER_IS_INSTALLED -ne 0 ]]; then
	InstallComposer
else
	echo ">>> Updating Composer"
	composer self-update
fi


if [[ $UOPZ_IS_INSTALLED = 'no' ]]; then
	InstallUOPZ
	RestartService
fi

AddShieldfy


echo "=== Congratulations, Shieldfy Installation Done , Go to the dashboard https://app.shieldfy.io === "

