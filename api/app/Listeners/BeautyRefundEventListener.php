<?php
namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;

class BeautyRefundEventListener
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
    
    public function onShow()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "查看退款单";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onReject()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单拒绝退款";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onAccept()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单确认退款";
        $data['object'] = "";
        return Log::create($data);
    }
    
    
    protected function getLogInfo()
    {
//                 //#@todo for test
//                 $data['username'] = "开发人员测试用户";
//                 $data['roles'] = "";
//                 $data['slug'] = Route::currentRouteName();
//                 $data['ip'] = Request::getClientIp();
//                 return $data;
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
