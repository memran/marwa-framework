<?php

namespace Marwa\App\Events;


final class ListenerPriority
{

    /**
     * High priority.
     *
     * @const int
     */
    public const HIGH = 100;

    /**
     * Normal priority.
     *
     * @const int
     */
    public const NORMAL = 0;

    /**
     * Low priority.
     *
     * @const int
     */
    public const LOW = -100;
}
