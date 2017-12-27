<?php
namespace Shieldfy\Monitors;
use Shieldfy\Jury\Judge;

class DBMonitor extends MonitorBase
{
	use Judge;
    
    protected $name = "db";
	protected $infected = [];

    /**
     * run the monitor
     */
    public function run()
    {
    	$this->issue('db');

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
        
        $this->infected = $infected;

        $this->listenTo([
            //MYSQL
            'mysql_query',
            //MYSQLI
            'mysqli_query',
            'mysqli_multi_query',
            'mysqli_real_query',
            ['mysqli','query'],
            ['mysqli','multi_query'],
            ['mysqli','real_query'],    
            //PDO
            ['pdo','exec'],
            ['pdo','query'],
            ['pdo','prepare']

        ],[$this,'analyze']);

    }

    public function analyze()
    {
        $arg_list = func_get_args();
        foreach($arg_list as $arg):
            //get the final query
            if(is_string($arg)) $this->deepAnalyze($arg);
        endforeach;
    }


    public function deepAnalyze($query)
    {
        $foundGuilty = false;
        $charge = "";

        foreach($this->infected as $infected):
            if (stripos($query, $infected['value']) !== false) {
                $foundGuilty = true;
                $charge = $infected;
                break;
            }
        endforeach;
        
        if($foundGuilty){

            $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS); 
            $code = $this->collectors['code']->pushStack($stack)->collectFromStack($stack);
            $this->sendToJail( $this->parseScore($charge['score']), $charge, $code );
            
        }
        
    }
    
}
