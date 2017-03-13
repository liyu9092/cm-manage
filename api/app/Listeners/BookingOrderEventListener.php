<?php
namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;

class BookingOrderEventListener
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
    
    public function onCreate($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单代下单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onReceive($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单接待";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onChange($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单修改项目信息";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onUpdate($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单修改项目方案";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onGift($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单手动赠送水光针";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onDestroy($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单删除";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onCash($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单收银";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onBill($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单补开发票";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onRelatively($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单补色";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onRelatively2($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单补色(二次)";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onRefund($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "定妆单(特殊)退款";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    protected function getLogInfo()
    {
//         //#@todo for test
//         $data['username'] = "开发人员测试用户";
//         $data['roles'] = "";
//         $data['slug'] = Route::currentRouteName();
//         $data['ip'] = Request::getClientIp();
//         return $data;
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
