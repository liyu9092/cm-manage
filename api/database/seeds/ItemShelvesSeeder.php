<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\SalonItem;
use Illuminate\Support\Facades\Log;
class ItemShelvesSeeder extends Seeder
{
    /**
     * 每分钟执行一次 上下架项目
     * Run the database seeds.
     *
     * @return void
     * 
     * php artisan db:seed --class=ItemShelvesSeeder  
     */
    public function run()
    {
    	$time = time();
	   	$added = SalonItem::where(['status'=>2])
	   			->where('timingAdded','<=',$time)
	   			->where('timingAdded','!=',0)
	   			->select(['itemid','itemname','exp_time','total_rep','sold','minPrice','minPriceOri','timingShelves'])
	   			->get()->toArray();  	 
    	if($added)
    	{
    		$idsadded = [];
    		$ids = [];
    		foreach($added as $item)
    		{
    			$id = $item['itemid'];
    			$ids[] = $id;
    			$name = $item['itemname'];
    			$exp_time = intval($item['exp_time']);
    			$total_rep = intval($item['total_rep']);
    			$sold = intval($item['sold']);
    			$min_price = floatval($item['minPrice']);
    			$min_price_ori = floatval($item['minPriceOri']);
    			if($min_price<=0 || $min_price_ori<=0)
    			{
    				$idsadded[] = $id;// 价格小于 0
    			}
    			if($exp_time >0 && $exp_time < $time)
    			{
    				$idsadded[] = $id;//有效期
    			}
    			if($total_rep >0 && $total_rep < $sold)
    			{
    				$idsadded[] = $id;//库存
    			}
    			if($item['timingShelves'] <= $time && $item['timingShelves'] > 0)
    			{
    				$idsadded[] = $id;//下架时间
    			}
    		}
    		if($idsadded)
    		{
    			$idsArr = array_diff($ids,$idsadded);
    		}
    		else
    		{
    			$idsArr = $ids;
    		}
    		if($idsArr)
    		{
    			if(SalonItem::whereIn('itemid',array_values($idsArr))->update(['status'=>1,'up_time'=>$time]))
    				Log::info('定时上架: '.json_encode(array_values($idsArr)));
    		}
    		
    	}
    	
    		
    	$shelves = SalonItem::whereRaw("status =1 AND ((timingShelves <= '{$time}' AND timingShelves != 0) OR (exp_time < '{$time}' AND exp_time != 0))")
    				->select(['itemid','itemname','exp_time','total_rep','sold','minPrice','minPriceOri'])
    				->get()->toArray();
    	if($shelves)
    	{
    		$idshArr = [];
    		foreach($shelves as $idsh)
    		{
    			$idshArr[] = $idsh['itemid'];
    		}
    		if(SalonItem::whereIn('itemid',$idshArr)->update(['status'=>2]))
    			Log::info('定时下架: '.json_encode($idshArr));
    	}
    	Log::info('定时上下架执行记录 ');
    	echo 'ok';
    }
 
}
