<?php namespace App\Http\Controllers;

use App\Item;
use App\SalonItemFormats;
use App\SalonItem;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use DB;

class ItemController extends Controller{

	/**
	 * @api {post} /item/index 1.项目列表
	 * @apiName index
	 * @apiGroup Item
	 *
	 * @apiParam {String} salonid 可选,店铺ID.
	 * @apiParam {String} itemname 可选,项目名.
	 * @apiParam {String} typeid 可选,项目分类.
	 * @apiParam {Number} norms_cat_id 可选,项目规格,1为有规格,2为无规格.
	 * @apiParam {String} exp_time 可选,期限限制,1为有期限限制,2为不限制.
	 * @apiParam {Number} total_rep 可选,库存数,1为有限制,2为无限制.
	 * @apiParam {Number} buylimit 可选,购买限制,1为首单限制,2为邀请限制.
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
	 * @apiSuccess {Number} itemid 项目ID.
	 * @apiSuccess {Number} salonid 店铺ID.
	 * @apiSuccess {Number} itemname 项目名.
	 * @apiSuccess {Number} typename 项目分类.
	 * @apiSuccess {Number} minPrice 臭美价.
	 * @apiSuccess {Number} minPriceOri 原价.
	 * @apiSuccess {Number} minPriceGroup 集团价.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": {
	 *	        "total": 43353,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 2168,
	 *  	    "from": 1,
	 *	        "to": 20,
	 *	        "data": [
	 *	            {
	 *	                "itemid": 44977,
	 *	                "salonid": 1216,
	 *	                "itemname": "老姜洗发水",
	 *	                "typename": "其他",
	 *	                "minPrice": 25,
	 *	                "maxPrice": 25,
	 *	                "minPriceOri": 30,
	 *	                "maxPriceOri": 30,
	 *	                "minPriceGroup": "23.00",
	 *	                "maxPriceGroup": "23.00",
	 *	                "format": "性别,造型师"
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
	public function index(){
		$param = $this->param;
		$param['item_type'] = Item::SALE;
		$param['status'] = Item::UP;
		$query = Item::getQueryByParam($param);
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});

		$fields = array(
		    'itemid',
		    'salonid',
			'itemname',
			'typename',
			'minPrice',
			'maxPrice',
			'minPriceOri',
			'maxPriceOri',
			'minPriceGroup',
			'maxPriceGroup',
			'norms_cat_id',
			'sort_in_type',
			'userId'
		);

		//分页
	    $result = $query->select($fields)->paginate($page_size)->toArray();
	    //获取规格
	    $norms_cat_ids = array_column($result['data'], "norms_cat_id");
        $format_infos = SalonItemFormats::getItemsByNormscatids($norms_cat_ids);
        $result['data'] = SalonItem::composite($result['data'],$format_infos);

	    unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);
	}


	/**
	 * @api {post} /item/show/:id 2.项目详情
	 * @apiName show
	 * @apiGroup Item
	 *
	 * @apiParam {String} id 必填,项目ID.
	 *
	 *
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {Number} itemid 项目ID.
	 * @apiSuccess {Number} itemname 项目名.
	 * @apiSuccess {Number} typename 项目分类.
	 * @apiSuccess {Number} addserviceStr 增值服务.
	 * @apiSuccess {Number} detail 项目详情.
	 * @apiSuccess {Number} exp_time 过期时间.
	 * @apiSuccess {Number} timingAdded 定时上架时间.
	 * @apiSuccess {Number} timingShelves 定时下架时间.
	 * @apiSuccess {Number} limit_time 限制购买次数.
	 * @apiSuccess {Number} total_rep 总库存.
	 * @apiSuccess {Number} limit_invite 限制邀请购买.
	 * @apiSuccess {Number} limit_first 限制首单购买.
	 * @apiSuccess {Number} sold 已售数量.
	 * @apiSuccess {Number} up_time 上架时间.
	 * @apiSuccess {Number} name 最后更新人.
	 * @apiSuccess {Number} UPDATE_date 最后更新时间.
	 * @apiSuccess {Number} sort_in_type 排序,大的在前面.
	 * @apiSuccess {Number} prices 各种规格对应的价格.
	 * @apiSuccess {Number} price 原价.
	 * @apiSuccess {Number} price_dis 臭美价.
	 * @apiSuccess {Number} price_group 集团价.
	 * @apiSuccess {Number} status 状态 1上架2下架3删除.
	 * @apiSuccess {Number} salon_item_format_id 规格ID.
	 * @apiSuccess {Number} formats 规格名称.
	 * 
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": {
	 *	        "itemid": 192,
	 *	        "itemname": "洗剪造型",
	 *	        "typename": "洗剪吹",
	 *	        "addserviceStr": "",
	 *	        "detail": null,
	 *	        "exp_time": 0,
	 *	        "timingAdded": 0,
	 *	        "timingShelves": 0,
	 *	        "limit_time": null,
	 *	        "total_rep": 0,
	 *	        "limit_invite": null,
	 *	        "limit_first": null,
	 *	        "sold": 0,
	 *	        "up_time": 1422536851,
	 *	        "name": null,
	 *	        "UPDATE_date": "2015-06-15 09:15:45",
	 *	        "sort_in_type": 0,
	 *	        "prices": [
	 *	            {
	 *	                "price": "58.00",
	 *	                "price_dis": "29.00",
	 *	                "price_group": "0.00",
	 *	                "salon_item_format_id": "6988",
	 *	                "formats": [
	 *	                    {
	 *	                        "format_name": "高级设计师",
	 *	                        "formats_name": "造型师",
	 *	                        "salon_item_formats_id": 1840
	 *	                    }
	 *	                ]
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
	public function show($id){
		$item = Item::get($id);
		if(!$item)
			throw new ApiException('未知项目ID', ERROR::ITEM_NOT_FOUND);
		return $this->success($item);
			
	}

	/**
	 * @api {post} /item/type 3.项目分类
	 * @apiName type
	 * @apiGroup Item
	 *
	 *
	 * @apiSuccess {Number} typeid  分类ID.
	 * @apiSuccess {Number} typename 分类名称.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
	 *	        {
	 *	            "typeid": 1,
	 *	            "typename": "洗剪吹"
	 *	        }
	 *	    ]
	 *	}	
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function type(){
		return $this->success(
			DB::table('salon_itemtype')
			->where('status','=',1)
			->orderBy('sortIt','desc')
			->get()
			);
	}



	/**
	 * @api {post} /item/sort 4.项目排序
	 * @apiName sort
	 * @apiGroup Item
	 *
	 *
	 * @apiSuccess {Number} sort  分类数组,包含以下两个元素.
	 * @apiSuccess {Number} itemid  项目ID.
	 * @apiSuccess {Number} sort_in_type 排序值,大的在前面.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
	 *	    ]
	 *	}	
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function sort(){
		$param = $this->param;
		$sorts = $param['sort'];
		foreach ($sorts as $sort) {
			$item = Item::find($sort['itemid']);
			$item->update(['sort_in_type'=>$sort['sort_in_type']]);
		}
		return $this->success();
	}



	/**
	 * @api {post} /item/export 5.导出项目
	 * @apiName export
	 * @apiGroup Item
	 *
	 * @apiParam {String} salonid 可选,店铺ID.
	 * @apiParam {String} itemname 可选,项目名.
	 * @apiParam {String} typeid 可选,项目分类.
	 * @apiParam {Number} norms_cat_id 可选,项目规格,1为有规格,2为无规格.
	 * @apiParam {String} exp_time 可选,期限限制,1为有期限限制,2为不限制.
	 * @apiParam {Number} total_rep 可选,库存数,1为有限制,2为无限制.
	 * @apiParam {Number} buylimit 可选,购买限制,1为首单限制,2为邀请限制.
	 * @apiParam {String} sort_key 排序的键,比如:start_at,end_at;
	 * @apiParam {String} sort_type 排序方式,DESC或者ASC;默认DESC
	 *
	 *
	 * @apiSuccess {File} Json文件.
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function export(){
		$param = $this->param;
		$param['item_type'] = Item::SALE;
		$param['status'] = Item::UP;
		$query = Item::getQueryByParam($param);

		$fields = [
			'salon_item.*',
			'salon_item_buylimit.limit_time',
			'salon_item_buylimit.limit_invite',
			'salon_item_buylimit.limit_first'
		];
	    $result = $query->take(100)->get();

	    $error = [];
	    foreach ($result as &$item) {
	    	if($item['userId']==0&&$item['norms_cat_id']!=0){
	    		$error[] = $item;
	    		continue;
	    	}	
	    	$item['prices'] = Item::getPrice($item['itemid']);
	    }

	    if(!empty($error)){
	    	$msg = '';
	    	foreach ($error as $key => $value) {
	    		$msg .= '项目：【'.$value['itemname'].'】</br>';
	    	}
	    	$msg .= '存在未修改过的多规格项目，请按现项目格式修改过后再导出';
	    	return $msg;
	    }


	    $result = json_encode($result);

		$filename = '项目列表-'.date('Ymd').'.json';
		header("Content-type: text/plain");
        header("Accept-Ranges: bytes");
        header("Content-Disposition: attachment; filename=".$filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0" );
        header("Pragma: no-cache" );
        header("Expires: 0" ); 
        exit($result);
	}


	 /**
     * @api {get} /item/down/:id 6.下架
     * @apiName down
     * @apiGroup item
     *
     * @apiParam {Number} id  要下架的id
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function down($id)
    {	
    	$item = Item::find($id);
		if(!$item)
			throw new ApiException('未知项目ID', ERROR::ITEM_NOT_FOUND);

        $result = $item->update(['status'=>SalonItem::STATUS_OF_DOWN,'updateTime'=>time()]);
        if($result)
        {
            return $this->success([]);
        }                
    }
    
    

}
?>