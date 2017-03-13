<?php namespace App\Http\Controllers\Item;

use App\Http\Controllers\Controller;
use DB;
use App\SalonItemType;
use App\SalonItem;
use App\Hairstylist;
use App\Addedservice;
use App\AddedserviceItemtype;
use App\AddedserviceSalon;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\SalonItemBuylimit;
use App\SalonItemFormatPrice;
use App\SalonItemFormats;
use App\SalonItemFormat;
use App\SalonNormsCat;
use App\SalonNorms;
use App\Salon;
use App\Utils;
class ItemInfoController extends Controller{

	//规格菜单英文标识 保持和前端一致
	private static  $_typeArr = ['sex'=>'性别','hairstylist'=>'造型师','longhair'=>'发长','solution'=>'药水'];
	
	/**
	 * @api {post} /itemInfo/index 1.店铺项目资料列表
	 * @apiName index
	 * @apiGroup  itemInfo
	 * 
	 * @apiParam {Number} key 可选,1店铺 2商户.
	 * @apiParam {String} keyword 可选,关键字.
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 * 
	 * @apiSuccess {Number} salonid 店铺id.
	 * @apiSuccess {String} salonname 店铺名称.
	 * @apiSuccess {Number} generalNums 普通项目数量.
	 * @apiSuccess {Number} specialNums 限时特价数量.
	 * @apiSuccess {Number} wareroomNums 项目仓库数量.
	 * @apiSuccess {Number} hairstyNums 造型师数量.
	 *
	 * @apiSuccessExample Success-Response:
	 *{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
	 *	    {
     *	    	    "salonid": 691,
     *	    	    "salonname": "choumeitest_salon",
     *	    	    "generalNums": 12,
     *	    	    "specialNums": 43,
     *	    	    "wareroomNums": 42,
     *	    	    "hairstyNums": 36
     *	    },
	 *			......
	 *	    ]
	 *}
	 *
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
		$key = isset($param['key'])?intval($param['key']):0;
		$keyword = isset($param['keyword'])?trim($param['keyword']):'';
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		$result = SalonItem::getSalonItem($key,$keyword,$page,$page_size);
		unset($result['next_page_url']);
		unset($result['prev_page_url']);
		return $this->success($result);
	}	
	
	
	/**
	 * @api {post} /itemInfo/getItems 2.获取分类下项目名称
	 * @apiName getItems
	 * @apiGroup  itemInfo
	 *
	 * @apiParam {Number} typeid 必填,项目分类id.
	 * @apiParam {Number} salonid 必填,店铺id.
	 * 
	 * @apiSuccess {Number} itemid 项目id.
	 * @apiSuccess {String} itemname 项目名称.
	 *
	 * @apiSuccessExample Success-Response:
	 *{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
	 *	        {
	 *	            "itemid": 29888,
	 *	            "itemname": "总监快剪-洗剪吹造型"
	 *	        },
	 *	        {
	 *	            "itemid": 29890,
	 *	            "itemname": "普通快剪--洗剪吹造型"
	 *	        },
	 *	        {
	 *	            "itemid": 29892,
	 *	            "itemname": "洗剪吹造型"
	 *	        },
	 *			......
	 *	    ]
	 *	}
	 *
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function getItemByTypeid()
	{
		$param = $this->param;
		$data['typeid'] = isset($param['typeid'])?intval($param['typeid']):0;
		$data['item_type'] = 1;//商品类型，1 默认在售，2 限时特价
		$data['salonid'] = isset($param['salonid'])?intval($param['salonid']):0;
		
		if(!$data['typeid'] || !$data['salonid'])
			 throw new ApiException('参数错误', ERROR::ITEM_ERROR);
		
		$items = SalonItem::select(['itemid','itemname'])->where($data)->get();
		return $this->success($items);
	}
	
	/**
	 * @api {post} /itemInfo/getAddedService 3.获取店铺项目分类下增值服务
	 * @apiName getAddedService
	 * @apiGroup  itemInfo
	 *
	 * @apiParam {Number} typeid 必填,项目分类id.
	 * @apiParam {Number} salonid 必填,店铺id.
	 *
	 * @apiSuccess {Number} sId 增值服务id.
	 * @apiSuccess {String} sName 增值服名称.
	 *
	 * @apiSuccessExample Success-Response:
	 *{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
	 *	        {
	 *	            "sId": 1,
	 *	            "sName": "进口面贴"
	 *	        },
	 *	        {
	 *	            "sId": 2,
	 *	            "sName": "进口烫染隔离霜"
	 *	        },
	 *			......
	 *	    ]
	 *	}
	 *
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function getAddedService()
	{
		$param = $this->param;
		$itemTypeId = isset($param['typeid'])?intval($param['typeid']):0;
		$salonid = isset($param['salonid'])?intval($param['salonid']):0;
		
		if(!$salonid || !$itemTypeId)
			throw new ApiException('参数错误', ERROR::ITEM_ERROR);

		$where['salonId'] = $salonid;
		$where['itemTypeId'] = $itemTypeId;
		$sInfo = AddedserviceSalon::where($where)->first();
		if(!$sInfo)
			return $this->success();
		
		$iwhere['itemType']=$itemTypeId;
		$iInfo = AddedserviceItemtype::select(['service_detail'])->where($iwhere)->first();
		if(!$iInfo['service_detail'])
			return $this->success();
		
		$detail = $iInfo['service_detail'];
		$list = Addedservice::select(['sId','sName'])->whereRaw("sId in ({$detail})")->get();
		return $this->success($list);
	}
	
	/**
	 * @api {post} /itemInfo/create 4.新增普通项目
	 * @apiName create
	 * @apiGroup  itemInfo
	 *
	 * @apiParam {Number} salonid  必填,店铺id.
	 * @apiParam {Number} typeid 必填,项目分类id.
	 * @apiParam {string} useLimit 可选,消费限制（特价项目）.
	 * @apiParam {string} logo 必填,logo.
	 * @apiParam {string} desc 必填,服务详情.
	 * @apiParam {Number} itemType 必填,商品类型，1 默认在售，2 限时特价.
	 * @apiParam {string} itemname 必填,项目名称
	 * @apiParam {Number} fastGrade 可选,快剪等级 1普通快剪 2总监快剪.
	 * @apiParam {string} repertory 可选,日库存（兑换专用+特价项目）.
	 * @apiParam {string} expTimeInput 可选,项目有效期（Y-m-d） 无限制 不传或0.
	 * @apiParam {Number} totalRepInput 可选,项目总库存 无限制 不传或0.
	 * @apiParam {array}  addedService 可选,增值服务(数组).
	 * @apiParam {string} timingAdded 可选,定时上架（Y-m-d H:i:s）.
	 * @apiParam {string} timingShelves 可选,定时下架（Y-m-d H:i:s）.
	 * @apiParam {string} timeLimitInput 可选,单人限制购买数 无限制 不传或0.
	 * @apiParam {string} inviteLimit 可选,1 限推荐用户购买 无限制 不传或0.
	 * @apiParam {string} firstLimit 可选,1 限首次下单可购买  无限制 不传或0.
	 * 
	 * @apiParam {string} priceStyle 必选,1无规格2有规格.
	 * @apiParam {string} price 无规格必填,原价.
	 * @apiParam {string} priceDis 无规格必填,臭美价.
	 * @apiParam {string} priceGroup 可选,集团价.
	 * 
	 * @apiParam {string} normMenu 有规格必填（json字符串）,规格说明  ["sex","hairstylist","longhair","solution"]（sex 性别 hairstylist造型师 longhair发长 solution药水 ）.
	 * @apiParam {string} normarr 有规格必填（json字符串）,规格模板价格price 价格 priceDis 臭美价 priceGroup集团价
	 * [
	 *	    {
	 *	        "type": {
	 *	            "sex": "男",
	 *	            "hairstylist": "高级造型师",
	 *	            "longhair": "长发",
	 *	            "solution": "普通药水"
	 *	        },
	 *	        "price": "50",
	 *	        "priceDis": "40",
	 *	        "priceGroup": "38"
	 *	    },
	 *	    {
	 *	        "type": {
	 *	            "sex": "男",
	 *	            "hairstylist": "普通造型师",
	 *	            "longhair": "中发",
	 *	            "solution": "臭美药水"
	 *	        },
	 *	        "price": "80",
	 *	        "priceDis": "48",
	 *	        "priceGroup": "30"
	 *	    },
	 *		.........	
	 *	]
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
	 *		    "msg": "参数错误"
	 *		}
	 */
	public function store()
	{
		$param = $this->param;
		return $this->save($param);

	}
	
