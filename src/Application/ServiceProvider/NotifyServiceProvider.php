<?php
namespace Marwa\Application\ServiceProvider;

use Marwa\Application\Containers\ServiceProvider;
use Marwa\Application\Notification\Broadcasts\Broadcast;
use Marwa\Application\Notification\Mailer\Mail;
use Marwa\Application\Notification\Notify;
use Marwa\Application\Notification\SMS\SMSClient;
use Marwa\Application\Notification\Voice\VoiceClient;

class NotifyServiceProvider extends ServiceProvider
{

	/**
	 * 
	 * 
	 * @param string $id
	 * @return bool
	 */
	public function provides(string $id): bool
	{
		$services = [
			'notify',
			'mail',
			'sms',
			'voice',
			'broadcast'
		];

		return in_array($id, $services);
	}


	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 */
	public function register(): void
	{
		$this->singleton('notify', Notify::class);
		$this->singleton('mail', Mail::class);
		$this->singleton('sms', SMSClient::class);
		$this->singleton('voice', VoiceClient::class);
		$this->singleton('broadcast', Broadcast::class);
	}
}
