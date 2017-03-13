<?php

/**
 * 提现相关
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;


class SalonMoneyWithdraw extends Model
{
    /**
     * 状态 审批不通过 (待提交)
     * @var unknown
     */
    const STATE_OF_TO_SUBMIT = 1;
    
    
    /**
     * 状态 待审批
     * @var unknown
     */
    const STATE_OF_TO_CHECK = 2;
    
    /**
     * 状态 待付款
     * @var unknown
     */
    const STATE_OF_TO_PAY = 3;
    
    /**
     * 状态 已付款
     * @var unknown
     */
    const STATE_OF_PAIED = 4;
    
    /**
     * 每日最大提取次数
     * @var unknown
     */
    const EXTRACT_LIMIT_OF_DAY =1 ;
    
    protected $table = 'salon_money_withdraw';
    
    
    /**
     * 获取店铺被占用的金额
     */
    public static function getBookedMoney($salon_id)
    {
        $base = self::selectRaw("SUM(`money`) as `money`")->where('salon_id',$salon_id)->whereIn('state',[self::STATE_OF_TO_CHECK,self::STATE_OF_TO_PAY])->first();
        if(empty($base))
        {
            return 0;
        }
        return floatval($base->money);
    }
    
    public function isFillable($key)
    {
        return true;
    }
}