	/**
	 * @api {post} /itemInfo/createSpecialItem 5.添加特价项目
	 * @apiName createSpecialItem
	 * @apiGroup  itemInfo
	 * 
	 * @apiParam {String} item 和添加普通项目参数保值一致.
	 * 
	 */
	public function createSpecialItem()
	{
		$param = $this->param;
		return $this->save($param);
	
	}
	
	/**
	 * @api {post} /itemInfo/updateSpecialItem 6.修改特价项目
	 * @apiName updateSpecialItem
	 * @apiGroup  itemInfo
	 * 
	 * @apiParam {String} item 和修改普通项目参数保值一致.
	 * 
	 */
	public function updateSpecialItem()
	{
		$param = $this->param;
		return $this->save($param);
	
	}
	
	/**
	 * @api {post} /itemInfo/update 7.修改项目
	 * @apiName update
	 * @apiGroup  itemInfo
	 *
	 * @apiParam {Number} itemid  必填,项目id.
	 * @apiParam {Number} salonid  必填,店铺id.
	 * @apiParam {Number} typeid 必填,项目分类id.
	 * @apiParam {string} useLimit 可选,消费限制（特价项目）.
	 * @apiParam {string} logo 必填,logo.
	 * @apiParam {string} desc 必填,服务详情.
	 * @apiParam {Number} itemType 必填,商品类型，1 默认在售，2 限时特价.
	 * @apiParam {string} itemname 必填,项目名称
	 * @apiParam {Number} fastGrade 可选,快剪等级 1普通快剪 2总监快剪.
	 * @apiParam {string} repertory 可选,日库存（兑换专用+特价项目）.
	 * @apiParam {string} expTimeInput 可选,项目有效期（Y-m-d） 无限制 不传或0.
	 * @apiParam {Number} totalRepInput 可选,项目总库存 无限制 不传或0.
	 * @apiParam {array}  addedService 可选,增值服务(数组).
	 * @apiParam {string} timingAdded 可选,定时上架（Y-m-d H:i:s）.
	 * @apiParam {string} timingShelves 可选,定时下架（Y-m-d H:i:s）.
	 * @apiParam {string} timeLimitInput 可选,单人限制购买数 无限制 不传或0.
	 * @apiParam {string} inviteLimit 可选,1 限推荐用户购买 无限制 不传或0.
	 * @apiParam {string} firstLimit 可选,1 限首次下单可购买  无限制 不传或0.
	 *
	 * @apiParam {string} priceStyle 必选,1无规格2有规格.
	 * @apiParam {string} price 无规格必填,原价.
	 * @apiParam {string} priceDis 无规格必填,臭美价.
	 * @apiParam {string} priceGroup 可选,集团价.
	 *
	 * @apiParam {string} normMenu 有规格必填（json字符串）,规格说明  ["sex","hairstylist","longhair","solution"]（sex 性别 hairstylist造型师 longhair发长 solution药水 ）.
	 * @apiParam {string} normarr 有规格必填（json字符串）,规格模板价格price 价格 priceDis 臭美价 priceGroup集团价
	 * [
	 *	    {
	 *	        "type": {
	 *	            "sex": "男",
	 *	            "hairstylist": "高级造型师",
	 *	            "longhair": "长发",
	 *	            "solution": "普通药水"
	 *	        },
	 *	        "price": "50",
	 *	        "priceDis": "40",
	 *	        "priceGroup": "38"
	 *	    },
	 *	    {
	 *	        "type": {
	 *	            "sex": "男",
	 *	            "hairstylist": "普通造型师",
	 *	            "longhair": "中发",
	 *	            "solution": "臭美药水"
	 *	        },
	 *	        "price": "80",
	 *	        "priceDis": "48",
	 *	        "priceGroup": "30"
	 *	    },
	 *		.........
	 *	]
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
	 *		    "msg": "参数错误"
	 *		}
	 */
	public function update()
	{
		$param = $this->param;
		return $this->save($param);
	}
	
