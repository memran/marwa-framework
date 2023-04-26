<?php


namespace Marwa\Application\Notification\Mailer\Interfaces;

interface MailerInterface
{
    public function send($message);
}