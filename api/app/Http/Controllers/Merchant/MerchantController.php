<?php

namespace App\Http\Controllers\Merchant;


use App\Salon;

use App\Http\Controllers\Controller;
use App\Merchant;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\SalonUser;
use Excel;
use Event;
use Illuminate\Support\Facades\Redis as Redis;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
class MerchantController extends Controller {
	/**
	 * @api {post} /merchant/index 1.商户列表
	 * @apiName index
	 * @apiGroup merchant
	 *
	 * @apiParam {String} mobile 可选,电话号码
	 * @apiParam {String} name 可选,商户名
	 * @apiParam {String} sn 可选,商户编号
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
	 * @apiSuccess {Number} id id.
	 * @apiSuccess {String} sn 商户编号.
	 * @apiSuccess {String} name 用户姓名.
	 * @apiSuccess {String} contact 联系人.
	 * @apiSuccess {String} mobile 联系手机.
	 * @apiSuccess {String} phone 电话.
	 * @apiSuccess {String} email 邮箱.
	 * @apiSuccess {String} addr 地址.
	 * @apiSuccess {String} foundingDate 商户成立时间(时间戳).
	 * @apiSuccess {String} salonNum 拥有店铺数量.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "total": 51,
	 *	        "per_page": "1",
	 *	        "current_page": 1,
	 *	        "last_page": 51,
	 *	        "from": 1,
	 *	        "to": 1,
	 *	        "data": [
	 *	            {
	 *	                "id": 53,
	 *	                "sn": "0000900",
	 *	                "name": "s卡段商户",
	 *	                "contact": "汪先生",
	 *	                "mobile": "13458745236",
	 *	                "phone": "0755236566",
	 *	                "email": "",
	 *	                "addr": "",
	 *	                "foundingDate": 1432202590,
	 *	                "salonNum": 0,
	 *	                "addTime": 1432202951
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
		$query = $this->getQueryByParam($param);
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
		
		$fields = array('id', 'sn','name','contact','mobile','phone','email','addr','foundingDate','salonNum','addTime' );
		//分页
	    $result = $query->select($fields)->orderBy('addTime', 'desc')->paginate($page_size)->toArray();
	    unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);
	}
	

	
	/**
	 * @api {post} /merchant/save 2.添加商户
	 * @apiName save
	 * @apiGroup  merchant
	 *
	 * @apiParam {String} name 必填,用户姓名.
	 * @apiParam {String} contact 必填,联系人.
	 * @apiParam {String} mobile 必填,联系手机.
	 * @apiParam {String} phone 电话.
	 * @apiParam {String} email 邮箱.
	 * @apiParam {String} addr 地址.
	 * @apiParam {Number} foundingDate 商户成立时间.
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
	 *		    "msg": "未授权访问"
	 *		}
	 */	
	public function save()
	{
		return $this->dosave($this->param);
	}
	
	
	/**
	 * @api {post} /merchant/update 3.修改商户
	 * @apiName update
	 * @apiGroup  merchant
	 *
	 *@apiParam {Number} id 必填,商家Id.
	 *
	 * @apiParam {String} sn 必填,商户编号.
	 * @apiParam {String} name 必填,用户姓名.
	 * @apiParam {String} contact 必填,联系人.
	 * @apiParam {String} mobile 必填,联系手机.
	 * @apiParam {String} phone 电话.
	 * @apiParam {String} email 邮箱.
	 * @apiParam {String} addr 地址.
	 * @apiParam {Number} foundingDate 商户成立时间.
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
	 *		    "msg": "未授权访问"
	 *		}
	 */	
	public function update()
	{
		return $this->dosave($this->param);
	}
	
