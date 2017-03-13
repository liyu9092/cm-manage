<?php namespace App\Http\Controllers;

use App\Manager;
use App\RoleUser;
use App\Permission;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use Event;
use Excel;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class PermissionController extends Controller{
	/**
	 * @api {post} /permission/index 1.权限列表
	 * @apiName list
	 * @apiGroup Permission
	 *
	 * @apiParam {Number} role_id 可选,角色ID.
	 * @apiParam {Number} department_id 可选,部门ID.
	 * @apiParam {Number} status 可选,用户状态.1正常、2停用、3注销.
	 * @apiParam {Number} city_id 可选,城市ID.
	 * @apiParam {String} start 可选,起始时间.
	 * @apiParam {String} end 可选,结束时间.
	 * @apiParam {String} keyword 可选,搜索关键字,匹配帐号或者姓名.
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 *
	 *
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {String} name 用户姓名.
	 * @apiSuccess {Number} status 用户状态.1正常、2停用、3注销.
	 * @apiSuccess {String} created_at 创建时间.
	 * @apiSuccess {String} slug 操作路径(路由名).
	 * @apiSuccess {String} description 描述信息.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "total": 4,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 1,
	 *	        "from": 1,
	 *	        "to": 4,
	 *	        "data": [
	 *	            {
	 *	                "title": "",
	 *	                "result": "1",
	 *	                "created_at": "0000-00-00 00:00:00",
	 *	                "slug": "",
	 *	                "description": null
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
		$query = Permission::getQueryByParam($param);
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
		$fields = array(
				'id',
				'title',
				'status',
				'created_at',
				'slug',
				'description'
			);
		//分页
	    $result = $query->select($fields)->paginate($page_size)->toArray();
	    unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);

	}	


	/**
	 * @api {post} /permission/export 5.导出权限
	 * @apiName export
	 * @apiGroup Permission
	 *
	 * @apiParam {Number} role_id 可选,角色ID.
	 * @apiParam {Number} department_id 可选,部门ID.
	 * @apiParam {Number} status 可选,用户状态.1正常、2停用、3注销.
	 * @apiParam {Number} city_id 可选,城市ID.
	 * @apiParam {String} start 可选,起始时间.
	 * @apiParam {String} end 可选,结束时间.
	 * @apiParam {String} keyword 可选,搜索关键字,匹配帐号或者姓名.
	 *
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
		$query = Permission::getQueryByParam();

	    $array = $query->get();
	    foreach ($array as $key => $value) {
	    	$result[$key]['id'] = $key+1;
	    	$result[$key]['title'] = $value->title;
	    	$result[$key]['status'] = $this->status($value->status);
	    	$result[$key]['created_at'] = $value->created_at;
	    	$result[$key]['slug'] = $value->slug;
	    	$result[$key]['description'] = $value->description;
	    }
		// 触发事件，写入日志
	    Event::fire('permission.export');
		
		//导出excel	   
		$title = '权限列表'.date('Ymd');
		$header = ['序号','权限名称','状态','添加时间','配置路径','权限说明'];
		Excel::create($title, function($excel) use($result,$header){
		    $excel->sheet('Sheet1', function($sheet) use($result,$header){
			        $sheet->fromArray($result, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
	        		$sheet->prependRow(1, $header);//添加表头

			    });
		})->export('xls');

	}

	/**
	 * @api {post} /permission/create 4.创建权限
	 * @apiName create
	 * @apiGroup Permission
	 *
	 * @apiParam {Number} inherit_id 继承于.
	 * @apiParam {String} name 权限标题.
	 * @apiParam {String} slug 操作路径(路由名).
	 * @apiParam {String} descrition 描述信息.
	 * @apiParam {String} note 备注信息.
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
	 *		    "msg": "权限创建失败"
	 *		}
	 */
	public function create()
	{
		$param = $this->param;
		$permission = Permission::create($param);
		if($permission)
		{
			Event::fire('permission.create',array($permission));
			return $this->success();
		}
			 
		else 
			throw new ApiException('权限创建失败', ERROR::PERMISSION_CREATE_FAILED);
	}


	/**
	 * @api {post} /user/show/:id 2.查看权限
	 * @apiName show
	 * @apiGroup Permission
	 *
	 * @apiParam {Number} id 必填,用户ID.
	 *
	 * @apiSuccess {Number} id ID.
	 * @apiSuccess {Number} inherit_id 继承于.
	 * @apiSuccess {String} name 权限标题.
	 * @apiSuccess {String} slug 操作路径(路由名).
	 * @apiSuccess {String} descrition 描述信息.
	 * @apiSuccess {String} note 备注信息.
	 * @apiSuccess {String} created_at 创建时间.
	 * @apiSuccess {String} updated_at 更新时间.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "id": 1,
	 *	        "inherit_id": null,
	 *	        "name": "查看用户列表1",
	 *	        "slug": null,
	 *	        "result": "1",
	 *	        "description": "查看用户列表",
 	 *	        "note": null,
 	 *	        "created_at": "2015-05-05 06:28:18",
	 *	        "updated_at": "2015-05-08 06:28:20"
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
	public function show($id)
	{
		$user = Permission::find($id);
		return $this->success($user);
	}


	/**
	 * @api {post} /permission/update/:id 3.更新权限
	 * @apiName update
	 * @apiGroup Permission
	 *
	 * @apiParam {Number} inherit_id 继承于.
	 * @apiParam {String} name 权限标题.
	 * @apiParam {String} slug 操作路径(路由名).
	 * @apiParam {String} descrition 描述信息.
	 * @apiParam {String} note 备注信息.
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": {
	 *		    }
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "没有符合条件数据"
	 *		}
	 */
	public function update($id)
	{
		$param = $this->param;
		$permission = Permission::find($id);
		DB::beginTransaction();
		$self = $permission->update($param);
		$other = Permission::where('inherit_id',$permission->id)->update(['status'=>$param['status']]);
		if($self)
		{
			DB::commit();
			Event::fire('permission.update',array($permission));
			return $this->success();
		}
		else
		{
			DB::rollBack();
			throw new ApiException('权限更新', PERMISSION_UPDATE_FAILED);
		}
			
	}



}