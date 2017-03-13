<?php
/**
 * 店铺结算相关
 * @author nian.zhu
 */
namespace App\Http\Controllers\ShopCount;


use App\Http\Controllers\Controller;
use App\ShopCountApi;
use Event;
use App\PrepayBill;
use Log;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\Mapping;

class ShopCountController extends Controller
{
    /**
     * @api {get} /shop_count/index 1.转付单列表
     * @apiName index
     * @apiGroup ShopCount
     *
     * @apiParam {Number} key  1 店铺搜索 2 商户搜索 3 店铺编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} pay_time_min 付款最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 付款最大时间 YYYY-MM-DD
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','created_at'(创建时间,默认),'code'(付款单号),'type'(付款类型),'pay_money'(付款金额),'pay_type(付款方式)','cost_money'(换算消费额),'day'(付款日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} code 付款单号.
     * @apiSuccess {Number} type 付款单类型 1:付交易代收款  2:付交易代收款 3:交易代收款返还
     * @apiSuccess {Number} state 状态 1:已付款 2待提交 3:待审批  4:待付款
     * @apiSuccess {String} pay_type 付款方式:1银行存款 2账扣返还 3现金 4支付宝 5财付通
     * @apiSuccess {String} pay_money 付款金额.
     * @apiSuccess {String} created_at 创建时间.
     * @apiSuccess {String} remark  备注
     * @apiSuccess {String} pay_day  实际付款日期.
     * @apiSuccess {Object} user  制表人信息.
     * @apiSuccess {Object} salon 店铺信息.
     * @apiSuccess {Object} merchant 商盟信息.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *          "result": 1,
     *          "data": {
     *              "total": 1,
     *              "per_page": 10,
     *              "current_page": 1,
     *              "last_page": 1,
     *              "from": 1,
     *              "to": 1,
     *              "data": [
     *                  {
     *                     "id": 1,
     *                     "created_at": "2015-07-03 00:00:00",
     *                     "merchant_id": 1,
     *                     "salon_id": 1,
     *                     "code": "fasdfasdfasdfasdfadfa",
     *                     "type": 1,
     *                     "uid": 1,
     *                     "pay_money": "2000.00",
     *                     "pay_type": 1,
     *                     "cost_money": "2500.00",
     *                     "remark": "备注",
     *                     "pay_day": "2015-09-02",
     *                     "user": {
     *                         "id": 1,
     *                         "name": ""
     *                     },
     *                     "salon": {
     *                         "salonid": 1,
     *                         "sn":"商铺编号",
     *                         "salonname": "嘉美专业烫染"
     *                     },
     *                     "merchant": {
     *                         "id": 1,
     *                         "name": "速度发多少"
     *                     }
     *                  }
     *              ]
     *          }
     *      }
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
        $param = $this->parameters([
            'key'=>self::T_INT,
            'keyword'=>self::T_STRING,
            'pay_time_min'=>self::T_STRING,
            'pay_time_min'=>self::T_STRING,
            'pay_time_max'=>self::T_STRING,
            'page'=>self::T_INT,
            'page_size'=>self::T_INT,
            'sort_key'=>self::T_STRING,
            'sort_type'=>self::T_STRING,
        ]);
        
        $items = ShopCountApi::searchPrepay($param);
        return $this->success($items);
    }
    
    /**
     * @api {get} /shop_count/export 11.导出转付单
     * @apiName export
     * @apiGroup ShopCount
     *
     * @apiParam {Number} key  1 店铺搜索 2 商户搜索 3 店铺编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} pay_time_min 付款最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 付款最大时间 YYYY-MM-DD
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','created_at'(创建时间,默认),'code'(付款单号),'type'(付款类型),'pay_money'(付款金额),'day'(付款日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function export()
    {        
        $param = $this->parameters([
            'key'=>self::T_INT,
            'keyword'=>self::T_STRING,
            'pay_time_min'=>self::T_STRING,
            'pay_time_min'=>self::T_STRING,
            'pay_time_max'=>self::T_STRING,
            'page'=>self::T_INT,
            'page_size'=>self::T_INT,
            'sort_key'=>self::T_STRING,
            'sort_type'=>self::T_STRING,
        ]);  
        $header = ['店铺名称','付款单号','付款类型','支付方式','付款金额','实际付款日期','备注','创建日期','制单人','状态'];      
        $items = ShopCountApi::getPrepayCondition($param)->addSelect('prepay_bill.updated_at as updated_at')->get()->toArray(); 
        Event::fire('shopcount.export');
        $this->export_xls("转付单".date("Ymd"),$header,self::format_prepay_data($items));
    }
 
    public function create()
    {
        throw new ApiException("功能已关闭!",ERROR::SERVER_STOPED);
//        $param_must = $this->parameters( 
//            ['type'=>self::T_INT,
//             'merchant_id'=>self::T_INT,
//             'salon_id'=>self::T_INT
//            ],true);
//        $param = $this->parameters([           
//             'pay_money'=>self::T_INT,
//             'cost_money'=>self::T_INT,
//             'day'=>self::T_STRING,
//         ]);
//         $param = array_merge($param,$param_must);
//         $param['uid'] = $this->user->id;        
//         $id = ShopCountApi::makePreviewPrepay($param);
//         if(! $id)
//         {
//             throw new \Exception("参数有误,生成预览失败");
//         }
//         $items = ShopCountApi::prepayDetail($id);
//         Event::fire('shopcount.create',$items['code']);
//         return $this->success($items);
    }

     /**
     * @api {post} /shop_count/create 3.新建转付单  接口已废弃
     * @apiName create
     * @apiGroup ShopCount
     *
     * @apiParam {Number} merchant_id  商户id
     * @apiParam {Number} salon_id     店铺id
     * @apiParam {Number} pay_type     付款方式  1银行存款 2账扣返还 3现金 4支付宝 5财付通
     * @apiParam {Number} pay_money    付款金额
     * @apiParam {String} day   付款日期 (YYYY-MM-DD)
     * 
     * @apiSuccess {String} id 创建成功后的id.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "id": 1
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,生成失败"
     *		}
     */
    public function store()
    {
        //废弃
//         $param = $this->parameters([
//             'type'=>self::T_INT,
//             'merchant_id'=>self::T_INT,
//             'salon_id'=>self::T_INT,
//             'pay_money'=>self::T_FLOAT,
//             'pay_type'=>self::T_INT,
//             'day'=>self::T_STRING,
//         ]);
//         $param['uid'] = $this->user->id;        
//         $ret = ShopCountApi::makePrepay($param);    
//         Event::fire('shopcount.store',$ret['code']);
//         return $this->success(['id'=>$ret['id']]);
    }