	/**
	 * 添加修改控制器集中处理
	 * */
	public function save($param)
	{
		$itemid = isset($param['itemid'])?intval($param['itemid']):null;
		$param['userId'] = $this->user->id;
		$err_msg = [];
		$ret = self::parametersFilter($param, $err_msg);
		if(!$ret)
		{
			throw new ApiException($err_msg['msg'],$err_msg['no']);
		}
		
		$data = self::compositeData($param);
		$res = SalonItem::upsertItem($data,$param['priceStyle'],$param['salonid'],$itemid);
		if($res)
		{
			return $this->success();
		}
		else 
		{
			throw new ApiException('操作失败',ERROR::ITEM_ERROR);
		}
	}
	
	/**
	 * 项目添加修改 参数过滤
	 * */
	public static function parametersFilter($param,&$err_msg)
	{
	    $itemid = isset($param['itemid'])?intval($param['itemid']):null;
	    $is_new = empty($itemid)?true:false;
	    if(!$param['salonid'] || !$param['typeid'] || !$param['logo'] || !$param['itemname'] || !$param['desc'] || !in_array($param['priceStyle'], [1,2]))
	    {
	        $err_msg = ['msg'=>'参数错误','no'=>ERROR::ITEM_ERROR];
	        return false;
	    }
	    $timingAdded  = isset($param['timingAdded'])?trim($param['timingAdded']):0;//定时上架
	    $timingShelves  = isset($param['timingShelves'])?trim($param['timingShelves']):0;//定时下架

	    if(!$itemid && $timingAdded)
	    {
	    	$data['timingAdded'] = strtotime($timingAdded);
	    	if($data['timingAdded'] < time())
	    	{
	    		$err_msg = ['msg'=>'日期或时间设置错误，必须大于当前时间','no'=>ERROR::ITEM_ERROR];
	    		return false;
	    	}
	    }
	    if(!$itemid && $timingShelves)
	    {
	    	$data['timingShelves'] = strtotime($timingShelves);
	    	if($data['timingShelves'] < time())
	    	{
	    		$err_msg = ['msg'=>'日期或时间设置错误，必须大于当前时间','no'=>ERROR::ITEM_ERROR];
	    		return false;
	    	}
	    }
	    if($timingAdded && $timingShelves)
	    {
	    	$data['timingAdded'] = strtotime($timingAdded);
	    	$data['timingShelves'] = strtotime($timingShelves);
	    	if($data['timingAdded'] >= $data['timingShelves'])
	    	{
	    		$err_msg = ['msg'=>'下架时间必须大于上架时间','no'=>ERROR::ITEM_ERROR];
	    		return false;
	    	}
	    }

	    if($itemid)
	    {
	    	$itemInfo = SalonItem::where(['itemid'=>$itemid])->select(['itemid','salonid','sold','timingAdded','typeid','norms_cat_id','status'])->first();
	    	if(!$itemInfo)
	    	{
	    		$err_msg = ['msg'=>'数据错误,项目id不存在！','no'=>ERROR::ITEM_DATA_ERROR];
	    		return false;
	    	}
	    	if($itemInfo->status == SalonItem::STATUS_OF_UP)
	    	{
	    		$data['timingShelves'] = strtotime($timingShelves);
		    	if($data['timingShelves'] < time() && $timingShelves)
		    	{
		    		$err_msg = ['msg'=>'日期或时间设置错误，必须大于当前时间','no'=>ERROR::ITEM_ERROR];
		    		return false;
		    	}
	    	}
	    	
	    	//判断库存设置
	    	$total_rep  = isset($param['total_rep'])?trim($param['total_rep']):0;
	    	if($total_rep > 0 && intval($itemInfo->sold) >= $total_rep)
    		{
    			$err_msg = ['msg'=>'项目总库存不正确！','no'=>ERROR::ITEM_TOTALREP_ERROR];
    			return false;
    		}
	    	
	    }
	    if($param['typeid'] == 8)//男士快剪检测 是否有对应的造型师 快剪等级
	    {
	    	$flags = Hairstylist::checkHairerGrade($param['fastGrade'],$param['salonid']);
	    	if(!$flags)
	    	{
	    		$err_msg = ['msg'=>'当前快剪等级下面无对应等级的造型师，请修改造型师界面中的快剪等级后再添加快剪项目！','no'=>ERROR::ITEM_GRADE_ERROR];
	    		return false;
	    	}	
	    }
	    $timeLimitInput = isset($param['timeLimitInput'])?intval($param['timeLimitInput']):0;
	    if ($timeLimitInput < 0)
	    {
	    	$err_msg = ['msg'=>'限制次数不正确！','no'=>ERROR::ITEM_RESTRICT_ERROR];
	    	return false;
	    }
	    
	    $expTimeInput = isset($param['expTimeInput'])?trim($param['expTimeInput']):0;//项目使用有效期
	    if ($expTimeInput)
	    {
	    	$expTime = $expTimeInput.' 23:59:00';
	    	$expTimeStamp = strtotime($expTime);
	    	if ($expTimeStamp < time())
	    	{
	    		$err_msg = ['msg'=>'项目有效期时间不正确！','no'=>ERROR::ITEM_EXPTIME_ERROR];
	    		return false;
	    	}
	    }
	    
	    $priceStyle = isset($param['priceStyle'])?intval($param['priceStyle']):0;//项目规格 选项
	    if($priceStyle == 1)
	    {
	    	if($param['price'] < $param['priceDis'] || $param['priceGroup'] > $param['priceDis'])
	    	{
	    		$err_msg = ['msg'=>'价格参数错误！','no'=>ERROR::ITEM_ERROR];
	    		return false;
	    	}
	    }
	    else 
	    {
	    	$normMenu = isset($param['normMenu'])?json_decode($param['normMenu'],true):'';
	    	$normarr = isset($param['normarr'])?json_decode($param['normarr'],true):'';
	    	if(!$normarr || !$normMenu)
	    	{
	    		$err_msg = ['msg'=>'参数错误！','no'=>ERROR::ITEM_ERROR];
	    		return false;
	    	}
	    	foreach($normarr as $val)
	    	{
	    		if($val['price'] < $val['priceDis'] || $val['price'] < $val['priceGroup'] || $val['priceDis'] < $val['priceGroup'])
	    		{
	    			$err_msg = ['msg'=>'价格参数错误！','no'=>ERROR::ITEM_ERROR];
	    			return false;
	    		}
	    	}
	    	if(count($normarr)<1)
	    	{
	    		$err_msg = ['msg'=>'规格参数错误！','no'=>ERROR::ITEM_ERROR];
	    		return false;
	    	}
	    }
	    

	    return true;
	}
	
