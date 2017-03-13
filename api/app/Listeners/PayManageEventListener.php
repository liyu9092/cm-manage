<?php
namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;

class PayManageEventListener
{
    /**
     * Create the event listener.
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
    
    public function onExport()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导出付款单";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onStore($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "新建付款单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onUpdate($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "修改付款单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onDestroy($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "删除付款单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onCheck($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "审批付款单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onConfirm($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "确认付款单";
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
