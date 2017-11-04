<?php
namespace Shieldfy;

use ArrayAccess;

class Config implements ArrayAccess
{
    /**
     * All of the configuration items.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new configuration container.
     *
     * Merge the user configurations with the default config
     *
     * @param array $defaults
     * @param array $userConfig
     *
     * @return void
     */
    public function __construct(array $userConfig = [])
    {
        $this->items = array_replace_recursive( $this->getDefaults() , $userConfig);
        $this->getEnvironmentIfAny();
        $this->loadUserConfig();
    }


    public function loadUserConfig()
    {
        $stack = debug_backtrace();
        $firstFrame = $stack[count($stack) - 1];
        $scriptOriginalRoot = dirname($firstFrame['file']);
        if(file_exists($scriptOriginalRoot.'/shieldfy.json')){
            return 1;
        }else{
            //go one up
            $scriptOriginalRootUp = realpath($scriptOriginalRoot.'/..');
            if(file_exists($scriptOriginalRootUp.'/shieldfy.json')){
                return 2;
            }
        }

        //non exists load default config
        $defaultConfigFile = __DIR__ .'/config.json';
        return 3;

    }

    public function getDefaults()
    {
        

        //echo $initialFile.':) :D :D ';

         $defaults =  json_decode(file_get_contents( __DIR__ .'/config.json') , TRUE);
         $defaults['rootDir'] = realpath(__DIR__.'/../');
         $defaults['dataDir'] = $defaults['rootDir'].'/src/data';
         $defaults['tmpDir'] = $defaults['rootDir'].'/tmp';
         $defaults['logsDir'] = $defaults['rootDir'].'/logs';
         $defaults['vendorsDir'] = str_replace('/shieldfy/shieldfy-php', '', $defaults['rootDir']);
         return $defaults;
    }

    public function getEnvironmentIfAny()
    {
        if(!isset($this->items['endpoint']) || !$this->items['endpoint']) $this->items['endpoint'] = getenv('SHIELDFY_ENDPOINT');
        if(!isset($this->items['app_key']) || !$this->items['app_key']) $this->items['app_key'] = getenv('SHIELDFY_APP_KEY');
        if(!isset($this->items['app_secret']) || !$this->items['app_secret']) $this->items['app_secret'] = getenv('SHIELDFY_APP_SECRET');
    }

    /**
     * Set configuration.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return void
     */
    public function offsetSet($key, $value)
    {
        if (is_null($key)) {
            $this->items[] = $value;
        } else {
            $this->items[$key] = $value;
        }
    }

    /**
     * Check if key exists.
     *
     * @param type $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->items[$key]);
    }

    /**
     * Remove config item.$
     *
     * @param type $key
     *
     * @return bool
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Get config item.
     *
     * @param type $key
     *
     * @return mixed value
     */
    public function offsetGet($key)
    {
        return isset($this->items[$key]) ? $this->items[$key] : null;
    }
}
