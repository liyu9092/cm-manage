<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis as Redis;
class Merchant extends Model {

	protected $table = 'merchant';
	
	public $timestamps = false;
	
	protected $fillable = ['id', 'sn','name','contact','mobile','phone','email','addr','foundingDate','salonNum','addTime' ];
	

	/**
	 * 查询商户名
	 * */
	public static  function getMerchantName($id)
	{
		$merchantRs = self::select(['name'])->where(['id'=>$id])->first();
		return $merchantRs->name;
	}
	
	/**
	 * 检测商户编号是否存在
	 *
	 * */
	public static  function getCheckSn($sn,$id=0)
	{
		$query = self::getQuery();
		$query->where('sn',$sn);
		if($id)
		{
			$query->where('id',$id);
		}
		return  $query->count();
	}
	
	/**
	 * 生成商户编号 SZ0001
	 * +20避免和之前1.3.0手动输入的编号冲突 
	 * */
	public static  function addMerchantSn($addNums = 20)
	{
		$redisKey = 'SZ';
		$value = Redis::hget('merchantSn',$redisKey);
		$value += 1;
		if($value <= 1)
		{
			$lastId = self::select(['id'])->orderBy('id', 'desc')->first();
			$value = $lastId->id;
		}
		Redis::hset('merchantSn',$redisKey,$value);
		$sn = intval($value)+$addNums;   
		$tps = "";
		for($i=4;$i>strlen($sn);$i--)
		{
			$tps .= 0;
		}
		$tmpSn = 'SZ'.$tps.$sn;
		$num = self::getCheckSn($tmpSn);
		if($num)
		{
			$twoNums = $addNums+1;
			$tmpSn = self:: addMerchantSn($addNums);
		}
		return $tmpSn;
	}

	public static function getSn(){
		$count = Self::count();
		$count++;
		return str_pad($count, 5, 0);
	}



}
