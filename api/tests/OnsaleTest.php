<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Item;

class OnsaleTest extends TestCase
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
          $this->post('onsale/index')            
             ->seeJson([
                'result'=>1,
                'current_page'=>1,
             ]);

    }
    public function testShow()
    {
        $item = Item::select('itemid')->first();
        $id =$item->itemid;
        $this->post("onsale/show/$id")            
             ->seeJson([
                'result'=>1
             ]);
        
        //尝试破坏测试
        $id =9999999;
        $this->post("onsale/show/$id")            
             ->seeJson([
                'result'=>0
             ]);
    }
    
}
