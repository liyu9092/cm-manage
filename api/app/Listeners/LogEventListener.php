<?php namespace App\Listeners;

use App\Events;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Route;
use Request;
use App\Log;
use JWTAuth;

class LogEventListener {

	/**
	 * Create the event handler.
	 *
	 * @return void
	 */
	public function __construct()
	{
		//
	}

	/**
	 * Handle the event.
	 *
	 * @param  Events  $event
	 * @return void
	 */
	public function handle(Events $event)
	{
		//
	}


	public function onExport()
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();

		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '导出日志';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}
}
