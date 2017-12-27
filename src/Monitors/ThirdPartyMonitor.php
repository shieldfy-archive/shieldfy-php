<?php
namespace Shieldfy\Monitors;

use Shieldfy\Jury\Judge;

class ThirdPartyMonitor extends MonitorBase
{
    use Judge;

    protected $name = "thirdparty";
    protected $infected = [];

    /**
     * run the monitor
     */
    public function run()
    {
    	$composerLock = $this->config['paths']['base'].'/composer.lock';
        if(!file_exists($composerLock)) return;
        if(!is_readable($composerLock)) return;

        $hashComposerLock = $this->config['paths']['data'].'/composer.lock.hash';
        $hash1 = sha1_file($composerLock);

        if(file_exists($hashComposerLock)){            
            $hash2 = file_get_contents($hashComposerLock);
            if($hash1 === $hash2) return; //nothing change
        }

        //need sync
        $content = file_get_contents($composerLock);

        $res = $this->dispatcher->trigger('security/scan',[
            'file' => 'composer.lock',
            'content' => $content
        ]);

        if($res){
            file_put_contents($hashComposerLock, $hash1);
        }

    }


}