<?php
namespace Shieldfy\Monitors;

use Shieldfy\Jury\Judge;

class ViewMonitor extends MonitorBase
{
    use Judge;

    protected $name = 'view';

    protected $vaguePhrases = [
        '<script>','</script>'
    ];
    
    /**
     * run the monitor
     */
    public function run()
    {
        $request = $this->collectors['request'];
        $info = $request->getInfo();
        $params = array_merge($info['get'], $info['post']);

        $this->issue('view');

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
        ob_start(array($this,'deepAnalyze'));        
    }

    public function deepAnalyze($content)
    {
        $foundGuilty = false;
        $charge = [];
        foreach($this->infected as $infected):
            if (in_array($infected['value'], $this->vaguePhrases)) {
                continue;
            }
            if (stripos($content, $infected['value']) !== false) {
                $foundGuilty = true;
                $charge = $infected;
                break;
            }
        endforeach;
        
        if($foundGuilty){
            $code = $this->collectors['code']->collectFromText($content, $charge['value']);
            return $this->sendToJail( $this->parseScore($charge['score']), $charge, $code );
        }
        return $content;
    }

}
