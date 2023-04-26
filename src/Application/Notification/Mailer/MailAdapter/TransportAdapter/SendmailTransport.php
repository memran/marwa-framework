<?php


namespace Marwa\Application\Notification\Mailer\MailAdapter\TransportAdapter;
use Exception;
use Marwa\Application\Notification\Mailer\Interfaces\TransportInterface;
use Swift_SendmailTransport;

class SendmailTransport implements TransportInterface
{
    /**
     * @var string
     */
    protected $_command ='/usr/sbin/sendmail -bs';

    /**
     * SendMailTransport constructor.
     * @param string $command
     */
    public function __construct(string $command)
    {
        $this->_command = $command;
    }

    /**
     * @return Swift_SendmailTransport
     * @throws Exception
     */
    public function getTransport()
    {
        if(is_null($this->_command) || empty($this->_command)) {
            throw new Exception('Sendmail command not found ');
        }
        return new Swift_SendmailTransport($this->_command);
    }

}