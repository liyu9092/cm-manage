<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\ImageStyle;

class ImageStyleTest extends TestCase
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
         $this->post('ImageStyle/index')            
             ->seeJson([
                'result'=>1,
                'current_page'=>1,
             ]);

        $imageStyle = ImageStyle::first();
        //测试条件筛选
        $this->post('ImageStyle/index',[
                'length'=>$imageStyle->length,
                'curl'=>$imageStyle->curl,
                'color'=>$imageStyle->color,
                'style'=>$imageStyle->style
                ])            
             ->seeJson(json_decode($imageStyle['data'],true));

        //筛选，搜索不存在的发长,返回空数据
        $this->post('ImageStyle/index',['length'=>str_random(20)])            
             ->seeJson([
                'data'=>[],
             ]);
    }
    
    public function  testCreate(){
         $query=[];
         $stylist = ImageStyle::first();
         if($stylist){
             $query['style'] =$stylist->style;
             $query['length'] =$stylist->length;
             $query['curl'] =$stylist->curl;
             $query['color'] =$stylist->color;
             $query['status'] =$stylist->status;
             $image =  json_decode($stylist['img'],true);
             $query['original']=  $image['original'];
             $query['thumb']=  $image['thumb'];
         }

          $this->post("ImageStyle/create",$query)
             ->seeJson( ['result'=>1]);
                    
    }
    
    public function testUpdate(){
         $stylist = ImageStyle::first();
         if($stylist){
             $image =  json_decode($stylist['img'],true);
             $stylist->original= str_random(20);
             $stylist->thumb=  $image['thumb'];  
         }
            $this->post("ImageStyle/update/$stylist->id",  json_decode($stylist,true))
             ->seeJson( ['result'=>1]);
            
            //是否修改成功
             $this->get("ImageStyle/show/$stylist->id")
             ->seeJson(json_decode($stylist['data']['original'],true));
         
    }
    
    public function  testDestroy(){
           $stylist = ImageStyle::orderBy("id", "DESC")->first();
           $id = $stylist->id;
           $this->post("ImageStyle/destroy/$id")
             ->seeJson( ['result'=>1]);
           
           //判断是否删除成功，删除成功status=2
            $this->get("ImageStyle/show/$id")
             ->seeJson(json_decode($stylist['data']['status'],true));
            
           //尝试破坏
           $id = 999999;
           $this->get("ImageStyle/destroy/$id")
             ->seeJson( ['result'=>0]);
    }
    
  
    public function testShow(){
        $stylist = ImageStyle::first();
        $id = $stylist->id;
        $this->get("ImageStyle/show/$id")
             ->seeJson(json_decode($stylist['data'],true));
         //尝试破坏
        $id = 999999;
        $this->get("ImageStyle/show/$id") 
             ->seeJson([ 'result'=>0 ]); 
    }
    
}
