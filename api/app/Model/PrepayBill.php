<?php
/**
 * 转付单相关
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class PrepayBill extends Model
{
    /**
     * 预付交易款类型
     * @var int
     */
    CONST TYPE_OF_PREPAY = 1;
    
    /**
     * 付代收交易款
     * @var int
     */
    CONST TYPE_OF_ALREADYPAY = 2;
    
    /**
     * 交易款返还
     * @var int
     */
    CONST TYPE_OF_RETURN = 3;
    
    /**
     * 状态  预览状态  已经不用   2015-08-24
     * @var int
     */
    CONST STATE_OF_PREVIEW = 0;
    
    /**
     * 状态  已付款状态
     * @var int
     */
    CONST STATE_OF_COMPLETED = 1;
    
    /**
     * 状态  待提交
     * @var int
     */
    CONST STATE_OF_TO_SUBMIT = 2;
    
    /**
     * 状态 待审批
     * @var int
     */
    CONST STATE_OF_TO_CHECK = 3;
    
    /**
     * 状态  带付款
     * @var int
     */
    CONST STATE_OF_TO_PAY = 4;
    
    protected $table = 'prepay_bill';
    
    public function merchant(){           
        return $this->belongsTo(Merchant::class);
    }
    
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
    
    public function user()
    {
        return $this->belongsTo(Manager::class,'uid');
    }

    public static function makeCompleted($params)
    {
        if (! isset($params['other_id']) || // 三方id
            ! isset($params['other_code']) || // 三方code
            ! isset($params['salon_id']) || 
           // ! isset($params['merchant_id']) || 
            ! isset($params['type']) || // 转付单类型
            ! isset($params['uid']) || // 制单人
            ! isset($params['pay_money']) || //  金额
            ! isset($params['pay_type']) || // 支付方式
            ! isset($params['pay_day']) || // 实际付款日期
            ! isset($params['remark']) // 备注
         )
        {
            return false;
        }

        $code = self::getNewCode($params['type']);
        $now_date = date("Y-m-d H:i:s");
        $record = [
            'code'=>$code,
            'salon_id'  => $params['salon_id'],
            //'merchant_id'  => $params['merchant_id'],
            'other_id'  => $params['other_id'],
            'other_code'  => $params['other_code'],
            'type'  => $params['type'],
            'uid'  => $params['uid'],
            'pay_money'  => $params['pay_money'],
            'pay_type'  => $params['pay_type'],
            'state'  => self::STATE_OF_COMPLETED,
            'pay_day'  => $params['pay_day'],
            'remark'  => $params['remark'],
            'created_at' => $now_date,
            'updated_at' => $now_date,
        ];
        $id = self::insertGetId($record);
        
        //结算
//         $count_at = $now_date;
//         if(isset($params['count_at']))
//         {
//             $count_at = $params['count_at'];
//         }
//         ShopCount::count_bill_by_pay_money($params['salon_id'], $params['merchant_id'],  $params['pay_money'],$remark,$count_at);
        
        return ['id'=>$id,'code'=>$code]; 
    }
    
    /**
     * 通过收款单生成转付单返还
     */
    public static function makeReturn($params)
    {
        if( !isset($params['id']) ||//三方id
            !isset($params['code']) ||//三方code
            !isset($params['salon_id']) ||
           // !isset($params['merchant_id']) ||
            !isset($params['type']) ||
            !isset($params['money']) ||//金额
            !isset($params['receive_type']) ||//支付方式
            !isset($params['receive_day']) ||//实际付款日期
            !isset($params['cash_uid'])    ||//确认人
            !isset($params['make_uid']) ||//制单人
            !isset($params['make_at']) ||//创建日期
            !isset($params['remark']) // 备注
        )
        {
            return false;
        }        
        $record = [
            'salon_id'  => $params['salon_id'],
           // 'merchant_id'  => $params['merchant_id'],
            'other_id'  => $params['id'],
            'other_code'  => $params['code'],
            'type'  => $params['type'],
            'uid'  => $params['make_uid'],
            'pay_money'  => $params['money'],
            'pay_type'  => $params['receive_type'],
            'pay_day'  => $params['receive_day'],
            'remark'  => $params['remark'],
       ];        
       $res = self::makeCompleted($record);
       $remark = "预付款返还";
       if($params['type'] != self::TYPE_OF_RETURN)
       {
           $remark = "预付保证金";
       }
       $count_at = date("Y-m-d H:i:s");
       ShopCount::count_bill_by_pay_money($params['salon_id'], NULL,  $params['money'],$remark,$count_at);
       return $res;     
    }
    
    /**
     * 生成新的一条单号
     */
    public static function getNewCode($type)
    {
        $type = intval($type);
        $prefix = "YF-";
        switch ($type) {
            case self::TYPE_OF_ALREADYPAY:
                $prefix = "FDS-";
                break;
            case self::TYPE_OF_RETURN:
                $prefix = "YFFH-";
                break;
            default:
                $prefix = "YF-";
        }
        $prefix .=date("ymdHis");
        
        $class = __CLASS__;
        $obj = new $class;
       
        $count = $obj->where("code","like",$prefix."%")->count();
        $now_num = intval($count) + 1;
        return $prefix.str_pad(strval($now_num), 3,"0",STR_PAD_LEFT);
    }
    
}
