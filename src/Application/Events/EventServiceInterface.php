<?php declare(strict_types=1);
/**
 * @author    Mohammad Emran <memran.dhk@gmail.com>
 * @copyright 2018
 *
 * @see https://www.github.com/memran
 * @see http://www.memran.me
 */
namespace Marwa\Application\Events;
use League\Event\ListenerInterface;

interface EventServiceInterface
{

    /**
     * [attachListener description]
     *
     * @param  string            $name     [description]
     * @param  ListenerInterface $listener [description]
     * @return [type]                      [description]
     */
    public function attachListener(string $name,ListenerInterface $listener);
    /**
     * [fire description]
     *
     * @param  string $name   [description]
     * @param  [type] $params [description]
     * @return [type]         [description]
     */
    public function fire(string $name,$params);

    /**
     * [fireBatch description]
     *
     * @param  array $events [description]
     * @return [type]         [description]
     */
    public function fireBatch(array $events);

}
