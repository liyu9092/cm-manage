<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model {

	protected $table = 'message';
	
	public $timestamps = false;

	/**
	 * 添加修改 操作
	 * */
	public static function dosave($save,$id = 0,$user = 0)
	{
		$query = self::getQuery();
	
		if($id)
		{
			$save['upTime'] = time();
			$status = $query->where('id',$id)->update($save);
		}
		else
		{
			$save['addTime'] = time();
			$status = $query->insertGetId($save);
		}
		return $status;
	}
}
