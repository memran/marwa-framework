<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Modules\Blog\Listeners;

use Marwa\Framework\Adapters\Event\ModuleLoaded;
use Marwa\Framework\Tests\Fixtures\Modules\Blog\BlogListenerState;

final class BlogLoadedListener
{
    public function __construct(private BlogListenerState $state) {}

    public function handle(ModuleLoaded $event): void
    {
        $this->state->handled = true;
        $this->state->slug = $event->slug;
    }
}
