<?php
namespace Marwa\Application\Notification\Mailer\MailAdapter;

use Marwa\Application\Notification\Mailer\MailMessage;
use Swift_Mailer;
use Marwa\Application\Notification\Mailer\Interfaces\MailerInterface;
use Marwa\Application\Notification\Mailer\TransportFactory;

class SmtpMailer implements MailerInterface
{
    /**
     * @var Swift_Mailer
     */
    protected $mailer;
    /**
     * @var array
     */
    protected $config = [];

    /**
     * SmtpMailer constructor.
     * @param array $config
     * @throws \Exception
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->newMailer();
    }

    /**
     * @return array
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @throws \Exception
     */
    protected function newMailer()
    {
        $this->mailer = new Swift_Mailer($this->getTransportAdapter());
    }

    /**
     * @return \Swift_SendmailTransport|\Swift_SmtpTransport
     * @throws \Exception
     */
    protected function getTransportAdapter()
    {
        return TransportFactory::create('smtp', $this->getConfig())->getTransport();
    }

    /**
     * @param MailMessage $mail
     * @return int
     */
    public function send(MailMessage $mail)
    {
        return $this->mailer->send($mail);
    }
}