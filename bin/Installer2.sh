#!/bin/bash

#[ "$UID" -eq 0 ] || exec sudo bash "$0" "$@"

echo ">>> Welcome to Shieldfy Installer"
echo ">>> The installer require sudo password for some operations "

sudo echo ">>> Begin Install"
# exit 0
# variables
PHP_VERSION_MAJOR=$(php -r "echo explode('.', PHP_VERSION)[0];")
PHP_VERSION_MINOR=$(php -r "echo explode('.', PHP_VERSION)[1];")
PHP_VERSION_RELEASE=$(php -r "echo explode('.', PHP_VERSION)[2];")

UOPZ_IS_INSTALLED=$(php -r "echo (extension_loaded('uopz'))?1:0;")

PHPConfDir="/etc/php/$PHP_VERSION_MAJOR.$PHP_VERSION_MINOR/mods-available"
PHPCliConfDir="/etc/php/$PHP_VERSION_MAJOR.$PHP_VERSION_MINOR/cli/conf.d"

# echo $PHPConfDir;
# echo $PHPCliConfDir;
# exit 0;

# locate php.ini

InstallPHPDev ()
{

	sudo apt-get update
	# install php-dev it will require automaticaly
	sudo apt-get install -y php-dev > /dev/null 2>&1
	sudo apt-get install -y php-pear
}

InstallUOPZForPHP7 ()
{
	echo ">>> Install UOPZ for PHP7"
	InstallPHPDev
	# use pecl
	sudo pecl install uopz

	#loaded ini
	ModsAvaialbe=$(phpenmod uopz 2>&1 >/dev/null | head -1 | cut -d"/" -f2,3,4,5,6,7,8,9)

sudo tee -a /$ModsAvaialbe/uopz.ini <<EOF
	; configuration for php uopz module
	; priority=5
	extension=uopz.so
EOF

	sudo phpenmod uopz

	RestartService
}


InstallUOPZForPHP5 ()
{
	echo ">>> Install UOPZ for PHP5"
	InstallPHPDev
	sudo pecl install uopz-2.0.7

	#loaded ini
	ModsAvaialbe=$(php5enmod uopz 2>&1 >/dev/null | head -1 | cut -d"/" -f2,3,4,5,6,7,8,9)

cat > /$ModsAvaialbe/uopz.ini <<EOF
	; configuration for php uopz module
	; priority=5
	extension=uopz.so
EOF

	sudo php5enmod uopz

	RestartService

}

RestartService ()
{
	# restart services
	# service --status-all | grep php | sed 's/\+//g' | sed 's/\[//g' | sed 's/\]//g' | sed 's/\s\+//g'
	# service --status-all | grep php | sed 's/[^a-zA-Z0-9\.\-]//g'
	# service --status-all | grep -o 'php.*'
	
	PHPFPM=$(service --status-all | grep -o 'php.*')

	nginx -v > /dev/null 2>&1
	NGINX_IS_INSTALLED=$?

	apache2 -v > /dev/null 2>&1
	APACHE_IS_INSTALLED=$?

	if [ $PHPFPM ]; then
		echo ">>> Restaring PHP fpm "
		sudo service $PHPFPM restart
	else
		echo ">>>  PHP fpm not exists restarting the webserver instead"
		if [ $NGINX_IS_INSTALLED ]; then
			echo ">>> Restaring Nginx "
			sudo service nginx restart
		fi
		if [ $APACHE_IS_INSTALLED ]; then
			echo ">>> Restaring Apache "
			sudo service apache2 restart
		fi
		
	fi

}



# AddUOPZModuleTORunningPHP ()
# {
# 	#check mods-enable first
# 	echo ">>"
# 	$PHPConfDir=$(getPHPConfDir)

# 	PHPINI=$(php --ini | grep Loaded | cut -d":" -f2 | sed 's/^ *//;s/ *$//')

# }


Main ()
{

	# X=$(getPHPConfDir)
	# XX=$(getPHPCliConfDir)

	# echo $X

	# echo $XX

	# exit 0;

	# Test if UOPZ is installed

	if [ $UOPZ_IS_INSTALLED -eq 1 ]; then
		echo ">>> Check UOPZ installation : OK"
		#exit 0
	else
		echo ">>> Check UOPZ installation : Not Installed"
		echo ">>> Installing UOPZ extention"
	fi

	# Install UOPZ
	if [ $PHP_VERSION_MAJOR -eq 7  ]; then
		echo ">>> PHP Version is 7"
		InstallUOPZForPHP7
	else
		echo ">>> PHP version is 5"
		InstallUOPZForPHP5
	fi



}


#run
Main
