<?php namespace App\Http\Controllers\Item;

use App\Http\Controllers\Controller;
use DB;
use App\Utils;
use App\BeautyItem;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use Event;
use App\BeautyItemNorm;
class BeautyItemController extends Controller{

	private $itemResult;

	//json 字段
	private $requiredParameter = ['description','archive','beauty','register_detail','equipment','register_workflow','images'];

	/**
	* @api {post} /beautyItem/index 1.韩式半永久列表
	* @apiName index
	* @apiGroup  beautyItem
	* 
	* @apiParam {Number} page 必填,页数.
	* @apiParam {Number} page_size 必填,分页大小.
	* 
	* @apiSuccess {String} name 项目名称.
	* @apiSuccess {Number} level 项目类型 1明星院长； 2院长.
	* @apiSuccess {Number} price 原价.
	* @apiSuccess {Number} vip_price 会员价.
	* @apiSuccess {Number} quantity 已预约数.
	* @apiSuccess {Number} item_id 项目id.
	*
	* @apiSuccessExample Success-Response:
	*{
	*	    "result": 1,
	*	    "token": "",
	*	    "data": [
	*	    {
	*	    	    "name": 韩式眉毛（明星院长）,
	*	    	    "level": "1",
	*	    	    "price": 12,
	*	    	    "item_id": "1",
	*	    	    "vip_price": 43,
	*	    	    "quantity": 42,
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
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;
		$type = 1;
		$is_gift = 0;
		$result = BeautyItem::getBeautyItem($page,$page_size,$type,$is_gift);
		return $this->success($result);
	}	
	
	
	/**
	* @api {post} /beautyItem/update 2.修改韩式半永久项目
	* @apiName update
	* @apiGroup  beautyItem
	*
	* @apiParam {Number} item_id 必填,项目id.
	* @apiParam {string} name 必填,项目名称.
	* @apiParam {Number} type  必填,1韩式半永久 2快时尚.
	* @apiParam {string} detail 必填,项目介绍.
	* @apiParam {string} logo 必填,项目icon.
	* @apiParam {string} level 必填,类别 1明星院长； 2院长.
	* @apiParam {string} vip_price 必填,臭美O2O爆品价.
	* @apiParam {string} price 必填,国内美容机构市场价.
	* @apiParam {string} content_url 必填,详情H5连接地址.
	* @apiSuccess {string} completed_num 已完成数.
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
		$data = $this->compositeData($param);
		$item_id = isset($param['item_id'])?intval($param['item_id']):0;
		$result = BeautyItem::where(['item_id'=>$item_id])->update($data);
		if($result !== false)
		{
			$log = 'item_id:'.$item_id.' name:'.$data['name'];
		    Event::fire('beautyItem.update',$log);
		    return $this->success();
		}
		else
		{
			throw new ApiException('更新失败',ERROR::BEAUTY_ITEM_UPDATE_FAIL);
		}
			
	}

	/**
	* @api {post} /beautyItem/show 3.韩式半永久项目详情
	* @apiName show
	* @apiGroup  beautyItem
	*
	* @apiParam {Number} item_id 必填,项目id.
	* @apiSuccess {string} name 项目名称.
	* @apiSuccess {Number} type  1韩式半永久 2快时尚.
	* @apiSuccess {string} detail 项目介绍.
	* @apiSuccess {string} description 产品介绍JSON[{"title": "预约时间","content": "可预约一周内的时间","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]},{"title": "专家等级","content": "当前项目为(院长)为你服务","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]}].
	* @apiSuccess {string} archive 产品档案JSON[{"title": "预约时间","content": "可预约一周内的时间"},{"title": "专家等级","content": "当前项目为(院长)为你服务"}].
	* @apiSuccess {string} beauty 定妆流程JSON[{"title": "预约时间","content": "可预约一周内的时间","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]},{"title": "专家等级","content": "当前项目为(院长)为你服务","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]}].
	* @apiSuccess {string} register_detail 预约详情JSON[{"title": "预约时间","content": "可预约一周内的时间"},{"title": "专家等级","content": "当前项目为(院长)为你服务"}].
	* @apiSuccess {string} register_workflow 预约流程JSON[{"title": "预约时间","content": "可预约一周内的时间","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]},{"title": "专家等级","content": "当前项目为(院长)为你服务","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]}].
	* @apiSuccess {string} logo 项目icon.
	* @apiSuccess {string} images 项目图片JSON[{"img":"http: \/\/sm.choumei.cn\/Uploads\/salonbrand\/2015-06-15\/143434957914358.jpg"},{"img":"http: \/\/sm.choumei.cn\/Uploads\/salonbrand\/2015-06-15\/163434957914352.jpg"}].
	* @apiSuccess {string} level 类别 1明星院长； 2院长.
	* @apiSuccess {string} price 原价.
	* @apiSuccess {string} vip_price 会员价.
	* @apiSuccess {string} beauty_workflow_name 流程名称.
	* @apiSuccess {string} genre 0其他 1水光针.
	* @apiSuccess {string} content_url 详情H5连接地址.
	* @apiSuccess {string} completed_num 已完成数.
	* @apiSuccessExample Success-Response:
	*{
	*    result: 1,
	*    token: "",
	*    data: {
	*        item_id: 1,
	*        beauty_id: 0,
	*        type: 1,
	*        name: "测试00试试00",
	*        detail: "",
	*        description: "",
	*        archive: "",
	*        beauty: "",
	*        register_detail: "",
	*        register_workflow: "",
	*        present: "",
	*        thumb: "",
	*        logo: "",
	*        images: "",
	*        level: 0,
	*        price: 0,
	*        vip_price: 0,
	*        expire: 0,
	*        explain: "",
	*        beauty_workflow_name: "12",
	*        created_at: "2015-01-01 08:00:00",
	*        updated_at: "2015-11-30 16:18:17"
	*    }
	*}
	*
	*
	* @apiErrorExample Error-Response:
	*		{
	*		    "result": 0,
	*		    "msg": "参数错误"
	*		}
	*/
	public function show()
	{
		$param = $this->param;
		$item_id = isset($param['item_id'])?intval($param['item_id']):0;
		$beautyItem = BeautyItem::find($item_id);
		if(!$item_id || !$beautyItem)
			throw new ApiException('未知项目',ERROR::BEAUTY_ITEM_NOT_FOUND);
		$this->parameterValidation($beautyItem);
		$beautyItem['quantity']  = BeautyItem::getQuantity($item_id);
		$beautyItem['completed_num'] = BeautyItem::getItemCompletedNumber($item_id);
		return $this->success($beautyItem);
	}
	
		
	/**
	* @api {post} /beautyItem/updateFashion 4.修改韩式快时尚项目
	* @apiName updateFashion
	* @apiGroup  beautyItem
	*
	* @apiParam {Number} item_id 必填,项目id.
	* @apiParam {string} name 必填,项目名称.
	* @apiParam {Number} type  必填,1韩式半永久 2快时尚.
	* @apiParam {string} detail 必填,项目介绍.
	* @apiParam {string} logo 必填,项目icon.
	* @apiParam {string} more_prices 必填,价格规格JSON [{"img":"http:\/\/www.jt.com\/images\/logo.png","norm":"腰部","price":"1500","vip_price":"60","bargain_price":"60","size":"4-8","times":"6"},{"img":"http:\/\/www.jt.com\/images\/logo.png","norm":"手臂","price":"2500","vip_price":"1800","bargain_price":"60","size":"4-8","times":"6"}].
	* @apiParam {string} present_explain 必填,赠送说明.	
	* @apiParam {string} is_gift 必填,是否是赠送项目0否 1是.
	* @apiParam {string} content_url 详情H5连接地址.
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
	public function updateFashion()
	{
		$param = $this->param;
		$data = $this->compositeData($param);
		$itemResult = $this->itemResult;
		$norm_data = $this->compositeNormByType($param);
		$item_id = isset($param['item_id'])?intval($param['item_id']):0;
		DB::beginTransaction();
		$result = BeautyItem::where(['item_id'=>$item_id])->update($data);
		if($result === false)
		{
			DB::rollBack();
			throw new ApiException('更新失败',ERROR::BEAUTY_ITEM_UPDATE_FAIL);
		}
	
		BeautyItemNorm::where(['item_id'=>$item_id])->delete();
		foreach($norm_data as $val)
		{
			if(!BeautyItemNorm::insertGetId($val))
			{
				DB::rollBack();
				throw new ApiException('更新失败',ERROR::BEAUTY_ITEM_UPDATE_FAIL);
			}
		}
		
		DB::commit();
		$log = 'item_id:'.$item_id.' name:'.$data['name'];
		Event::fire('beautyItem.updateFashion',$log);
		return $this->success();	
	}
	
	/**
	 * 组合参数.  2.1版本
	 * param     数据参数
	 * act_type  1韩式定妆修改 2快时尚修改
	 * */
	private  function compositeData($param)
	{
		$must_param = ['type','name','content_url'];
		$item_id = isset($param['item_id'])?intval($param['item_id']):0;
		$data['type'] = isset($param['type'])?intval($param['type']):1;
		$data['name'] = isset($param['name'])?trim($param['name']):'';
		$data['detail'] = isset($param['detail'])?trim($param['detail']):'';
		$data['logo'] = isset($param['logo'])?trim($param['logo']):'';
		$data['level'] = isset($param['level'])?trim($param['level']):'';
		$data['content_url'] = isset($param['content_url'])?trim($param['content_url']):'';
		$data['present_explain'] = isset($param['present_explain'])?trim($param['present_explain']):'';
		$beautyItem = BeautyItem::find($item_id);
		if(!$item_id || !$beautyItem)
		{
			throw new ApiException('未知项目',ERROR::BEAUTY_ITEM_NOT_FOUND);
		}
		if($this->checkItemTitle($data['name'],$item_id))
		{
			throw new ApiException('项目名称重复',ERROR::BEAUTY_ITEM_NAME_REOEAT);
		}
		$retMissing = '';
		foreach($must_param as $val)
		{
			if(!$data[$val])
			{
				throw new ApiException("缺失参数", ERROR::BEAUTY_ITEM_ERROR);
			} 
		}

		if($data['type'] == 1 || $beautyItem->is_gift == 1)
		{
			$data['price'] = isset($param['price'])?intval($param['price']):0;
			$data['vip_price'] = isset($param['vip_price'])?intval($param['vip_price']):0;
			$data['bargain_price'] = isset($param['bargain_price'])?intval($param['bargain_price']):0;
			if($data['price'] < $data['vip_price'])
			{
				throw new ApiException("价格参数错误", ERROR::BEAUTY_ITEM_WRONG_PRICE);
			}
		}
		else
		{
			$data['price'] = 0;
			$data['vip_price'] = 0;
			$data['bargain_price'] = 0;
			$data['level'] = 0;
		}
		return $data;

	}
	
	
	/**
	 * 组合参数.  2.0版本需求字段组合 废弃
	 * param     数据参数
	 * act_type  1韩式定妆修改 2快时尚修改
	 * */
	private  function compositeData_bak($param,$act_type)
	{
		$must_param = ['type','name','detail','description','logo','images','beauty_workflow_name'];
		$item_id = isset($param['item_id'])?intval($param['item_id']):0;
		$data['type'] = isset($param['type'])?intval($param['type']):1;
		$data['name'] = isset($param['name'])?trim($param['name']):'';
		$beautyItem = BeautyItem::find($item_id);
		if(!$item_id || !$beautyItem)
		{
			throw new ApiException('未知项目',ERROR::BEAUTY_ITEM_NOT_FOUND);
		}
		if($this->checkItemTitle($data['name'],$item_id))
		{
			throw new ApiException('项目名称重复',ERROR::BEAUTY_ITEM_NAME_REOEAT);
		}
		
		$this->itemResult = $beautyItem;
		
		$data['detail'] = isset($param['detail'])?trim($param['detail']):'';
		$data['description'] = isset($param['description'])?trim($param['description']):'';
		$data['archive'] = isset($param['archive'])?trim($param['archive']):'';
		$data['beauty'] = isset($param['beauty'])?trim($param['beauty']):'';
		$data['register_detail'] = isset($param['register_detail'])?trim($param['register_detail']):'';
		$data['register_workflow'] = isset($param['register_workflow'])?trim($param['register_workflow']):'';
		$data['logo'] = isset($param['logo'])?trim($param['logo']):'';
		$data['images'] = isset($param['images'])?trim($param['images']):'';
		$data['level'] = isset($param['level'])?trim($param['level']):'';
		if($data['type'] == 1 || $beautyItem->genre == 1)
		{
			$data['price'] = isset($param['price'])?intval($param['price']):'';
			$data['vip_price'] = isset($param['vip_price'])?intval($param['vip_price']):'';
		}
		else
		{
			$data['price'] = 0;
			$data['vip_price'] = 0;
		}

		
		$data['beauty_workflow_name'] = isset($param['beauty_workflow_name'])?trim($param['beauty_workflow_name']):'';
		$data['equipment_slogan'] = isset($param['equipment_slogan'])?trim($param['equipment_slogan']):'';
		
		
		$data['updated_at'] = time();
		
		$data['equipment_cover'] = isset($param['equipment_cover'])?trim($param['equipment_cover']):'';//设备封面
		$data['equipment'] = isset($param['equipment'])?trim($param['equipment']):'';//设备介绍
		$data['present_explain'] = isset($param['present_explain'])?trim($param['present_explain']):'';
		$data['is_gift'] = isset($param['is_gift'])?intval($param['is_gift']):0;//是否是赠送项目
		
		$retMissing = '';
		foreach($must_param as $val)
		{
			if(!$data[$val])
			{
				throw new ApiException("缺失参数", ERROR::BEAUTY_ITEM_ERROR);
			} 
		}
		if($data['price'] < $data['vip_price'] && $data['type'] == 1)
		{
			throw new ApiException("价格参数错误", ERROR::BEAUTY_ITEM_WRONG_PRICE);
		}
		
		return $data;
	}

