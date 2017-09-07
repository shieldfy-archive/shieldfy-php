<?php
namespace Shieldfy\Test;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Shieldfy\Config;
use Shieldfy\Cache\CacheManager;
use Shieldfy\Exceptions\CacheDriverNotExistsException;

class CacheTest extends TestCase
{
    protected $root;
    protected $config;
    public function setUp()
    {
        //set virtual filesystem
        $this->root = vfsStream::setup();
        mkdir($this->root->url().'/tmp/', 0700, true);
        $config = new Config();
        $config['debug'] = true;
        $this->config = $config;
    }
    public function testNotExistDriverException()
    {
        $this->expectException(CacheDriverNotExistsException::class);
        $cache = new CacheManager($this->config);
        $cache->setDriver('notExists');
    }
    public function testInstanceCreationAndRetrival()
    {
        $cache = (new CacheManager($this->config))->setDriver('file', [
            'path'=> $this->root->url().'/tmp/',
        ]);
        $this->assertInternalType('object', $cache);
    }
    public function testSet()
    {
        $cache = (new CacheManager($this->config))->setDriver('file', [
            'path'=> $this->root->url().'/tmp/',
        ]);
        $cache->set('foo', ['bar']);
        $this->assertEquals('["bar"]', $this->root->getChild('tmp/foo.json')->getContent());
    }
    public function testGet()
    {
        $cache = (new CacheManager($this->config))->setDriver('file', [
            'path'=> $this->root->url().'/tmp/',
        ]);
        $cache->set('foo', ['bar']);
        $res = $cache->get('foo');
        $this->assertEquals($res, ['bar']);
        $this->assertFalse($cache->get('bar'));
    }
    public function testHas()
    {
        $cache = (new CacheManager($this->config))->setDriver('file', [
            'path'=> $this->root->url().'/tmp/',
        ]);
        $cache->set('foo', ['bar']);
        $this->assertTrue($cache->has('foo'));
        $this->assertFalse($cache->has('bar'));
    }

    // public function testClean()
    // {
    //     $path = $this->root->url().'/tmp/';
    //     $cache = (new CacheManager($this->config))->setDriver('file', [
    //         'path'=> $path,
    //     ]);
    //     //create files
    //     file_put_contents($path.'1.json', 'Some Contents');
    //     file_put_contents($path.'2.json', 'Some Contents');
    //     file_put_contents($path.'3.json', 'Some Contents');
    //     sleep(3);
    //     file_put_contents($path.'4.json', 'Some Contents');
    //     $cache->clean(1); // clean 1 second old
    //     $this->assertTrue(file_exists($path.'4.json'));
    //     $this->assertFalse(file_exists($path.'1.json'));
    //     $this->assertFalse(file_exists($path.'2.json'));
    //     $this->assertFalse(file_exists($path.'3.json'));
    // }
}
