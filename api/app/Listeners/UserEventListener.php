<?php namespace App\Listeners;

use App\Events;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Route;
use Request;
use App\Log;
use JWTAuth;

class UserEventListener {

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
		$data['object'] = $user->mobilephone;
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '创建用户';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onUpdate($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$data['object'] = $user->mobilephone;
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '更新用户';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}	

	public function onDelete($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$data['object'] = $user->mobilephone;
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '删除用户';
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
		$data['operation'] = '导出用户';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onDisable($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '禁用用户';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onEnable($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '启用用户';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onResetCode($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '解除邀请码';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}

	public function onSetCode($user)
	{
    	$operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '绑定邀请码';
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
	}


}
