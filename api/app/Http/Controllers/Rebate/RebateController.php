<?php namespace App\Http\Controllers;

use Illuminate\Pagination\AbstractPaginator;
use DB;
use Event;
use Excel;
use Auth;
use App\Rebate;
use Request;
use Storage;
use File;
use Fileentry;
use App\ShopCount;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class RebateController extends Controller{
	/**
	 * @api {post} /rebate/index 1.返佣单列表
	 * @apiName list
	 * @apiGroup Rebate
	 *
	 * @apiParam {String} merchantname 可选,商户名关键字.
	 * @apiParam {String} salonname 可选,店铺名关键字.
	 * @apiParam {String} salonsn 可选,店铺编号.
	 * @apiParam {Number} start 可选,起始日期.
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
	 * @apiSuccess {String} id 返佣单ID.
	 * @apiSuccess {String} salon_id 店铺ID.
	 * @apiSuccess {Number} salonsn 店铺编号.
	 * @apiSuccess {Array} salonname 店铺名.
	 * @apiSuccess {Object} sn 返佣单编号.
	 * @apiSuccess {Object} amount 返佣金额.
	 * @apiSuccess {Object} status 状态,1确认,2未确认.
	 * @apiSuccess {String} start_at 起始时间.
	 * @apiSuccess {String} end_at 结束时间.
	 * @apiSuccess {String} confirm_at 确认时间.
	 * @apiSuccess {String} confirm_by 确认人.
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
	 *	            {
	 *	                "id": 1,
	 *	                "salon_id": 1,
	 *	                "salonsn": "SZ0320001",
	 *	                "salonname": "嘉美专业烫染",
	 *	                "sn": "FY-15081400011",
	 *	                "amount": "0.00",
	 *	                "status": 2,
	 *	                "start_at": "0000-00-00 00:00:00",
	 *	                "end_at": "0000-00-00 00:00:00",
	 *	                "created_at": "0000-00-00 00:00:00",
	 *	                "confirm_at": "0000-00-00 00:00:00",
	 *	                "confirm_by": "administrator",
	 *	                "created_by": "administrator"
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
		$query = Rebate::getQueryByParam($param);
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});

		$fields = array(
		    'rebate.id as id',
			'salon_id',
			'salon.sn as salonsn',
			'salon.salonname',
			'rebate.sn as sn',
			'amount',
			'note',
			'rebate.status as status',
			'start_at',
			'end_at',
			'created_at',
			'confirm_at',
			'confirm_by',
			'created_by'
		);

		//分页
	    $result = $query->select($fields)->paginate($page_size)->toArray();
	    unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);

	}


	/**
	 * @api {post} /rebate/export 2.导出返佣单
	 * @apiName export
	 * @apiGroup Rebate
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
		$query = Rebate::getQueryByParam($param);

		$fields = array(
		    'rebate.id as id',
			'salon_id',
			'salon.sn as salonsn',
			'salon.salonname',
			'rebate.sn as sn',
			'amount',
			'note',
			'rebate.status as status',
			'start_at',
			'end_at',
			'confirm_at',
			'confirm_by',
			'created_at',
			'created_by',
		);

		//分页
	    $array = $query->select($fields)->get();
	    foreach ($array as $key => $value) {
	    	$result[$key]['salonsn'] = $value->salonsn;
	    	$result[$key]['salonname'] = $value->salonname;
	    	$result[$key]['sn'] = $value->sn;
	    	$result[$key]['start_at'] = substr($value->start_at, 0,10);
	    	$result[$key]['end_at'] = substr($value->end_at, 0,10);
	    	$result[$key]['amount'] = $value->amount;
	    	$result[$key]['created_at'] = substr($value->created_at, 0,10);
	    	$result[$key]['confirm_at'] = substr($value->confirm_at, 0,10);
	    	$result[$key]['confirm_by'] = $value->confirm_by;
	    	$result[$key]['created_by'] = $value->created_by;
	    	$result[$key]['note'] = $value->note;
	    	$result[$key]['status'] = $value->status==1?'已确认':'待确认';
	    }
		// 触发事件，写入日志
	    Event::fire('rebate.export');
		
		//导出excel	   
		$title = '返佣单列表'.date('Ymd');
		$header = ['店铺编号','店铺名称','返佣编号','结算起始日','结算截止日','金额','创建日期','确认日期','确认人','制单人','备注','状态'];
		Excel::create($title, function($excel) use($result,$header){
					$excel->setTitle('rebate');
		    		$excel->sheet('Sheet1', function($sheet) use($result,$header){
			        $sheet->fromArray($result, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
	        		$sheet->prependRow(1, $header);//添加表头

			    });
		})->export('xls');

	}

	 /**
	 * @api {post} /rebate/create 3.新增返佣单
	 * @apiName create
	 * @apiGroup Rebate
	 *
	 * @apiParam {Number} salon_id 店铺ID.
	 * @apiParam {String} start_at 结算起始日.
	 * @apiParam {String} end_at 结算截止日.
	 * @apiParam {Number} amount 金额.
	 *
	 * @apiSuccessExample Success-Response:
	 *	    {
	 *	        "result": 1,
	 *	        "data": null
	 *	    }
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "创建失败"
	 *		}
	 */
	public function create()
	{
		$param = $this->param;
		$rebate = new Rebate;
		if($param['end_at']<$param['start_at'])
			return $this->error('截止日不能小于起始日');
		$param['created_by'] = $this->user->name;
		$param['status'] = 2;
		$sn = $rebate->getSn();
		$param['sn'] = $sn;
		$rebate = Rebate::create($param);
		if($rebate){
			// 触发事件，写入日志
		    Event::fire('rebate.create',[$rebate]);
		    return $this->success();
		}
		else 
			throw new ApiException('创建失败', ERROR::REBATE_CREATE_FAILED);
	}


/**
	 * @api {post} /rebate/update 8.修改返佣单
	 * @apiName update
	 * @apiGroup Rebate
	 *
	 * @apiParam {Number} salon_id 店铺ID.
	 * @apiParam {String} start_at 结算起始日.
	 * @apiParam {String} end_at 结算截止日.
	 * @apiParam {Number} amount 金额.
	 *
	 * @apiSuccessExample Success-Response:
	 *	    {
	 *	        "result": 1,
	 *	        "data": null
	 *	    }
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "修改失败"
	 *		}
	 */
	public function update($id)
	{
		$param = $this->param;
		$rebate = Rebate::find($id);
		if(!$id)
			throw new ApiException('未知返佣单', ERROR::REBATE_NOT_FOUND);
		
		$result = $rebate->update($param);
		if($result){
			// 触发事件，写入日志
		    Event::fire('rebate.update',[$rebate]);
		    return $this->success();
		}
		else 
			throw new ApiException('更新失败', ERROR::REBATE_UPDATE_FAILED);
	}

	/**
	 * @api {post} /rebate/show/:id 4.查看返佣单信息
	 * @apiName show
	 * @apiGroup Rebate
	 *
	 * @apiParam {Number} id 必填,返佣单ID.
	 *
	 * @apiSuccess {Number} id 返佣单ID.
	 * @apiSuccess {String} salon_id 店铺ID.
	 * @apiSuccess {String} sn 返佣单编号.
	 * @apiSuccess {String} amount 金额.
	 * @apiSuccess {String} status 状态,1已确认,2未确认.
	 * @apiSuccess {String} start_at 起始日期.
	 * @apiSuccess {String} end_at 结束日期.
	 * @apiSuccess {String} confirm_by 确认人.
	 * @apiSuccess {String} confirm_at 确认日期.
	 * @apiSuccess {String} created_by 创建人.
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
	 *       "id": 1,
	 *       "salon_id": 1,
	 *       "sn": "FY-15081400011",
	 *       "amount": "0.00",
	 *       "status": 1,
	 *       "start_at": "0000-00-00 00:00:00",
	 *       "end_at": "0000-00-00 00:00:00",
	 *       "confirm_by": null,
	 *       "confirm_at": "0000-00-00 00:00:00",
	 *       "created_by": "这是用户名Admin",
	 *       "created_at": "2015-08-14 16:38:46",
	 *       "updated_at": "2015-08-17 14:51:35",
	 *       "salonname": "嘉美专业烫染",
	 *       "salonsn": "SZ0320001",
	 *       "merchantname": "深圳市南山区嘉美季理发店",
	 *       "merchantid": 475	
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
		$rebate = Rebate::join('salon', 'salon.salonid', '=', 'rebate.salon_id')
				 ->join('merchant', 'merchant.id', '=', 'salon.merchantid')
				 ->select('rebate.*','salon.salonname','salon.sn as salonsn','merchant.name as merchantname','merchant.id as merchantid')
				 ->find($id);
		
		if(!$rebate)
			throw new ApiException('未知返佣单', ERROR::REBATE_NOT_FOUND);
		return $this->success($rebate); 
	}

	/**
	 * @api {post} /rebate/confirm 5.确认返佣单
	 * @apiName confirm
	 * @apiGroup Rebate
	 *
	 * @apiParam {Array} rebate 必填,返佣单ID,数组,例如[1,2,3].
	 *
	 *
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "token": "",
	 *		    "data": {
	 *		        "total": 3,
	 *		        "success": 1,
	 *		        "error": 2
	 *		    }
	 *		}
	 * 
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "确认失败",
	 *		    "token": "",
	 *		    "code": 0
	 *		}
	 */
	public function confirm()
	{
		$param = $this->param;
		if(empty($param['rebate']))
			throw new ApiException('未指定返佣单', ERROR::REBATE_NOT_DEFINED);
		DB::beginTransaction();
		
		$rebates = Rebate::whereIn('id',$param['rebate'])
			->join('salon','salon.salonid','=','rebate.salon_id')
			->select(['id','salon_id','amount','merchantId','rebate.status','rebate.start_at','rebate.end_at'])
			->get();
		$result = 0;
		foreach ($rebates as $key => $rebate) {
			if($rebate->status==1)
				continue;
			$date = date('Y-m-d H:i:s');
			ShopCount::count_bill_by_commission_return_money($rebate->salon_id,$rebate->merchantId,$rebate->amount,'返佣起始日:'.$rebate->start_at.'</br>'.'佣金截止日:'.$rebate->end_at,$date);
			$update = $rebate->update(['status'=>1,'confirm_at'=>$date,'confirm_by'=>$this->user->name]);
			$result++;
		}

		if($result>0)
		{
			DB::commit();
			// 触发事件，写入日志
		    Event::fire('rebate.confirm',[$param['rebate']]);
		    $data['total'] = count($param['rebate']);
		    $data['success'] = $result;
		    $data['error'] =$data['total'] - $result;
			return $this->success($data);
		}
		else
		{
			DB::rollback();
			throw new ApiException('确认失败', ERROR::REBATE_UPDATE_FAILED);
		}
	}

	/**
	 * @api {post} /rebate/upload 6.导入返佣单
	 * @apiName upload
	 * @apiGroup Rebate
	 *
	 * @apiParam {File} rebate 必填,excel文件.
	 *
	 *
	 * @apiSuccessExample Success-Response:
	 *	    {
	 *	        "result": 1,
	 *	        "data": null
	 *	    }
	 * 
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */

	public function upload(){
		$file = Request::file('rebate');
		if(!$file)
			throw new ApiException('请上传文件', ERROR::FILE_EMPTY);
		$rebate = new Rebate;
		$extension = $file->getClientOriginalExtension();
		if(!in_array($extension, ['xls','xlsx']))
			throw new ApiException('请上传xls或者xlsx文件', ERROR::FILE_FORMAT_ERROR);

		$result = false;
		Excel::load($file->getPathname(), function($reader) use($rebate,&$result){
	    	$reader = $reader->getSheet(0);
		    $array = $reader->toArray();
		    array_shift($array);
		    $data = [];
		    foreach ($array as $key => $value) {
		    	if(empty($value[0])||empty($value[2])||empty($value[3])||empty($value[4])||empty($value[5]))
		    		continue;
		    	$date = date('Y-m-d H:i:s');
		    	$salonsn = $value[0];
		    	$salonid = $rebate->getSalonid($salonsn);
		    	if(!$salonid)
		    		continue;
		    	$data[$key]['salon_id'] = $salonid;
		    	$data[$key]['start_at'] = $value[3];
		    	$data[$key]['end_at'] = $value[4];
		    	$data[$key]['amount'] = $value[5];
	    		$data[$key]['created_by'] = $this->user->name;
				$data[$key]['note'] = $value[9];
				$data[$key]['created_at'] = $date;
				$data[$key]['updated_at'] = $date;
				$data[$key]['status'] = 2;
				$sn = $rebate->getSn();
				$data[$key]['sn'] = $sn;
		    }
		    if(!empty($data))
		   		$result = $rebate->insert($data);

		},'UTF-8');

		$name =$rebate->getName();
		$folder = date('Y/m/d').'/';
		$src = $folder.$name.'.'.$extension;
		Storage::disk('local')->put($src,  File::get($file));
 		if($result)
 			return $this->success();
 		throw new ApiException('返佣单导入失败', ERROR::REBATE_UPLOAD_FAILED);
	}

	/**
	 * @api {post} /rebate/destroy/{id} 7.删除返佣单
	 * @apiName destroy
	 * @apiGroup Rebate
	 *
	 * @apiParam {Number} id 返佣单ID.
	 *
	 *
	 * @apiSuccessExample Success-Response:
	 *	    {
	 *	        "result": 1,
	 *	        "data": null
	 *	    }
	 * 
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "返佣单不存在"
	 *		}
	 */
	public function destroy($id)
	{
		$rebate = Rebate::find($id);
		if(!$rebate)
			throw new ApiException('返佣单不存在', ERROR::REBATE_NOT_FOUND);
		$result = $rebate->delete();
		if($result)
			return $this->success();
		throw new ApiException('返佣单删除失败', ERROR::REBATE_DELETE_FAILED);
	}


}