	/**
	 * 组合参数
	 * */
	public static function compositeData($param)
	{
	    $res =  [
	      'salon_item'=>[],
	      'salon_item_buylimit'=>[],
	      'salon_item_format_price'=>[],
	      'salon_norms_cat'=>[],
	      'salon_norms'=>[],
	    ];
	    
	    $now_time = time();
	    $salon_item = [];
	    $itemid = isset($param['itemid'])?intval($param['itemid']):null;
	    $salon_item['salonid'] = $salonid = isset($param['salonid'])?intval($param['salonid']):0;
	    $salon_item['typeid'] = isset($param['typeid'])?intval($param['typeid']):0;//分类id
	    $salon_item['useLimit'] = isset($param['useLimit'])?trim($param['useLimit']):'';//消费限制（特价项目）
	    $salon_item['logo'] = isset($param['logo'])?trim($param['logo']):'';
	    $salon_item['desc'] = isset($param['desc'])?trim($param['desc']):'';
	    $salon_item['item_type']      = isset($param['itemType'])?intval($param['itemType']):0;;//'商品类型，1 默认在售，2 限时特价'
	    $salon_item['itemname'] = isset($param['itemname'])?trim($param['itemname']):'';
	    $salon_item['itemname'] = str_replace(' ', '', $salon_item['itemname']);
	    $salon_item['fastGrade']  = isset($param['fastGrade'])?intval($param['fastGrade']):0;//快剪等级
	    $timingAdded  = isset($param['timingAdded'])?trim($param['timingAdded']):0;//定时上架
	    $timingShelves  = isset($param['timingShelves'])?trim($param['timingShelves']):0;//定时下架
	    
	    $salon_item['timingAdded'] = 0;
	    if($timingAdded)
	    {
	        $salon_item['timingAdded'] = strtotime($timingAdded);
	    }
	    $salon_item['timingShelves'] = 0;
	    if($timingShelves)
	    {
	        $salon_item['timingShelves'] = strtotime($timingShelves);
	    }
	    
	    $salon_item['repertory']  = 0;
	    if($salon_item['typeid'] == 6 || $salon_item['item_type'] != 1 )//兑换专用  --日库存
	    {
	    	$salon_item['repertory']  = isset($param['repertory'])?intval($param['repertory']):0;
	    }
	    
	    $salon_item['exp_time'] = 0;
	    $expTimeInput = isset($param['expTimeInput'])?trim($param['expTimeInput']):0;//项目使用有效期
	    if ($expTimeInput)
	    {
	    	$expTime = $expTimeInput.' 23:59:00';
	    	$expTimeStamp = strtotime($expTime);
	    	$salon_item['exp_time'] = $expTimeStamp;
	    }
	    
	    $salon_item['total_rep'] = 0;
	    $totalRepInput = isset($param['totalRepInput'])?trim($param['totalRepInput']):0;//项目总库存	    
	    if($totalRepInput > 0)
	    {
	    	$salon_item['total_rep'] = $totalRepInput;
	    }	
	    
	    //增值服务
	    $salon_item['addserviceStr'] = '';
	    $addedService = isset($param['addedService'])?$param['addedService']:'';
	    if($addedService)
	    {
	    	$salon_item['addserviceStr']=implode(',',$addedService);
	    }

	    $salon_item['userId'] 	= $param['userId'];
	    if(empty($itemid))
	    {
	        if(strtotime($timingAdded) > $now_time)//上线时间 》 当前时间   下架状态
	        {
	            $salon_item['status'] 	= SalonItem::STATUS_OF_DOWN;
	        }
	        else
	        {
	        	$salon_item['up_time'] 	= $now_time;//上架时间
	            $salon_item['status'] 	= SalonItem::STATUS_OF_UP;
	        }
	        $salon_item['add_time'] 	= $now_time;
	    }
		else
		{
			$salon_item['updateTime'] 	= $now_time;
		}
	    
	    //处理购买限制表 开始
	    $timeLimitInput = isset($param['timeLimitInput'])?intval($param['timeLimitInput']):0;
	    $inviteLimit = isset($param['inviteLimit'])?intval($param['inviteLimit']):0;
	    $firstLimit = isset($param['firstLimit'])?intval($param['firstLimit']):0;
	    if ($timeLimitInput >= 0)
	    {
	    	  $res['salon_item_buylimit']['limit_time'] = $timeLimitInput;
	    }
	    $res['salon_item_buylimit']['limit_invite'] = $inviteLimit?1:0;
	    $res['salon_item_buylimit']['limit_first'] = $firstLimit?1:0;
	    $res['salon_item_buylimit']['update_time'] = $now_time;
	    if(empty($itemid))
	    {
	       $res['salon_item_buylimit']['create_time'] = $now_time;
	    }

	    //无规格--价格处理
	    if($param['priceStyle'] == 1)
	    {
	    	$salon_item['norms_cat_id'] = 0;
	    	$priceOriArr[] = $price = isset($param['price'])?intval($param['price']):0;
	    	$priceGroupArr[] = $priceGroup = isset($param['priceGroup'])?intval($param['priceGroup']):0;
	    	$priceDisArr[] = $priceDis = isset($param['priceDis'])?intval($param['priceDis']):0;

	    	$pricetmp = [
	    				'dis_id'=>0,
	    				'discount'=>0,
	    				'price'=>$price,
	    				'price_dis'=>$priceDis,
	    				'price_group'=>$priceGroup,
	    				'salon_norms_id'=>0,
	    				'add_time'=>time(),	
	    		];
	    	$res['salon_item_format_price'][] = $pricetmp;
	    }
	    else 
	    {	  
	    	$normMenu = isset($param['normMenu'])?json_decode($param['normMenu'],true):'';
	    	$normarr = isset($param['normarr'])?json_decode($param['normarr'],true):'';
	    	
	    	$attribute = self::setNorms($normMenu, $normarr);

	    	$res['salon_norms_cat'] = [
	    			'salonid' => $salonid,
	    			'norms_cat_name' => date('YmdHis'),//模板名称
	    			'add_time' => $now_time,
	    	];
	    	
	    	foreach($normarr as $ks=>$vs)
	    	{
	    		foreach($vs['type'] as $vtype)
	    		{
	    			$formatsId[$ks][] = $attribute[$vtype];
	    		}
	    		$res['salon_norms'][] = [
	    				'salonid' => $salonid,
	    				'salon_item_format_id' =>implode(',',$formatsId[$ks]),
	    				'add_time' => $now_time,
	    		];
	    		$normarr[$ks]['salonNormsMark'] = implode(',',$formatsId[$ks]);
	    	}
	    	
	    	$priceDisArr = [];
	    	$priceGroupArr = [];
	    	$priceOriArr = [];
	    	//规则价格
	    	foreach($normarr as $value)
	    	{
	    		$res['salon_item_format_price'][] = [
	    											'price_dis'=>$value['priceDis'],
	    											'price_group'=>$value['priceGroup'],
								    				'salonNormsMark'=>$value['salonNormsMark'],
								    				'price'=>$value['price'],
								    				'add_time'=>$now_time,
	    									];
	    		if($value['priceDis'])
	    		{
	    			$priceDisArr[] = $value['priceDis'];
	    		}
	    		if($value['priceGroup'])
	    		{
	    			$priceGroupArr[] = $value['priceGroup'];
	    		}
	    		if($value['price'])
	    		{
	    			$priceOriArr[] = $value['price'];
	    		}
	    	}
	    	
	    }
	    
	    $salon_item['minPrice'] = $priceDisArr?min($priceDisArr):0;
	    $salon_item['maxPrice'] = $priceDisArr?max($priceDisArr):0;
	    $salon_item['minPriceGroup'] = $priceGroupArr?min($priceGroupArr):0;
	    $salon_item['maxPriceGroup'] = $priceGroupArr?max($priceGroupArr):0;
	    $salon_item['minPriceOri'] = $priceOriArr?min($priceOriArr):0;
	    $salon_item['maxPriceOri'] = $priceOriArr?max($priceOriArr):0;
	    $res['salon_item'] = $salon_item;
	    return $res;
	}
	
