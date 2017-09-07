<?php
require_once('../vendor/autoload.php');

$guard = Shieldfy\Guard::init([
    'app_key'=>'key',
    'app_secret'=>'secret'
]);

echo 'Welcome';
unset($guard);
echo 'Too';