<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use App\BookingCalendar;
use App\BeautyItemNorm;
use App\BeautyOrderItem;
use App\BookingOrder;
use App\Model\PresentArticleCode;
class BeautyItem extends Model {

	protected $table = 'beauty_item';
	
	public $timestamps = false;
	
	protected $primaryKey = 'item_id';
	
	public static function getBeautyItem($page,$page_size,$type,$is_gift)
	{
		$fields = ['item_id','beauty_id','type','name','level','price','vip_price','is_gift','genre','bargain_price'];
		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
		
	    $query = self::select($fields)->orderBy('level', 'asc')->orderBy('item_id', 'desc');
		if(isset($type) && intval($type))
		{
			$query = $query->where('type','=',$type);
		}
		if(isset($is_gift))
		{
			$query = $query->where('is_gift','=',$is_gift);
		}
		$result = $query->paginate($page_size)->toArray();
		
		if($result)
		{
		
			foreach($result['data'] as $key=>$val)
			{	
				$quantityRs = self::getQuantity($val['item_id'],$val['is_gift']);
				$result['data'][$key]['quantity'] =  $quantityRs;
				if($val['type'] == 2)//韩式快时尚 多规格价格查询
				{
					$priceRs = self::getMinMaxPrices($val['item_id']);
					$result['data'][$key]['prices'] =  $priceRs;
					unset($result['data'][$key]['price']);
					unset($result['data'][$key]['vip_price']);
				}
				$result['data'][$key]['completed_num'] = self::getItemCompletedNumber($val['item_id']);
			}
			unset($result['next_page_url']);
			unset($result['prev_page_url']);
		}
        return $result;
	}
	
	/**
	*获取多规格 价格区间
	*item_id 项目Id
	*/
	public static function getMinMaxPrices($item_id)
	{
		return BeautyItemNorm::selectRaw('min(price) as `min_price`,max(price) as `max_price`,min(vip_price) as `min_vip_price`,max(vip_price) as `max_vip_price`,min(bargain_price) as `min_bargain_price`,max(bargain_price) as `max_bargain_price`')->where(['item_id'=>$item_id])->first();		
	}
	
	/**
	*获取总预约数
	*item_id 项目Id
	*/
	public static function getQuantity($item_id,$is_gift=0)
	{
		if($is_gift)
		{
			$numsObj = PresentArticleCode::selectRaw('count(*) as `quantity`')->first();
		}
		else
		{
			$numsObj = BookingCalendar::selectRaw('SUM(QUANTITY) as `quantity`')->where(['ITEM_ID'=>$item_id])->first();
		}
		if($numsObj)
		{
			return $numsObj->quantity;
		}
		else
		{
			return 0;
		}

	}	
	
	/**
	*获取已完成数
	*
	*/
	public static function getItemCompletedNumber($item_id)
	{
		if(!$item_id) return '';
		$result = BeautyOrderItem::selectRaw('count(*) as `nums`')
			 ->leftjoin('booking_order', function ($join) {
				 $join->on('beauty_order_item.order_sn', '=', 'booking_order.ORDER_SN');
			 })->where(['beauty_order_item.item_id'=>$item_id,'booking_order.STATUS'=>'CSD'])->first(); 
		$row = 0;
		if($result)
		{
			$row = $result->nums;
		}
		return $row;
			 
	}
	
	public static function getItemBeautyId($item_ids)
	{
	    $res = [];
	    $items = BeautyItem::whereIn('item_id',$item_ids)->get(['beauty_id','item_id'])->toArray();
	    foreach($items as $item)
	    {
	        $key = $item['item_id'];
	        $val = $item['beauty_id'];
	        $res[$key] = $val;
	    }
	    return $res;
	}
	
}

