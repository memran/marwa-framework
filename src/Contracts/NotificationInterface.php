<?php

declare(strict_types=1);

namespace Marwa\Framework\Contracts;

interface NotificationInterface
{
    /**
     * @return list<string>
     */
    public function via(?object $notifiable = null): array;

    /**
     * @return array<string, mixed>
     */
    public function toMail(?object $notifiable = null): array;

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(?object $notifiable = null): array;

    /**
     * @return array<string, mixed>
     */
    public function toHttp(?object $notifiable = null): array;

    /**
     * @return array<string, mixed>
     */
    public function toSms(?object $notifiable = null): array;

    /**
     * @return array<string, mixed>
     */
    public function toBroadcast(?object $notifiable = null): array;
}
