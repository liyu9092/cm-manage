<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Salon;
use Illuminate\Support\Facades\Log;
class SalonLogoSeeder extends Seeder
{
    /**
     * 原店铺logo 字段 映射到salonLogo字段
     * Run the database seeds.
     * eq：不能重复执行
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonLogoSeeder  
     */
    public function run()
    {
    	$logoList = Salon::select(['logo','salonid'])->where('salonLogo','=','')->where('logo','!=','')->get()->toArray();

    	foreach($logoList as $val)
    	{
    		$logoImg = [];
    		$logoImg[] = [
    				'img'		=> $val['logo'],
    				'thumbimg'	=> $val['logo'],
    		];
    		$logoJson = json_encode($logoImg);
    		Salon::where(['salonid'=>$val['salonid']])->update(['salonLogo'=>$logoJson]);
    		Log::info($val['salonid'].'logo数据迁移 '.$logoJson);
    	}
    	echo 'ok';
    }
 
}
