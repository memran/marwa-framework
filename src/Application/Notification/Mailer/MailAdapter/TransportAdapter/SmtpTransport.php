<?php

namespace Marwa\Application\Notification\Mailer\MailAdapter\TransportAdapter;
use Exception;
use Swift_SmtpTransport;
use Marwa\Application\Notification\Mailer\Interfaces\TransportInterface;

class SmtpTransport implements TransportInterface
{
    /**
     * @var Swift_SmtpTransport
     */
    protected $transport;
    /**
     * @var array
     */
    protected $config=[];

    /**
     * SmtpTransport constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config)
    {
        if(empty($config)) {
            throw new Exception('SMTP configuration not found');
        }
        $this->config = $config;
    }

    /**
     * @return Swift_SmtpTransport
     */
    public function getTransport()
    {
        $this->transport = new Swift_SmtpTransport();
        $this->setupTransport();
        return $this->transport;
    }

    /**
     *
     */
    protected function setupTransport()
    {
        $this->setHost();
        $this->setPort();
        $this->setUserName();
        $this->setPassword();
        $this->setEncryption();
    }

    /**
     *
     */
    protected function setHost()
    {
        if(isset($this->config['host'])) {
            $this->transport->setHost($this->config['host']);
        }
    }

    /**
     *
     */
    protected function setPort()
    {
        if(isset($this->config['port'])) {
            $this->transport->setPort($this->config['port']);
        }
    }

    /**
     *
     */
    protected function setUserName()
    {
        if(isset($this->config['username'])) {
            $this->transport->setUsername($this->config['username']);
        }
    }

    /**
     *
     */
    protected function setPassword()
    {
        if(isset($this->config['password'])) {
            $this->transport->setPassword($this->config['password']);
        }
    }

    /**
     *
     */
    protected function setEncryption()
    {
        if(isset($this->config['encryption'])) {
            $this->transport->setEncryption($this->config['encryption']);
        }
    }

}
