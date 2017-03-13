<?php

namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;


class BannerEventListener {
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

    public function onCreate($log = '') {
        $operator = JWTAuth::parseToken()->authenticate();
        $data['username'] = $operator->username;
        $data['roles'] = $operator->roles->toArray();
        $roles = [];
        foreach ($data['roles'] as $key => $value) {
            $roles[] = $value['name'];
        }
        $data['roles'] = implode($roles, ',');
        $data['operation'] = '添加banner';
        $data['object'] = $log;
        $data['slug'] = Route::currentRouteName();
        $data['ip'] = Request::getClientIp();
        return Log::create($data);
    }

    public function onEdit($log = '') {
        $operator = JWTAuth::parseToken()->authenticate();
        Log::info("onEdit operator is".$operator);
        $data['username'] = $operator->username;
        $data['roles'] = $operator->roles->toArray();
        $roles = [];
        foreach ($data['roles'] as $key => $value) {
            $roles[] = $value['name'];
        }
        $data['roles'] = implode($roles, ',');
        $data['operation'] = '修改banner';
        $data['object'] = $log;
        $data['slug'] = Route::currentRouteName();
        $data['ip'] = Request::getClientIp();
        return Log::create($data);
    }

    public function onDestroy($log = '') {
        $operator = JWTAuth::parseToken()->authenticate();
        $data['username'] = $operator->username;
        $data['roles'] = $operator->roles->toArray();
        $roles = [];
        foreach ($data['roles'] as $key => $value) {
            $roles[] = $value['name'];
        }
        $data['roles'] = implode($roles, ',');
        $data['operation'] = '删除banner';
        $data['object'] = $log;
        $data['slug'] = Route::currentRouteName();
        $data['ip'] = Request::getClientIp();
        return Log::create($data);
    }

}
