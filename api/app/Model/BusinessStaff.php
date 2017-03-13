<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis as Redis;
use DB;

class BusinessStaff extends Model {

	protected $table = 'business_staff';
	
	public $timestamps = false;
	
	CONST REDIS_KEY = 'business_staff_salon';
	
	/*
	 * 获取业务代表
	 * */
	public static  function getBusinessStaff()
	{
		$rkey = self::REDIS_KEY;
		$result = Redis::hget($rkey,'list');
		if(!$result)
		{
			$sql = "SELECT id,businessName from cm_business_staff ORDER BY CONVERT( businessName USING gbk ) COLLATE gbk_chinese_ci ASC";
			$result = DB::select($sql);
			foreach ($result as $key=>$val)
			{
				Redis::hset($rkey,$val->id,$val->businessName);
				$rs[$key]['id'] = $val->id;
				$rs[$key]['businessName'] = $val->businessName;
			}
			Redis::hset($rkey,'list',json_encode($rs));
			Redis::EXPIREAT($rkey,strtotime(date('Y-m-d').'23:59:59'));//当天过期
			return $rs;
		}
		else
		{
			return json_decode($result,true);
		}
	}
	
	/*
	 * 通过id 获取业务代表名
	 * */
	public  static function getBusinessNameById($id)
	{
		if(!$id) return '';
		$key = self::REDIS_KEY;
		$result = Redis::hget($key,$id);
		if(!$result)
		{
			self::getBusinessStaff();
			$result = Redis::hget($key,$id);
		}
		return $result;
	}
	

}
