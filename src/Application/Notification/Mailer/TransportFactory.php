<?php


namespace Marwa\Application\Notification\Mailer;
use Exception;
use Marwa\Application\Notification\Mailer\MailAdapter\TransportAdapter\SendmailTransport;
use Marwa\Application\Notification\Mailer\MailAdapter\TransportAdapter\SmtpTransport;

class TransportFactory
{
    /**
     * @param string $type
     * @param $config
     * @return SendmailTransport|SmtpTransport
     * @throws Exception
     */
    public static function create(string $transport,$config)
    {
        switch ($transport){
            case 'smtp':
                return new SmtpTransport($config);
            case 'sendmail':
                return new SendmailTransport($config);
            default:
                throw new Exception("Transport not found");
        }
    }

}