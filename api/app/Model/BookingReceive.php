<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use DB;
Use PDO;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class BookingReceive extends Model
{
    protected $table = 'booking_receive';
    protected $primaryKey = 'id';
    public $timestamps = false;
    

    public static function getByBookingId($booking_id,$fields=['*'])
    {
        $base = self::where('booking_id',$booking_id)->first($fields);
        if(empty($base))
        {
            return null;
        }
        $res = $base->toArray();
        $ext = @json_decode($res['ext'],true);
        if(is_array($ext))
        {
            $res['ext'] = $ext;
        }
        else {
            $res['ext'] = null;
        }
        $res['manager']=Manager::getBaseInfo($res['uid']);
        $res['guide_user'] = Artificer::getBaseInfo($res['guideId']);
        return $res;
    }
    
    public static function receive_base($id,$params,$change_arrive=true)
    {
        if(!isset($params['uid']))
        {
            throw new ApiException("接待人必填!", ERROR::PARAMS_LOST);
        }
        $base = BookingOrder::where('ID',$id)->first();
        $now_time = time();
        $now_date = date('Y-m-d',$now_time);
        $now_datetime = date('Y-m-d H:i:s',$now_time);
        if(empty($base))
        {
            throw new ApiException("定妆单{$id}不存在或者已经被删除!", ERROR::ORDER_NOT_EXIST);
        }
        $ordersn = $base->ORDER_SN;
        $receive = self::getReceiveInfo($id);
        $origin_item_ids = self::getBookingOrderItemIds($ordersn);
        $attr = $params;
        if(empty($receive))
        {
            $attr['booking_id'] = $id;
            $attr['booking_sn'] = $base->BOOKING_SN;
            $attr['order_sn'] = $ordersn;
            $attr['arrive_at'] = $now_datetime;
            $attr['created_at'] = $now_datetime;
            BookingCalendar::arrive($origin_item_ids, $now_date);
            BookingReceive::create($attr);
        }
        else
        {
            $old_arrive_date = date("Y-m-d",strtotime($receive->arrive_at));
            if($change_arrive && $old_arrive_date != $now_date)
            {
                BookingCalendar::arrive($origin_item_ids, $old_arrive_date,BookingCalendar::CHANGE_TYPE_OF_DEL);
                BookingCalendar::arrive($origin_item_ids, $now_date);
            }
            $receive->update($attr);
        }
        $base->update(['COME_SHOP'=>'COME','BOOKING_DESC'=>'DEF']);
        return $base;
    }
    
    public static function getReceiveInfo($booking_id)
    {
        return BookingReceive::where("booking_id",$booking_id)->first();
    }
    
    public static function receiveItem($id,$params)
    {
        $item_ids = $params['item_ids'];
        unset($params['item_ids']);
        $base = self::receive_base($id, $params,false);
        $origin_item_ids = self::getBeautyOrderItemIds($base->ORDER_SN);
       // $need_insert_item_ids = array_diff($array1, $array2);
        
        return $base;
    }
    
    public static function copyFromBook($ordersn,$item_ids,$use_bargain_price=false)
    {
        if(count($item_ids)<1)
        {
            return ;
        } 
        $items_info = self::getItemNormInfoAtFirst($item_ids,$use_bargain_price);
        self::insertNewItems($ordersn, $items_info);
        return ;
    }
    
    public static function copyFromBookByOrdersn($ordersn,$use_bargain_price=false)
    {
        $origin_item_ids = self::getBookingOrderItemIds($ordersn);
        return self::copyFromBook($ordersn,$origin_item_ids,$use_bargain_price);
    }
    
    public static function addBeautyItems($id,$uid,$item_id_str,$use_bargain_price=false)
    {
        $id_infos = self::getItemIdsNormIds($item_id_str);
        $insert_items_info = self::getItemNormInfo($id_infos,$use_bargain_price);
        unset($id_infos);
        $rev = self::getReceiveInfo($id);
        $ordersn = null;
        if(empty($rev))
        {
            self::receive_base($id, ['uid'=>$uid]);
        }
        else 
        {
            $ordersn = $rev->order_sn;
        }
        if(empty($ordersn))
        {
            $base = BookingOrder::where('ID',$id)->first();
            if(!empty($base))
            {
                $ordersn = $base->ORDER_SN;
            }
        }
        self::insertNewItems($ordersn, $insert_items_info);
    }

    
//     /**
//      * @param int $id
//      * @param array $params
//      * @param string $copy_from_base
//      */
//     public static function receive($id,$params,$copy_from_base=false)
//     {
//         $base = BookingOrder::where('ID',$id)->first();
//         if(empty($base))
//         {
//             throw new ApiException("定妆单{$id}不存在或者已经被删除!", ERROR::ORDER_NOT_EXIST);
//         }
//         $base= $base->toArray();
//         $ordersn = $base['ORDER_SN'];
//         $datetime = date("Y-m-d H:i:s");
//         $receive = BookingReceive::where("booking_id",$id)->first();
//         $is_first_receive = false;
//         if(empty($receive) || $copy_from_base)
//         {
//             $is_first_receive = true;
//         }
        
//         $old_items_ids = [];
//         $origin_item_ids = self::getBookingOrderItemIds($ordersn);
//         if($is_first_receive)
//         {           
//             $old_items_ids = $origin_item_ids;
//         }
//         else 
//         {
//             $old_items_ids = self::getBeautyOrderItemIds($ordersn);
//         }
        
//         $insert_items_info = [];
        
//         if($copy_from_base)
//         {
//             $insert_items_info = self::getItemNormInfoAtFirst($old_items_ids);
//         }
//         else 
//         {
//             $id_infos = self::getItemIdsNormIds($params['item_ids']);
//             $insert_items_info = self::getItemNormInfo($id_infos);
//             unset($id_infos);
//         }
        
//         $old_booking_date = empty($base['UPDATED_BOOKING_DATE'])?$base['BOOKING_DATE']:$base['UPDATED_BOOKING_DATE'];
//         $update_booking_date = isset($params['update_booking_date'])?date("Y-m-d",strtotime($params['update_booking_date'])):NULL;
//         $now_booking_date = empty($update_booking_date)?$old_booking_date:$update_booking_date;

//         $attr = [
//             'booking_id'=>$id,
//             'order_sn'=>$ordersn,
//             'booking_sn'=>empty($base['BOOKING_SN'])?"":$base['BOOKING_SN'],
//             'uid'=>$params['uid'],
//             'created_at'=>$datetime,
//         ];
        
//         $base_update_attr = ['COME_SHOP'=>'COME','BOOKING_DESC'=>'DEF'];
//         if(!empty($update_booking_date))
//         {
//             $attr['update_booking_date'] = $update_booking_date;
        
//             if(!empty($base['UPDATED_BOOKING_DATE'])  || $now_booking_date != $old_booking_date )
//             {
//                 $base_update_attr['UPDATED_BOOKING_DATE'] = $now_booking_date;
//             }
//         }
//         if(isset($params['remark']))
//         {
//             $attr['remark'] = $params['remark'];
//         }
//         if(isset($params['arrive_at']))
//         {
//             $attr['arrive_at'] = date("Y-m-d H:i:s",strtotime($params['arrive_at']));
//         }
//         else
//         {
//             $attr['arrive_at'] = $datetime;
//         }        
//         $arrive_date = date("Y-m-d",strtotime($attr['arrive_at']));        
        
//         $old_arrive_date = null;
        
//         if(!empty($receive) && !empty($receive->arrive_at))
//         {
//             $old_arrive_date = date("Y-m-d",strtotime($receive->arrive_at)); 
//         }
        
//         if(!empty($old_booking_date))
//         {
//              BookingCalendar::change_items_date($old_items_ids, $old_booking_date,BookingCalendar::CHANGE_TYPE_OF_DEL);
//         }
//         if(!empty($old_arrive_date))
//         {
//             BookingCalendar::arrive($old_items_ids, $old_arrive_date,BookingCalendar::CHANGE_TYPE_OF_DEL);
//         }
        
//         self::deleteOldItems($ordersn, $old_items_ids);
        
//         if(!empty($now_booking_date))
//         {
//             BookingCalendar::change_items_date($old_items_ids, $now_booking_date);
//         }
//         if(!empty($arrive_date))
//         {
//             BookingCalendar::arrive($old_items_ids, $arrive_date);
//         }
//         self::insertNewItems($ordersn, $insert_items_info);
//         if(!empty($receive))
//         {
//             $receive->delete();
//         }
//         BookingOrder::where('ID',$id)->update($base_update_attr);
//         BookingReceive::create($attr);
//         return $base;
//     }
    
    public static function getBookingOrderItemIds($ordersn)
    {
        $items = BookingOrderItem::where('ORDER_SN',$ordersn)->get(['ITEM_ID'])->toArray();
        $item_ids = array_column($items, "ITEM_ID");
        return $item_ids;
    }
    
    public static function getBeautyOrderItemIds($ordersn)
    {
        $items = BeautyOrderItem::where('order_sn',$ordersn)->get(['item_id'])->toArray();
        $item_ids = array_column($items, "item_id");
        return $item_ids;
    }

    public static function deleteOldItems($ordersn,$item_ids)
    {
        BeautyOrderItem::where('order_sn',$ordersn)->delete();
    }  
      
    public static function insertNewItems($ordersn,$item_infos)
    {      
        if(count($item_infos)<1)
        {
            return ;
        }
        $datetime = date("Y-m-d H:i:s");   

        foreach ($item_infos as $item)
        {       
            $attrs = [];
            $attrs['order_sn'] = $ordersn;
            $attrs['created_at'] = $datetime;
            $attrs['beauty_id'] = $item['beauty_id'];
            $attrs['item_id'] = $item['item_id'];
            $attrs['item_name'] = $item['item_name'];
            $attrs['norm_id'] = $item['norm_id'];
            $attrs['norm_name'] = $item['norm_name'];
            $attrs['quantity'] = 1;
            $attrs['price'] = $item['price'];
            $attrs['discount_price'] = $item['discount_price'];
            $attrs['amount'] = $item['amount'];
            $attrs['to_pay_amount'] = $item['to_pay_amount'];            
            BeautyOrderItem::create($attrs);  
        }
    }
    
    public static function getItemIdsNormIds($itemStr)
    {
        $res = [];
        foreach ($itemStr as $idStr)
        {
            $ids = explode("_", $idStr);
            $item_id = array_shift($ids);
            if(count($ids)>0)
            {
                foreach($ids as $id)
                {
                    $res[] = ['item_id'=>$item_id,'norm_id'=>$id];
                }
            }
            else 
            {
                $res[] = ['item_id'=>$item_id,'norm_id'=>null];
            }
        }
        return $res;
    }
    
    public static function getItemNormInfo($ids_info,$use_bargain_price=false)
    {
        $res = [];
        $item_ids = array_map("intval",array_column($ids_info, "item_id"));
        $norm_ids = array_map("intval",array_column($ids_info, "norm_id"));
        $item_infos = BeautyItem::whereIn('item_id',$item_ids)->get(['item_id','beauty_id','name','price','vip_price','bargain_price'])->toArray();
        $item_info_idx = Utils::column_to_key("item_id",$item_infos);
        $norm_infos = BeautyItemNorm::whereIn('id',$norm_ids)->get(['id','norm','price','vip_price','bargain_price'])->toArray();
        $norm_info_idx = Utils::column_to_key("id",$norm_infos);
        foreach($ids_info as $info)
        {
            $item_id = $info['item_id'];
            $norm_id = $info['norm_id'];
            if(!isset($item_info_idx[$item_id]))
            {
                throw new ApiException("项目 {$item_id} 不存在或者已删除!",ERROR::BEAUTY_ITEM_ERROR);
            }
            $tmp =
            [
                'item_id'=>$item_id,
                'item_name'=>$item_info_idx[$item_id]['name'],
                'beauty_id'=>$item_info_idx[$item_id]['beauty_id'],
            ];
            if(!empty($norm_id) && isset($norm_info_idx[$norm_id]))
            {
                $tmp['norm_id'] = $norm_info_idx[$norm_id]['id'];
                $tmp['norm_name'] = $norm_info_idx[$norm_id]['norm'];
                $tmp['price'] = $norm_info_idx[$norm_id]['price'];
                $tmp['discount_price'] = $use_bargain_price?$norm_info_idx[$norm_id]['bargain_price']:$norm_info_idx[$norm_id]['vip_price'];
                $tmp['amount'] = $norm_info_idx[$norm_id]['price'];
                $tmp['to_pay_amount'] = $use_bargain_price?$norm_info_idx[$norm_id]['bargain_price']:$norm_info_idx[$norm_id]['vip_price'];
            }
            else 
            {
                $tmp['norm_id'] = 0;
                $tmp['norm_name'] = "";    
                $tmp['price'] =$item_info_idx[$item_id]['price'];
                $tmp['discount_price'] = $use_bargain_price?$item_info_idx[$item_id]['bargain_price']:$item_info_idx[$item_id]['vip_price'];
                $tmp['amount'] =$item_info_idx[$item_id]['price'];
                $tmp['to_pay_amount'] = $use_bargain_price?$item_info_idx[$item_id]['bargain_price']:$item_info_idx[$item_id]['vip_price'];
            }
            $res[] = $tmp;
        }
        return $res;
    }
    
    public static function getItemNormInfoAtFirst($item_ids,$use_bargain_price=false)
    {
        $res = [];
        $items = BeautyItem::whereIn('item_id',$item_ids)->get(['item_id','beauty_id','name','price','vip_price','bargain_price'])->toArray();
        $norms = BeautyItemNorm::whereIn('item_id',$item_ids)->groupBy('item_id')->orderBy('id','ASC')->get(['id','item_id','norm','price','vip_price','bargain_price'])->toArray();
        $norm_info_idx = Utils::column_to_key("item_id",$norms);
        $item_info_idx = Utils::column_to_key("item_id",$items);
        foreach($item_ids as $item_id)
        {
           if(!isset($item_info_idx[$item_id]))
           {
               continue;
           }
            $info = $item_info_idx[$item_id];
            $tmp =
            [
                'item_id'=>$item_id,
                'item_name'=>$info['name'],
                'beauty_id'=>$info['beauty_id'],
                'norm_id' => 0,
                'norm_name' => "",
                'price' => $info['price'],
                'discount_price' => $use_bargain_price?$info['bargain_price']:$info['vip_price'],
                'amount' => $info['price'],
                'to_pay_amount' => $use_bargain_price?$info['bargain_price']:$info['vip_price'],
            ];
            if( isset($norm_info_idx[$item_id]))
            {
                $tmp['norm_id'] = $norm_info_idx[$item_id]['id'];
                $tmp['norm_name'] = $norm_info_idx[$item_id]['norm'];
                $tmp['price'] = $norm_info_idx[$item_id]['price'];
                $tmp['discount_price'] = $use_bargain_price?$norm_info_idx[$item_id]['bargain_price']:$norm_info_idx[$item_id]['vip_price'];
                $tmp['amount'] = $norm_info_idx[$item_id]['price'];
                $tmp['to_pay_amount'] = $use_bargain_price?$norm_info_idx[$item_id]['bargain_price']:$norm_info_idx[$item_id]['vip_price'];
            }
            $res[] = $tmp;
        }

        return $res;
    }
    
    
    public function isFillable($key)
    {
        return true;
    }
}
