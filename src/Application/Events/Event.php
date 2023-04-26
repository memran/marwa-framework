<?php declare(strict_types=1);
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */
namespace Marwa\Application\Events;
use League\Event\Emitter;
use League\Event\ListenerInterface;
use Marwa\Application\Events\EventServiceInterface;

class Event implements EventServiceInterface
{
      /**
       * [protected description]
       *
       * @var League\Event\Emitter
       */
      protected $emitter = null;

      /**
       * [__construct description]
       */
    public function __construct()
    {
        //loading event module
        $this->createEmitter();

        //load the listener
        $this->registerAllEventListener();
    }

      /**
       * [attachListener description]
       *
       * @param  string            $name     [description]
       * @param  ListenerInterface $listener [description]
       * @return [type]                      [description]
       */
    public function attachListener(string $name,$listener) : void
    {
        if(is_null($this->emitter)) {
            $this->createEmitter();
        }
        $this->emitter->addListener($name, $listener);
    }
      /**
       * [loadModule description]
       *
       * @return [type] [description]
       */
    public function createEmitter()
    {
        $this->emitter = new Emitter();
    }

      /**
       * [add description]
       *
       * @param string $name     [description]
       * @param [type] $callback [description]
       */
    public function add(string $name,$callback)
    {
        $this->emitter->addListener($name, $callback);
    }

      /**
       * [fire description]
       *
       * @param  string $name   [description]
       * @param  [type] $params [description]
       * @return [type]         [description]
       */
    public function fire(string $name, $params=null)
    {
         return $this->emitter->emit($name, $params);
    }

    /**
     * [fireBatch description]
     *
     * @param  array $events [description]
     * @return [type]         [description]
     */
    public function fireBatch(array $events)
    {
        return $this->emitter->emitBatch($events);
    }

      /**
       * [registerAllEventListener description]
       *
       * @return [type] [description]
       */
    protected function registerAllEventListener() : void
    {
        $listeners = app('config')->load('events.php');

        if(!empty($listeners)) {
            foreach ($listeners as $event => $listener) {
                //add listener
                $this->attachListener($event, $listener);
            }
        }
    }

}
