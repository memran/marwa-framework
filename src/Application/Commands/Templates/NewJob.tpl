<?php

namespace App\Jobs;
use Marwa\Application\Jobs\AbstractListener;

class {{CLASSNAME}} extends AbstractListener
{
    public function handle(array $params=[]) : void
    {
        logger("it works from job handlers",$params);
    }
}
