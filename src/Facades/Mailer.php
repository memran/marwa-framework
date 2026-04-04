<?php

declare(strict_types=1);

namespace Marwa\Framework\Facades;

use Marwa\Framework\Contracts\MailerInterface;

final class Mailer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return MailerInterface::class;
    }
}
