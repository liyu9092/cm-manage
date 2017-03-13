<?php namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Kodeine\Acl\Traits\HasRole;
use App\Permission;
use Illuminate\Support\Facades\Redis as Redis;
use JWTAuth;

class Manager extends Model implements AuthenticatableContract, CanResetPasswordContract {

	use Authenticatable, CanResetPassword, HasRole;

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'managers';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['username', 'name', 'tel', 'department_id', 'position_id', 'city_id', 'email', 'password', 'status'];

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = ['password', 'remember_token'];


	public function roles(){
		return $this->belongsToMany('App\Role');
	}


    public function department(){
        return $this->belongsTo('App\Department');
    }    

    public function city(){
        return $this->belongsTo('App\City','city_id','iid');
    }

    public function position(){
        return $this->belongsTo('App\Position');
    }

 	/**
     * Users can have many permissions overridden from permissions.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function permissions()
    {

        return $this->belongsToMany('App\Permission')->withTimestamps();
    }

    /**
     * Get all user permissions including
     * user all role permissions.
     *
     * @return array|null
     */
    public function getPermissions()
    {
        //先从redis里读取
        $redis = Redis::connection();
        $token = JWTAuth::getToken();
        if($permissions = $redis->get('permissions:'.$token))
            return unserialize($permissions);

        $permissions = [];
        foreach ($this->roles as $role) {
        	if($role->status!=1)
        		continue;
            if($role->id==1)
            {
                $permissions = Permission::where('status',1)->lists('slug')->toArray();
                break;
            }
                
            foreach ($role->permissions->toArray() as $permission) {
                if($permission['status']==1)
                    $permissions[] = $permission['slug'];  
            }
        }
        $redis->set('permissions:'.$token,serialize($permissions));
        return $permissions;
    }

    /**
     * Check if User has the given permission.
     *
     * @param  string $permission
     * @param  string $operator
     * @return bool
     */
    public function can($permission, $operator = null)
    {
        // user permissions including
        // all of user role permissions
        $permissions = $this->getPermissions();

        return in_array($permission, $permissions);
    }

    public static function getQueryByParam($param=[]){
        $query = Self::with(['roles'=>function($q){
            $q->lists('role_id','name');
        }]);

        $query = $query->with(['department'=>function($q){
            $q->lists('id','title');
        }]);

        $query = $query->with(['city'=>function($q){
            $q->lists('iid','iname');
        }]);        

        $query = $query->with(['position'=>function($q){
            $q->lists('id','title');
        }]);

        //角色筛选
        if(isset($param['role_id'])&&$param['role_id']){
            $ids = RoleManager::where('role_id','=',$param['role_id'])->get(['user_id'])->toArray();
            $ids = array_values($ids);
            $query =Manager::whereHas('roles',function($q) use($param){
                $q->where('role_id','=',$param['role_id']);
            });
        }

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
        //登录帐号筛选
        if(isset($param['username'])&&$param['username']){
            $keyword = '%'.$param['username'].'%';
            $query = $query->where('username','like',$keyword);
        }       
        //姓名筛选
        if(isset($param['name'])&&$param['name']){
            $keyword = '%'.$param['name'].'%';
            $query = $query->where('name','like',$keyword);
        }
        //角色名筛选
        if(isset($param['role'])&&$param['role']){
            $keyword = '%'.$param['role'].'%';
            $query = $query->whereHas('roles',function($q) use($keyword){
                $q->where('name','like',$keyword);
            });
        }

        //隐藏超级管理员
        $query = $query->where('id','>',1);

        //排序
        $sort_key = empty($param['sort_key'])?'id':$param['sort_key'];
        $sort_type = empty($param['sort_type'])?'DESC':$param['sort_type'];
        $query = $query->orderBy($sort_key,$sort_type);

        return $query;
    }

    public static function getBaseInfo($id)
    {
        $base = Manager::where('id',$id)->first(['id','name']);
        if(empty($base))
        {
            return null;
        }
        return $base->toArray();
    }
    
    public static function getBaseInfos($ids)
    {
        $bases = Manager::whereIn('id',$ids)->get(['id','name'])->toArray();
        $bases_idx = Utils::column_to_key('id',$bases);
        $res = [];
        foreach ($ids as $id)
        {
            if(isset($bases_idx[$id]))
            {
                $res[$id] = $bases_idx[$id];
            }
            else 
            {
                $res[$id] = null;
            }
        }
        return $res;
    }
}