	/*
	* 组合多规格价格参数
	*/
	private function compositeNormByType($param)
	{
		$more_prices = isset($param['more_prices'])?json_decode($param['more_prices'],true):'';
		if(!$more_prices)
		{
			throw new ApiException("价格参数错误", ERROR::BEAUTY_ITEM_ERROR);
		} 
		$result = [];
		foreach($more_prices as $val)
		{
			if($val['price'] < $val['vip_price'])
			{
				throw new ApiException("价格参数错误", ERROR::BEAUTY_ITEM_WRONG_PRICE);
			}
			$result[] = [
					'img_url'=>isset($val['img'])?$val['img']:'',
					'norm'=>$val['norm'],
					'price'=>$val['price'],
					'vip_price'=>$val['vip_price'],
					'bargain_price'=>isset($val['bargain_price'])?$val['bargain_price']:0,
					'times'=>isset($val['times'])?$val['times']:'',
					'size'=>isset($val['size'])?$val['size']:'',
					'item_id'=>$param['item_id'],
					'created_at'=>time(),
			];
		}
		return $result;
	}
	
	/**
	* @api {post} /beautyItem/checkName 5.项目名称检测
	* @apiName checkName
	* @apiGroup  beautyItem
	*
	* @apiParam {Number} item_id 必填,项目id.
	* @apiParam {string} name 必填,项目名称.	
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
	public function checkName()
	{
		$param = $this->param;
		$item_id = isset($param['item_id'])?intval($param['item_id']):0;
		$name = isset($param['name'])?trim($param['name']):'';
		if(!$item_id || !$name)
		{
			throw new ApiException("参数错误", ERROR::BEAUTY_ITEM_ERROR);
		}
		if(!$this->checkItemTitle($name,$item_id))
		{
			return $this->success();
		}
		throw new ApiException("项目名称重复", ERROR::BEAUTY_ITEM_NAME_REOEAT);
	}
	
	/**
	*检测项目名称是否重复
	*/
	private function checkItemTitle($name,$item_id)
	{
		if(!$name) return '';
		$result = BeautyItem::where(['name'=>$name])->where('item_id','!=',$item_id)->count();
		return $result;
	}
	
