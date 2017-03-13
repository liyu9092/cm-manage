<?php namespace App\Http\Controllers;

use App\Manager;
use App\City;
use App\Department;
use App\Position;
use App\Permission;

class ListController extends Controller{


	/**
	 * @api {post} /list/city 1.获取城市列表
	 * @apiName city
	 * @apiGroup List
	 *
	 *
	 * @apiSuccess {Array} city 返回城市列表数组.
	 *
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": [
	 *	        {
	 *	            "id": 4,
	 *	            "title": "北京"
	 *	        },
	 *	        {
	 *	            "id": 2,
	 *	            "title": "广州"
	 *	        },
	 *	        {
	 *	            "id": 3,
	 *	            "title": "武汉"
	 *	        },
	 *	        {
	 *	            "id": 1,
	 *	            "title": "深圳"
	 *	        }
	 *	    ]
	 *	}
	 *
	 */
	public function city(){
		$result = City::select(['iid','iname'])->get();
		return $this->success($result);
	}



	/**
	 * @api {post} /list/department 2.获取部门列表
	 * @apiName department
	 * @apiGroup List
	 *
	 *
	 * @apiSuccess {Array} department 返回部门列表数组.
	 *
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": [
	 *	        {
	 *	            "id": 1,
	 *	            "title": "产品部"
	 *	        }
	 *	    ]
	 *	}
	 *
	 */
	public function department(){
		$result = Department::select(['id','title'])->get();
		return $this->success($result);
	}


	/**
	 * @api {post} /list/position/:id 3.获取职位列表
	 * @apiName position
	 * @apiGroup List
	 *
	 * @apiParam {Number} id 部门id
	 * 
	 * @apiSuccess {Array} position 返回职位列表数组.
	 *
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": [
	 *	        {
	 *	            "id": 2,
	 *	            "title": "Andorid"
	 *	        },
	 *	        {
	 *	            "id": 4,
	 *	            "title": "Html5"
	 *	        },
	 *	        {
	 *	            "id": 3,
	 *	            "title": "IOS"
	 *	        },
	 *	        {
	 *	            "id": 1,
	 *	            "title": "PHP"
	 *	        }
	 *	    ]
	 *	}
	 *
	 */
	public function position(){
		$param = $this->param;
		$query = Position::getQuery();
		if(!empty($param['id']))
		$query = $query->where('department_id',$param['id']);
		$result = $query->select(['id','title'])->get();
		return $this->success($result);
	}


	/**
	 * @api {post} /list/permission 4.获取权限列表
	 * @apiName permission
	 * @apiGroup List
	 *
	 *
	 * @apiSuccess {Array} permission 返回权限列表数组.
	 * @apiSuccess {String} inherit_id 继承于.
	 * @apiSuccess {String} title 标题.
	 * @apiSuccess {String} slug 路径.
	 * @apiSuccess {Number} sort 排序.
	 * 
	 * 
	 *
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": [
	 *	        {
	 *	            "id": 10,
	 *	            "inherit_id": 11
	 *	            "title": "dddd",
	 *	            "slug":"user.update",
	 *	            "sort": 1
	 *	        },
	 *	        {
	 *	            "id": 11,
	 *	            "inherit_id": 0
	 *	            "title": "ddddsssss",
	 *	            "slug":"user.show",
	 *	            "sort": 2
	 *	        }
	 *	    ]
	 *	}
	 *
	 */
	public function permission(){
		$result = Permission::select(['id','inherit_id','title','slug','sort'])->get();
		$result = $result->toArray();
		$result = $this->array_multiuniue($result);
		$result = $this->tree($result);
		return $this->success($result);
	}



	/**
	 * @api {post} /list/permission 5.获取用户菜单
	 * @apiName menu
	 * @apiGroup List
	 *
	 *
	 * @apiSuccess {number} id 权限id.
	 * @apiSuccess {number} inherit_id 继承于.
	 * @apiSuccess {String} title 权限标题.
	 * @apiSuccess {String} slug 权限路由.
	 * @apiSuccess {Number} sort 排序.
	 * @apiSuccess {String} show 是否作为菜单显示.
	 * 
	 * 
	 *
	 * @apiSuccessExample Success-Response:
	 *
	 * 	{
	 *	    "result": 1,
	 *	    "data": [
	 *	       {
	 *		        "id": 2,
	 *		        "inherit_id": 1,
	 *		        "title": "查看用户信息",
	 *		        "slug": "user.create",
	 *		        "sort": 2,
	 *		        'show': "1"
	 *		    }
	 *	    ]
	 *	}
	 *	
	 *
	 */
	public function menu(){
		$user = $this->user;
		if(!$user)
			return $this->success([]);
        $permissions = [];
        foreach ($user->roles as $role) {
        	if($role->status!=1)
        		continue;
        	$query = $role->permissions();
        	$id = 'permission_id as id';
        	if($role->id==1){
        		$query = Permission::getQuery();
        		$id = 'id';
        	}		
            foreach ($query->select([$id,'inherit_id','title','slug','sort','show'])->where('status',1)->orderBy('sort','desc')->get() as $permission) {
                $permissions[] = $permission;  
            }
        }
        $permissions = $this->array_multiuniue($permissions);
        $permissions = $this->tree($permissions);
        return $this->success($permissions);
	}


}
?>