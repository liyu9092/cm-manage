<?php
namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;

class BookingCalendarEventListener
{
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
    
    
    public function onStatus($log='')
    {
       $operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '客服修改拨打电话状态';
		$data['object'] = $log;
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
    }
    
    public function onModifyDay($log='')
    {
       $operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '客服更改预约时间';
		$data['object'] = $log;
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
    }
    
    public function onExport($log='')
    {
       $operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '导出某日订单预约';
		$data['object'] = $log;
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
    }
    
    public function onModifyLimit($log='')
    {
        $operator = JWTAuth::parseToken()->authenticate();
		$data['username'] = $operator->username;
		$data['roles'] = $operator->roles->toArray();
		$roles = [];
		foreach ($data['roles'] as $key => $value) {
			$roles[] = $value['name'];
		}
		$data['roles'] = implode($roles, ',');
		$data['operation'] = '修改预约上限设置';
		$data['object'] = $log;
		$data['slug'] = Route::currentRouteName();
		$data['ip'] = Request::getClientIp();
		return Log::create($data);
    }
}
