<?php

namespace Shieldfy\Test;

use PHPUnit\Framework\TestCase;
use org\bovigo\vfs\vfsStream;

use Shieldfy\Http\Dispatcher;
use Shieldfy\Http\ApiClient;
use Shieldfy\Config;
use Shieldfy\Session;
use Shieldfy\Collectors\RequestCollector;
use Shieldfy\Collectors\UserCollector;
use Shieldfy\Cache\CacheManager;


class SessionTest extends TestCase
{
    protected $config;
    protected $root;
    protected $api;
    protected $user;
    protected $request;
    protected $cache;

    public function setup()
    {
        $this->config = new Config();
        $this->config['app_key'] = 'testKey';
        $this->config['app_secret'] = 'testSecret';
        $this->config['endpoint'] = 'https://shieldfy.io';

        //set virtual filesystem
        $this->root = vfsStream::setup();
        mkdir($this->root->url().'/tmp/', 0700, true);
        
        $this->cache = (new CacheManager($this->config))->setDriver('file', [
            'path'=> $this->root->url().'/tmp/',
        ]);

        $this->api = $this->getMockBuilder(ApiClient::class)
                    ->disableOriginalConstructor()
                    ->getMock();

        $this->api->method('request')
                ->willReturn(json_decode(
                    json_encode(
                        [
                            'status'=>'success',
                            'sessionId'=>'Ddxfe55',
                            'score'=>5
                        ]
                    )
                ));

        
        $this->request = new RequestCollector([], [], ['HTTP_HOST'=>'example.com','REQUEST_METHOD'=>'get','REQUEST_URI'=>'/hello']);
        $this->user = new UserCollector($this->request);
        $this->dispatcher = new Dispatcher($this->api);

    }

    public function testNewSession()
    {
        $session = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        $this->assertEquals(true,$session->isNewVisit());
    }

    public function testSessionInfo()
    {
        $session = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        $this->assertEquals('Ddxfe55',$session->getId());
        $this->assertEquals('Ddxfe55',$this->user->getSessionId());
        $this->assertEquals(5,$this->user->getScore());
    }
    
    public function testSaveIsNew()
    {
        $session = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        
        $res = $session->save();
        
        $this->assertEquals(-1 , $res);
        $sessionId = $session->getId();
        $userId = $this->user->getId();
        $this->assertEquals('{"id":0,"ip":"0.0.0.0","userAgent":null,"sessionId":"Ddxfe55","score":5}',$this->root->getChild('tmp/'.$userId.'.json')->getContent());
        //response code is false because it called in cli environment
        //see: http://php.net/manual/en/function.http-response-code.php
        $this->assertEquals(
                ['0'=>["method"=>"get","uri"=>"/hello","responseCode"=>false]],
                array_values(json_decode($this->root->getChild('tmp/'.$sessionId.'.json')->getContent(),1))
            );
    }

    public function testExistingSession()
    {
        $session = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        
        $session->save();
        $session2 = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        $this->assertEquals(false, $session2->isNewVisit());
        $this->assertEquals(['0'=>["method"=>"get","uri"=>"/hello","responseCode"=>false]],
                            array_values($session2->getHistory())
        );
    }

    

    public function testSave()
    {
        $session = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        
        $session->save();
        sleep(1);
        $session2 = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        $res = $session2->save();
        $this->assertEquals(false, $session2->isNewVisit());
        $this->assertEquals(-1 , $res);

        $sessionId = $session2->getId();
        
        $userId = $this->user->getId();
        $this->assertEquals('{"id":0,"ip":"0.0.0.0","userAgent":null,"sessionId":"Ddxfe55","score":5}',$this->root->getChild('tmp/'.$userId.'.json')->getContent());
        //response code is false because it called in cli environment
        //see: http://php.net/manual/en/function.http-response-code.php
        $this->assertEquals(
                [
                    '0'=>["method"=>"get","uri"=>"/hello","responseCode"=>false],
                    '1'=>["method"=>"get","uri"=>"/hello","responseCode"=>false]
                ],
                array_values(json_decode($this->root->getChild('tmp/'.$sessionId.'.json')->getContent(),1))
            );
    }

    public function testSaveAndFlush()
    {
        $session = new Session($this->user, 
                               $this->request, 
                               $this->dispatcher, 
                               $this->cache);
        $this->dispatcher->setData(['user'=>'thirduser']);
        $session->markToFlush();
        $res = $session->save();
        $this->assertEquals(1,$res);
        $sessionId = $session->getId();
        $this->assertEquals(
                [],
                array_values(json_decode($this->root->getChild('tmp/'.$sessionId.'.json')->getContent(),1))
            );
    }

}
