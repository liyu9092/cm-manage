<?php

namespace App\Http\Controllers\ShopCount;

use App\Http\Controllers\Controller;
use App\ShopCountApi;
use Event;
use Log;
use App\Mapping;

class BalanceController extends Controller
{
    /**
     * @api {get} /shop_count/balance 9.商户往来列表
     * @apiName balance
     * @apiGroup ShopCount
     *
     * @apiParam {Number} key  1 店铺搜索 2 商户搜索 3 商户编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','created_at'(创建时间,默认),'salon_name','salon_type','pay_money','cost_money',...(money相关的key)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} pay_money 付款
     * @apiSuccess {String} spend_money 收款(消费额).
     * @apiSuccess {String} commission_money 佣金
     * @apiSuccess {String} commission_return_money 佣金
     * @apiSuccess {String} balance_money 应收余额.
     * @apiSuccess {String} invest_money 付投资款.
     * @apiSuccess {String} invest_return_money 付投款返还.
     * @apiSuccess {String} invest_balance_money 投资余额.
     * @apiSuccess {String} borrow_money 付借款.
     * @apiSuccess {String} borrow_return_money 借款返还.
     * @apiSuccess {String} borrow_balance_money 借款余额.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "total": 1,
     *               "per_page": 10,
     *               "current_page": 1,
     *               "last_page": 1,
     *               "from": 1,
     *               "to": 1,
     *               "data": [
     *                   {
     *                       "id": 1,
     *                       "created_at": "2015-07-01 00:00:00",
     *                       "merchant_id": 3,
     *                       "salon_id": 2,
     *                       "pay_money": "123.00",
     *                       "spend_money": "23434.00",
     *                       "commission_money": "12345",
     *                       "commission_return_money": "123",
     *                       "balance_money": "2334.00",
     *                       "invest_money": "2334.00",
     *                       "invest_return_money": "23.00",
     *                       "invest_balance_money": "343.00",
     *                       "borrow_money": "2323.00",
     *                       "borrow_return_money": "34.00",
     *                       "borrow_balance_money": "2334.00",
     *                       "salon": {
     *                          "salonid": 3,
     *                          "sn":"商铺编号",
     *                          "shopType":1,
     *                          "salonname": "米莱国际造型连锁（田贝店）"
     *                      },
     *                      "merchant": {
     *                          "id": 2,
     *                          "name": "地对地导弹"
     *                      }
     *                   }
     *               ]
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
        $items = ShopCountApi::searchShopCount($param);
        return $this->success($items);
    }
    
    /**
     * @api {get} /shop_count/balance_export 13.导出商户往来列表
     * @apiName balance_export
     * @apiGroup ShopCount
     *
     * @apiParam {Number} key  1 店铺搜索 2 商户搜索 3 商户编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','created_at'(创建时间,默认),'salon_name','salon_type','pay_money','cost_money',...(money相关的key)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
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
        $header = ['店铺编号','店铺','店铺类型','所属商户','付款','收款(已消费)','应收佣金','佣金返还','应收余额','付投资款','投资款返还','投资余额','付借款','借款返还','借款余额'];
        $items = ShopCountApi::getShopCountCondition($param)->get()->toArray();
        Event::fire('shopcount.balanceExport');
        $this->export_xls("店铺往来".date("Ymd"), $header, self::format_shopcount_data($items));
    }   
   
    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        //
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
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update($id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
    
    protected static function format_shopcount_data($datas)
    {
        $res = [];
        foreach ($datas as $data) {
            $salon_type = isset($data['salon']['shopType'])?$data['salon']['shopType']:'';
            $salon_type_name =Mapping::getShopTypeName($salon_type);
            $salon_id = isset($data['salon']['salonid']) ? $data['salon']['salonid'] : '';
            $salon_sn = isset($data['salon']['sn'])?$data['salon']['sn']:'';
            $salon_name = isset($data['salon']['salonname'])?$data['salon']['salonname']:'';
            $merchant_name = isset($data['merchant']['name'])?$data['merchant']['name']:'';
            $typename = "项目消费";
            $res[] = [
                $salon_sn,
                $salon_name,
                $salon_type_name,
                $merchant_name,
                $data['pay_money'],
                $data['spend_money'],
                $data['commission_money'],
                $data['commission_return_money'],
                $data['balance_money'],
                $data['invest_money'],
                $data['invest_return_money'],
                $data['invest_balance_money'],
                $data['borrow_money'],
                $data['borrow_return_money'],
                $data['borrow_balance_money']
            ];
        }
        return $res;
    }
}
