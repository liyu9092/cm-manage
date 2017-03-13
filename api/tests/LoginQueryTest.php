<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\RequestLog;

class LoginQueryTest extends TestCase
{

    /**
     * 屏蔽中间件
     */
    use WithoutMiddleware;
    /**
     * 每次执行完之后清理垃圾数据
     */
    use DatabaseTransactions;

    public function testIndex()
    {
        //基本正确性验证
        $this->post('requestLog/index')            
             ->seeJson([
                'result'=>1,
                'current_page'=>1,
             ]);
        $requestLog=RequestLog::first();
        //测试条件筛选
        $this->post('requestLog/index',[
                'mobilephone'=>$requestLog->mobilephone  
                ])            
             ->seeJson(json_decode($requestLog['data'],true));

        //筛选mobilephone,搜索不存在的电话,返回空数据
        $this->post('requestLog/index',['mobilephone'=>str_random(20)])            
             ->seeJson([
                'data'=>[],
             ]);
    }
}
