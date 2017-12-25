<?php
namespace Shieldfy\Monitors;

use Shieldfy\Jury\Judge;

class HeadersMonitor extends MonitorBase
{
    use Judge;

    protected $name = "headers";
    protected $infected = [];

    /**
     * run the monitor
     */
    public function run()
    {
        
        $request = $this->collectors['request'];
        $this->issue('headers');

        $request = $this->collectors['request'];
        $info = $request->getInfo();
        $params = array_merge($info['get'], $info['post']);

        $infected = [];
        foreach ($params as $key => $value) {

            $result = $this->sentence($value);
            if($result['score']){
                $result['value'] = $value;
                $result['key'] = $key;
                $infected[] = $result;
            }

        }

        if(count($infected) > 0){
            //its time to listen
            $this->runAnalyzers($infected);            
        }


        
    }

    public function runAnalyzers(Array $infected = [])
    {
        //$this->checkForOr($infected)
        
        $this->infected = $infected;

        $this->listenTo([
            'header'
        ],[$this,'analyze']);
    }
    

    public function analyze()
    {
        $arg_list = func_get_args();
        foreach($arg_list as $arg):
            if(is_string($arg)) $this->deepAnalyze($arg);
        endforeach;
    }


    public function deepAnalyze($arg)
    {

        $foundGuilty = false;
        $charge = "";

        foreach($this->infected as $infected):
            if (stripos($arg, $infected['value']) !== false) {
                $foundGuilty = true;
                $charge = $infected;
                break;
            }
        endforeach;
        
        if($foundGuilty){

            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); 
            $code = $this->collectors['code']->pushStack($stack)->collectFromStack($stack);
            
            $this->sendToJail($this->parseScore($charge['score']), $charge , $code);    
        }
    }

}
