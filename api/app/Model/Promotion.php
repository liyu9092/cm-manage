<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model {

	protected $table = 'promotion';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['title', 'type', 'sn', 'content', 'department_id', 'manager_id','scope_id','ticket_id','deleted_at'];

	public static function getQueryByParam($param=[]){
		$query = Permission::getQuery();

		//活动编码
		if(!empty($param['sn'])){
			$query = $query->where('sn','=',$param['sn']);
		}

		//标题
		if(!empty($param['title'])){
			$title = '%'.$param['title'].'%';
			$query = $query->where('title','like',$title);
		}

		//状态
		if(!empty($param['status'])){
			$query = $query->where('status','=',$param['status']);
		}

		//部门
		if(!empty($param['department_id'])){
			$query = $query->where('department_id','=',$param['department_id']);
		}

		//起始时间
		if(!empty($param['start_at'])){
			$query = $query->where('start_at','<=',$param['start_at']);
		}

		//结束时间
		if(!empty($param['end_at'])){
			$query = $query->where('end_at','<=',$param['end_at']);
		}

		$query = $query->leftJoin('departments','departments.id','=','promotion.department_id');

		return $query;
	}
}
