<?php
namespace Shieldfy\Callbacks;

use Shieldfy\Config;

abstract class Callback
{
    protected $config;
    protected $cache;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    abstract public function handle();
}
