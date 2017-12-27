<?php
namespace Shieldfy\Monitors;

use Shieldfy\Config;
use Shieldfy\Session;
use Shieldfy\Http\Dispatcher;

class MonitorsBag
{
    /**
     * list of available monitors
     * name => class extended from MonitorBase class
     * 
     */
    private $monitors = [
        'UserMonitor'               =>    \Shieldfy\Monitors\UserMonitor::class,
        'RequestMonitor'            =>    \Shieldfy\Monitors\RequestMonitor::class,
        'HeadersMonitor'            =>    \Shieldfy\Monitors\HeadersMonitor::class,
        'UploadMonitor'             =>    \Shieldfy\Monitors\UploadMonitor::class,
        'DBMonitor'                 =>    \Shieldfy\Monitors\DBMonitor::class,
        'InternalsMonitor'          =>    \Shieldfy\Monitors\InternalsMonitor::class,
        'ExecutionMonitor'          =>    \Shieldfy\Monitors\ExecutionMonitor::class,
        'MemoryMonitor'             =>    \Shieldfy\Monitors\MemoryMonitor::class,
        'ViewMonitor'               =>    \Shieldfy\Monitors\ViewMonitor::class,
        'ExceptionsMonitor'         =>    \Shieldfy\Monitors\ExceptionsMonitor::class,
        'ThirdPartyMonitor'         =>    \Shieldfy\Monitors\ThirdPartyMonitor::class,
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
    public function __construct(Config $config, Session $session, Dispatcher $dispatcher, Array $collectors)
    {
        $this->config = $config;
        $this->session = $session;
        $this->dispatcher = $dispatcher;
        $this->collectors = $collectors;
    }

    public function run()
    {
        foreach ($this->monitors as $monitorName => $monitorClass) {
            if (!in_array($monitorName, $this->config['disable'])) {
                (new $monitorClass($this->config, $this->session, $this->dispatcher, $this->collectors))->run();
            }
        }
    }
}