   /**
     * @api {get} /shop_count/show/{id} 4.转付单详情
     * @apiName show
     * @apiGroup ShopCount
     *
     * @apiParam {Number} id  id
     *
     * @apiSuccess {String} code 付款单号.
     * @apiSuccess {Number} type 付款单类型  1:付交易代收款  2:付交易代收款 3:交易代收款返还
     * @apiSuccess {Number} state 状态 1:已付款 2待提交 3:待审批  4:待付款
     * @apiSuccess {Number} pay_type 付款方式 1银行存款 2账扣返还 3现金 4支付宝 5财付通
     * @apiSuccess {String} pay_money 付款金额.
     * @apiSuccess {String} created_at 创建时间.
     * @apiSuccess {String} remark  备注.
     * @apiSuccess {String} pay_day  实际付款日期.
     * @apiSuccess {Object} user  制表人信息.
     * @apiSuccess {Object} salon 店铺信息.
     * @apiSuccess {Object} merchant 商盟信息.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "id": 1,
     *               "created_at": "2015-07-03 00:00:00",
     *               "merchant_id": 1,
     *               "salon_id": 1,
     *               "code": "fasdfasdfasdfasdfadfa",
     *               "type": 1,
     *               "uid": 1,
     *               "pay_money": "2000.00",
     *               "pay_type": "1",
     *               "pay_day":"2015-10-10",
     *               "remark": "备注",
     *               "user": {
     *                   "id": 1,
     *                   "name": ""
     *               },
     *               "salon": {
     *                   "salonid": 1,
     *                   "sn":"商铺编号",
     *                   "salonname": "嘉美专业烫染"
     *               },
     *               "merchant": {
     *                   "id": 1,
     *                   "name": "速度发多少"
     *               }
     *           }
     *       }
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
        $items = ShopCountApi::prepayDetail($id);
        return $this->success($items);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

     /**
     * @api {post} /shop_count/update/{id} 5.转付单 修改  (废弃)
     * @apiName update
     * @apiGroup ShopCount
     *
     * @apiParam {Number} merchant_id  商户id 
     * @apiParam {Number} salon_id     店铺id 
     * @apiParam {Number} pay_money    付款金额  
     * @apiParam {Number} pay_type     付款方式  1银行存款 2账扣返还 3现金 4支付宝 5财付通
     * @apiParam {String} day   付款日期 (YYYY-MM-DD) 
     * 
     * @apiSuccess {String} id 修改成功后的id.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "id": 1
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,生成失败"
     *		}
     */
    public function update($id)
    { 
        //废弃       
//         $param = $this->parameters([
//             'merchant_id'=>self::T_INT,
//             'salon_id'=>self::T_INT,
//             'pay_money'=>self::T_INT,
//             'pay_type'=>self::T_INT,
//             'day'=>self::T_STRING,
//         ]);
//         $param['uid'] = $this->user->id;
//         $ret = ShopCountApi::updatePrepay($id, $param);
//         if(! $ret)
//         {
//             throw new \Exception("参数有误,更新失败");
//         }
        
//         $prepays = PrepayBill::where('id',$id)->get()->toArray();
//         Event::fire('shopcount.update',$prepays[0]['code']);
//         return $this->success(['id'=>$id]);
    }

