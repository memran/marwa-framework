<?php
	
	
	namespace Marwa\Application\Notification\Mailer;
	
	use Exception;
//	use Marwa\Application\Notification\Mailer\MailAdapter\MailgunMailer;
//	use Marwa\Application\Notification\Mailer\MailAdapter\SendgridMailer;
//	use Marwa\Application\Notification\Mailer\MailAdapter\SendmailMailer;
	use Marwa\Application\Notification\Mailer\MailAdapter\SmtpMailer;
	
	
	class MailerFactory {
		
		/**
		 * @param string $mailer
		 * @param $config
		 * @return SmtpMailer
		 * @throws Exception
		 */
		public static function create( string $mailer, array $config )
		{
			switch ( $mailer )
			{
				case 'smtp':
					return new SmtpMailer($config);
//				case 'sendmail':
//					return new SendmailMailer($config);
//				case 'mailgun':
//					return new MailgunMailer($config);
//				case 'sendgrid' :
//					return new SendgridMailer($config);
				default:
					throw new Exception("Mailer not found");
			}
		}
	}