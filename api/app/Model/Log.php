<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Log extends Model {

	protected $table = 'management_log';

    protected $fillable = ['username', 'roles', 'operation', 'slug', 'object', 'ip'];

    public static function getQueryByParam($param){
    	$query = Self::getQuery();

		//操作对象
		if(isset($param['object'])&&$param['object']){
			$query = $query->where('object','=',$param['object']);
		}

		//起始时间
		if(isset($param['start'])&&$param['start']){
			$query = $query->where('created_at','>=',$param['start']);
		}

		//结束时间
		if(isset($param['end'])&&$param['end']){
			$query = $query->where('created_at','<',date('Y-m-d',strtotime('+1 day',strtotime($param['end']))));
		}

		if(isset($param['username'])&&$param['username']){
			$keyword = '%'.$param['username'].'%';
			$query = $query->where('username','like',$keyword);
		}

		$query = $query->orderBy('id','desc');
		return $query;
    }

}
