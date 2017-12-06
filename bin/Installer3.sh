# wget http://pecl.php.net/get/uopz-5.0.2.tgz
# tar -xvf uopz-5.0.2.tgz
# cd uopz-5.0.2
# phpize
# ./configure
# make
# make test
# make install



# touch /etc/php.d/gearman.ini

getPHPINIFile ()
{
	service --status-all | grep -o 'php.*' 2>&1
	PHPFPM_IS_INSTALLED=$?

	nginx -v > /dev/null 2>&1
	NGINX_IS_INSTALLED=$?

	apache2 -v > /dev/null 2>&1
	APACHE_IS_INSTALLED=$?


	php-fpm -v > /dev/null 2>&1
	PHPFPM=$?

	php-fpm7.0 -v > /dev/null 2>&1
	PHPFMP70=$?

	php-fpm7.1 -v > /dev/null 2>&1
	PHPFMP71=$?

	php-fpm7.2 -v > /dev/null 2>&1
	PHPFMP72=$?


	BasePHPFolder=$(php --ini | grep -o "/.*/" | head -1)
	PHPINICLIPATH=$(php --ini | grep -o "/.*/php.ini")
	PHPINIPATH="/etc/php5/apache2/php.ini"

	if [ $APACHE_IS_INSTALLED == 0 ]; then
		PHPINIPATH="$BasePHPFolder/apache2/php.ini"
	fi

	if [[ $PHPFPM == 0 ]]; then
		echo -e "\e[32m>>> Checking PHP Calling ... PHP Fpm\e[0m"
		PHPINIPATH=$(php-fpm -i | grep -o "/.*/php.ini")
	fi

	if [[ $PHPFMP70 == 0 ]]; then
		echo -e "\e[32m>>> Checking PHP Calling ... PHP Fpm 7.0\e[0m"
		PHPINIPATH=$(php-fpm7.0 -i | grep -o "/.*/php.ini")
	fi

	if [[ $PHPFMP71 == 0 ]]; then
		echo -e "\e[32m>>> Checking PHP Calling ... PHP Fpm 7.1\e[0m"
		PHPINIPATH=$(php-fpm7.1 -i | grep -o "/.*/php.ini")
	fi

	if [[ $PHPFMP72 == 0 ]]; then
		echo -e "\e[32m>>> Checking PHP Calling ... PHP Fpm 7.2\e[0m"
		PHPINIPATH=$(php-fpm7.2 -i | grep -o "/.*/php.ini")
	fi
}


InstallDependecies ()
{
	PHPBaseDevPackage='php-dev'
	PHPAlternativeDevPackage="php$PHP_VERSION_MAJOR.$PHP_VERSION_MINOR-dev"

	#sudo apt-get update
	#sudo apt-get install -y $PHPBaseDevPackage
	#sudo apt-get install -y $PHPAlternativeDevPackage
	sudo apt-get install -y php-pear
}

InstallUOPZ ()
{
	UOPZ_IS_INSTALLED=$(php -r "echo (extension_loaded('uopz'))?1:0;")

	if [ $UOPZ_IS_INSTALLED -eq 1 ]; then
		echo ">>> Check UOPZ installation : OK"
		return;
	else
		echo ">>> Check UOPZ installation : Not Installed"
		echo ">>> Installing UOPZ extention ... "

		InstallDependecies

		if [ $PHP_VERSION_MAJOR -eq 7 ]; then
			InstallUOPZForPHP7
		else
			InstallUOPZForPHP5
		fi

		echo "extension = uopz.so" | sudo tee --append  $PHPINIPATH
		echo "extension = uopz.so" | sudo tee --append  $PHPINICLIPATH
		
	fi

	RestartService
}

InstallUOPZForPHP7 ()
{
	echo ">>> Install UOPZ for PHP7"
	sudo pecl install uopz
	if [ $? -ne 0 ]; then
	    #failed try compile it from source
	    wget http://pecl.php.net/get/uopz-5.0.2.tgz
		tar -xvf uopz-5.0.2.tgz
		cd uopz-5.0.2
		phpize
		./configure
		make
		#make test
		sudo make install
		cd ..
		rm uopz-5.0.2.tgz
		rm package.xml
		rm -r uopz-5.0.2
	fi
	#echo "hI"
	#echo "extension = uopz.so" | sudo tee --append  $PHPINIPATH
}

InstallUOPZForPHP5 ()
{
	echo ">>> Install UOPZ for PHP5"
	sudo pecl install uopz-2.0.7
	if [ $? -ne 0 ]; then
	    #failed try compile it from source
	    wget http://pecl.php.net/get/uopz-2.0.7.tgz
		tar -xvf uopz-2.0.7.tgz
		cd uopz-2.0.7
		phpize
		./configure
		make
		#make test
		sudo make install
		cd ..
		rm uopz-2.0.7.tgz
		rm package.xml
		rm -r uopz-2.0.7
	fi
	#sudo echo "extension = uopz.so" >> $PHPINIPATH
}


RestartService ()
{
	# restart services
	# service --status-all | grep php | sed 's/\+//g' | sed 's/\[//g' | sed 's/\]//g' | sed 's/\s\+//g'
	# service --status-all | grep php | sed 's/[^a-zA-Z0-9\.\-]//g'
	# service --status-all | grep -o 'php.*'

	if [ $PHPFPM == 0 ]; then
		echo ">>> Restaring PHP fpm "
		sudo service $PHPFPM restart
	else
		echo ">>>  PHP fpm not exists restarting the webserver instead"
		if [ $NGINX_IS_INSTALLED == 0 ]; then
			echo ">>> Restaring Nginx "
			sudo service nginx restart
		fi
		if [ $APACHE_IS_INSTALLED == 0 ]; then
			echo ">>> Restaring Apache "
			sudo service apache2 restart
		fi
		
	fi

}

AddShieldfy ()
{
	
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

Main ()
{

	echo -e "\e[32m"
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

	echo ">>> Welcome to Shieldfy Installer"
	echo ">>> The installer require sudo password for some operations"

	#echo -e "\e[32m"
	#sudo echo -e "\e[32m>>> Begin Install\e[0m"
	#echo -e "\e[0m"



	# Required Constants & Checks

	echo ">>> Checking PHP Version "

	PHP_VERSION_MAJOR=$(php -r "echo explode('.', PHP_VERSION)[0];")
	PHP_VERSION_MINOR=$(php -r "echo explode('.', PHP_VERSION)[1];")
	PHP_VERSION_RELEASE=$(php -r "echo explode('.', PHP_VERSION)[2];")

	echo -e "\e[32m>>> PHP $PHP_VERSION_MAJOR.$PHP_VERSION_MINOR.$PHP_VERSION_RELEASE ... OK\e[0m"

	getPHPINIFile
	#echo $BasePHPFolder
	#echo $PHPINIPATH
	#echo $PHPINICLIPATH
	#exit 0;
	

	#try locate php.ini

	# Install UOPZ
	InstallUOPZ

	#add Shieldfy
	AddShieldfy
}


#run
Main