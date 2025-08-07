<?php declare(strict_types=1);

interface ShouldQueueEmail
{
    public function build(Mail $mail): Mail;
}