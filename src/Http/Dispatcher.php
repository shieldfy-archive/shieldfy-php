<?php
namespace Shieldfy\Http;

use Shieldfy\Http\ApiClient;
use Shieldfy\Exceptions\Exceptioner;
use Shieldfy\Exceptions\Exceptionable;

class Dispatcher implements Exceptionable
{
    use Exceptioner;

    /**
     * @var supported events list
    */
    private $events = ['update', 'session', 'activity', 'exception'];

    private $data = [];

    public $apiClient = null;

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function flush()
    {
        return $this->trigger('activity',$this->data);
    }

    /**
     * trigger event with data
     *
     * @param string $event
     * @param array  $data
     *
     * @return result of the event | false
     */
    public function trigger($event, $data = [])
    {
        if (!in_array($event, $this->events)) {
            $this->throwException(new EventNotExistsException('Event '.$event.' not loaded', 302));
            return false; //return to avoid extra execution if errors is off
        }
        $data = json_encode($data);

        return $this->apiClient->request('/'.$event, $data);
    }

}
