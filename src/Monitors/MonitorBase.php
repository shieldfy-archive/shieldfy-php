<?php
/*
* User Monitor => Bots , Scanners , Tor , VPNs , Proxies , Bad Reputation ... etc
* View Monitor => ob_start
* DB Monitor => listen DB Execution functions (N)
* Session Monitor =>
* Request Monitor => CSRF , DOM XSS
* Upload Monitor => backdoors , XXE
* DoS Monitor
* Bruteforce MOnitor
* API Monitor =>
* Exception Monitor =>
* 3rdParty Monitor =>
* SSRF Monitor => SSRF 
* RCE Monitoring => Object Injection , Code Injection , Command Injection , Template Injection
* Headers Monitoring => CRLF , Email Injection
* Information Disclosure Monitor
*/

namespace Shieldfy\Monitors;

use Shieldfy\Config;

abstract class MonitorBase
{
    
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

    /**
     * Force children to have its own run function
     */
    abstract public function run();

}