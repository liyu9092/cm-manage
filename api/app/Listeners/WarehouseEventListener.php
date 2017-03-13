<?php
namespace App\Listeners;

use App\Events;
use Route;
use Request;
use App\Log;
use JWTAuth;

class WarehouseEventListener
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
    
    
    public function onPuton($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "项目上架";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onDestroy($code)
    {
        $data = $this->getLogInfo();
        $data['operation'] = "项目删除";
        $data['object'] = $code;
        return Log::create($data);
    }
    
    public function onImport($code='')
    {
        $data = $this->getLogInfo();
        $data['operation'] = "导入项目";
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
