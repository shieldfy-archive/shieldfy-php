<?php

namespace Shieldfy\Monitors;

use Closure;
use Shieldfy\Config;
use Shieldfy\Session;
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
    protected $session;
    protected $dispatcher;

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
       
        //class
        if(is_array($func)){
            uopz_set_hook($func[0],$func[1],$callback);
            return;
        }

        //single function
        uopz_set_hook($func,$callback);
    }

    private function set_function($func, Closure $callback)
    {

        //class
        if(is_array($func)){
            $originalFunc = uopz_copy($func[0],$func[1]);            
            uopz_function($func[0],$func[1], function() use($originalFunc, $callback) {
                $args = func_get_args();
                call_user_func_array($callback,$args);
                /* can call original func from here */
                return call_user_func_array($originalFunc,$args);
            });
            return;
        }

        //single function
        $originalFunc = uopz_copy($func);
        uopz_function($func, function() use($originalFunc, $callback) {
            $args = func_get_args();
            call_user_func_array($callback,$args);
            /* can call original func from here */
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
     */
    
    protected function sendToJail($severity = 'low', $charge  = [], $code = [])
    {

        
        //based on severity and config , lets judge it 
        $incidentId = $this->generateIncidentId($this->collectors['user']->getId());
        
        
        if($this->dispatcher->hasData() && $severity  != 'high'){
            //merge
            $data = $this->dispatcher->getData();
            if($data['charge']['key'] == $charge['key'])
            {
                //same 
                $charge['score'] += $data['charge']['score'];
                $charge['rulesIds'] = array_merge($data['charge']['rulesIds'], $charge['rulesIds']);
                //recalculate the severity
                $severity = $this->parseScore($charge['score']);
            }
        }

        $this->dispatcher->setData([
            'incidentId'        => $incidentId,
            'host'              => $this->collectors['request']->getHost(),
            'sessionId'         => $this->session->getId(),
            'user'              => $this->collectors['user']->getInfo(),
            'monitor'           => $this->name,
            'severity'          => $severity,
            'charge'            => $charge,
            'request'           => $this->collectors['request']->getProtectedInfo(),
            'code'              => $code,
            'response'          => ($severity == 'high' && $this->config['action'] == 'block') ? 'block' : 'pass'
        ]);

        if($severity == 'high' && $this->config['action'] == 'block')
        {
            if($this->name == 'view') {
                /* view is special case because it uses ob_start so we need to flush data here */
                $this->dispatcher->flush();
                return $this->respond()->returnBlock($incidentId);
            }
            $this->respond()->block($incidentId);
        }       
        return;
    }

    protected function parseScore($score = 0)
    {
        if($score >= 70) return 'high';
        if($score >= 40) return 'med';
        return 'low';
    }


    private function generateIncidentId($userId)
    {
        return md5($userId.uniqid().mt_rand());
    }

}