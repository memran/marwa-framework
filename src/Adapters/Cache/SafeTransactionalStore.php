<?php

declare(strict_types=1);

namespace Marwa\Framework\Adapters\Cache;

use MatthiasMullie\Scrapbook\Buffered\TransactionalStore;
use MatthiasMullie\Scrapbook\Buffered\Utils\Buffer;
use MatthiasMullie\Scrapbook\Buffered\Utils\Transaction;

final class SafeTransactionalStore extends TransactionalStore
{
    public function __construct(
        \MatthiasMullie\Scrapbook\KeyValueStore $cache,
        private int $bufferLimit
    ) {
        parent::__construct($cache);
    }

    public function begin(): void
    {
        $buffer = new Buffer($this->bufferLimit);
        $cache = end($this->transactions);
        $this->transactions[] = new Transaction($buffer, $cache);
    }
}
