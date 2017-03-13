<?php namespace App\Http\Controllers;

use App\Manager;
use App\RoleUser;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use Kodeine\Acl\Models\Eloquent\Role;
use Kodeine\Acl\Models\Eloquent\Permission;
use Event;
use Excel;
use Auth;

class SelfController extends Controller{

	/**
	 * @api {post} /self/show 1.查看个人信息
	 * @apiName show
	 * @apiGroup Self
	 *
	 *
	 * @apiSuccess {Number} id 用户ID.
	 * @apiSuccess {String} username 用户名.
	 * @apiSuccess {String} name 用户姓名.
	 * @apiSuccess {String} tel 用户电话.
	 * @apiSuccess {String} department_id 所属部门.
	 * @apiSuccess {String} position_id 职位.
	 * @apiSuccess {String} city_id 所属城市.
	 * @apiSuccess {String} email email.
	 * @apiSuccess {Number} status 用户状态.1正常、2停用、3注销.
	 * @apiSuccess {String} created_at 创建时间.
	 * @apiSuccess {String} update_at 更新时间.
	 * @apiSuccess {Array} roles 用户角色.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "id": 1,
	 *	        "username": "soothion",
	 *	        "name": "老王",
	 *	        "tel": "18617185201",
	 * 	        "department_id": 1,
	 *	        "position_id": 1,
	 *	        "city_id": 1,
	 *	        "email": "soothion@sina.com",
	 *	        "result": "1",
	 *	        "created_at": "2015-05-07 14:15:00",
	 *	        "updated_at": "2015-05-11 07:18:23",
	 *	        "roles": [
	 *	            {
	 *	                "id": 1,
	 *	                "name": "test1sssssssssss",
	 *	                "slug": "administrator",
	 *	                "description": "manage administration privileges",
	 *	                "department_id": 1,
	 *	                "city_id": 1,
	 *	                "result": "1",
	 *	                "note": null,
	 *	                "created_at": "2015-05-05 06:23:43",
	 *	                "updated_at": "2015-05-11 07:15:28"
	 *	            }
	 *	        ]
	 *	    }
	 *	}
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function show()
	{
		$id = $this->user->id;
		$user = Manager::with('roles')->find($id);
		return $this->success($user);
	}

	/**
	 * @api {post} /self/update 3.更新个人信息
	 * @apiName update
	 * @apiGroup Self
	 *
	 * @apiParam {String} name 用户姓名.
	 * @apiParam {String} old_password 用户原密码.
	 * @apiParam {String} password 用户新密码.
	 * @apiParam {String} tel 用户电话.
	 * @apiParam {String} department_id 所属部门.
	 * @apiParam {String} position_id 职位.
	 * @apiParam {String} city_id 所属城市.
	 * @apiParam {String} email email.
	 * @apiParam {Number} status 用户状态.1正常、2停用、3注销.
	 * @apiParam {Array} roles 用户角色.
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": null
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "没有符合条件数据"
	 *		}
	 */
	public function update()
	{
		$param = $this->param;
		DB::beginTransaction();
		$user = $this->user;
		$update_role = 1;
		if(isset($param['password'])&&$param['password']){
			$param['password'] = bcrypt($param['password']);
			//当前用户修改自己密码需要提交原密码
			if (!Auth::attempt(array('username' => $user->username, 'password' => $this->param['old_password'])))
				return $this->error('原密码错误');
		}
		if(isset($param['roles'])){
			$roles = $param['roles'];
			unset($param['roles']);
			$update_role = $user->roles()->sync($roles);
		}
		$update_user = $user->update($param);
		if($update_role&&$update_user){
			DB::commit();
			//触发事件，写入日志
			$response = Event::fire('user.update',array($user));
			return $this->success();
		}
		else
		{
			DB::rollBack();
			return $this->error('用户更新失败');
		}

	}

}