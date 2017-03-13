<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\SalonRatingsRecord;
use App\SalonInfo;

class SalonGradeSeeder extends Seeder
{
    /**
     * 调整店铺等级
     * Run the database seeds.
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonGradeSeeder  
     */
    public function run()
    {
    	$salonRelust = DB::table('salon')->select(['salonid','add_time'])->get();
  		$i = 1;
		foreach($salonRelust as $val)
		{
			if($val->add_time < 1438358400)   //20150801
			{
				SalonRatingsRecord::insertGetId(['changeTime'=>$val->add_time,'endTime'=>'1438358399','addTime'=>time(),'salonid'=>$val->salonid,'commissionRate'=>'9.09']);	
				$changeTime = 1438358400;
			}
			else 
			{
				$changeTime = $val->add_time;
			}
			SalonRatingsRecord::insertGetId(['changeTime'=>$changeTime,'addTime'=>time(),'salonid'=>$val->salonid,'commissionRate'=>'9.00']);
			
			$salonTmpInfo = SalonInfo::where(['salonid'=>$val->salonid])->first();
			if(!$salonTmpInfo)
			{
				SalonInfo::insertGetId(array('salonid'=>$val->salonid,'commissionRate'=>'9.00'));
			}
			else 
			{
				SalonInfo::where(['salonid'=>$val->salonid])->update(['commissionRate'=>'9.00']);
			}
			$i++;
			echo $i."\r\n";
		}
	
    	
    }
 
}