     /**
     * @api {post} /shop_count/destroy/{id} 6.转付单 删除  (废弃)
     * @apiName destroy
     * @apiGroup ShopCount
     * 
     * @apiSuccess {String} ret 1 成功删除
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "ret": 1
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "参数有误,生成失败"
     *		}
     */
    public function destroy($id)
    {        
//         //是 ShopCount 不是 ShopCountApi  不要改回去了
  
//         $ret = ShopCountApi::deletePrepay($id);
//         if($ret)
//         {
//             Event::fire('shopcount.destroy',$ret['code']);
//             return $this->success(['ret'=>1]);
//         }
//         else
//         {
//             throw new ApiException("删除出错!",ERROR::UNKNOWN_ERROR);
//         }
    }
    
    /**
     * @api {post} /shop_count/count_order 10.订单结算相关 (外部调用)
     * @apiName count_order
     * @apiGroup ShopCount
     *
     * @apiParam {Number} type  1 订单 2 赏金单
     * @apiParam {String} ordersn  订单单号,赏金单单号(多个用英文逗号","隔开)
     * @apiParam {String} token  加密验证指纹
     *
     * @apiSuccess {Array} success 成功结算的订单号
     * @apiSuccess {Array} already 已经结算过的订单号
     * @apiSuccess {Number} type 结算的类型 同传入的type
     *
     * @apiSuccessExample Success-Response:
     *      {
     *           "result": 1,
     *           "data": {
     *               "success": [
     *                   "33923619970",
     *                   "33924964189",
     *                   "33927676599",
     *                   "33928797449",
     *                   "33929103073",
     *                   "33929641274",
     *                   "33929787504",
     *                   "33930191013",
     *                   "33930816691",
     *                   "33994190861"
     *               ],
     *               "type": 2,
     *               "already": [
     *
     *               ]
     *           }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "错误信息"
     *		}
     */    
    public function countOrder()
    {
        $param = $this->parameters([
            'type'=>self::T_INT,
            'ordersn'=>self::T_STRING,
            'token'=>self::T_STRING,
        ],true);
        $passed = ShopCountApi::checkToken($param);
        if(!$passed)
        {
            throw new ApiException("Unauthorized",ERROR::ACCOUNT_INVALID);
        }
        $orders = explode(",", $param['ordersn']);
        Log::info('请求参数:'.json_encode($param));

        $res = null;
        $str = "";
        if ($param['type'] == 1)
        {
            $str = "订单结算";
            $res = ShopCountApi::countOrder($orders);
            if(time() <= strtotime("2016-01-01"))
            {
                ShopCountApi::commissionOrder($orders);
            }
        }
        else if($param['type'] == 2)
        {
            $str = "赏金单结算";
            $res = ShopCountApi::countBounty($orders);
            if(time() <= strtotime("2016-01-01"))
            {
                ShopCountApi::commissionBounty($orders);
            }
        }     
        return $this->success($res);
       
    }

    protected static function format_prepay_data($datas)
    {
        $res = [];
        foreach ($datas as $data) {
            $salon_name = isset($data['salon']['salonname']) ? $data['salon']['salonname'] : '';      
            $username = isset($data['user']['name'])?$data['user']['name']:'';
            $typename = $data['type'] == 3 ? "交易代收款返还" : "付交易代收款";
            $pay_type_name = Mapping::getPayTypeName($data['pay_type']);
            $username = $data['user']['name'];
            $statename = Mapping::getPrepayStateName($data['state']);
            $res[] = [               
                $salon_name,
                $data['code'],
                $typename,
                $pay_type_name,
                $data['pay_money'],
                $data['pay_day'],
                $data['remark'],
                date("Y-m-d",strtotime($data['updated_at'])),
                $username,
                $statename,
            ];
        }
        return $res;
    }
}
