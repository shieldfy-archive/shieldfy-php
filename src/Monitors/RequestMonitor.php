<?php
namespace Shieldfy\Monitors;

use Shieldfy\Jury\Judge;

class RequestMonitor extends MonitorBase
{
    use Judge;

    protected $name = "request";

    /**
     * run the monitor
     * Monitor for bots traditional attacks
     * Monitor fot request attacks , CSRF 
     */
    public function run()
    {
        
        $request = $this->collectors['request'];
        $user = $this->collectors['user'];

        
    }
}
