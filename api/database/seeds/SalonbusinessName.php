<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Salon;
use App\SalonInfo;
use App\Manager;
use Illuminate\Support\Facades\Log;

class SalonbusinessNameSeeder extends Seeder
{
    /**
     * 调整业务代表  只允许执行一次
     * Run the database seeds.
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonbusinessNameSeeder  
     */
    public function run()
    {
    	$flag = Salon::select(['businessId'])->where('businessId','>',60)->get()->toArray();
    	if($flag)
    	{
    		die('error');
    	}
    	$salonResult = Salon::select(['businessId','salonid','businessName'])
    						->leftjoin('business_staff', 'business_staff.id', '=', 'salon.businessId')
    						->where('businessId','!=',0)
    						->get()
    						->toArray();
    	
    	$managerResult = Manager::where(['department_id'=>13])->select(['name','id'])->get()->toArray();
    	foreach($salonResult as $val)
    	{
    		foreach($managerResult as $manager)
    		{
    			if($val['businessName'] == $manager['name'])
    			{
    				Salon::where(['salonid'=>$val['salonid']])->update(['businessId'=>$manager['id']]);
    				$logMsg = 'salonid:'.$val['salonid'].' 原：'.$val['businessId'].'更改'.$manager['id'];
    				Log::info('调整业务代表：'.$logMsg);
    			}
    		}
    	}
    	echo 'ok';
    }
 
}
