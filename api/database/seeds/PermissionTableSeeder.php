<?php
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Permission;
class PermissionTableSeeder extends Seeder {

    public function run()
    {

        $system = Permission::create(['title' => '系统管理','slug'=>'','status'=>'1','description'=>'']);

        $user = Permission::create(['inherit_id'=>$system->id,'title' => '用户管理','slug'=>'user.index','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$user->id,'title' => '查看用户','slug'=>'user.show','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$user->id,'title' => '更新用户','slug'=>'user.update','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$user->id,'title' => '新增用户','slug'=>'user.create','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$user->id,'title' => '导出用户','slug'=>'user.export','status'=>'1','description'=>'']);

        $role = Permission::create(['inherit_id'=>$system->id,'title' => '角色管理','slug'=>'role.index','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$role->id,'title' => '导出角色','slug'=>'role.export','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$role->id,'title' => '新增角色','slug'=>'role.create','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$role->id,'title' => '查看角色','slug'=>'role.show','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$role->id,'title' => '修改角色','slug'=>'role.update','status'=>'1','description'=>'']);


        $permission = Permission::create(['inherit_id'=>$system->id,'title' => '权限管理','slug'=>'permission.index','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$permission->id,'title' => '导出权限','slug'=>'permission.export','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$permission->id,'title' => '查看权限','slug'=>'permission.show','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$permission->id,'title' => '修改权限','slug'=>'permission.update','status'=>'1','description'=>'']);


        $log = Permission::create(['inherit_id'=>$system->id,'title' => '日志管理','slug'=>'role.index','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$log->id,'title' => '导出日志','slug'=>'log.export','status'=>'1','description'=>'']);

        $salon = Permission::create(['inherit_id'=>$system->id,'title' => '店铺管理','slug'=>'salon.index','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$salon->id,'title' => '店铺更新','slug'=>'salon.save','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$salon->id,'title' => '查看店铺','slug'=>'salon.getSalon','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$salon->id,'title' => '中止合作','slug'=>'salon.endCooperation','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$salon->id,'title' => '删除店铺','slug'=>'salon.del','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$salon->id,'title' => '检测店铺编号','slug'=>'salon.checkSalonSn','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$salon->id,'title' => '获取省市区商圈','slug'=>'salon.getProvinces','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$salon->id,'title' => '获取省市区','slug'=>'salon.getBussesName','status'=>'1','description'=>'']);

        $merchant = Permission::create(['inherit_id'=>$system->id,'title' => '商户管理','slug'=>'merchant.index','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$merchant->id,'title' => '修改商户','slug'=>'merchant.save','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$merchant->id,'title' => '删除商户','slug'=>'merchant.del','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$merchant->id,'title' => '检测商户编号','slug'=>'merchant.checkMerchantSn','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$merchant->id,'title' => '查看商户','slug'=>'merchant.getMerchantList','status'=>'1','description'=>'']);


        $shop_count = Permission::create(['inherit_id'=>$system->id,'title' => '店铺结算','slug'=>'','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '转付单','slug'=>'shop_count.index','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '查看转付单','slug'=>'shop_count.show','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '更新转付单','slug'=>'shop_count.update','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '预览转付单','slug'=>'shop_count.preview','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '新增转付单','slug'=>'shop_count.create','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '删除转付单','slug'=>'shop_count.destroy','status'=>'1','description'=>'']);
       
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '代收单','slug'=>'shop_count.delegate_list','status'=>'1','description'=>'']);
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '查看代收单','slug'=>'shop_count.delegate_detail','status'=>'1','description'=>'']);
        
        Permission::create(['inherit_id'=>$shop_count->id,'title' => '往来余额','slug'=>'shop_count.balance','status'=>'1','description'=>'']);
    }

}
