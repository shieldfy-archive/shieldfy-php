<?php
namespace shieldfy;

use Shieldfy\Config;
use Shieldfy\Http\ApiClient;
use Shieldfy\Http\Dispatcher;
use Shieldfy\Collectors\UserCollector;
use Shieldfy\Collectors\RequestCollector;
use Shieldfy\Collectors\ExceptionsCollector;
use Shieldfy\Session;

class Guard
{
    /**
     * @var Singleton Reference to singleton class instance
     */
    private static $instance = null;

    /**
     * @var Api Endpoint
     * @var Version
     * @var Config
     * @var Dispatcher
     * @var Collectors
     * @var Session
     */
    public $endpoint = 'https://api.shieldfy.io';
    public $version = '3.0.0';
    public $config = null;
    public $dispatcher = null;
    public $collectors = [];
    public $session = null;

    /**
     * Initialize Shieldfy guard.
     *
     * @param array $config
     * @return object
     */
    public static function init(array $config = [])
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new self($config);
    }

    /**
     * Create a new Guard Instance
     * @param array $userConfig
     * return initialized guard
     */
    private function __construct(array $userConfig)
    {

        
        //set config container
        $this->config = new Config($userConfig);

        //overwrite the endpoint
        if(isset($this->config['endpoint'])){
            $this->endpoint = $this->config['endpoint'];
        }


        //set Dispatcher
        $apiClient = new ApiClient($this->endpoint, $this->config);
        $this->dispatcher = new Dispatcher($this->config, $apiClient);

        //start shieldfy guard
        $this->startGuard();
    }

    /**
     * Starting Guard
     */
    private function startGuard()
    {
        //starting collectors
        $this->collectors = $this->startCollecting();
        //starting session
        $this->session = new Session(
                                $this->collectors['user'],
                                $this->collectors['request'],
                                $this->dispatcher
                        );

        //starting monitors
        register_shutdown_function([$this,'flush']);
        $this->exposeHeaders();
    }

    /**
     * Start Collecting data needed
     * @return Array CollectorsBag
     */  
    private function startCollecting()
    {
        //$exceptionsCollector = new ExceptionsCollector($this->config);
        $requestCollector = new RequestCollector($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
        $userCollector = new UserCollector($requestCollector);

        return [
          //  'exceptions' => $exceptionsCollector,
            'request'    => $requestCollector,
            'user'       => $userCollector
        ];
    }

    /**
     * flush data to the API
     */ 
    private function flush()
    {
        $this->session->flush();
    }

    /**
     * Expose useful headers
     * @return void
     */
    private function exposeHeaders()
    {
        if (function_exists('header_remove')) {
            header_remove('x-powered-by');
        } else {
            header('x-powered-by: unknown');
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

    /* singelton protection */
    protected function __clone(){}
    protected function __wakeup(){}

}
