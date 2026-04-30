<?php

declare(strict_types=1);

namespace Marwa\Framework\Tests\Fixtures\Modules\Blog;

final class BlogListenerState
{
    public bool $handled = false;

    public ?string $slug = null;
}
