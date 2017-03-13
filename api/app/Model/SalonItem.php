<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use App\SalonItem;
use App\SalonItemBuylimit;
use App\SalonItemFormatPrice;
use App\SalonNormsCat;
use App\Salon;
use DB;
use App\Hairstylist;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use Event;


class SalonItem extends Model {

    /**
     * 上架状态
     * @var unknown
     */
    CONST STATUS_OF_UP = 1;
    
    /**
     * 下架状态
     * @var unknown
     */
    CONST STATUS_OF_DOWN = 2;
    
    /**
     * 删除状态
     * @var unknown
     */
    CONST STATUS_OF_DELETE = 3;
    
	protected $table = 'salon_item';

	public $timestamps = false;
	
	public function salonItemType()
	{
	    return $this->belongsTo(SalonItemType::class,'typeid','typeid');
	}
	
	public function salon()
	{
	    return $this->belongsTo(Salon::class,'salonid','salonid');
	}
	
	/**
	 * 添加修改项目
	 * @param  $priceType 1 无规格 2有规格
	 * */
	public static function upsertItem($datas,$priceType,$salonid,$itemid=null)
	{
	    $salon_buylimit_id = null;
	    $salon_norms_cat_id = null;
	    $act = null;
	    DB::beginTransaction();
	    if(empty($itemid))
	    {
	    	$act = 'add';
	        $itemid = self::insertGetId($datas['salon_item']);
	        if(!$itemid)
	        {
	        	DB::rollBack();
	        	return false;
	        }
	        $datas['salon_item_buylimit']['salon_item_id'] = $itemid;
	        $salon_buylimit_id = SalonItemBuylimit::insertGetId($datas['salon_item_buylimit']);
	    }
	    else 
	    {	
	    		
	        SalonItemFormatPrice::where(['itemid'=>$itemid])->delete();
	        self::where('itemid',$itemid)->update($datas['salon_item']);
	        $datas['salon_item_buylimit']['salon_item_id'] = $itemid;
	        $salon_buylimit_id = SalonItemBuylimit::where('salon_item_id',$itemid)->update($datas['salon_item_buylimit']);
	    }
	    
	    $itemInfo = self::where(['itemid'=>$itemid])->select(['itemid','norms_cat_id'])->first()->toArray();
	    if($itemInfo['norms_cat_id'])
	    {
	    	$catNums = self::where(['norms_cat_id'=>$itemInfo['norms_cat_id']])->count();
	    	if($catNums == 1)//只有当前项目使用规则 就删除原规格
	    	{
	    		SalonNormsCat::where(['salon_norms_cat_id'=>$itemInfo['norms_cat_id']])->delete();
	    		SalonNorms::where(['salon_norms_cat_id'=>$itemInfo['norms_cat_id']])->delete();
	    	}
	    }
	    	
	    if($priceType == 2)
	    {
	   		$salon_norms_cat_id = SalonNormsCat::insertGetId($datas['salon_norms_cat']);
		    foreach($datas['salon_norms'] as $norms)
		    {
		    	$norms['salon_norms_cat_id'] = $salon_norms_cat_id;
		    	$tmp_norms_id[$norms['salon_item_format_id']] = SalonNorms::insertGetId($norms);
		    }
		    
		    self::where(['itemid'=>$itemid])->update(['norms_cat_id'=>$salon_norms_cat_id]);
	    }
	    
	    foreach($datas['salon_item_format_price'] as $price)
	    {
	    	$price['itemid'] = $itemid;
	    	if($priceType == 2)
	    	{
	    		$price['salon_norms_id'] = $tmp_norms_id[$price['salonNormsMark']];
	    		unset($price['salonNormsMark']);
	    	}
	        $tmp_salon_item_format_price_id = SalonItemFormatPrice::insertGetId($price);
	        if(!$tmp_salon_item_format_price_id)
	        {
	        	DB::rollBack();
	        	return false;
	        }
	    }
	    
	   if( $datas['salon_item']['maxPrice'] >= 1000 )   //项目价格大于1000  调整店铺类型
	    	Salon::where(['salonid'=>$salonid])->update(['bountyType'=>4]);
	
	   DB::commit();
	   $log = '项目Id:'.$itemid.' 项目名称：'.$datas['salon_item']['itemname'];
	   if($act == 'add')
	   {
	   		Event::fire('ItemInfo.save',$log);
	   }
	   else 
	   {
	   		Event::fire('ItemInfo.update',$log);
	   }
	   return true;
	}
	
