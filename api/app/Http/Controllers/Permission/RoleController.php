<?php namespace App\Http\Controllers;

use App\Manager;
use App\RoleUser;
use App\Role;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use Event;
use Excel;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class RoleController extends Controller{
	/**
	 * @api {post} /role/index 1.角色列表
	 * @apiName list
	 * @apiGroup Role
	 *
	 * @apiParam {Number} department_id 可选,部门ID.
	 * @apiParam {Number} status 可选,用户状态.1正常、2停用、3删除.
	 * @apiParam {Number} city_id 可选,城市ID.
	 * @apiParam {String} start 可选,起始时间.
	 * @apiParam {String} end 可选,结束时间.
	 * @apiParam {String} keyword 可选,搜索关键字,匹配角色名.
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 *
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {String} name 角色名.
	 * @apiSuccess {Number} status 用户状态.1正常、2停用、3删除.
	 * @apiSuccess {String} department_id 所属部门.
	 * @apiSuccess {String} city_id 所属城市.
	 * @apiSuccess {String} created_at 创建时间.
	 * @apiSuccess {String} description  角色描述.
	 * @apiSuccess {Object} department  角色职位.
	 * @apiSuccess {Object} city  角色区域.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "total": 2,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 1,
	 *	        "from": 1,
	 *	        "to": 2,
	 *	        "data": [
	 *	            {
	 *	                "name": "管理员",
	 *	                "status": "1",
	 *	                "created_at": "2015-05-05 06:23:43",
	 *	                "description": "manage administration privileges",
	 *	                "department": {
	 *	                    "id": 1,
	 *	                    "title": "产品部"
	 *	                },
	 *	                "city": {
	 *	                    "id": 1,
	 *	                    "title": "深圳"
	 *	                }
	 *	            }
	 *	        ]
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function index()
	{
		$param = $this->param;
		$query = Role::getQueryByParam($param);
		
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
		$fields = array(
			    'id',
				'name',
				'status',
				'department_id',
				'city_id',
				'created_at',
				'description'
			);
		//分页
	    $result = $query->select($fields)->paginate($page_size)->toArray();
	    unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);

	}

	/**
	 * @api {post} /role/export 5.导出角色
	 * @apiName export
	 * @apiGroup Role
	 *
	 * @apiParam {Number} department_id 可选,部门ID.
	 * @apiParam {Number} status 可选,用户状态.1正常、2停用、3删除.
	 * @apiParam {Number} city_id 可选,城市ID.
	 * @apiParam {String} start 可选,起始时间.
	 * @apiParam {String} end 可选,结束时间.
	 * @apiParam {String} keyword 可选,搜索关键字,匹配角色名.
	 *
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function export()
	{
		$param = $this->param;
		$query = Role::getQueryByParam($param);
		
		$array = $query->get();
	    foreach ($array as $key => $value) {
	    	$result[$key]['id'] = $key+1;
	    	$result[$key]['name'] = $value->name;
	    	$result[$key]['status'] = $this->status($value->status);
	    	$result[$key]['department'] = $value->department->title;
	    	$result[$key]['city'] = $value->city->title;
	    	$result[$key]['created_at'] = $value->created_at;
	    	$result[$key]['description'] = $value->description;
	    }
		//触发事件，写入日志
	    Event::fire('role.export');
		
		//导出excel	   
		$title = '角色列表'.date('Ymd');
		$header = ['序号','角色姓名','角色状态','所属部门','所属城市'	,'添加时间','角色说明'];
		Excel::create($title, function($excel) use($result,$header){
		    $excel->sheet('Sheet1', function($sheet) use($result,$header){
			        $sheet->fromArray($result, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
	        		$sheet->prependRow(1, $header);//添加表头

			    });
		})->export('xls');

	}

	/**
	 * @api {post} /role/create 4.创建角色
	 * @apiName create
	 * @apiGroup Role
	 *
	 * @apiParam {String} name 必填,角色名称.
	 * @apiParam {String} department_id 所属部门.
	 * @apiParam {String} city_id 所属城市.
	 * @apiParam {Number} status 角色状态.
	 * @apiParam {String} description 角色状态.
	 * @apiParam {String} note 备注信息.
	 * @apiParam {Array} permission 角色权限.
	 *
	 * 
	 * @apiSuccess {String} status 请求状态.
	 * @apiSuccess {String} msg 提示信息.
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
	 *		    "msg": "角色更新失败"
	 *		}
	 */
	public function create()
	{
		$param = $this->param;
		if(Role::where('name','=',$param['name'])->first())
			throw new ApiException('角色已存在', ERROR::ROLE_EXIST);
		DB::beginTransaction();
		$role = Role::create($param);
		$permission = 1;
		if(isset($param['permissions'])){
			$permissions = $param['permissions'];
			unset($param['permissions']);
			$ermission = $role->permissions()->sync($permissions);
		}
		
		if($permission&&$role){
			DB::commit();
			//触发事件，写入日志
			Event::fire('role.create',array($role));
			return $this->success();
		}
		else
		{
			DB::rollBack();
			throw new ApiException('角色创建失败', ROLE_CREATE_FAILED);
		}
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{

	}

	/**
	 * @api {post} /role/show/:id 2.查看角色信息
	 * @apiName show
	 * @apiGroup Role
	 *
	 * @apiParam {Number} id 必填,角色ID.
	 * @apiSuccess {String} name 角色名.
	 * @apiSuccess {String} slug 保留字段.
	 * @apiSuccess {String} description 描述信息.
	 * @apiSuccess {Number} department_id 所属部门.
	 * @apiSuccess {String} city_id 所属城市.
	 * @apiSuccess {Number} status 用户状态.1正常、2停用、3注销.
	 * @apiSuccess {String} description 角色说明.
	 * @apiSuccess {String} note 备注信息.
	 * @apiSuccess {String} created_at 创建时间.
	 * @apiSuccess {String} update_at 更新时间.
	 * @apiSuccess {Array} permissions 角色权限.
	 * @apiSuccess {Object} department 角色部门.
	 * @apiSuccess {Object} city 角色区域.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "id": 1,
	 *	        "name": "管理员",
	 *	        "slug": "administrator",
	 *	        "description": "manage administration privileges",
	 *	        "department_id": 1,
	 *	        "city_id": 1,
	 *	        "status": "1",
	 *	        "note": null,
	 *	        "created_at": "2015-05-05 06:23:43",
	 *	        "updated_at": "2015-05-11 07:15:28",
	 *	        "department": {
	 *	            "id": 1,
	 *	            "title": "产品部"
	 *	        },
	 *	        "city": {
	 *	            "id": 1,
	 *	            "title": "深圳"
	 *	        },
	 *	        "permissions": [
	 *	            {
	 *	                "id": 3,
	 *	                "title": "修改用户信息"
	 *	            }
	 *	        ]
	 * 	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "没有符合条件数据"
	 *		}
	 */
	public function show($id)
	{

		$query = Role::with(['department'=>function($q){
			$q->lists('id','title');
		}]);

        $query = $query->with(['city'=>function($q){
            $q->lists('iid','iname');
        }]);     	

		$query = $query->with(['permissions'=>function($q){
			$q->lists('permission_id as id','title');
		}]);	

		// $fields = array(
		// 	'name',
		// 	'status',
		// 	'description',
		// 	'note'
		// 	);

		$role = $query->find($id);
		return $this->success($role);
	}

	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
		//
	}

	/**
	 * @api {post} /role/update/:id 3.更新角色信息
	 * @apiName update
	 * @apiGroup Role
	 *
	 * @apiParam {Number} id 必填,用户ID.
	 * @apiParam {String} name 用户姓名.
	 * @apiParam {String} username 用户名.
	 * @apiParam {Number} status 用户状态.1正常、2停用、3注销.
	 * @apiParam {String} department_id 所属部门.
	 * @apiParam {String} city_id 所属城市.
	 * @apiParam {Array} permissions 用户角色.
	 *
	 * 
	 * @apiSuccess {String} status 请求状态.
	 * @apiSuccess {String} msg 提示信息.
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
	 *		    "msg": "更新失败"
	 *		}
	 */
	public function update($id)
	{
		$param = $this->param;
		DB::beginTransaction();
		$role = Role::find($id);
		$update_permission = 1;

		if(isset($param['permissions'])){
			$permissions = $param['permissions'];
			unset($param['permissions']);
		}
		else
			$permissions = [];
			$update_permission = $role->permissions()->sync($permissions);
		
		$update_role = $role->update($param);
		if($update_permission&&$update_role){
			DB::commit();
			Event::fire('role.update',array($role));
			return $this->success();
		}
		else
		{
			DB::rollBack();
			throw new ApiException('角色更新失败', ERROR::ROLE_UPDATE_FAILED);
		}

	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{

	}

}