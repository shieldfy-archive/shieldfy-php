<?php
namespace Shieldfy;
use Shieldfy\Config;
use Shieldfy\Exceptions\Exceptionable;
use Shieldfy\Exceptions\Exceptioner;
use Shieldfy\Exceptions\InstallationException;
use Shieldfy\Http\Dispatcher;
use Shieldfy\Collectors\RequestCollector;

use Composer\Script\Event;
use Composer\Installer\PackageEvent;


class Installer implements  Exceptionable
{
    use Exceptioner;
    /**
     * @var config
     * @var dispatcher
     * @var request
     */
    protected $request;
    protected $dispatcher;
    protected $config;

    /**
     * @param RequestCollector $request 
     * @param Config           $config  
     */
    public function __construct(RequestCollector $request,Dispatcher $dispatcher, Config $config)
    {
        $this->request = $request;
        $this->dispatcher = $dispatcher;
        $this->config = $config;
    }
    /**
     * Run installation
     * 'application_id' => $this->app->id,
            'https'          => $this->data['https'],
            'url'            => $this->data['host'],
            'info'           => json_encode($this->data),
     */
    public function run()
    {

        $response = $this->dispatcher->trigger('install', [
            'host' => $this->request->server['HTTP_HOST'],
            'https' => $this->request->isSecure(),
            'lang' => 'php',
            'sdk_version' => $this->config['version'],
            'server' => isset($this->request->server['SERVER_SOFTWARE']) ? $this->request->server['SERVER_SOFTWARE'] : 'NA',
            'php_version' => PHP_VERSION,
            'sapi_type' => php_sapi_name(),
            'os_info' => php_uname(),
            'disabled_functions' => ini_get('disable_functions') ?: 'NA',
            'loaded_extensions' => implode(',', get_loaded_extensions()),
            'display_errors' => ini_get('display_errors')
        ]);

        if (!$response) {
            $this->throwException(new InstallationException('Unknown error happened', 200));
            return false;
        }
        if ($response->status == 'error') {
            $this->throwException(new InstallationException($response->message, $response->code));
            return false;
        }
        if ($response->status == 'success') {
            $this->save((array)$response->data);
        }
        return true;
    }

    /**
     * Save grapped data
     * @param  array $data
     */
    private function save(array $data = [])
    {

        //if not writable , try to chmod it
        if (!is_writable($this->config['paths']['data'])) {
            @chmod($this->config['paths']['data'], 0755);
            if(!is_writable($this->config['paths']['data'])){
                $this->throwException(new InstallationException('Data folder :'.$this->config['paths']['data'].' Is not writable', 200));
            }
        }
        
        $data_path = $this->config['paths']['data'];
        file_put_contents($data_path.'/installed', time());

        foreach($data['rules'] as $ruleName => $ruleContent):
            $content = base64_decode($ruleContent);
            if($this->isJson($content)){
                file_put_contents($data_path.'/'.$ruleName.'.json',$content);
            }            
        endforeach;

    }



    private function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }



    /**
    * Change mode for writable directories within the installation phase
    * Add shieldfy.json
    * @return void
    * composer config extra.shieldfy.appkey hik && composer config extra.shieldfy.appsecret his ;
    * composer require shieldfy/shieldfy-php
    */
    public static function runCli(Event $event)
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        echo $vendorDir;
        $extra = $event->getComposer()->getPackage()->getExtra();
        print_r($extra);
        // chmod("tmp/", 0777);
        // chmod("tmp/", 0777);
        // chmod("logs/", 0777);
    }


}