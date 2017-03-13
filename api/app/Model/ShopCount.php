<?php
/**
 * 商铺往来结算相关
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class ShopCount extends Model
{
    protected $table = 'shop_count';

   
    public function merchant(){
        return $this->belongsTo(Merchant::class);
    }
    
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }

//     /**
//      * 付给商户钱
//      * 
//      * @param array $option            
//      */
//     public static function payMoney($attrs)
//     {
//         if (isset($attrs['salon_id']) && isset($attrs['merchant_id']) && isset($attrs['pay_money']) && isset($attrs['cost_money'])) {
//             $salon_id = $attrs['salon_id'];
//             DB::transaction(function () use($attrs)
//             {
//                 $salon_id = $attrs['salon_id'];
//                 $class = __CLASS__;
//                 $model = new $class();
//                 if (empty($salon_id)) {
//                     return false;
//                 }                
//                 $item = $model->where([
//                     'salon_id' => $salon_id
//                 ])->first();
//                 if (empty($item)) {
//                     $attrs['balance_money'] = $attrs['cost_money'];
//                     $model::create($attrs);
//                 } else {
//                     $id = $item->id;
//                     $attrs = self::mergeMoney($attrs, $item);
//                     unset($attrs['id'],$attrs['salon_id']);
//                     self::where('id',$id)->update($attrs);
//                 }
//                 return true;
//             });
//             return true;
//         } else {
//             return false;
//         }
//     }
    
    /**
     * 店铺订单结算
     */
    public static function ShopCountOrder($code,$salon_id,$merchant_id,$money,$time,$type)
    {
        $ds_code =InsteadReceive::getNewCode();
       
        $return_code = 0;
        DB::transaction(function () use($code,$salon_id,$merchant_id,$money,$time,$type,$ds_code,&$return_code)
        {           
            $detail = ShopCountDetail::where("code",$code)->where("type",$type)->get()->toArray();
           
            if(!empty($detail))//已存在
            {
                $return_code = 2;
                return ;
            }
            $ret = ShopCountDetail::create([
                'code' => $code,
                'type' => $type,
                'money' => $money,
                'salon_id' => $salon_id,
                //'merchant_id' => $merchant_id,
                'created_at' => date("Y-m-d H:i:s", $time)
            ]);
            
            $ir = InsteadReceive::where('salon_id',$salon_id)->where('day',date("Y-m-d", $time))->first();
            if(empty($ir))
            {
                InsteadReceive::create([
                    'code' => $ds_code,
                    'salon_id' => $salon_id,
                    //'merchant_id' => $merchant_id,
                    'type' => InsteadReceive::TYPE_OF_ORDER,
                    'money' => $money,
                    'day' => date("Y-m-d", $time),
                    'created_at' => date("Y-m-d H:i:s")
                ]);
            }
            else
            {
                $now_money = floatval($ir->money) + $money;
                InsteadReceive::where('id',$ir->id)->update(['money'=> $now_money]);
            }
            $remark = $type == 1 ?"臭美券消费":"打赏金额";
            ShopCount::count_bill_by_receive_money($salon_id, $merchant_id, $money,$remark,$time);
            $return_code = 1;
            return ;         
        });
        return $return_code;
    }
    
    
    /**
     * 店铺往来结算(多种金额)
     * @param unknown $salon_id
     * @param unknown $merchant_id
     * @param array $money [type1=>change_money1,type2=>change_money2,...]
     */
    public static function count_bill_mutil($salon_id,$merchant_id,$money_info)
    {
        //@todo 暂时不要用    没有流水信息
//         $salon_id = intval($salon_id);
//         $merchant_id = intval($merchant_id);
//         $money_info = array_map("floatval",$money_info);
//         $types = array_keys($money_info);
//         $select_keys = $types;
//         $select_keys[] = 'id';
//         $items = self::where("salon_id",$salon_id)->get($select_keys)->toArray();
//         $now_date = date("Y-m-d H:i:s");
//         if(empty($items) || !isset($items[0]))
//         {
//             $records = ['salon_id'=>$salon_id,'merchant_id'=>$merchant_id,'created_at'=>$now_date,'updated_at'=>$now_date];
//             foreach($money_info as $key => $money)
//             {
//                 $records[$key] = $money;
//             }
//             self::create($records);
//         }
//         else
//         {
//             $records = ['merchant_id'=>$merchant_id,'updated_at'=>$now_date];
//             foreach($types as $type)
//             {
//                 $records[$type] = floatval($items[0][$type]) + $money_info[$type];
//             }
//             $id = $items[0]['id'];
//             self::where('id',$id)->update($records);
//         }
//         return true;
    }
    
    /**
     * 店铺往来结算
     * @param int $salon_id
     * @param int $merchant_id
     * @param float $money
     * @param string $money_type
     * @param string $remark
     * @param datetime|timestamp $count_at
     * @return boolean
     */
    public static function count_bill($salon_id,$merchant_id,$money,$money_type,$remark="",$count_at="0000-00-00 00:00:00")
    {
        $salon_id = intval($salon_id);
       // $merchant_id = intval($merchant_id);      
        $items = self::where("salon_id",$salon_id)->get(['id',$money_type])->toArray();
        $now_date = date("Y-m-d H:i:s");
        
        //结算的log
        switch ($money_type)
        {
            case "pay_money":
                ShopCountLog::add_log($salon_id, ShopCountLog::TYPE_OF_PREPAY, $money, $count_at,$remark);
                break;
            case "spend_money":
                ShopCountLog::add_log($salon_id, ShopCountLog::TYPE_OF_SPEND, $money, $count_at,$remark);
                break;
            case "commission_money":
                ShopCountLog::add_log($salon_id, ShopCountLog::TYPE_OF_COMMISSION, $money, $count_at,$remark);
                break;
            case "commission_return_money":
                ShopCountLog::add_log($salon_id, ShopCountLog::TYPE_OF_COMMISSION_RETURN, $money, $count_at,$remark);
                break;                
        }
        
        if(empty($items) || !isset($items[0]))
        {
            self::create(['salon_id'=>$salon_id,"{$money_type}"=>$money,'created_at'=>$now_date,'updated_at'=>$now_date]);
        }
        else
        {
            $now_money = bcadd($items[0][$money_type] , $money,2);
            $id = $items[0]['id'];
            self::where('id',$id)->update(["{$money_type}"=>$now_money,'updated_at'=>$now_date]);
        }
        return true;
    }
    
    /**
     * 结算付款
     * @param int $salon_id
     * @param int $merchant_id
     * @param float $money
     * @param string $remark
     * @param datetime|timestamp $count_at
     * @return boolean
     */
    public static function count_bill_by_pay_money($salon_id,$merchant_id,$money,$remark="预付保证金",$count_at="0000-00-00 00:00:00")
    {
        return self::count_bill($salon_id, $merchant_id, $money, "pay_money",$remark,$count_at);
    }
  
    /**
     * 结算收款款(已消费)
     * @param int $salon_id
     * @param int $merchant_id
     * @param float $money
     * @param string $remark
     * @param datetime|timestamp $count_at
     * @return boolean
     */
    public static function count_bill_by_receive_money($salon_id,$merchant_id,$money,$remark="臭美券消费",$count_at="0000-00-00 00:00:00")
    {
        return self::count_bill($salon_id, $merchant_id, $money, "spend_money",$remark,$count_at);
    }
    
  
    /**
     * 结算佣金
     * @param int $salon_id
     * @param int $merchant_id
     * @param float $money
     * @param string $remark
     * @param datetime|timestamp $count_at
     * @return boolean
     */
    public static function count_bill_by_commission_money($salon_id,$merchant_id,$money,$remark="",$count_at="0000-00-00 00:00:00")
    {
        return self::count_bill($salon_id, $merchant_id, $money, "commission_money",$remark,$count_at);
    }
    
    /**
     * 结算佣金返还
     * @param int $salon_id
     * @param int $merchant_id
     * @param float $money
     * @param string $remark
     * @param datetime|timestamp $count_at
     * @return boolean
     */
    public static function count_bill_by_commission_return_money($salon_id,$merchant_id,$money,$remark="",$count_at="0000-00-00 00:00:00")
    {
        return self::count_bill($salon_id, $merchant_id, $money, "commission_return_money",$remark,$count_at);
    }
    
    /**
     * 结算投资款
     * @param unknown $salon_id
     * @param unknown $merchant_id
     * @param unknown $money
     */
    public static function count_bill_by_invest_money($salon_id,$merchant_id,$money)
    {
        return self::count_bill($salon_id, $merchant_id, $money, "invest_money");
    }
    
    /**
     * 结算投资款(返还)
     * @param unknown $salon_id
     * @param unknown $merchant_id
     * @param unknown $money
     */
    public static function count_bill_by_invest_return_money($salon_id,$merchant_id,$money)
    {
        return self::count_bill($salon_id, $merchant_id, $money, "invest_return_money");
    }
    
