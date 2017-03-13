<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Works;
use App\Stylist;
use App\Exceptions\ERROR;

class WorksTest extends TestCase
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
        $Stylist=Stylist::first();
        $id=$Stylist->stylistId;
             //基本正确性验证
        $this->get("works/index/$id")            
             ->seeJson([ 'result'=>1]);

    }
    
     public function testCreate(){
        $this->withoutEvents();
        //创建作品works/create
        $Stylist=Stylist::first();
        $id=$Stylist->stylistId;
        $works = [
            'stylistId'=>$id,
            'description'=>str_random(20),
            'img'=>str_random(200)
            ];
        $this->post('works/create',$works)            
           ->seeJson([
              'result'=>1
           ]);
         //判断数据库是否存在此记录
        $this->seeInDatabase('hairstylist_works', ['description' => $works['description']]); 
  
     }
     
    public function testUpdate(){
        $this->withoutEvents();
        $Stylist=Works::orderBy("recId", "DESC")->first();
        $id=$Stylist->recId;
        $data = [
             'img'=>str_random(100)
        ];
        $this->post("works/update/$id",$data)
             ->seeJson([
                    'result'=>1
                ]);
        
        //判断是否修改成功
         $this->post("works/show/$id")
             ->seeJson(json_decode($Stylist['data'],true));

    }
      
    public function testDel(){
        $Stylist=Works::orderBy("recId", "DESC")->first();
        $id=$Stylist->recId;
        $works['img']=str_random(100);
        $this->get("works/del/$id",$works)            
             ->seeJson([ 'result'=>1]); 
    }
    
     public function testDel_list(){
         //添加一条数据，给于删除
        $Stylist=Stylist::first();
        $id=$Stylist->stylistId;
        $works = [
            'stylistId'=>$id,
            'description'=>str_random(20),
            'img'=>str_random(200)
            ];
        $this->post('works/create',$works)            
           ->seeJson([
              'result'=>1
           ]);
         //判断数据库是否存在此记录
        $this->seeInDatabase('hairstylist_works', ['description' => $works['description']]);    
        
        
        $works2=Works::orderBy("recId", "DESC")->first();
        $id2=$works2->recId;
        $this->get("works/del_list/$id2")            
             ->seeJson([ 'result'=>1]);
        
        //判断是否成功删除
          $this->get("works/show/$id2") 
                    ->seeJson([ 'result'=>0]);
          
        //recId不存在
        $id=999999;
        $this->get("works/del_list/$id")            
             ->seeJson([ 'result'=>0]);
    }
    
    
}