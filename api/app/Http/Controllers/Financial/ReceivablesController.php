<?php namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Receivables;
use DB;
use Excel;
use App\ShopCountApi;
use App\Merchant;
use App\Salon;
use App\PayManage;
use App\PrepayBill;
use App\ShopCount;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class ReceivablesController extends Controller{

	
	/**
	 * @api {post} /receivables/index 1.收款列表
	 * @apiName index
	 * @apiGroup receivables
	 *
	 * @apiParam {String} salonname 可选,店铺名称.
	 * @apiParam {String} sn 可选,店铺编号.
	 * @apiParam {String} merchantName 可选,商户名称.
	 * @apiParam {String} startTime 可选,收款起始日期Y-m-d H:i:s.
	 * @apiParam {String} endTime 可选,收款结束日期Y-m-d H:i:s.
	 * @apiParam {Number} type 可选,收款类型 1业务投资款返还2交易代收款返还.
	 * @apiParam {Number} paymentStyle 可选,收款方式1银行存款2账扣返还3现金4支付宝5财付通.
	 * @apiParam {Number} status 可选,状态1.待确认2  已确认.
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 * @apiParam {String} sort_key 可选,排序 type收款类型  paymentStyle 收款方式  receiptDate收款日期 addTime创建日期.
	 * @apiParam {String} sort_type 可选,排序 DESC倒序 ASC升序.
	 *
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {Number} id id.
	 * @apiSuccess {Number} salonid 店铺Id.
	 * @apiSuccess {String} salonname 店铺名称.
	 * @apiSuccess {String} sn 店铺编号.
	 * @apiSuccess {String} name 商户名称.
	 * @apiSuccess {Number} type 收款类型 1业务投资款返还2交易代收款返还.
	 * @apiSuccess {Number} paymentStyle 收款方式1银行存款2账扣返还3现金4支付宝5财付通.
	 * @apiSuccess {String} money 收款金额.
	 * @apiSuccess {Number} addTime 创建时间(时间戳).
	 * @apiSuccess {String} singleNumber 收款单号.
	 * @apiSuccess {Number} status 状态1.待确认2  已确认.
	 * @apiSuccess {String} preparedByName 制单人.
	 * @apiSuccess {String} receiptDate 收款日期(时间戳).
	 * 
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
	 *					"id":1,
	 *	                "salonid": 53,
	 *	                "salonname": "名流造 型SPA（皇岗店）",
	 *	                "sn": "SZ0620001",
	 *	                "name": "choumeitest",
	 *	                "type": "13458745236",
	 *	                "paymentStyle": "0755236566",
	 *	                "money": "36.33",
	 *	                "addTime": "1432202590",
	 *	                "singleNumber": 1432202590,
	 *	                "status": 1,
	 *	                "preparedByName": "唐飞",
	 *					"receiptDate":1432202590,
	 *	            }
	 *              ......
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
		$where = '';
		
		$param = $this->param;
		$where['salonname'] = isset($param['salonname'])?$param['salonname']:'';//店铺名
		$where['salonSn'] = isset($param['sn'])?$param['sn']:'';//店铺编号
		$where['merchantName'] = isset($param['merchantName'])?$param['merchantName']:'';//商户名称
		$where['startTime'] = isset($param['startTime'])?strtotime($param['startTime']):'';//收款日期
		$where['endTime'] = isset($param['endTime'])?strtotime($param['endTime']):'';//收款日期
		$where['type'] = isset($param['type'])?$param['type']:'';//收款类型
		$where['paymentStyle'] = isset($param['paymentStyle'])?$param['paymentStyle']:'';//收款方式
		$where['status'] = isset($param['status'])?$param['status']:'';//收款状态
		
		$sort_key = isset($param['sort_key'])?$param['sort_key']:'r.addTime';
    	$sort_type = isset($param['sort_type'])?$param['sort_type']:'desc';
		

		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;
		$list = Receivables::getList($where,$page,$page_size,$sort_key,$sort_type);
		if($list['data'])
		{
			foreach ($list['data'] as $key=>$val)
			{
				$value = (array)$val;
				$list['data'][$key] = $value;
				if($value['status'] == 1)//待确认的收款单无收款日期
				{
					$list['data'][$key]['receiptDate'] = '';
				}
			}
		}
		unset($list['next_page_url']);
		unset($list['prev_page_url']);
	    return $this->success($list);
	}
	
	/**
	 * @api {post} /receivables/save 2.新增收款
	 * @apiName save
	 * @apiGroup  receivables
	 *
	 * @apiParam {Number} salonid 必填,店铺Id.
	 * @apiParam {Number} type 必填,收款类型1业务投资款返还 2交易代收款返还.
	 * @apiParam {Number} paymentStyle 必填,收款方式 1银行存款2账扣返还3现金4支付宝5财付通.
	 * @apiParam {String} money 必填,收款金额.
	 * @apiParam {String} remark 选填,备注信息.
	 * @apiParam {String} receiptDate 必填,收款日期(Y-m-d H:i:s).
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
	 *		    "msg": "参数错误"
	 *		}
	 */	
	public function save()
	{
		return $this->dosave($this->param);
	}
	
	/**
	 * @api {post} /receivables/update 3.修改收款
	 * @apiName update
	 * @apiGroup  receivables
	 *
	 * @apiParam {Number} id 必填,收款id.
	 * @apiParam {Number} salonid 必填,店铺Id.
	 * @apiParam {Number} type 必填,收款类型1业务投资款返还 2交易代收款返还.
	 * @apiParam {Number} paymentStyle 必填,收款方式 1银行存款2账扣返还3现金4支付宝5财付通.
	 * @apiParam {String} money 必填,收款金额.
	 * @apiParam {String} remark 选填,备注信息.
	 * @apiParam {String} receiptDate 必填,收款日期(Y-m-d H:i:s).
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
	 *		    "msg": "参数错误"
	 *		}
	 */	
	public function update()
	{
		$param = $this->param;
		$id = isset($param['id'])?$param['id']:0;
		if(!$id || !Receivables::getCheckRecRsStatus($id))
		{
			throw new ApiException('参数错误', ERROR::RECEIVABLES_ERROR);
		}
		
		return $this->dosave($this->param);
	}
	
	/***
	 * *
	 * 添加修改操作
	 * */
	private function dosave($param)
	{
		$save = array();
		$id = isset($param['id'])?$param['id']:0;
		$save['salonid'] = isset($param['salonid'])?$param['salonid']:0;
		$save['type'] = isset($param['type'])?$param['type']:0;
		$save['paymentStyle'] = isset($param['paymentStyle'])?$param['paymentStyle']:0;
		$save['money'] = isset($param['money'])?$param['money']:0;
		$save['remark'] = isset($param['remark'])?$param['remark']:0;
		$save['receiptDate'] = isset($param['receiptDate'])?strtotime($param['receiptDate']):0;
		if(!$save['salonid'] || !in_array($save['type'], array('1','2'))  || !$save['paymentStyle'] || !$save['money'] || !$save['receiptDate'])
		{
			throw new ApiException('参数错误', ERROR::RECEIVABLES_ERROR);
		}

		if(Receivables::dosave($save,$id,$this->user->id))//制单人 未填写
		{
			return $this->success();
		}
		else
		{
			throw new ApiException('更新失败', ERROR::RECEIVABLES_UPDATE_FAILED);
		}

	}
	
	/**
	 * @api {post} /receivables/confirmAct 4.批量确认收款
	 * @apiName confirmAct
	 * @apiGroup  receivables
	 *
	 * @apiParam {Number} idStr 必填,收款id （1,2,5）多个用英文逗号隔开.
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
	 *		    "msg": "参数错误"
	 *		}
	 */
	public function confirmAct()
	{
		$param = $this->param;
		$idStr = isset($param['idStr'])?$param['idStr']:0;
		$idArr = explode(',', $idStr);
		if(!$idStr)
		{
			throw new ApiException('参数错误', ERROR::RECEIVABLES_ERROR);
		}
		$payTypeId = [];
		$list = Receivables::select(['type','type','id','status','paymentStyle','receiptDate','salonid','money','singleNumber','cashier','preparedBy','checkTime','addTime','remark'])->whereIn('id', $idArr)->get();
		foreach($list as $key=>$val)
		{
			if($val->status != 1)
			{
				throw new ApiException('数据错误，请重新勾选', ERROR::RECEIVABLES_ERROR);
			}
			$salonResult = Salon::select(['merchantId'])->where(['salonid'=>$val->salonid])->first();
			if($val->type == 1)//取财务管理-收款管理中的业务投资款返还(已确认)的单		  		
			{
				ShopCount::count_bill_by_invest_return_money($val->salonid,$salonResult->merchantId,$val->money);
			}
			if($val->paymentStyle == 2 || $val->type == 2) //type 收款类型 1业务投资款返还 2交易代收款返还   paymentStyle 收款方式 1银行存款 2账扣返还 3现金 4支付宝 5财付通
			{
				$payTypeId[$key]['type'] 			= $val->type;
				$payTypeId[$key]['paymentStyle']    = $val->paymentStyle;
				$payTypeId[$key]['id'] 				= $val->id;//账扣返还id
				$payTypeId[$key]['salonid'] 		= $val->salonid;
				$payTypeId[$key]['receiptDate'] 	= date('Y-m-d',$val->receiptDate);
				$payTypeId[$key]['checkTime'] 		= date('Y-m-d');
				$payTypeId[$key]['addTime'] 		= date('Y-m-d H:i:s',$val->addTime);
				$payTypeId[$key]['money']		    = $val->money;
				$payTypeId[$key]['merchantId'] 		= $salonResult->merchantId;
				$payTypeId[$key]['singleNumber'] 	= $val->singleNumber;
				$payTypeId[$key]['cashier']		    = $val->cashier;
				$payTypeId[$key]['preparedBy'] 		= $val->preparedBy;
				$payTypeId[$key]['remark'] 			= $val->remark;
			}
		}
		$row = Receivables::confirmReceivables($idArr,$this->user->id,$payTypeId);
		//$row = Receivables::confirmReceivables($idArr,1,$payTypeId);
		if($row)
		{
			return $this->success();
		}
		else
		{
			throw new ApiException('操作失败', ERROR::RECEIVABLES_UPDATE_FAILED);
		}
		
	}
	/**
	 * @api {post} /receivables/export 5.导出列表
	 * @apiName export
	 * @apiGroup receivables
	 *
	 * @apiParam {String} salonname 可选,店铺名称.
	 * @apiParam {String} sn 可选,店铺编号.
	 * @apiParam {String} merchantName 可选,商户名称.
	 * @apiParam {String} startTime 可选,收款起始日期Y-m-d H:i:s.
	 * @apiParam {String} endTime 可选,收款结束日期Y-m-d H:i:s.
	 * @apiParam {Number} type 可选,收款类型 1业务投资款返还2交易代收款返还.
	 * @apiParam {Number} paymentStyle 可选,收款方式1银行存款2账扣返还3现金4支付宝5财付通.
	 * @apiParam {Number} status 可选,状态1.待确认2  已确认.
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
		$where['salonname'] = isset($param['salonname'])?$param['salonname']:'';//店铺名
		$where['salonSn'] = isset($param['sn'])?$param['sn']:'';//店铺编号
		$where['merchantName'] = isset($param['merchantName'])?$param['merchantName']:'';//商户名称
		$where['startTime'] = isset($param['startTime'])?strtotime($param['startTime']):'';//收款日期
		$where['endTime'] = isset($param['endTime'])?strtotime($param['endTime']):'';//收款日期
		$where['type'] = isset($param['type'])?$param['type']:'';//收款类型
		$where['paymentStyle'] = isset($param['paymentStyle'])?$param['paymentStyle']:'';//收款方式
		$where['status'] = isset($param['status'])?$param['status']:'';//收款状态
		
		$sort_key = isset($param['sort_key'])?$param['sort_key']:'r.addTime';
		$sort_type = isset($param['sort_type'])?$param['sort_type']:'desc';
		
		$list = Receivables::getListExport($where,$sort_key,$sort_type);
		$result = [];
		$typeArr = Receivables::$typeArr;
		$paymentStyleArr = Receivables::$paymentStyleArr;
		$statusArr = Receivables::$statusArr;
		if($list)
		{
			foreach ($list as $key=>$val)
			{
				$value = (array)$val;
				$result[$key]['sn'] = $value['sn'];
				$result[$key]['salonname'] = $value['salonname'];
				$result[$key]['singleNumber'] = $value['singleNumber'];
				$result[$key]['type'] = $typeArr[$value['type']];
				$result[$key]['paymentStyle'] = $paymentStyleArr[$value['paymentStyle']];
				$result[$key]['money'] = $value['money'];
				$result[$key]['addTime'] = date('Y-m-d',$value['addTime']);
				$result[$key]['preparedByName'] = $value['preparedByName'];
 				$result[$key]['cashierName'] = $value['cashierName']?$value['cashierName']:'';
				if($value['status'] == 1)//待确认的收款单无收款日期
				{
					$result[$key]['receiptDate'] = '';
				}
				else 
				{
					$result[$key]['receiptDate'] = date('Y-m-d',$value['receiptDate']);
				}
				$result[$key]['status'] = $statusArr[$value['status']];
				$result[$key]['payCode'] = $value['payCode'];
				$result[$key]['paySingleCode'] = $value['paySingleCode'];
				$result[$key]['remark'] = $value['remark'];
			}
		}
		//导出excel
		$title = '收款列表'.date('Ymd');
		$header = ['店铺编号','店铺名称','收款单号','收款类型','收款方式','收款金额','创建日期','制单人','出纳','收款日期','状态','关联付款单号','关联转付单号','备注'];
		Excel::create($title, function($excel) use($result,$header){
			$excel->sheet('Sheet1', function($sheet) use($result,$header){
				$sheet->fromArray($result, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
				$sheet->prependRow(1, $header);//添加表头
					
			});
		})->export('xls');
	}
	
	/**
	 * @api {post} /receivables/getone 6.收款详情
	 * @apiName getone
	 * @apiGroup receivables
	 *
	 * @apiParam {Number} id 可选,id.
	 * 
	 * @apiSuccess {Number} id id.
	 * @apiSuccess {Number} salonid 店铺Id.
	 * @apiSuccess {String} salonname 店铺名称.
	 * @apiSuccess {String} sn 店铺编号.
	 * @apiSuccess {String} name 商户名称.
	 * @apiSuccess {Number} type 收款类型 1业务投资款返还2交易代收款返还.
	 * @apiSuccess {Number} paymentStyle 收款方式1银行存款2账扣返还3现金4支付宝5财付通.
	 * @apiSuccess {String} money 收款金额.
	 * @apiSuccess {Number} addTime 创建时间(时间戳).
	 * @apiSuccess {Number} receiptDate 收款日期(时间戳).
	 * @apiSuccess {String} singleNumber 收款单号.
	 * @apiSuccess {Number} status 状态1.待确认2  已确认.
	 * @apiSuccess {Number} preparedBy 制单人Id.
	 * @apiSuccess {String} preparedByName 制单人.
	 * @apiSuccess {Number} cashier 出纳Id.
	 * @apiSuccess {String} cashierName 出纳.
	 * @apiSuccess {Number} checkTime 确认收款时间(时间戳).
	 * @apiSuccess {String} payCode 关联付款单号.
	 * @apiSuccess {String} paySingleCode 关联转付单号.
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *					"id":1,
	 *	                "salonid": 53,
	 *	                "salonname": "名流造 型SPA（皇岗店）",
	 *	                "sn": "SZ0620001",
	 *	                "name": "choumeitest",
	 *	                "type": "13458745236",
	 *	                "paymentStyle": "0755236566",
	 *	                "money": "36.33",
	 *	                "addTime": "1432202590",
	 *	                "receiptDate": "1435202590",
	 *	                "singleNumber": 1432202590,
	 *	                "status": 1,
	 *	                "preparedByName": "唐飞",
	 *	                "preparedBy":3,
	 *	                "cashier":3,
	 *	                "cashierName":3,
	 *	                "checkTime":1432202590,
	 *	            }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function getReceivablesByid()
	{
		$param = $this->param;
		$id = isset($param['id'])?$param['id']:0;
		if(!$id)
		{
			throw new ApiException('参数错误', ERROR::RECEIVABLES_ERROR);
		}
		if(!Receivables::select(['status'])->where("id","=",$id)->where('status','!=','3')->first())
		{
			throw new ApiException('未知收款单', ERROR::RECEIVABLES_ID_IS_ERROR);
		}
		$du =  Receivables::getOneById($id);
		return  $this->success($du);
	}
	
	/**
	 * @api {post} /receivables/del 7.删除收款
	 * @apiName del
	 * @apiGroup receivables
	 *
	 *@apiParam {Number} id 删除必填,Id.
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
	 *		    "msg": "删除失败"
	 *		}
	 */
	public function del()
	{
		$param = $this->param;
		$query = Receivables::getQuery();
	
		$id = isset($param["id"])?$param["id"]:0;
	
		if(!$id)
		{
			throw new ApiException('参数错误', ERROR::RECEIVABLES_ERROR);
		}
		if(!Receivables::getCheckRecRsStatus($id))
		{
			throw new ApiException('未知收款单', ERROR::RECEIVABLES_ID_IS_ERROR);
		}
	
		$status = Receivables::dodel($id);
		if ($status)
		{
			return $this->success();
		}
		else
		{
			throw new ApiException('更新失败', ERROR::RECEIVABLES_UPDATE_FAILED);
		}
	}
}
?>