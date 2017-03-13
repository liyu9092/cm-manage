<?php namespace App\Http\Controllers;

use Illuminate\Pagination\AbstractPaginator;
use DB;
use Event;
use Excel;
use Auth;
use App\Order;
use Request;
use Storage;
use File;
use Fileentry;
use App\Commission;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class CommissionController extends Controller{
	/**
	 * @api {post} /commission/index 1.佣金单列表
	 * @apiName list
	 * @apiGroup Commission
	 *
	 * @apiParam {String} merchantname 可选,商户名关键字.
	 * @apiParam {String} salonname 可选,店铺名关键字.
	 * @apiParam {String} salonsn 可选,店铺编号.
	 * @apiParam {Number} start 可选,起始日期.
	 * @apiParam {String} group 统计方式,按日day或者按月month.
	 * @apiParam {String} end 可选,结束时间.
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 * @apiParam {String} sort_key 排序的键,比如:start_at,end_at;
	 * @apiParam {String} sort_type 排序方式,DESC或者ASC;默认DESC
	 *
	 *
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {String} id 佣金单ID.
	 * @apiSuccess {String} orderid 订单ID.
	 * @apiSuccess {String} salonid 店铺ID.
	 * @apiSuccess {Number} salonsn 店铺编号.
	 * @apiSuccess {Array} salonname 店铺名.
	 * @apiSuccess {Object} sn 佣金单编号.
	 * @apiSuccess {Object} amount 返佣金额.
	 * @apiSuccess {String} created_at 确认时间.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "total": 1,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 1,
	 *	        "from": 1,
	 *	        "to": 1,
	 *	        "data": [
	 *				{
	 *				"id": 101,
	 *				"salonsn": "SZ0420002",
	 *				"salonname": "唯那丝（黄金山店）",
	 *				"sn": "YJ-15082400029",
	 *				"amount": "100.86",
	 *				"date": "2015-08-24"
	 *				}
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
		if(empty($param['group']))
			$param['group'] = 'day';

		$query = Commission::getQueryByParam($param);

		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});

		if($param['group']=='month'){
			$query = $query->groupBy('salon.sn');
			$fields = array(
				'commission.id',
				'salon.sn as salonsn',
				'salon.salonname',
				DB::raw('sum(amount) as amount'),
				'commission.date'
			);
		}
		else if($param['group']=='day'){
			$fields = array(
				'commission.id',
				'salon.sn as salonsn',
				'salon.salonname',
				'commission.sn as sn',
				'commission.amount as amount',
				'commission.date'
			);
		}
		$result = $query->select($fields)->paginate($page_size)->toArray();
	    unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);

	}


	/**
	 * @api {post} /commission/export 2.导出佣金单
	 * @apiName export
	 * @apiGroup Commission
	 *
	 * @apiParam {String} merchantname 可选,商户名关键字.
	 * @apiParam {String} salonname 可选,店铺名关键字.
	 * @apiParam {String} salonsn 可选,店铺编号.
	 * @apiParam {Number} start 可选,起始日期.
	 * @apiParam {String} end 可选,结束时间.
	 * @apiParam {String} sort_key 排序的键,比如:start_at,end_at;
	 * @apiParam {String} sort_type 排序方式,DESC或者ASC;默认DESC
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": null
	 *	}
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
		if(empty($param['group']))
			$param['group'] = 'day';

		$query = Commission::getQueryByParam($param);

		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});

		if($param['group']=='month'){
			$query = $query->groupBy('salon.sn');
			$fields = array(
				'commission.id',
				'salon.sn as salonsn',
				'salon.salonname',
				DB::raw('sum(amount) as amount'),
				'commission.date'
			);
		}
		else if($param['group']=='day'){
			$fields = array(
				'commission.id',
				'salon.sn as salonsn',
				'salon.salonname',
				'commission.sn as sn',
				'commission.amount as amount',
				'commission.date'
			);
		}

		//分页
	    $array = $query->select($fields)->get();
	    $result = [];
	    foreach ($array as $key => $value) {
	    	$result[$key]['salonsn'] = $value->salonsn;
	    	$result[$key]['salonname'] = $value->salonname;
	    	$result[$key]['sn'] = $value->sn;
	    	$result[$key]['amount'] = $value->amount;
	    	$result[$key]['date'] = $value->date;
	    }
		// 触发事件，写入日志
	    Event::fire('commission.export');
		
		//导出excel	   
		$title = '佣金单列表'.date('Ymd');
		$header = ['店铺编号','店铺名称','返佣编号','金额','创建日期'];
		Excel::create($title, function($excel) use($result,$header){
					$excel->setTitle('commission');
		    		$excel->sheet('Sheet1', function($sheet) use($result,$header){
			        $sheet->fromArray($result, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
	        		$sheet->prependRow(1, $header);//添加表头

			    });
		})->export('xls');

	}


	/**
	 * @api {post} /commission/show/:id 3.查看佣金单信息
	 * @apiName show
	 * @apiGroup Commission
	 *
	 * @apiParam {Number} id 必填,返佣单ID.
	 *
	 * @apiSuccess {Number} id 返佣单ID.
	 * @apiSuccess {String} ordersn 订单编号.
	 * @apiSuccess {String} salonid 店铺ID.
	 * @apiSuccess {String} sn 佣金单编号.
	 * @apiSuccess {String} amount 金额.
	 * @apiSuccess {Number} created_at 创建日期.
	 * @apiSuccess {String} update_at 更新时间.
	 * @apiSuccess {String} salonname 店铺名.
	 * @apiSuccess {Number} salonnaid 店铺ID.
	 * @apiSuccess {String} merchantname 商户名.
	 * @apiSuccess {Number} merchantid 商户ID.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	  "result": 1,
	 *	  "token": "",
	 *	  "data": {
	 *		"id": 22,
	 *		"ordersn": "2727801211365",
	 *		"salonid": 780,
	 *		"sn": "YJ-15082400029",
	 *		"amount": "100.86",
	 *		"created_at": "2015-08-24 20:08:47",
	 *		"updated_at": "2015-08-24 20:08:47",
	 *		"salonname": "唯那丝（黄金山店）",
	 *		"salonsn": "SZ0420002",
	 *		"merchantname": "深圳市龙岗区坂田轩尼丝理发店",
	 *		"merchantid": 286
	 *	  }
	 *	}	
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function show($id)
	{
		$commission = Commission::join('salon', 'salon.salonid', '=', 'commission.salonid')
				 ->join('merchant', 'merchant.id', '=', 'salon.merchantid')
				 ->select('commission.*','salon.salonname','salon.sn as salonsn','merchant.name as merchantname','merchant.id as merchantid')
				 ->find($id);

		if(!$commission)
			throw new ApiException('未知佣金单', ERROR::COMMISSION_NOT_FOUND);
		return $this->success($commission); 
	}



}