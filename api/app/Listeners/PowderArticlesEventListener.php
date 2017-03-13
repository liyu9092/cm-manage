<?php namespace App\Listeners;

use App\Events;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldBeQueued;
use Route;
use Request;
use App\Log;
use JWTAuth;

class PowderArticlesEventListener {

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
        //创建赠送券活动
	public function onCreate($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '新增赠送活动';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //查看活动详情
        public function onShowArticleDetail($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '查看活动详情';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //关闭活动验证
        public function onCloseArticleVerify($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '活动验证开关';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //关闭活动
        public function onCloseArticle($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '活动开关';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //查看兑换券详情
        public function onShowArticleTicketInfo($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '查看兑换券详情';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //活动券导出
        public function onExportArticleTicket($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '活动券导出';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //查询活动券详情
        public function onShowTicketInfo($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '查询活动券详情';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //验证活动券
        public function onUseArticleTicket($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '消费活动券';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        //编辑定妆赠送消费使用信息
        public function onEditPresentUseInfo($log ='')
	{
            $operator = JWTAuth::parseToken()->authenticate();
            $data['username'] = $operator->username;
            $data['roles'] = $operator->roles->toArray();
            $roles = [];
            foreach ($data['roles'] as $key => $value) {
                    $roles[] = $value['name'];
            }
            $data['roles'] = implode($roles, ',');
            $data['operation'] = '编辑定妆赠送消费使用信息';
            $data['object'] = $log;
            $data['slug'] = Route::currentRouteName();
            $data['ip'] = Request::getClientIp();
            return Log::create($data);
	}
        
	
}
