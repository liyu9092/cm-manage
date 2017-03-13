<?php namespace App\Http\Controllers\Merchant;


use App\Http\Controllers\Controller;
use App\SalonUser;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\Salon;
use App\SalonAccount;
use Event;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class SalonAccountController extends Controller {
	
	private $pwd = "choumei";
	
	private $cmPwd = "cmm111";
	
	private $addMsg = array(
				1=>"当前店铺已存在普通用户，请查询",
				2=>"当前商户已存在超级管理员，请查询",
		);
	
	/**
	 * @api {post} /salonAccount/index 1.店铺账号列表
	 * @apiName index
	 * @apiGroup salonAccount
	 *
	 * @apiParam {String} salonname 可选,店铺名称
	 * @apiParam {String} name 可选,商户名
	 * @apiParam {String} username 可选,账号名称
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 * @apiParam {Number} sort_key 可选,排序字段 status状态 roleType角色.
	 * @apiParam {Number} sort_type 可选,排序 DESC倒序 ASC升序.
	 *
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {Number} salonUserId 账号Id.
	 * @apiSuccess {String} username 账号名称.
	 * @apiSuccess {Number} salonid 店铺Id.
	 * @apiSuccess {Number} roleType 账号类型 1.普通用户2.超级管理员.
	 * @apiSuccess {Number} addTime 创建时间 （时间戳 1436242693）.
	 * @apiSuccess {String} status 状态  1.正常使用2.已停用3.已删除.
	 * @apiSuccess {String} merchantId 商户Id.
	 * @apiSuccess {String} name 商户名称.
	 * @apiSuccess {String} salonname 店铺名称.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *  {
	 *	    "result": 1,
	 *	    "data": {
	 *	        "total": 3,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 1,
	 *	        "from": 1,
	 *	        "to": 3,
	 *	        "data": [
	 *	            {
	 *	                "salonUserId": 1155,
	 *	                "username": "臭美商盟美发店",
	 *	                "salonid": 2,
	 *	                "roleType": 1,
	 *	                "addTime": 1436236918,
	 *	                "status": 3,
	 *	                "merchantId": 1,
	 *	                "name": "15854856985",
	 *	                "salonname": "名流造型SPA（皇岗店）"
	 *	            },
	 *	            ......
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
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;
		$sort_key = isset($param["sort_key"])?$param["sort_key"]:"salon_user_id";
    	$sort_type = isset($param["sort_type"])?$param["sort_type"]:"desc";
		$result = SalonAccount::getList($param,$page,$page_size,$sort_key,$sort_type);
		unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);
	}

	/**
	 * @api {post} /salonAccount/save 2.添加账号
	 * @apiName save
	 * @apiGroup salonAccount
	 *
	 * @apiParam {String} username 必填,用户名.
	 * @apiParam {Number} salonid 必填,店铺Id.
	 * @apiParam {Number} merchantId 必填,商户Id.
	 * @apiParam {String} roleType 必填,账号类型 1.普通用户2.超级管理员.
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "msg": "",
	 *	    "data": {
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "当前店铺已存在普通用户，请查询"
	 *		}
	 */
	
	public function save()
	{
		$param = $this->param;
		if(!isset($param["username"]) || !isset($param["salonid"]) || !isset($param["merchantId"]) || !isset($param["roleType"]))
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
		}
		if(!$param["username"] || !$param["merchantId"] || !in_array($param["roleType"], array(1,2)) )
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
		}
		$oldRs = $this->getAccount($param["username"]);
		if($oldRs)
		{
			throw new ApiException('该用户名重复，请重新修改', ERROR::MERCHANT_ACCOUNT_NAME_IS_ERROR);
		}

		$save["username"] = $param["username"];
		if($param["roleType"] == 1)//普通用户
		{
			$save["salonid"] = $param["salonid"];
		}
		
		$save["merchantId"] = $param["merchantId"];
		$save["roleType"] = $param["roleType"];
		$save["password"] = md5($this->pwd);
		$save["admin_password"] = md5($this->cmPwd);

		$save["addTime"] = time();
		$save["status"] = 1;
		
		$nums = SalonAccount::getAccountNums($save);//查看管理员个数是否符合要求 
		if($nums >= 1)
		{
			throw new ApiException($this->addMsg[$param["roleType"]], ERROR::MERCHANT_ACCOUNT_CONFLICT_IS_ERROR);
		}
		$id = SalonAccount::dosave($save);//添加账号
		if($param["roleType"] == 2)//超级管理员
		{
			Salon::where(array("merchantId"=>$param["merchantId"]))->update(array("puserid"=>$id));
		}
		
		if($id)
		{
			//触发事件，写入日志
			Event::fire('salonAccount.save',$this->getAccountByUserid($id));
			return $this->success();
		}	
		else
		{
			throw new ApiException('更新失败', ERROR::MERCHANT_UPDATE_FAILED);
		}
	}
	
	/**
	 * 通过账号名查询
	 * 
	 * */
	private function getAccount($username)
	{
		$query = SalonUser::getQuery();
		$query = $query->where('username','=',$username);
		$query = $query->where('status','!=',3);//排除删除账号
		$list = $query->first();
		if($list)
		{
			$list = (array)$list;
		}
		return $list;
	}
	
	/**
	 * @api {post} /salonAccount/resetPwd 3.重置密码
	 * @apiName resetPwd
	 * @apiGroup salonAccount
	 *
	 *@apiParam {Number} salonUserId 必填,账号ID.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "msg": "",
	 *	    "data": {
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "操作失败"
	 *		}
	 */

	public function resetPwd()
	{
		$param = $this->param;
		if(!isset($param['salonUserId']))
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);	
		}
		if(SalonUser::where("salon_user_id","=",$param['salonUserId'])->update(array("password"=>md5($this->pwd),"upTime"=>time(),"admin_password"=>md5($this->cmPwd))))
		{
			//触发事件，写入日志
			Event::fire('salonAccount.resetPwd',$this->getAccountByUserid($param['salonUserId']));
			return	$this->success();
		}
		else
		{
			throw new ApiException('操作失败', ERROR::MERCHANT_UPDATE_FAILED);
		}
	}
	
	/**
	 * 获取用户信息
	 * 
	 * **/
	private function getAccountByUserid($userId)
	{
		$list = SalonUser::where('salon_user_id','=',$userId)->first();
		return $list?$list->username:'';
	}
	
	/**
	 * @api {post} /salonAccount/delAct 4.停用 删除账号
	 * @apiName delAct
	 * @apiGroup salonAccount
	 *
	 *@apiParam {Number} salonUserId 必填,账号ID.
	 *@apiParam {Number} type 必填,操作类型 1.停用 2.删除.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "msg": "",
	 *	    "data": {
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "操作失败"
	 *		}
	 */
	public function delAct()
	{
		$param = $this->param;
		if(!isset($param['salonUserId']) || !isset($param['type']))
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);	
		}
		if($param["type"] == 1)
		{
			$status = 2;//停用
		}
		elseif ($param["type"] == 2)
		{
			$status = 3;//删除
		}
		else 
		{
			throw new ApiException('参数异常', ERROR::MERCHANT_ERROR);	
		}
		$status = SalonAccount::doUpdate($param['salonUserId'], array("status"=>$status,"upTime"=>time()));
		if($status)
		{
			//触发事件，写入日志
			Event::fire('salonAccount.delAct',$this->getAccountByUserid($param['salonUserId']));
			return	$this->success();
		}
		else
		{
			throw new ApiException('操作失败', ERROR::MERCHANT_UPDATE_FAILED);
		}
	}
	
	
	/**
	 * @api {post} /salonAccount/getSalonName 5.模糊查找店铺
	 * @apiName getSalonName
	 * @apiGroup salonAccount
	 *
	 * @apiParam {String} salonname 必填,店铺名称.
	 * 
	 * @apiSuccess {Number} merchantId 商户Id.
	 * @apiSuccess {String} name 商户名称.
	 * @apiSuccess {Number} salonid 店铺Id.
	 * @apiSuccess {String} salonname 店铺名称.
	 * @apiSuccess {String} sn 店铺编号.
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": [
	 *	        {
	 *	            "merchantId": 1,
	 *	            "salonid": 1,
	 *	            "salonname": "嘉美专业烫染",
	 *				"sn": "SZ00252",
	 *				"name": "嘉烫染"
	 *	        },
	 *	        {
	 *	            "merchantId": 33,
	 *	            "salonid": 804,
	 *	            "salonname": "臭美腾讯专属高端店",
	 *				"sn": "SZ00252",
	 *              "name": "嘉美烫染"
	 *	        },
	 *	        ......
	 *	    ]
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "操作失败"
	 *		}
	 */
	public function getSalonName()
	{
		$param = $this->param;
		if(!isset($param['salonname']))
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);	
		}
		$data = SalonAccount::getSalonNamebyCon($param);
		return $this->success($data?$data:array());
	}
	
}

?>