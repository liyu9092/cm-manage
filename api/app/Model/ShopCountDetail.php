<?php
/**
 * 商铺往来结算明细
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
class ShopCountDetail extends Model
{
    protected $table = 'shop_count_detail';
    
    /**
     * 订单结算
     * @var int
     */
    CONST TYPE_OF_ORDER = 1;
    
    /**
     * 赏金单结算
     * @var int
     */
    CONST TYPE_OF_BOUNTY = 2;
    
    /**
     * 预付单 结算
     * @var int
     */
    CONST STATE_OF_PREPAY = 3;
    
    /**
     * 预付单 返还
     * @var int
     */
    CONST STATE_OF_PREPAY_REFUND = 4;
    
    /**
     * 重写  免得蛋疼
     * @see \Illuminate\Database\Eloquent\Model::isFillable()
     */
    public function isFillable($key)
    {
        return true;
    }
}

?>