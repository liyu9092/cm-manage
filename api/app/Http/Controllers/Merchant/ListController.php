<?php

namespace App\Http\Controllers\Merchant;


use App\Http\Controllers\Controller;
use App\Merchant;
use App\BusinessStaff;
use App\Province;
use App\SalonCity;
use App\SalonArea;
use App\Town;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\SalonItemType;

class ListController extends Controller {

	/**
	 * @api {post} /salonList/getProvinces 1.获取省市区商圈菜单 
	 * @apiName getProvinces
	 * @apiGroup  salonList
	 *
	 * @apiParam {Number} type 获取类型  1 省 2市 3区 4商圈.
	 * @apiParam {Number} areaId 上级Id(获取下级时必填，上级Id).
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{   
	 *	    "result": 1,//省
	 *	    "data": [
	 *	        {
	 *	            "pid": 1,
	 *	            "pname": "广东省"
	 *	        },
	 *	        {
	 *	            "pid": 2,
	 *	            "pname": "北京市"
	 *	        },
	 *	        ......
	 *	    ]
	 *	}
	 * @apiSuccessExample Success-Response:
	 *	{   
	 *	    "result": 1,//市
	 *	    "data": [
	 *	        {
	 *	            "iid": 1,
	 *	            "iname": "深圳市"
	 *	        },
	 *	        {
	 *	            "iid": 2,
	 *	            "iname": "广州市"
	 *	        },
	 *	        ......
	 *	    ]
	 *	}
	 *@apiSuccessExample Success-Response:
	 *	{   
	 *	    "result": 1,//区
	 *	    "data": [
	 *	        {
	 *	            "tid": 1,
	 *	            "tname": "福田区"
	 *	        },
	 *	        {
	 *	            "tid": 2,
	 *	            "tname": "罗湖区"
	 *	        },
	 *	        ......
	 *	    ]
	 *	}
	 *@apiSuccessExample Success-Response:
	 *	{   
	 *	    "result": 1,//商圈
	 *	    "data": [
	 *	        {
	 *	            "areaid": 16,
	 *	            "areaname": "香蜜湖"
	 *	        },
	 *	        {
	 *	            "areaid": 21,
	 *	            "areaname": "八卦路"
	 *	        },
	 *	        ......
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
    public function getProvinces() 
	{
		$param = $this->param;
		$type = isset($param["type"])?intval($param["type"]):1;//1 省 2市 3区 4商圈
		$areaId = isset($param["areaId"])?trim($param["areaId"]):0;//对应的上级id
		if($type != 1 && !$areaId)
		{
			throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
		}
		$list = $this->provincesList($type,$areaId);
		return $this->success($list);
    }
    
    
	/**
	 * 获取省市区
	 */
    private  function provincesList($type=1,$areaId)
    {
        if($type == 1)
        {
        	$list = Province::select(['pid','pname'])->get();
        }
        elseif($type == 2)
        {
        	$list = SalonCity::select(['iid','iname'])->where("pid","=",$areaId)->get();
        }
 	    elseif($type == 3)
        {
        	$list = Town::select(['tid','tname'])->where("iid","=",$areaId)->get();
        }
    	elseif($type == 4)//商圈按拼音排序 特殊处理    一次可获取多个区域商圈
        {
        	$sql = "SELECT areaid,areaname from cm_salon_area where parentid in ({$areaId}) ORDER BY CONVERT( areaname USING gbk ) COLLATE gbk_chinese_ci ASC";
        	$list = DB::select($sql);
        }
        return $list?$list:array();
        
    }
    
    /**
	 * @api {post} /salonList/getBussesName 2.获取所有业务代表
	 * @apiName getBussesName
	 * @apiGroup  salonList
	 *
	 * @apiParam {Number} cityid 城市id.
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{   
	 *	    "result": 1,
	 *	    "data": [
	 *	        {
	 *	            "id": 1,
	 *	            "businessName": "张三",
	 *	        },
	 *	        ......
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
    public function getBussesName()
    {
    	$param = $this->param;
    	$cityid = isset($param["cityid"])?trim($param["cityid"]):0;
    	if(!$cityid)
    	{
    		throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
    	}
    	//department_id 13 商务合作部   position_id77商务总监助理 78商务助理 79 信息采集专员
    	$where = 'status = 1 AND department_id = 13 AND position_id NOT IN (77,78, 79) AND city_id = '.$cityid;
    	$sql = "SELECT id,name as businessName from cm_managers where {$where} ORDER BY CONVERT( name USING gbk ) COLLATE gbk_chinese_ci ASC";


    	$result = DB::select($sql);
		return $this->success($result);
    }
     
}

?>