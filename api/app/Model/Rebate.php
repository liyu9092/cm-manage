<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis as Redis;
use App\Salon;

class Rebate extends Model {

	protected $table = 'rebate';

	protected $fillable = ['salon_id', 'author', 'sn', 'amount', 'note', 'status', 'start_at', 'end_at', 'confirm_at','confirm_by','created_at','created_by','updated_at'];

	//获取query对象
	public static function getQueryByParam($param=[]){
		$query = Self::join('salon', 'salon.salonid', '=', 'rebate.salon_id')
				->join('merchant', 'merchant.id', '=', 'salon.merchantid');
		//商户名筛选
		if(isset($param['merchantname'])&&$param['merchantname']){
			$query = $query->where('merchant.name', 'like', '%' . $param['merchantname'] .'%');
		}	

		//店铺名筛选
		if(isset($param['salonname'])&&$param['salonname']){
			$query = $query->where('salon.salonname','like','%'.$param['salonname'].'%');
		}		

		//店铺编号筛选
		if(isset($param['salonsn'])&&$param['salonsn']){
			$query = $query->where('salon.sn','like','%'.$param['salonsn'].'%');
		}

		//起始时间
		if(isset($param['start'])&&$param['start']){
			$query = $query->where('start_at','>=',$param['start']);
		}

		//结束时间
		if(isset($param['end'])&&$param['end']){
			$query = $query->where('end_at','<',date('Y-m-d',strtotime('+1 day',strtotime($param['end']))));
		}

		//排序
    	$sort_key = empty($param['sort_key'])?'created_at':$param['sort_key'];
    	$sort_type = empty($param['sort_type'])?'DESC':$param['sort_type'];
        $query = $query->orderBy($sort_key,$sort_type);

		return $query;
	}

    public function salon(){
        return $this->belongsTo('App\Salon');
    }    

	public function getSn(){
		$redis = Redis::connection();
		$key = 'FY-'.date('ymd');
		if($redis->get($key)==FALSE)
			$redis->setex($key,3600*24,0);
		$sn = $redis->incr($key);
		$sn = str_pad($sn, 5,'0',STR_PAD_LEFT);
		$sn = $key.$sn;
		return $sn;
	}

	public function getName(){
		$redis = Redis::connection();
		$key = 'rebate-'.date('ymd');
		if($redis->get($key)==FALSE)
			$redis->setex($key,3600*24,0);
		$name = $redis->incr($key);
		$name = str_pad($name, 3,'0',STR_PAD_LEFT);
		return 'rebate'.$name;
	}

	public function getSalonid($sn){
		$salon = Salon::where('sn',$sn)->select('salonid')->first();
		if($salon)
			return $salon->salonid;
		return 0;
	}

}
