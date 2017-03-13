<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model {

	protected $table = 'permissions';

	protected $hidden = ['pivot'];

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['inherit_id', 'title', 'slug', 'status', 'description', 'note'];

	public static function getQueryByParam($param=[]){
		$query = Permission::getQuery();

		//状态筛选
		if(isset($param['status'])&&$param['status']){
			$query = $query->where('status','=',$param['status']);
		}

		//起始时间
		if(isset($param['start'])&&$param['start']){
			$query = $query->where('created_at','>=',$param['start']);
		}

		//结束时间
		if(isset($param['end'])&&$param['end']){
			$query = $query->where('created_at','<',date('Y-m-d',strtotime('+1 day',strtotime($param['end']))));
		}

		if(isset($param['keyword'])&&$param['keyword']){
			$keyword = '%'.$param['keyword'].'%';
			$query = $query->where('title','like',$keyword);
		}

        //排序
    	$sort_key = empty($param['sort_key'])?'id':$param['sort_key'];
    	$sort_type = empty($param['sort_type'])?'DESC':$param['sort_type'];
        $query = $query->orderBy($sort_key,$sort_type);

		return $query;
	}
}
