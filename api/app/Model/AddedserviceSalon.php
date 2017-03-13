<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use App\SalonItemType;

class AddedserviceSalon extends  Model
{
    protected $table = 'addedservice_salon';

    public $timestamps = false;
    
    /**
     * 新增店铺默认添加所有服务
     * */
    public static function setSalonGrade($salonid)
    {
    	if(!$salonid) return false;
    	
    	$itemType = SalonItemType::all();
    	$result = self::where('salonId',$salonid)->get()->toArray();
		if(!$result)//如果存在就不开通所有服务--
		{
			foreach($itemType as $val)
			{
				self::insertGetId(['itemTypeId' => $val->typeid,'salonId'=>$salonid]);
			}
		}
    }
}