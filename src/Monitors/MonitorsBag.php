<?php
namespace Shieldfy\Monitors;

use Shieldfy\Config;
use Shieldfy\Session;
use Shieldfy\Cache\CacheInterface;

class MonitorsBag
{
    /**
     * list of available monitors
     * name => class extended from MonitorBase class
     * 
     */
    private $monitors = [

        // 'UserMonitor'               =>    \Shieldfy\Monitors\UserMonitor::class,
        // 'DoSMonitor'                =>    \Shieldfy\Monitors\DoSMonitor::class,
        // 'BruteForceMonitor'         =>    \Shieldfy\Monitors\BruteForceMonitor::class,        
        // 'RequestMonitor'            =>    \Shieldfy\Monitors\RequestMonitor::class,
        // 'HeadersMonitor'            =>    \Shieldfy\Monitors\HeadersMonitor::class,
        // 'UploadMonitor'             =>    \Shieldfy\Monitors\UploadMonitor::class,
        // 'SessionMonitor'            =>    \Shieldfy\Monitors\ViewMonitor::class,
         'DBMonitor'                 =>    \Shieldfy\Monitors\DBMonitor::class,
        // 'InternalAccessMonitor'     =>    \Shieldfy\Monitors\InternalAccessMonitor::class,
        // 'CodeExecutionMonitor'      =>    \Shieldfy\Monitors\CodeExecutionMonitor::class,
        // 'ViewMonitor'               =>    \Shieldfy\Monitors\ViewMonitor::class,
        //'ThridPartyMonitor'       =>    \Shieldfy\Monitors\ViewMonitor::class
    ];

    /**
     * @var Config $config
     */
    protected $config;
    protected $collectors;

    /**
     * Constructor
     * @param Config $config
     */
    public function __construct(Config $config, $collectors)
    {
        $this->config = $config;
        $this->collectors = $collectors;
    }

    public function run()
    {
        foreach ($this->monitors as $monitorName => $monitorClass) {
            if (!in_array($monitorName, $this->config['disable'])) {
                (new $monitorClass($this->config,$this->collectors))->run();
            }
        }
    }
}
