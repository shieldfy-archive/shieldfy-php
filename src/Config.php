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
    }


    public function getDefaults()
    {
        $defaults =  json_decode(file_get_contents( __DIR__ .'/config.json') , TRUE);
        return $defaults;
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
