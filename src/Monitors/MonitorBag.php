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
     */
    private $monitors = [
        // 'UserMonitor'           =>    \Shieldfy\Monitors\UserMonitor::class,
        // 'UploadMonitor'         =>    \Shieldfy\Monitors\UploadMonitor::class,
        // 'CSRFMonitor'           =>    \Shieldfy\Monitors\CSRFMonitor::class,
        // 'RequestMonitor'        =>    \Shieldfy\Monitors\RequestMonitor::class,
        // 'APIMonitor'            =>    \Shieldfy\Monitors\APIMonitor::class,
        // 'ExceptionMonitor'      =>    \Shieldfy\Monitors\ExceptionMonitor::class,
        // 'QueryMonitor'          =>    \Shieldfy\Monitors\QueryMonitor::class,
        // 'ViewMonitor'           =>    \Shieldfy\Monitors\ViewMonitor::class
    ];

    /**
     * @var Config $config
     */
    protected $config;

    /**
     * Constructor
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function run()
    {
        foreach ($this->monitors as $monitorName => $monitorClass) {
            if (!in_array($monitorName, $this->config['disable'])) {
                (new $monitorClass($this->config))->run();
            }
        }
    }
}
