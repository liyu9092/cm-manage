<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\SalonUser;
use App\Salon;
use App\Merchant;

class SalonNumsSeeder extends Seeder
{
    /**
     * Run the database seeds.   同步商户 中 店铺数量值
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonNumsSeeder
     * 
     */
    public function run()
    {
    	$sList = DB::table('salon')
    				->select(DB::raw('count(*) as nums, merchantId'))
    				->where('salestatus', '=', '1')
    				->where('merchantId', '!=', '0')
    				->groupBy('merchantId')
    				->get();
   		if($sList)
		{
			foreach($sList as $v)
			{
				DB::table('merchant')->where('id', $v->merchantId)->update(array("salonNum"=>$v->nums));
			}
		}
		echo "ok";
    	
    }

}
