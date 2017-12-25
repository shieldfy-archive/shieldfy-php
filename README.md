# Shieldfy Official PHP SDK

## Description

TBD

## Installation

### Install Shieldfy Server Dependecies (UOPZ Extention)

#### Automated script (Ubuntu)
```bash
bash <(curl -Ss https://github.com/shieldfy/shieldfy-php/blob/master/bin/install)
```

#### Manual Install 
See Here


### Install Shieldfy Composer Package

```
composer require shieldfy/shieldfy-php
```


#### PHP Native

```php
if(!class_exists(\Composer\Autoload\ClassLoader::class)) require_once(__DIR__.'/vendor/autoload.php');

\Shieldfy\Guard::init([
	'app_key' 		=> 'YOURAPPKEY',
	'app_secret' 	=> 'YOURAPPSECRET'
]);
```

#### Laravel Extention (add laravel service provider)
in `config/app.php` add `ShieldfyServiceProvider` to the `providers` list
```php
'providers' => [
	\Shieldfy\Extentions\Laravel\ShieldfyServiceProvider::class
]
```

#### CodeIgniter Extention (Add CI Bridge)

```php
if(!class_exists(\Composer\Autoload\ClassLoader::class)) require_once(__DIR__.'/vendor/autoload.php');

$guard = \Shieldfy\Guard::init([
	'app_key' 		=> 'YOURAPPKEY',
	'app_secret' 	=> 'YOURAPPSECRET'
]);

$CI =& get_instance();
\Shieldfy\Extentions\CodeIgniter\Bridge::load($$guard,$CI);

```

#### Symfony Extention ()

TBD 

#### CakePHP

TBD 

#### ZendPHP

TBD 

#### Yii PHP

TBD

## Configurations

TBD

## Running Unit Testing

`phpunit`



## Testing Environment

```bash
cd Example
php -S localhost:8080
```

## Changelog

TBD

## Contribution

TBD

## Credits

TBD