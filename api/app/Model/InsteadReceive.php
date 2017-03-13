<?php
/**
 * 代收单相关
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class InsteadReceive extends Model
{
    /**
     * 项目消费
     * @var unknown
     */
    CONST TYPE_OF_ORDER = 1;
    
    protected $table = 'instead_receive';
    
    public function merchant(){
        return $this->belongsTo(Merchant::class);
    }
    
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
    
    /**
     * 生成新的一条单号
     */
    public static function getNewCode()
    {     
        $prefix ="DS-";
        $prefix .=date("ymdHis");
        $class = __CLASS__;
        $obj = new $class;
        $count = $obj->where("code","like",$prefix."%")->count();
        $now_num = intval($count) + 1;
        return $prefix.str_pad(strval($now_num), 3,"0",STR_PAD_LEFT);
    }
    
    
    /**
     * 重写  免得蛋疼
     * @see \Illuminate\Database\Eloquent\Model::isFillable()
     */
    public function isFillable($key)
    {
        return true;
    }
}
