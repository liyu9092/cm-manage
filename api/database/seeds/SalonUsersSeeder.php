<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\SalonUser;
use App\Salon;
use App\Merchant;

class SalonUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.   兼容老店铺账号体系
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonUsersSeeder
     * 
     */
    public function run()
    {
    	$sList = DB::table('salon_user')->select(['salon_user_id','salonid'])->get();
   		if($sList)
		{
			foreach($sList as $v)
			{
				if($v->salonid)
				{
					$salonList = DB::table('salon')
						->select(['salonid','merchantId','status'])
						->where("salonid",$v->salonid)
						->where('merchantId', '!=', '0')
						->first();
					if($salonList)//修改普通管理员
					{
	
						DB::table('salon_user')
							->where('salon_user_id', $v->salon_user_id)
							->update(
									array(
										"merchantId"=>$salonList->merchantId,
										"status"=>$salonList->status,
										"roleType"=>1,
										"addTime"=>time(),
									)
								);
					}
					
				}
				else
				{
					$salonListAdmin = DB::table('salon')
						->select(['salonid','merchantId','status'])
						->where("puserid",$v->salon_user_id)
						->where('merchantId', '!=', '0')
						->get();
					if($salonListAdmin)//修改超级管理员
					{
						$sStatus = 2;
						foreach($salonListAdmin as $vt)
						{
							if($vt->status == 1)
							{
								$sStatus = 1;
							}
							$smerchantId = $vt->merchantId;
						}
						
						DB::table('salon_user')
							->where('salon_user_id', $v->salon_user_id)
							->update(
									array(
										"merchantId"=>$smerchantId,
										"status"=>$sStatus,
										"roleType"=>2,//超级管理员
										"addTime"=>time(),
									)
								);
					}
				}
			}
		}
		echo "ok";
    	
    }

}
