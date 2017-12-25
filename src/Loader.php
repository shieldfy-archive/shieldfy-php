<?php


if(!defined('DIRECTORY_SEPARATOR')){
    define('DIRECTORY_SEPARATOR','/');
}


function getBaseDirectory()
{

    // -- first method --
    //search stack for find original folder from composer folder
    $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); 
    foreach($stack as $frame)
    {
        if($frame['function'] == 'getLoader'){
            $baseDirectory1 =  realpath(dirname($frame['file']).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
            break;
        }
    }
    if(file_exists($baseDirectory1.DIRECTORY_SEPARATOR.'shieldfy.json')){
        return $baseDirectory1;
    }

    // -- second method --
    $firstFrame = $stack[count($stack) - 1];
    $baseDirectory2 = dirname($firstFrame['file']);
    if(file_exists($baseDirectory2.DIRECTORY_SEPARATOR.'shieldfy.json')){
        return $baseDirectory2;
    }

    // -- third method --
    $baseDirectory3 =  realpath($baseDirectory2.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR);
    if(file_exists($baseDirectory3.DIRECTORY_SEPARATOR.'shieldfy.json')){
        return $baseDirectory3;
    }

    return false;
    
}


function loadShieldfyWebApplicationFirewall($baseDirectory)
{
    $config = array();

    if($baseDirectory && is_readable($baseDirectory.'/shieldfy.json')){
        $config = json_decode(file_get_contents( $baseDirectory.'/shieldfy.json') , TRUE);
        if(!is_array($config)) $config = []; //just caution if file is corrupted and returned null
    }

    //overwrite env if exists
    if(getenv('SHIELDFY_APP_KEY')) $config['app_key'] = getenv('SHIELDFY_APP_KEY');
    if(getenv('SHIELDFY_APP_SECRET')) $config['app_secret'] = getenv('SHIELDFY_APP_SECRET');
    if(getenv('SHIELDFY_DEBUG')) $config['debug'] = getenv('SHIELDFY_DEBUG');
    if(getenv('SHIELDFY_ACTION')) $config['action'] = getenv('SHIELDFY_ACTION');

    $config['paths'] = [
        'base'      => $baseDirectory,
        'root'      =>  realpath(__DIR__.DIRECTORY_SEPARATOR.'..'),
        'src'       =>  __DIR__,
        'data'      =>  __DIR__.DIRECTORY_SEPARATOR.'Data',
        'logs'      =>  realpath(__DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'logs',
        'vendors'   =>  str_replace('/shieldfy/shieldfy-php/src', '', __DIR__)
    ];

    Shieldfy\Guard::init($config);
}

loadShieldfyWebApplicationFirewall(getBaseDirectory());