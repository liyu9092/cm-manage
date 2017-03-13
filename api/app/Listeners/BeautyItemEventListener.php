<?php namespace App\Listeners;

use App\Events;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Route;
use Request;
use App\Log;
use JWTAuth;

class BeautyItemEventListener {

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
	

	
	public function onUpdate($log  = '')
	{
		$data = $this->getLogInfo();
        $data['operation'] = "韩式半永久项目";
        $data['object'] = $log;
        return Log::create($data);
	}
	

	public function onUpdateFashion($log = '')
	{
		$data = $this->getLogInfo();
        $data['operation'] = "韩式快时尚项目";
        $data['object'] = $log;
        return Log::create($data);
	}
	
    protected function getLogInfo()
    {
        $operator = JWTAuth::parseToken()->authenticate();
        $data['username'] = $operator->username;
        $data['roles'] = $operator->roles->toArray();
        $roles = [];
        foreach ($data['roles'] as $key => $value) {
            $roles[] = $value['name'];
        }
        $data['roles'] = implode($roles, ',');
        $data['slug'] = Route::currentRouteName();
        $data['ip'] = Request::getClientIp();
        return $data;
    }
	

}