	/*
	 * 获取店铺项目资料
	 * */
	public static function getSalonItem($key,$keyword,$page,$page_size)
	{

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
			return $page;
		});
		$query = Salon::select(['salonid','salonname'])->where('status','!=','3')->orderBy('salonid','desc');
		if($key == 1)
		{
			$keyword = '%'.$keyword.'%';
			$query = $query->where('salonname','like',$keyword);
		}
		elseif($key == 2)
		{
			$keyword = '%'.$keyword.'%';
			$query = $query->whereRaw("merchantId in (SELECT `id` FROM `cm_merchant` WHERE `name` LIKE '{$keyword}')");
		}
		
		$salonList =    $query->paginate($page_size);
		$result = $salonList->toArray();
		if($result)
		{
			foreach ($result['data'] as $key=>$val)
			{
				$result['data'][$key]['generalNums'] = self::where(['salonid'=>$val['salonid'],'status'=>1,'item_type'=>1])->count();//普通项目
				$result['data'][$key]['specialNums'] = self::where(['salonid'=>$val['salonid'],'status'=>1,'item_type'=>2])->count();//限时特价
				$result['data'][$key]['wareroomNums'] = self::where(['salonid'=>$val['salonid'],'status'=>2])->count();//项目仓库
				$result['data'][$key]['hairstyNums'] = Hairstylist::where(['salonid'=>$val['salonid']])->where('status','!=',2)->count();//造型师
			}
		}
		return $result;
	}

	public static function composite($items,$formats)
	{
	    foreach($items as &$item)
	    {
	        $cat_id = $item['norms_cat_id'];
	        if(isset($formats[$cat_id]))
	        {
	            $item['salon_norms_cat'] = $formats[$cat_id];
	        }
	        else 
	        {
	            $item['salon_norms_cat'] = [];
	        }
	    }
	    return $items;
	}

	/**
	 * @param array $itemIds 要上架的salon item ids的数组
	 * @return array $result 检查的结果 或者 参数错误提示
	 */
	public static function checkUp($ids) 
	{
	    $ids = array_unique($ids);
	    $input_count = count($ids);
	    if($input_count<1)
	    {
	        return true;
	    }  
	    
	    $now_time = time();
	    $items = SalonItem::select(['itemid','itemname','exp_time','total_rep','sold','minPrice','minPriceOri','timingShelves'])->whereIn('itemid',$ids)->where('status',SalonItem::STATUS_OF_DOWN)->get();
	    if(empty($items))
	    {
	        throw new ApiException("要上架的项目不存在或者状态不正确",ERROR::ITEM_LOST_OR_WRONG_STATE);
	    }
	    $itemArr = $items->toArray();
	    $item_ids = array_column($itemArr, "itemid");
	    $error_ids = array_diff($item_ids, $ids);
	    if(count($error_ids)>0)
	    {
	        throw new ApiException("ids : [".implode(',', $error_ids)."] 项目不存在或者状态不正确",ERROR::ITEM_LOST_OR_WRONG_STATE);
	    }
	    
	    foreach ($itemArr as $item)
	    {
	        $id = $item['itemid'];
	        $name = $item['itemname'];
	        $exp_time = intval($item['exp_time']);
	        $timingShelves = intval($item['timingShelves']);
	        $total_rep = intval($item['total_rep']);
	        $sold = intval($item['sold']);
	        $min_price = floatval($item['minPrice']);
	        $min_price_ori = floatval($item['minPriceOri']);
	        if($timingShelves>0 && $timingShelves<$now_time)
	        {
	            throw new ApiException("项目 [{$id} : $name] 已满足定时下架时间设置,请修改定时下架时间  定时下架时间 [".date("Y-m-d H:i:s",$timingShelves)."] ",ERROR::ITEM_WRONG_EXP_TIME);
	        }
	        if($min_price<=0 || $min_price_ori<=0)
	        {
	            throw new ApiException("项目 [{$id} : $name] 价格小于 0 ",ERROR::ITEM_WRONG_PRICE);
	        }
	        if($exp_time >0 && $exp_time < $now_time)
	        {
	            throw new ApiException("项目 [{$id} : $name] 有效期 [".date("Y-m-d H:i:s",$exp_time)."]应大于当前时间",ERROR::ITEM_WRONG_EXP_TIME);
	        }
	        
	        if($total_rep >0 && $total_rep < $sold)
	        {
	            throw new ApiException("项目 [{$id} : $name] 库存[{$total_rep}]应大于已售份数[{$sold}]",ERROR::ITEM_WRONG_TOTAL_REQ);
	        }
	    }
	    return true;
	}
	
}
