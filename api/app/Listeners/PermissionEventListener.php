<?php namespace App\Listeners;

use App\Events;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Route;
use Request;
use App\Log;
use JWTAuth;

class PermissionEventListener {

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

	public function onCreate($permission)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$data['object'] = $permission->title;
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '创建权限';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onUpdate($permission)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$data['object'] = $permission->title;
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '更新权限';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onExport()
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '导出权限';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}
}
