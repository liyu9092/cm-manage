<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use App\SalonItemFormat;
class SalonNorms extends  Model
{
    protected $table = 'salon_norms';
    
    public $timestamps = false;
    
    /**
     * 根据模板id获取 规格模板列表
     * @param $catId
     * @return bool|mixed
     */
    public static function getListByCatId($catId){
    	if(!$catId)
    		return false;
		$list = self::where(['salon_norms_cat_id'=>$catId])->select(['salon_norms_id as snId','salon_norms_cat_id as sncId','salon_item_format_id as sifId'])->get();
    	if(!$list)
    		return false;
    	$listV = [];
    	$i = 1;
    	foreach($list as $key=>$val)
    	{
    		if($i == 1)
    		{
    			$listV['norm']['sncId'] = $val->sncId;
    			$listV['norm']['title'] = self::getNormName(explode(',', $val->sifId));
    		}
    			
    		$listV['normList'][$key]['salonNormsId'] = $val->snId;
    		$listV['normList'][$key]['itemForMatName'] = SalonItemFormat::getListInId($val->sifId);
    		$i++;
    	}
    	return $listV;
    }
    
    private static function getNormName($tid)
    {
    	$result = SalonItemFormat::select('s.formats_name')
    			->leftJoin('salon_item_formats as s','salon_item_format.salon_item_formats_id','=','s.salon_item_formats_id')
    			->whereIn("salon_item_format.salon_item_format_id",$tid)
    			->get();
    	if(!$result) return false;
    	foreach($result as $val)
    	{
    		$rs[]=$val->formats_name;
    	}
    	return $rs;
    }
    
}