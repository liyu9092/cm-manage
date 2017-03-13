<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\SalonItem;
use JWTAuth;
use App\Exceptions\ERROR;

class WarehouseTest extends TestCase
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
         $this->post('warehouse/index')            
             ->seeJson([
                'result'=>1,
                'current_page'=>1
             ]);

        $warehouse = SalonItem::select(['itemname','salonid','itemid'])->first();
        //测试条件筛选
        $this->post('warehouse/index',[
                'itemname'=>$warehouse->itemname
                ])            
             ->seeJson([
                'result'=>1,
                'current_page'=>1
             ]);

        //筛选项目名称,搜索不存在的用户名,返回空数据
        $this->post('warehouse/index',['itemname'=>str_random(20)])            
             ->seeJson([
                'data'=>[]
             ]);
    }
  
    public function testDestroy(){
         $salonItem=SalonItem::first();
         $token['ids']=$salonItem['itemid'];
           $this->post("warehouse/destroy",$token)            
             ->seeJson([
                'result'=>0      //因为成功返回为空
             ]);
           
          //判断数据库是否存在此记录
        $this->seeInDatabase('salon_item', ['status' =>3]); 
    }
    
    public function  testShow(){
         $salonItem=SalonItem::first();
         $id=$salonItem->itemid;
         $this->post("warehouse/show/$id")            
             ->seeJson([
                'result'=>1
             ]);
         
         //id不存在测试
          $this->post("warehouse/show/999999")            
             ->seeJson([
                'result'=>0
             ]);   
    }       
}
