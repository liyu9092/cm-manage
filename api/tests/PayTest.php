<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\PayManage;
use JWTAuth;
use App\Exceptions\ERROR;

class PayTest extends TestCase
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
        $this->post('pay_manage/index')            
             ->seeJson([
                'result'=>1,
                'current_page'=>1,
             ]);
         //测试条件筛选
        $this->post('pay_manage/index',[
                'state'=>1,
                'pay_time_min'=>date('Y-m-d',strtotime('-100 days')),
                'pay_time_max'=>date('Y-m-d')
                ])            
             ->seeJson([
                 'result'=>1,
                'current_page'=>1,
             ]);   
        
        
        $this->post('pay_manage/index',[
                'state'=>99999999,
                'keyword'=>  str_random(20),
                'pay_time_min'=>date('Y-m-d'),
                'pay_time_max'=>date('Y-m-d')
                ])            
             ->seeJson([
                 'result'=>1,
                'current_page'=>1,
             ]);   
        
        
    }
    
    
    public function testStore(){
     $token=[
         'type'=>1,
         'salon_id'=>84,
         'merchant_id'=>556,
         'money'=>10000,
         'pay_type'=>4,
         'require_day'=>date('Y-m-d'),
         'cycle'=>3,
         'cycle_day'=>2018,
         'cycle_money'=>1000
     ]; 
       $this->post("pay_manage/create",$token)
             ->seeJson([]);
     
    }
    
    public function  testShow(){
        $query = PayManage::first();
        $id=$query->id;
        $this->get("pay_manage/show/$id")
             ->seeJson(['result'=>1]);
    }

}