//     public static function mergeMoney($attrs,$model)
//     {
//         if(isset($attrs['pay_money']) && isset($attrs['cost_money']))
//         {
//             $attrs['pay_money'] = floatval($model->pay_money) + floatval($attrs['pay_money']);
//             $attrs['cost_money'] = floatval($model->cost_money) + floatval($attrs['cost_money']);
//             $attrs['spend_money'] = floatval($model->spend_money);
//             $attrs['balance_money'] = $attrs['cost_money'] - $attrs['spend_money'];
//         }
        
//         if(isset($attrs['invest_money']))
//         {
//             $attrs['invest_money'] = floatval($model->invest_money) + floatval($attrs['invest_money']);
//             $attrs['invest_return_money'] = floatval($model->invest_return_money);
//             $attrs['invest_balance_money'] = $attrs['invest_money'] - $attrs['invest_return_money'];;
//         }
        
//         if(isset($attrs['borrow_money']))
//         {
//             $attrs['borrow_money'] = floatval($model->borrow_money) + floatval($attrs['borrow_money']);
//             $attrs['borrow_return_money'] = floatval($model->borrow_return_money);
//             $attrs['borrow_balance_money'] = $attrs['borrow_money'] - $attrs['borrow_return_money'];;
//         }        
//         return $attrs;
//     }
    
    /**
     * 重写 
     * @see \Illuminate\Database\Eloquent\Model::isFillable()
     */
    public function isFillable($key)
    {
       return true;
    }
}
