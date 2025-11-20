<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters;

use Marwa\DebugBar\DebugBar;

class DebugbarAdapter
{
    protected DebugBar $bar;
    protected array $collectors;
    public function __construct(array $collectors)
    {
        $this->bar = new DebugBar(true);
        $this->collectors = $collectors;
    }

    public function addCollector(string $collector): self
    {
        $this->bar->collectors()->register($collector);
        return $this;
    }
    /**
     * enable basic debugbar collector
     */
    public function registerCollectors()
    {
        foreach ($this->collectors as $collector) {
            $this->addCollector($collector);
        }
    }
    /**
     * getDebugger
     */
    public function getDebugger(): DebugBar
    {
        return $this->bar;
    }
}