	/**
	 * 添加修改操作
	 * 
	 * */
	private  function dosave($param)
	{
		$param["id"] = isset($param["id"])?$param["id"]:0;
		$save["name"] = isset($param["name"])?trim($param["name"]):"";
		$save["sn"] = isset($param["sn"])?trim($param["sn"]):"";
		$save["contact"] = isset($param["contact"])?trim($param["contact"]):"";
		$save["mobile"] = isset($param["mobile"])?trim($param["mobile"]):"";
		$save["phone"] = isset($param["phone"])?trim($param["phone"]):"";
		$save["email"] = isset($param["email"])?trim($param["email"]):"";
		$save["addr"] = isset($param["addr"])?trim($param["addr"]):"";
		$save["foundingDate"] = isset($param["foundingDate"])?trim($param["foundingDate"]):"";
		$save["foundingDate"] = strtotime($save["foundingDate"]);
		if(!$save["name"] || !$save["contact"] || !$save["mobile"])
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
		}
		
		if($param["id"])
		{
			$save["upTime"] = time();
			$result = Merchant::where(['id'=>$param['id']])->select('sn')->first();
			if(!$result)
			{
				throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
			}
			if($result->sn != $param['sn'])
			{
				$snCount = Merchant::getCheckSn($param['sn']);//检测商户编号
				if($snCount)
				{
					throw new ApiException('商户编号重复已经存在', ERROR::MERCHANT_SN_IS_ERROR);
				}
			}
			
			$status = Merchant::where(['id'=>$param['id']])->update($save);
			if($status)
			{
				//触发事件，写入日志
				Event::fire('merchant.update','商户Id:'.$param['id']." 商户名称：".$save['name']);
			}
			
		}
		else
		{
			$save["addTime"] = time();
			$save["sn"] = Merchant::addMerchantSn();
			$status = Merchant::insertGetId($save);
			if($status)
			{
				Event::fire('merchant.save','商户Id:'.$status." 商户名称：".$save['name']);
			}
		}
		
