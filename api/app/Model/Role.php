<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {

    /**
     * The attributes that are fillable via mass assignment.
     *
     * @var array
     */
    protected $fillable = ['name', 'slug', 'description', 'department_id', 'city_id', 'status', 'note'];

	protected $table = 'roles';

	protected $hidden = ['pivot'];

	public function users(){
		return $this->belongsToMany('App\Manager');
	}

    public function city(){
        return $this->belongsTo('App\City','city_id','iid');
    }

	public function department(){
		return $this->belongsTo('App\Department');
	}

	public function permissions(){
		return $this->belongsToMany('App\Permission');
	}

	public static function getQueryByParam($param=[]){
		$query = Self::with(['department'=>function($q){
			$q->lists('id','title');
		}]);

        $query = $query->with(['city'=>function($q){
            $q->lists('iid','iname');
        }]);     

		//所属部门筛选
		if(isset($param['department_id'])&&$param['department_id']){
			$query = $query->where('department_id','=',$param['department_id']);
		}

		//状态筛选
		if(isset($param['status'])&&$param['status']){
			$query = $query->where('status','=',$param['status']);
		}

		//所属城市
		if(isset($param['city_id'])&&$param['city_id']){
			$query = $query->where('city_id','=',$param['city_id']);
		}

		//起始时间
		if(isset($param['start'])&&$param['start']){
			$query = $query->where('created_at','>=',$param['start']);
		}

		//结束时间
		if(isset($param['end'])&&$param['end']){
			$query = $query->where('created_at','<',date('Y-m-d',strtotime('+1 day',strtotime($param['end']))));
		}

		//隐藏超级管理员
		$query = $query->where('id','<>',1);
		
        //排序
    	$sort_key = empty($param['sort_key'])?'id':$param['sort_key'];
    	$sort_type = empty($param['sort_type'])?'DESC':$param['sort_type'];
        $query = $query->orderBy($sort_key,$sort_type);

		if(isset($param['keyword'])&&$param['keyword']){
			$keyword = '%'.$param['keyword'].'%';
			$query = $query->where('name','like',$keyword);
		}
		return $query;
	}
}