	/**
	 * 处理规格数据入库
	 * */
	private static function setNorms($normMenu,$normarr)
	{
		$menus =  array_keys($normarr[0]['type']);
		foreach($normarr as $v)
		{
			foreach($v['type'] as $key=>$val)
			{
				$itemType[$key][]=$val;
			}
		}
		$typeArr = self::$_typeArr;
		$vals = array_values($normMenu);
		$val_idx = [];
		foreach ($vals as $val)
		{
			$val_idx[] = self::$_typeArr[$val];
		}
		$formatsIdArr = SalonItemFormats::where(['salonid'=>0])->whereIn('formats_name',$val_idx)->get(['formats_name','salon_item_formats_id'])->toArray();
		$formatsIdArr = Utils::column_to_key('formats_name',$formatsIdArr);
		$clearVal =[];
		foreach($itemType as $key=>$value)
		{
			foreach ($value as $kt=>$vt)
			{
				if(!in_array($vt, $clearVal))
				{
					$st = SalonItemFormat::where(['salonid'=>0,'format_name'=>$vt,'salon_item_formats_id'=>$formatsIdArr[self::$_typeArr[$key]]['salon_item_formats_id']])->first();
					if($st)
						$norId = $st->salon_item_format_id;
					else
						$norId = SalonItemFormat::insertGetId(['salonid'=>0,'format_name'=>$vt,'salon_item_formats_id'=>$formatsIdArr[self::$_typeArr[$key]]['salon_item_formats_id']]);
					$attribute[$vt] = $norId;//属性数组
					$clearVal[] = $vt;
				}
			}
		}
		return $attribute;
	}
}

?>