<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis as Redis;
use App\Salon;
use App\Order;

class CommissionLog extends Model {

	protected $table = 'commission_log';

	protected $fillable = ['id', 'ordersn','type','salonid', 'amount', 'created_at', 'updated_at','rate','grade','note'];

    public function salon(){
        return $this->belongsTo('App\Salon');
    }    
    
	public static function getSn(){
		$redis = Redis::connection();
		$key = 'YJ-'.date('ymd');
		if($redis->get($key)==FALSE)
			$redis->setex($key,3600*24,0);
		$sn = $redis->incr($key);
		$sn = str_pad($sn, 5,'0',STR_PAD_LEFT);
		$sn = $key.$sn;
		return $sn;
	}

	public function getSalonid($sn){
		$salon = Salon::where('sn',$sn)->select('salonid')->first();
		if($salon)
			return $salon->salonid;
		return 0;
	}

	public static function getQueryByParam($param){
		$query = Order::join('salon', 'salon.salonid', '=', 'order.salonid');
		$query = $query->join('commission', 'commission.ordersn', '=', 'order.ordersn');
		//商户名筛选
		if(isset($param['merchantname'])&&$param['merchantname']){
			$query = $query->join('merchant', 'merchant.id', '=', 'salon.merchantId');
			$query = $query->where('merchant.name','like','%'.$param['merchantname'].'%');
		}	

		//店铺名筛选
		if(isset($param['salonname'])&&$param['salonname']){
			$query = $query->where('salonname','like','%'.$param['salonname'].'%');
		}		

		//店铺编号筛选
		if(isset($param['salonsn'])&&$param['salonsn']){
			$query = $query->where('salon.sn','like','%'.$param['salonsn'].'%');
		}

		//起始时间
		if(isset($param['start'])&&$param['start']){
			$query = $query->where('created_at','>=',$param['start']);
		}

		//结束时间
		if(isset($param['end'])&&$param['end']){
			$query = $query->where('created_at','<',date('Y-m-d',strtotime('+1 day',strtotime($param['end']))));
		}

		//排序
		if(isset($param['sort_key'])&&$param['sort_key']){
			$param['sort_type'] = empty($param['sort_type'])?'DESC':$param['sort_type'];
			$query = $query->orderBy($param['sort_key'],$param['sort_type']);
		}
		return $query;
	}

}
