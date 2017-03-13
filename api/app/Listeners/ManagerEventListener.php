<?php namespace App\Listeners;

use App\Events;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Route;
use Request;
use App\Log;
use JWTAuth;

class ManagerEventListener {

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

	public function onCreate($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$data['object'] = $user->username;
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '创建管理员';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onUpdate($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$data['object'] = $user->username;
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '更新管理员';
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
		$data['operation'] = '导出管理员';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onLogin($user)
	{
		$data['username'] = $user->username;
		$data['roles'] = $user->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '登录系统';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onLogout($user)
	{
		$data['username'] = $user->username;
		$data['roles'] = $user->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '退出系统';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}
}
