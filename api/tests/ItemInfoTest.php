<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Item;
use DB;
use App\Exceptions\ERROR;

class ItemInfoTest extends TestCase
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
          $this->post('itemInfo/index')            
             ->seeJson([
                'result'=>1
             ]);
    }
    
    public  function  tesrCreate(){
        $item=Item::first();
        $item['itemname']=  str_random(20);
        $this->post('itemInfo/create',  json_decode($item, true))            
             ->seeJson([
                'result'=>1
             ]);
        //判断是否添加成功
         $this->seeInDatabase('salon_item', ['itemname' => $item['itemname']]); 
    }
    
     public  function  tesrUpdate(){
        $item=Item::first();
        $item['itemname']=  str_random(20);
        $this->post("itemInfo/update/$item->itemid",  json_decode($item, true))            
             ->seeJson([
                'result'=>1
             ]);
        //判断是否修改成功
         $this->seeInDatabase('salon_item', ['itemname' => $item['itemname']]); 
     }
     
     
     public function testGetItemByTypeid(){
           $item=Item::first();
           $token['typeid']=$item['typeid'];
           $token['salonid']=$item['salonid'];
           $this->post("itemInfo/getItems",$token)            
             ->seeJson([
                'result'=>1
             ]);
           
            //尝试破坏
           $token['salonid']='';
           $this->post("itemInfo/getItems",$token)            
             ->seeJson([
                'result'=>0
             ]);
     }
     
     public function testGetAddedService(){
          $item=Item::first();
           $token['typeid']=$item['typeid'];
           $token['salonid']=$item['salonid'];
           $this->post("itemInfo/getAddedService",$token)            
             ->seeJson([
                'result'=>1
             ]);
           
           //尝试破坏
           $token['salonid']='';
           $this->post("itemInfo/getAddedService",$token)            
             ->seeJson([
                'result'=>0
             ]);
     }   
}
