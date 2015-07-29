<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Task\Task;
use Notification\Notification;

class CronCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'cron:notifications';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check for new notifications';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	// ------------------------------------------------------------------------
	public function fire()
	{

		// first get all users that want to receive notifications
		$users = User::where('notifications', '=', 1)->get();

		// get all notifications that have not been sent out
		$notifications = Notification::whereNull('sent_at')->get();
		if($notifications->count()==0) 
		{
			$this->info("*** No New Notification ***");
			return;
		}

		$results = [];
		foreach ($notifications as $notice) {
			$this->info("Notification: ".$notice->getTitle()." : ".$notice->event);
			$status = $notice->send();
			$this->info("\t status: ".strbool($status));
		}
		return  $results;
	}

	// ------------------------------------------------------------------------
	protected function getArguments()
	{
		return array(
			// array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	// ------------------------------------------------------------------------
	protected function getOptions()
	{
		return array(
		);
	}

}
