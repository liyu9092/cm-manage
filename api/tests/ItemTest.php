<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Item;
use DB;
use App\Exceptions\ERROR;

class ItemTest extends TestCase
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
        $this->post('item/index')            
             ->seeJson([
                'result'=>1,
                'current_page'=>1,
             ]);
        
        //筛选店铺名称,搜索不存在的店铺名称,返回空数据
        $this->post('item/index',['itemname'=>str_random(20)])            
             ->seeJson([
                'data'=>[],
             ]);
    }
    
        public function testShow(){
            $item = Item::first();
            $id = $item->itemid;
            $this->get("item/show/$id")
                 ->seeJson(['result'=>1]);
            
            //测试
            $id = 999999;
            $this->get("item/show/$id") 
                 ->seeJson([
                        'result'=>0
                ]); 
        }

}