		if($status)
		{
			return $this->success();
		}	 
		else
		{
			throw new ApiException('商户更新失败', ERROR::MERCHANT_UPDATE_FAILED);
		} 
			
	}
	

	
	/**
	 * @api {post} /merchant/del 4.删除商户
	 * @apiName del
	 * @apiGroup merchant
	 *
	 *@apiParam {Number} id 删除必填,商家ID.
	 *
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
	 *		    "msg": "商户删除失败"
	 *		}
	 */	
	public function del()
	{
		$param = $this->param;
		$param["id"] = isset($param["id"])?$param["id"]:0;
		if(!$param["id"])
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
		}
		$flag = $this->selectMerSalonStatus($param['id']);
		if($flag > 0)
		{
			throw new ApiException('该商户还有正在合作的店铺请先终止该商户所有店铺合作，再删除商户', ERROR::MERCHANT_STATUS_IS_ERROR);
		}
		
		$save["status"] = 2;//1正常 2删除
		$save["uptime"] = time();
		$status = Merchant::where(['id'=>$param['id']])->update($save);
		SalonUser::where(['merchantId'=>$param['id']])->update(['status'=>SalonUser::STATUS_DEl]);//删除普通用户账号
		
		$merchantId = $param['id'];
		$usersCount = SalonUser::where(['merchantId'=>$merchantId,'status'=>1])->where('salonid','!=',0)->count();
		if(!$usersCount)
		{
			SalonUser::where(['merchantId'=>$merchantId,'salonid'=>0])->update(['status'=>SalonUser::STATUS_DEl]);//删除账号  超级管理员
		}

		if($status)
		{
			Event::fire('merchant.del','商户Id:'.$param['id']." 商户名称：".Merchant::getMerchantName($param['id']));
			return $this->success();
		}	 
		else
		{
			throw new ApiException('商户删除失败', ERROR::MERCHANT_UPDATE_FAILED);
		} 
		
	}
	
	/**
	 * 删除查看是否有合作的店铺
	 * 
	 * */
	private function selectMerSalonStatus($merchantId)
	{
		return Salon::where(['merchantId'=>$merchantId,'status'=>1])->count();
	}
	
	/**
	 * @api {post} /merchant/checkMerchantSn 5.检测商家编号是否重复
	 * @apiName checkMerchantSn
	 * @apiGroup merchant
	 *
	 *@apiParam {String} sn 必填商家编号.
	 *
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
	 *		    "msg": "店铺编号重复已经存在"
	 *		}
	 */	
	public function checkMerchantSn()
	{
		$param = $this->param;
		$sn = isset($param["sn"])?trim($param["sn"]):"";	

		if(!$sn)
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
		}

		$snNo = Merchant::getCheckSn($sn);//检测商铺编号
		if($snNo)
		{
			throw new ApiException('商户编号重复已经存在', ERROR::MERCHANT_SN_IS_ERROR);
		}
		else 
		{
			return $this->success();
		}
	}
	
	/**
	 * @api {post} /merchant/getMerchantList 6.获取商户详情
	 * @apiName getMerchantList
	 * @apiGroup merchant
	 *
	 *@apiParam {Number} id 必填商家id.
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "id": 48,
	 *	        "sn": "00048",
	 *	        "name": "sn手动输入",
	 *	        "contact": "汪先生",
	 *	        "mobile": "13458745236",
	 *	        "phone": "0755236566",
	 *	        "email": "",
	 *	        "addr": "",
	 *	        "foundingDate": 0,
	 *	        "addTime": 1432202115,
	 *	        "upTime": 0,
	 *	        "status": 1,
	 *	        "salonNum": 0
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "参数错误"
	 *		}
	 */		
	public function  getMerchantList()
	{
		$param = $this->param;
		$id = isset($param["id"])?trim($param["id"]):"";	
		if(!$id)
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
		}
		$rs = Merchant::find($id);
		return $this->success($rs);
	}
	
	/**
	 * 商户查询
	 * */
	private function getQueryByParam($param)
	{
		$query = Merchant::getQuery();
		
		//状态筛选
		if(isset($param['name']) && urldecode($param['name']))
		{
			$keyword = '%'.urldecode($param['name']).'%';
			$query = $query->where('name','like',$keyword);
		}
		if(isset($param['sn']) && urldecode($param['sn']))
		{
			$keyword = '%'.urldecode($param['sn']).'%';
			$query = $query->where('sn','like',$keyword);
		}
		
		if(isset($param['mobile'])&&$param['mobile'])
		{
			$kModile = '%'.$param['mobile'].'%';
			$query = $query->where('mobile','like',$kModile);
		}
		$query = $query->where('status','=',1);//排除删除
		return $query;
	}
	
	/**
	 * @api {post} /merchant/export 7.商户列表导出
	 * @apiName export
	 * @apiGroup merchant
	 *
	 * @apiParam {String} mobile 可选,电话号码
	 * @apiParam {String} name 可选,商户名
	 * @apiParam {String} sn 可选,商户编号
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
		$query = $this->getQueryByParam($param);
		
		$fields = array('name','sn','contact','mobile','phone','email','addr','foundingDate','addTime' );
		$rs = $query->select($fields)->orderBy('addTime', 'desc')->get();
		$result = array();
		foreach ($rs as $key => $value)
		{
			$result[$key]['sn'] = $value->sn;
			$result[$key]['name'] = $value->name;
			
			$result[$key]['contact'] = $value->contact;
			$result[$key]['mobile'] = $value->mobile;
			$result[$key]['phone'] = $value->phone;
			$result[$key]['email'] = $value->email;
			$result[$key]['addr'] = $value->addr;
			$result[$key]['foundingDate'] = $value->foundingDate?date('Y-m-d',$value->foundingDate):'';
			$result[$key]['addTime'] = date('Y-m-d H:i:s',$value->addTime);
		}
		Event::fire('merchant.export');
		//导出excel
		$title = '商户列表'.date('Ymd');
		$header = ['商户编号','商户名称','联系人','联系手机','联系座机','联系邮箱','详情地址','成立日期','创建日期']; 
	    Excel::create($title, function($excel) use($result,$header){
		    $excel->sheet('Sheet1', function($sheet) use($result,$header){
			        $sheet->fromArray($result, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
	        		$sheet->prependRow(1, $header);//添加表头

			    });
		})->export('xls');
	
	}
	
	/**
	 * @api {post} /merchant/getMerchantList 8.获取新的商户编号
	 * @apiName getSn
	 * @apiGroup merchant
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "sn": SB09090
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "参数错误"
	 *		}
	 */		
	public function  getSn()
	{
		$sn = Merchant::getSn();
		return $this->success(['sn'=>$sn]);
	}

}

?>