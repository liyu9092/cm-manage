<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class SalonUser extends Model {

	protected $table = 'salon_user';
	
	public $timestamps = false;
	
	/**
	 * 正常使用
	 * @var unknown
	 */
	CONST STATUS_UP = 1;
	
	/**
	 * 停用
	 * @var unknown
	 */
	CONST STATUS_STOP = 2;
	
	/**
	 * 删除
	 * @var unknown
	 */
	CONST STATUS_DEl = 3;
	
	/**
	 * 设置超级管理员账户
	 *
	 * */
	public static function setAdminAccount($merchantId)
	{
		$userId = 0;
		$salonAccount = self::where(array('merchantId'=>$merchantId,'roleType'=>2,'status'=>self::STATUS_UP))->select(array('salon_user_id'))->first();
		if($salonAccount)
			$userId = $salonAccount->salon_user_id;
	
		return $userId;
	
	}

}
