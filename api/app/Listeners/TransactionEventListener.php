<?php
namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;

class TransactionEventListener
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
    
    public function onOrderExport()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导出订单";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onTicketExport()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导出臭美券";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onRefundExport()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导出退款单";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onAccept($info)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "退款通过";
        $data['object'] = $info;
        return Log::create($data);
    }
    
    public function onReject($info)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "退款拒绝";
        $data['object'] = $info;
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
