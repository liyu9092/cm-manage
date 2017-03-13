<?php
/**
 * 商铺往来结算明细
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class ShopCountLog extends Model
{
    public $timestamps = false;
    protected $table = 'shop_count_log';
    
    /**
     * 预付款
     * @var unknown
     */
    CONST TYPE_OF_PREPAY = 1;
    
    /**
     * 消费
     * @var unknown
     */
    CONST TYPE_OF_SPEND = 2;
        
    /**
     * 佣金
     * @var unknown
     */
    CONST TYPE_OF_COMMISSION = 3;
    
    /**
     * 佣金返还
     * @var unknown
     */
    CONST TYPE_OF_COMMISSION_RETURN = 4;
    
    /**
     * 添加记录
     * @param int $salon_id
     * @param int $type
     * @param float $money
     * @param datetime|timestamp $time
     * @param string $remark
     */
    public static function add_log($salon_id,$type,$money,$time,$remark="")
    {
        $change_money = $money;        
       
        if( $type == self::TYPE_OF_SPEND || $type == self::TYPE_OF_COMMISSION_RETURN)
        {
            $change_money = bcmul($change_money, -1,2);
        }
        $change_money =floatval($change_money);
        if(is_numeric($time))
        {
            $time = date("Y-m-d H:i:s",intval($time));
        }  
        $update_method = "decrement";
        if($change_money >= 0)
        {
            $update_method == "increment";
        }
        
        $record = ['salon_id'=>$salon_id,
            'type'=>$type,
            'money'=>$money,
           // 'balance_money'=>$balance_money,
            'count_at'=>$time,
            'remark'=>$remark,
            'created_at'=>date("Y-m-d H:i:s")           
        ];
        
        
        //之前的信息
        
       // DB::beginTransaction();
        $query = self::where('salon_id',$salon_id);
        $query->useWritePdo();
        $model = $query->where("count_at","<",$time)->select("balance_money")->orderBy('count_at','DESC')->orderBy('id','ASC')->first();
        $balance_money = 0;
        if(!empty($model))
        {
            $balance_money = floatval($model->balance_money) ;
        }
             
        //插入记录
        $id = self::insertGetId([
            'salon_id'=>$salon_id,
            'type'=>$type,
            'money'=>$money,
            'balance_money'=>$balance_money,
            'count_at'=>$time,
            'remark'=>$remark,
            'created_at'=>date("Y-m-d H:i:s")
        ]);
        
        self::count_after($salon_id,$time,$balance_money);
        //更新本条记录之后的余额信息
        //self::where('salon_id',$salon_id)->where("id","<",$id)->where("count_at",">=",$time)->{$update_method}("balance_money",abs($money));
        //DB::commit();
        
    }
    
    public static function count_after($salon_id,$count_at,$last_balance)
    {
        $query = self::select(['id','type','money']);
        $query->useWritePdo();
        $items = $query->where('salon_id',$salon_id)->where('count_at',">=",$count_at)->orderBy('count_at','ASC')->orderBy('id','DESC')->get()->toArray();
        foreach ($items as $item)
        {
            $id = $item['id'];
            $type = intval($item['type']);
            $money = floatval($item['money']);
            $change_money = $money;
            if($type == ShopCountLog::TYPE_OF_COMMISSION_RETURN || $type == ShopCountLog::TYPE_OF_SPEND)
            {
                $change_money = bcmul($change_money,-1,2);
            }
            $last_balance = bcadd($last_balance,$change_money,2);
            ShopCountLog::where('id',$id)->update(['balance_money'=>$last_balance]);
        }
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
