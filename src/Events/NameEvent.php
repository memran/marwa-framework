<?php

namespace Marwa\App\Events;

use League\Event\HasEventName;

class NameEvent implements HasEventName
{

    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function eventName(): string
    {
        return $this->name;
    }
}
