<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class BookingCalendar extends Model {

	protected $table = 'booking_calendar';
	
	public $timestamps = false;
	
	protected $primaryKey = 'ID';
	
	CONST CHANGE_TYPE_OF_ADD = 1;
	CONST CHANGE_TYPE_OF_DEL = 2;
	CONST CHANGE_FIELD_OF_BOOK = 1;
	CONST CHANGE_FIELD_OF_ARRIVE = 2;
	public static function change_items_date($item_ids,$booking_date,$change_type = self::CHANGE_TYPE_OF_ADD)
	{
	    Utils::log("logs",date("Y-m-d H:i:s")." BOOK:".($change_type == self::CHANGE_TYPE_OF_ADD ?"ADD":"DEL") .": items:[".implode(",", $item_ids)."] date:{$booking_date} \n");
	    if(count($item_ids)<1)
	    {
	        return;
	    }	   
	    $item_idx = BeautyItem::getItemBeautyId($item_ids);

	    foreach ($item_ids as $item_id)
	    {
	        $beauty_id = 1;
	        if (isset($item_idx[$item_id]))
	        {
	            $beauty_id = $item_idx[$item_id];
	        }
	        self::upsert($item_id,$booking_date,$beauty_id,$change_type);
	    }
	}
	
	public static  function upsert($item_id,$booking_date,$beauty_id,$change_type = self::CHANGE_TYPE_OF_ADD,$update_field = self::CHANGE_FIELD_OF_BOOK)
	{
	    $now_date = date("Y-m-d H:i:s");
	    $query = self::where('ITEM_ID',$item_id);
	    $query->useWritePdo();	    
        $item = $query->where('BOOKING_DATE',$booking_date)->first();
        if(empty($item))
        {
            $attr = [
                'ITEM_ID'=>$item_id,
                'BOOKING_DATE'=>$booking_date,
                'BEAUTY_ID'=>$beauty_id,
                'QUANTITY'=>0,
                'CREATE_TIME'=>$now_date,
                'UPDATE_TIME'=>$now_date,
            ];
            if ($change_type == self::CHANGE_TYPE_OF_ADD)
            {
                if($update_field == self::CHANGE_FIELD_OF_BOOK)
                {
                    $attr['QUANTITY']  = 1;
                }
                else 
                {
                    $attr['CAME'] = 1;
                }
                self::create($attr);
            }
        }
        else 
        {
            $field = 'QUANTITY';
            if($update_field != self::CHANGE_FIELD_OF_BOOK)
            {
                $field = 'CAME';
            }
            if ($change_type == self::CHANGE_TYPE_OF_ADD)
            {
                self::where('ITEM_ID',$item_id)->where('BOOKING_DATE',$booking_date)->increment($field,1,['UPDATE_TIME'=>$now_date,'BOOKING_MORN_COUNT'=>0,'BOOKING_AFTERNOON_COUNT'=>0]);
            }
            else
            {
                self::where('ITEM_ID',$item_id)->where('BOOKING_DATE',$booking_date)->where($field,'>',0)->decrement($field,1,['UPDATE_TIME'=>$now_date,'BOOKING_MORN_COUNT'=>0,'BOOKING_AFTERNOON_COUNT'=>0]);
            }
        }
	}
	
	public static function arrive($item_ids,$arrive_date,$change_type = self::CHANGE_TYPE_OF_ADD)
	{
	    Utils::log("logs",date("Y-m-d H:i:s")." ARRIVE:".($change_type == self::CHANGE_TYPE_OF_ADD ?"ADD":"DEL") .": items:[".implode(",", $item_ids)."] date:{$arrive_date} \n");
	    if(count($item_ids)<1)
	    {
	        return;
	    }
	    $item_idx = BeautyItem::getItemBeautyId($item_ids);
	    
	    foreach ($item_ids as $item_id)
	    {
	        $beauty_id = 1;
	        if (isset($item_idx[$item_id]))
	        {
	            $beauty_id = $item_idx[$item_id];
	        }
	        self::upsert($item_id,$arrive_date,$beauty_id,$change_type,self::CHANGE_FIELD_OF_ARRIVE);
	    }
	}
	public static function refundUpdateCalendar( $orderSn = ''){
		if( empty($orderSn) ) return false;
		$bookings = BookingOrder::select(['booking_date as bookingDate','updated_booking_date as updatedBookingDate','BOOKING_DESC as bookingDesc'])->where(['order_sn'=>$orderSn])->first();
		if( empty( $bookings ) )	return false;
		$bookings = $bookings->toArray();
		if( !empty( $bookings['updatedBookingDate'] ) ) $bookingTime = $bookings['updatedBookingDate'];
		else $bookingTime = $bookings['bookingDate'];
		$tempField = ['DEF'=>'','MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
		$itemQuantity = BookingOrderItem::select(['ITEM_ID as itemId','quantity'])->where(['order_sn'=>$orderSn])->get()->toArray();
		$tempItemIds = [];
		$t = [];
		foreach( $itemQuantity as $k => $v ){
			$tempItemIds[] = $v['itemId'];
			$t[ $v['itemId'] ] = $v;
		}
		$calendarItemIds = BookingCalendar::select(['ITEM_ID as itemId','quantity','BOOKING_MORN_COUNT','BOOKING_AFTERNOON_COUNT'])->whereIn('ITEM_ID',$tempItemIds)->where(['BOOKING_DATE'=>$bookingTime])->get()->toArray();
		$where['BOOKING_DATE'] = $bookingTime;
		$bookingDesc = $bookings['bookingDesc'];
		foreach( $calendarItemIds as $k => $v ){
			$updateCalendar = [];
			$where['ITEM_ID'] = $v['itemId'];
			$changeNum = $v['quantity'] - $t[ $v['itemId'] ]['quantity'];
			if( $bookingDesc != 'DEF' ){
				$changeDesc = $v[ $tempField[ $bookingDesc] ] - $t[ $v['itemId'] ]['quantity'];
				if( $changeDesc<0 ) $changeDesc = 0;
				$updateCalendar[ $tempField[ $bookingDesc] ] = $changeDesc;
			}
			if( $changeNum  < 0 ) $changeNum = 0;
			$updateCalendar['QUANTITY'] = $changeNum;
			$calendarNum = BookingCalendar::where($where)->update($updateCalendar);
		}
		return true;
	}
	public function isFillable($key)
	{
	    return true;
	}
	
}

