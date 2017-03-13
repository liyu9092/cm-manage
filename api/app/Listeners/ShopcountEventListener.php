<?php
namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;

class ShopcountEventListener
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
    
    public function onExport()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导出转付单";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onDelegateExport()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导出代收单";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onBalanceExport()
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导出商户往来余额";
        $data['object'] = "";
        return Log::create($data);
    }
    
    public function onCreate($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "预览转付单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onStore($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "新建转付单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onUpdate($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "修改转付单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onDestroy($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "删除转付单";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onCountOrder($info)
    {        
        $data = [];
        $data['username'] = "系统结算";
        $data['roles'] = "";
        $data['slug'] = Route::currentRouteName();
        $data['ip'] = Request::getClientIp();
        $data['operation'] = isset($info['operation'])?$info['operation']:"";
        $data['object'] = isset($info['object'])?$info['object']:"";
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