	/**
	* @api {post} /beautyItem/indexFashion 6.韩式快时尚列表
	* @apiName indexFashion
	* @apiGroup  beautyItem
	* 
	* @apiParam {Number} page 必填,页数.
	* @apiParam {Number} page_size 必填,分页大小.
	* @apiParam {Number} is_gift 必填,是否是赠送项目 0否 1是.
	* 
	* @apiSuccess {String} name 项目名称.
	* @apiSuccess {Number} level 项目类型 1明星院长； 2院长.
	* @apiSuccess {Array} price 价格 min_price 最小原价 max_price 最大原价 min_vip_price 最小臭美会员价 max_vip_price 最大臭美会员价.
	* @apiSuccess {Number} quantity 已预约数.
	* @apiSuccess {Number} item_id 项目id.
	*
	* @apiSuccessExample Success-Response:
	*{
	*	    "result": 1,
	*	    "token": "",
	*	    "data": [
	*	    {
	*	    	    "name": 韩式眉毛（明星院长）,
	*	    	    "level": "1",
	*				"item_id": "1",
	*	    	    "quantity": 42,
	*               "prices": {
    *              		 "min_price": 1500,
    *                	 "max_price": 2500,
    *                    "min_vip_price": 60,
    *                    "max_vip_price": 1800
    *            }
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
	public function indexFashion()
	{
		$param = $this->param;
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;
		$type = 2;
		$is_gift = isset($param['is_gift'])?$param['is_gift']:0;

		$result = BeautyItem::getBeautyItem($page,$page_size,$type,$is_gift);
		return $this->success($result);
	}
	
		/**
	* @api {post} /beautyItem/showFashion 7.韩式定妆项目详情
	* @apiName showFashion
	* @apiGroup  beautyItem
	*
	* @apiParam   {Number} item_id 必填,项目id.
	* 
	* @apiSuccess {Number} item_id 项目id.
	* @apiSuccess {string} name 项目名称.
	* @apiSuccess {Number} type  1韩式半永久 2快时尚.
	* @apiSuccess {string} detail 项目介绍.
	* @apiSuccess {string} description 产品介绍JSON[{"title": "预约时间","content": "可预约一周内的时间","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]},{"title": "专家等级","content": "当前项目为(院长)为你服务","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]}].
	* @apiSuccess {string} archive 产品档案JSON[{"title": "预约时间","content": "可预约一周内的时间"},{"title": "专家等级","content": "当前项目为(院长)为你服务"}].
	* @apiSuccess {string} beauty 定妆流程JSON[{"title": "预约时间","content": "可预约一周内的时间","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]},{"title": "专家等级","content": "当前项目为(院长)为你服务","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]}].
	* @apiSuccess {string} register_detail 预约详情JSON[{"title": "预约时间","content": "可预约一周内的时间"},{"title": "专家等级","content": "当前项目为(院长)为你服务"}].
	* @apiSuccess {string} register_workflow 预约流程JSON[{"title": "预约时间","content": "可预约一周内的时间","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]},{"title": "专家等级","content": "当前项目为(院长)为你服务","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]}].
	* @apiSuccess {string} logo 项目icon.
	* @apiSuccess {string} images 项目图片JSON[{"img":"http: \/\/sm.choumei.cn\/Uploads\/salonbrand\/2015-06-15\/143434957914358.jpg"},{"img":"http: \/\/sm.choumei.cn\/Uploads\/salonbrand\/2015-06-15\/163434957914352.jpg"}].
	* @apiSuccess {string} equipment_cover 设备封面
	* @apiSuccess {string} equipment 设备介绍JSON[{"title": "预约时间","content": "可预约一周内的时间","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]},{"title": "专家等级","content": "当前项目为(院长)为你服务","image": [{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/143434957914358.jpg"},{"img": "http: //sm.choumei.cn/Uploads/salonbrand/2015-06-15/163434957914352.jpg"}]}].
	* @apiSuccess {Number} level 类别 1明星院长； 2院长.
	* @apiSuccess {Number} expire 时间限制.
	* @apiSuccess {string} present_explain 赠送说明.
	* @apiSuccess {Array} price 价格 min_price 最小原价 max_price 最大原价 min_vip_price 最小臭美会员价 max_vip_price 最大臭美会员价.
	* @apiSuccess {Array} more_prices 规格价格.
	* @apiSuccess {string} quantity 预约数.
	* @apiSuccess {string} equipment_slogan 设备宣传语.	
	* @apiSuccess {string} beauty_workflow_name 流程名称.
	* @apiSuccess {string} genre 0其他 1水光针.	
	* @apiSuccess {string} content_url 详情H5连接地址.
	* @apiSuccess {string} completed_num 已完成数.
	*
	* @apiSuccessExample Success-Response:
	* {
 	*    "result": 1,
 	*    "token": "",
 	*    "data": {
  	*       "item_id": 1,
  	*       "beauty_id": 0,
  	*       "type": 1,
   	*       "name": "测试000",
   	*       "detail": "",
  	*       "description": "",
   	*       "archive": "",
   	*       "beauty": "",
   	*       "register_detail": "",
   	*       "equipment": "",
   	*       "equipment_cover": "",
  	*       "register_workflow": "",
  	*       "present": "",
   	*       "thumb": "",
   	*       "logo": "",
   	*       "images": "",
  	*       "level": 1,
   	*       "price": 0,
 	*       "vip_price": 0,
  	*       "expire": 0,
  	*       "present_explain": "",
  	*       "is_gift": 0,
   	*       "created_at": "1970-01-01 08:00:00",
  	*       "updated_at": "2015-12-01 09:46:22",
	*       "quantity": "12",
	*       "equipment_slogan": "12",
	*       "beauty_workflow_name": "12",
	*       "content_url": "http://www.choumei.cn",
	*       "prices": {
	*              		 "min_price": 1500,
	*                	 "max_price": 2500,
	*                    "min_vip_price": 60,
	*                    "max_vip_price": 1800
	*            }
  	*       "more_prices": [
   	*          {
   	*              "img": "",
   	*              "norm": "234",
   	*              "price": 33,
  	*              "vip_price": 20,
  	*              "size": 20,
  	*              "times": 20,	
   	*          },
  	*           {
   	*              "img": "",
   	*              "norm": "343",
   	*              "price": 50,
   	*              "vip_price": 10,
  	*              "size": 20,
  	*              "times": 20,	
   	*          },
   	*          {
   	*              "img": "",
  	*               "norm": "",
   	*              "price": 5434,
   	*              "vip_price": 652,
  	*              "size": 20,
  	*              "times": 20,	
  	*           }
  	*       ]
  	*   }
	* }
	*
	*
	* @apiErrorExample Error-Response:
	*		{
	*		    "result": 0,
	*		    "msg": "参数错误"
	*		}
	*/
	public function showFashion()
	{
		$param = $this->param;
		$item_id = isset($param['item_id'])?intval($param['item_id']):0;
		$beautyItem = BeautyItem::find($item_id);
		if(!$item_id || !$beautyItem)
			throw new ApiException('未知项目',ERROR::BEAUTY_ITEM_NOT_FOUND);
		
		$beautyItem = $beautyItem->toArray();
		//$this->parameterValidation($beautyItem);

		$beautyItem['prices'] = BeautyItem::getMinMaxPrices($item_id);
		$beautyItem['more_prices'] = BeautyItemNorm::where(['item_id'=>$item_id])->select(['img_url as img','norm','price','vip_price','size','times','bargain_price'])->get()->toArray();
		$beautyItem['quantity']  = BeautyItem::getQuantity($item_id,$beautyItem['is_gift']);
		$beautyItem['completed_num'] = BeautyItem::getItemCompletedNumber($item_id);
		return $this->success($beautyItem);
	}
	
	
		/**
	* @api {post} /beautyItem/itemList 8.韩式定妆项目列表
	* @apiName itemList
	* @apiGroup  beautyItem
	* 
	* @apiParam   {Number} type 选填,项目类型 1韩式半永久 2快时尚.
	* @apiParam   {Number} is_gift 选填,是否是赠送项目 0否 1是
	* 
	* @apiSuccess {String} name 项目名称.
	* @apiSuccess {Number} item_id 项目id.
	* @apiSuccess {Number} type 项目类型 1韩式半永久 2快时尚.
	* @apiSuccess {Number} is_gift 是否是赠送项目 0否 1是
	* @apiSuccess {Array} more_prices 多规格展示  id 规格id norm 规格名称
	*
	* @apiSuccessExample Success-Response:
	*{
	*	    "result": 1,
	*	    "token": "",
	*	    "data": [
	*	    {
	*	    	    "name": 韩式眉毛（明星院长）,
	*	    	    "item_id": "1",
	*	    	    "type": "1",
	*	    	    "is_gift": "0",
	*               "more_prices": [
    *               {
    *                  "id": 47,
    *                  "img": "http://img01.choumei.cn/1/7/2015120217501449049856031749987.jpg?imageMogr2/crop/!75x75a50a0/thumbnail/100x100",
    *                  "norm": "腿部",
    *                  "price": 850,
    *                  "vip_price": 120,
    *                  "size": "",
    *                  "times": 0
    *              }
    *         ]
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
	public function itemList()
	{
		$param = $this->param;
		$type = isset($param['type'])?intval($param['type']):0;
		$is_gift = isset($param['is_gift'])?intval($param['is_gift']):0;
		
		$query = BeautyItem::select(['item_id','name','is_gift','type','genre']);
		if($type == 1)
		{
			$query = $query->where(['type'=>$type]);
		}
		elseif($type == 2)
		{
			$query = $query->where(['type'=>$type]);
		}
		if(isset($param['is_gift']))
		{
			$query = $query->where(['is_gift'=>$is_gift]);
		}
		
		$result = $query->orderBy('type', 'asc')->orderBy('level', 'asc')->orderBy('item_id', 'desc')->get()->toArray();
		foreach($result as &$val)
		{
			if($val['type'] == 2)
				$val['more_prices'] =  BeautyItemNorm::where(['item_id'=>$val['item_id']])->select(['id','img_url as img','norm','price','vip_price','size','times'])->get()->toArray();
		}
		
		return $this->success($result);
	}
	
	/**
	*json 数据错误处理
	*
	*/
	private function parameterValidation(&$beautyItem)
	{
		if(!$beautyItem) return $beautyItem;
		foreach($this->requiredParameter as $val)
		{
			if($beautyItem[$val])
			{
				$flag = json_decode($beautyItem[$val],true);
				if(!is_array($flag))
				{
					$beautyItem[$val] = '';
				}
			}
		}
		return $beautyItem;
	}
}

?>