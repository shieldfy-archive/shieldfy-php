<?php
namespace shieldfy;

use Shieldfy\Config;
use Shieldfy\Cache\CacheManager;
use Shieldfy\Http\ApiClient;
use Shieldfy\Http\Dispatcher;
use Shieldfy\Collectors\UserCollector;
use Shieldfy\Collectors\RequestCollector;
use Shieldfy\Collectors\ExceptionsCollector;

class Guard
{
    /**
     * @var Singleton Reference to singleton class instance
     */
    private static $instance;

    /**
     * @var api endpoint
     */
    public $endpoint = 'https://api.shieldfy.io';

    /**
     * @var version
     */
    public $version = '3.0.0';

    public $config = null;

    public $dispatcher = null;

    public $cache = null;

    public $collectors = [];


   /**
     * Initialize Shieldfy guard.
     *
     * @param array $config
     * @param CacheInterface $cache
     * @return object
     */
    public static function init(array $config, $cache = null)
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $cache);
        }
        return self::$instance;
    }

    /**
     * Create a new Guard Instance
     * @param array $userConfig
     * @param CacheInterface $cache
     * return initialized guard
     */
    public function __construct(array $userConfig, CacheInterface $cache = null)
    {
        //set config container
        $this->config = new Config($userConfig);

        //prepare the cache method if not supplied
        if ($cache === null) {
            //create a new file cache
            $cache = new CacheManager($this->config);

            $cache = $cache->setDriver('file', [
                'path'=> $this->config['rootDir'].'/tmp/',
            ]);
        }

        $this->cache = $cache;

        //set Dispatcher
        $apiClient = new ApiClient($this->endpoint, $this->config);
        $this->dispatcher = new Dispatcher($apiClient);

        //start shieldfy guard
        $this->startGuard();
    }

    public function startGuard()
    {
        //starting collectors
        $collectors = $this->startCollecting();
        //starting session

        $session = new Session($collectors['user'],$collectors['request'],$this->dispatcher,$this->cache);

        //$session->save(); //save as step
        //starting monitors
        
        register_shutdown_function([$this,'flush']);

        $this->exposeHeaders();
    }

    public function startCollecting()
    {
        $exceptionsCollector = new ExceptionsCollector($this->config);
        $requestCollector = new RequestCollector($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
        $userCollector = new UserCollector($requestCollector);

        return [
            'exceptions' => $exceptionsCollector,
            'request'    => $requestCollector,
            'user'       => $userCollector
        ];
    }

    public function flush()
    {
        //session sync
        //echo http_response_code();
    }

    /**
     * Expose useful headers
     * @return void
     */
    private function exposeHeaders()
    {
        if (function_exists('header_remove')) {
            header_remove('x-powered-by');
        }

        foreach ($this->config['headers'] as $header => $value) {
            if ($value === false) {
                continue;
            }
            header($header.': '.$value);
        }

        $signature = hash_hmac('sha256', $this->config['app_key'], $this->config['app_secret']);
        header('X-Web-Shield: ShieldfyWebShield');
        header('X-Shieldfy-Signature: '.$signature);
    }
        
}