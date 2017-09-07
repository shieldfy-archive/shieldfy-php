<?php
namespace Shieldfy;

use Shieldfy\Config;
use Shieldfy\Exceptions\Exceptionable;
use Shieldfy\Exceptions\Exceptioner;
use Shieldfy\Collectors\UserCollector;
use Shieldfy\Collectors\RequestCollector;
use Shieldfy\Cache\CacheInterface;
use Shieldfy\Http\Dispatcher;

class Session implements Exceptionable
{

    use Exceptioner;

    protected $isNew = false;
    protected $needToFlush = false;
    protected $user;
    protected $request;
    protected $dispatcher;
    protected $cache;
    protected $sessionId;
    protected $history = [];
    /**
     * Start new session
     * @param UserCollector    $user
     * @param RequestCollector $request
     * @param Dispatcher           $dispatcher
     * @param CacheInterface   $cache
     */
    public function __construct(UserCollector $user, 
                                RequestCollector $request, 
                                Dispatcher $dispatcher, 
                                CacheInterface $cache)
    {
        $this->dispatcher = $dispatcher;
        $this->user = $user;
        $this->request = $request;
        $this->cache = $cache;

        if (!$cache->has($user->getId())) {
            $this->loadNewUser();
            return;
        }
        $this->loadExistingUser();
    }

    /**
     * Session not found , lets load new user
     */
    public function loadNewUser()
    {
        $this->isNew = true;
        $response = $this->dispatcher->trigger('session', [
            'host'=>$this->request->getHost(),
            'user'=>$this->user->getInfo()
        ]);
        if ($response && $response->status == 'success') {
            $this->sessionId = $response->sessionId;
            $this->user->setSessionId($response->sessionId);
            $this->user->setScore($response->score);
        }
    }

    /**
     * Session found , load existing user
     */
    public function loadExistingUser()
    {
        $user = $this->cache->get($this->user->getId());
        $this->sessionId = $user['sessionId'];
        $this->user->setSessionId($user['sessionId']);
        $this->user->setScore($user['score']);
        $this->history = $this->cache->get($this->user->getSessionId());
    }

    public function getId()
    {
        return $this->sessionId;
    }

    /**
     * Is new visit
     * @return boolean
     */
    public function isNewVisit()
    {
        return $this->isNew;
    }

    public function save()
    {
        if ($this->isNew) {
            //save the user session
            $this->cache->set($this->user->getId(), $this->user->getInfo());
        }

        if ($this->needToFlush) {
            // there is need to flush the data to the server
            // data is already waiting at the dispatcher
            $this->dispatcher->flush();
            $this->history = [];
            $this->cache->set($this->user->getSessionId(), $this->history);
            return 1;
        }

        /* prepare history steps */
        $history = $this->request->getShortInfo();
        $history['responseCode'] = http_response_code();
        $this->history[time()] = $history;
        $this->cache->set($this->user->getSessionId(), $this->history);
        return -1;
    }

    public function markToFlush()
    {
        $this->needToFlush = true;
    }

    /**
     * Retrive session history
     */
    public function getHistory()
    {
        return $this->history;
    }

}
