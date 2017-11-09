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

/*
    * 1.User Monitor => user score ip / bots / etc
    * 2.Request Monitor => DOM XSS , CSRF
    * 3.View Monitor => View => XSS , Sensetive info , Open Redirect
    * 4.DB Monitor => SQLI , NoSQLI
    * 5.Internal Access Monitor => LFI , SSRF
    * 6.Code Execution Monitor => Object Injection / Code / Template / Command
    * 7.Headers Monitor => Email Injection , CRLF , JWT bypasses
    * 8.Uploads Monitor => Backdoors , XXE
    * 9.DDos Monitor => DDos
    * 10.Bruteforce Monitor => brute
    * 11.Session Monitor => session hijacking / session fixation .. etc
    * 12.ThirdPartyMonitor => composer.json / package.json / bower.json / crossdomain.xml & other sensetive files
 */

namespace Shieldfy\Monitors;

use Closure;
use Shieldfy\Config;
use Shieldfy\Http\Dispatcher;
use Shieldfy\Response\Response;

abstract class MonitorBase
{
    
    use Response;
    /**
     * @var Config $config
     */
    protected $config;
    protected $collectors;
    protected $dispatcher;

    /**
     * Constructor
     * @param Config $config
     */
    public function __construct(Config $config, Dispatcher $dispatcher, Array $collectors)
    {
        $this->config = $config;
        $this->dispatcher = $dispatcher;
        $this->collectors = $collectors;
        
        //check for uopz
        $this->isUOPZ = extension_loaded('uopz');
    }

    public function listenTo(Array $funcs,Array $callback)
    {
        if(!$this->isUOPZ) return false;

        foreach($funcs as $func):
            $this->listen($func, function() use($callback) {
                call_user_func_array($callback, func_get_args());
            });
        endforeach;

        return true;
    }

    public function listen($func, Closure $callback)
    {
        
        if(function_exists('uopz_set_hook')){
            $this->set_hook($func, $callback);
            return;
        }

        if(function_exists('uopz_rename')){
            $this->set_function($func, $callback);
            return;
        }

    }


    private function set_hook($func, Closure $callback)
    {
       
        if(is_array($func)){
            uopz_set_hook($func[0],$func[1],$callback);
            return;
        }
        uopz_set_hook($func,$callback);
    }

    private function set_function($function, Closure $callback)
    {

        //uopz_copy
        $originalFunc = uopz_copy($function);
        uopz_function($function, function() use($originalFunc, $callback) {
            /* can call original strtotime from here */
            call_user_func_array($callback,$args);
            return call_user_func_array($originalFunc,$args);
        });
    }

    /**
     * Force children to have its own run function
     */
    abstract public function run();

    /**
     * handle the judgment info
     * @param  array $judgment judgment informatoin
     * @return void
     *
     * [
     *     incident id
     *     session id
     *     attacker ip
     *     attacker info
     *     request info
     *     attack info
     *     url 
     *     type
     *     sub type
     *     rules info
     *     code stack
     *     code block
     * ]
     * 
     */
    
    protected function sendToJail($severity = 'low', $charge  = [], $code = [])
    {

        
        //based on severity and config , lets judge it 
        $incidentId = $this->generateIncidentId($this->collectors['user']->getId());

        $this->dispatcher->setData([
            'incidentId'        => $incidentId,
            'host'              => $this->collectors['request']->getHost(),
            'sessionId'         => $this->session->getId(),
            'user'              => $this->collectors['user']->getInfo(),
            'monitor'           => $this->name,
            'charge'            => $charge,
            'request'           => $this->collectors['request']->getProtectedInfo(),
            'code'              => $code,
            'response'          => $this->config['action']
        ]);

        if($severity == 'high' && $this->config['action'] == 'block')
        {
            $this->respond()->block($incidentId);
        }


        
        return;
        
    }


    private function generateIncidentId($userId)
    {
        return md5($userId.uniqid().mt_rand());
    }

}