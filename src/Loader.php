<?php
class ShieldfyLoader
{
    protected $config = [];
    public function __construct($baseDirectory)
    {
        //check if configFile Exists
        if(file_exists($baseDirectory.'/shieldfy.json') && is_readable($baseDirectory.'/shieldfy.json')){
            $this->config = json_decode(file_get_contents( $baseDirectory.'/shieldfy.json') , TRUE);
        }
        if(!is_array($this->config)) $this->config = []; //just caution if file is corrupted and returned null

        //overwrite env if exists
        if(getenv('SHIELDFY_APP_KEY')) $this->config['app_key'] = getenv('SHIELDFY_APP_KEY');
        if(getenv('SHIELDFY_APP_SECRET')) $this->config['app_secret'] = getenv('SHIELDFY_APP_SECRET');
        if(getenv('SHIELDFY_DEBUG')) $this->config['debug'] = getenv('SHIELDFY_DEBUG');
        if(getenv('SHIELDFY_ACTION')) $this->config['action'] = getenv('SHIELDFY_ACTION');

    }

    public function load()
    {
        $cache = null; //default

        if(isset($this->config['cache']) && is_array($this->config['cache'])){
            //create a new cache
            $cache = new Shieldfy\CacheManager($this->config);
            $cache = $cache->setDriver(
                    $this->config['cache']['driver'], 
                    $this->config['cache']['config']
                );
        }

        Shieldfy\Guard::init($this->config,$cache);
    }
}

$shieldfyLoader = new ShieldfyLoader($_SERVER['DOCUMENT_ROOT']);
$shieldfyLoader->load();