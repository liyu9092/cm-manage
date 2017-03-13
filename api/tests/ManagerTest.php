<?php

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Manager;
use JWTAuth;
use App\Exceptions\ERROR;

class ManagerTest extends TestCase
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
        $this->post('manager/index')            
             ->seeJson([
                'result'=>1,
                'current_page'=>1,
             ]);

        $manager = factory(App\Manager::class)->create(['department_id'=>1,'status'=>2,'city_id'=>2]);
        $roles = DB::table('manager_role')->insert(['role_id'=>20,'manager_id'=>$manager->id]);

        //测试条件筛选
        $this->post('manager/index',[
                'username'=>$manager->username,
                'name'=>$manager->name,
                'department_id'=>1,
                'status'=>2,
                'city_id'=>2,
                'start_at'=>date('Y-m-d',strtotime('-10 days')),
                'end_at'=>date('Y-m-d')
                ])            
             ->seeJson([
                'username'=>$manager->username,
             ]);

        //筛选username,搜索不存在的用户名,返回空数据
        $this->post('manager/index',['username'=>str_random(20)])            
             ->seeJson([
                'data'=>[],
             ]);
    }

    public function testCreate(){
        $this->withoutEvents();
        //创建用户
        $manager = [
            'username'=>str_random(20),
            'password'=>str_random(20)
            ];
        $this->post('manager/create',$manager)            
             ->seeJson([
                'result'=>1,
             ]);
        //判断数据库是否存在此记录
        $this->seeInDatabase('managers', ['username' => $manager['username']]); 

        //判断用户名重复
        $this->post('manager/create',$manager)            
             ->seeJson([
                'result'=>0,
                'code'=>ERROR::USER_EXIST,
             ]);
    }

    public function testShow(){
        $manager = Manager::first();
        $id = $manager->id;
        $this->get("manager/show/$id")
             ->seeJson($manager->toArray());

        $id = 999999;
        $this->get("manager/show/$id") 
             ->seeJson([
                    'result'=>0,
                    'code'=>ERROR::USER_NOT_FOUND,
                ]); 
    }


    public function testUpdate(){
        $this->withoutEvents();
        $manager = Manager::first();
        $id = $manager->id;
        $data = [
            'username'=>str_random(20),
            'name'=>str_random(10),
            'tel'=>'13909090909',
            'department_id'=>2,
            'position_id'=>1,
            'city_id'=>3,
            'email'=>'soothion@sina.com'
        ];
        $this->post("manager/update/$id",$data)
             ->seeJson([
                    'result'=>1
                ]);

        $this->get("manager/show/$id")
             ->seeJson($data);     
    }

}
