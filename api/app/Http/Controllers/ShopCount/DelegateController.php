<?php

namespace App\Http\Controllers\ShopCount;


use App\Http\Controllers\Controller;
use App\ShopCountApi;
use Event;
use Log;
class DelegateController extends Controller
{
    /**
     * @api {get} /shop_count/delegate_list 7.代收单 列表
     * @apiName delegate_list
     * @apiGroup ShopCount
     *
     * @apiParam {Number} key  1 店铺搜索 2 商户搜索 3 店铺编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} pay_time_min 付款最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 付款最大时间 YYYY-MM-DD
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','created_at'(创建时间,默认),'code'(代收单号),'type'(代收类型),'money'(代收金额),'day'(代收日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} code 代收单号.
     * @apiSuccess {Number} type 代收类型.1项目消费.
     * @apiSuccess {String} money 代收金额.
     * @apiSuccess {String} created_at 创建时间.
     * @apiSuccess {String} day  代收日期.
     * @apiSuccess {Object} salon 店铺信息.
     * @apiSuccess {Object} merchant 商盟信息.
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
     *                       "created_at": "2015-07-02 00:00:00",
     *                       "merchant_id": 2,
     *                       "salon_id": 3,
     *                       "code": "dfasdagasdfasdfasd",
     *                       "type": 1,
     *                       "money": "3600.00",
     *                       "day": "2015-06-01",
     *                       "salon": {
     *                           "salonid": 3,
     *                           "sn":"商铺编号",
     *                           "salonname": "米莱国际造型连锁（田贝店）"
     *                       },
     *                       "merchant": {
     *                           "id": 2,
     *                           "name": "地对地导弹"
     *                       }
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
        $items = ShopCountApi::searchInsteadReceive($param);
        return $this->success($items);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
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
     * @api {get} /shop_count/delegate_detail/{id} 8.代收单 详情
     * @apiName delegate_detail
     * @apiGroup ShopCount
     *
     * @apiParam {Number} id  id
     *
     * @apiSuccess {String} code 代收单号.
     * @apiSuccess {Number} type 代收类型.1项目消费.
     * @apiSuccess {String} money 代收金额.
     * @apiSuccess {String} created_at 创建时间.
     * @apiSuccess {String} day  代收日期.
     * @apiSuccess {Object} salon 店铺信息.
     * @apiSuccess {Object} merchant 商盟信息.
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "data": {
     *               "id": 1,
     *               "created_at": "2015-07-02 00:00:00",
     *               "merchant_id": 2,
     *               "salon_id": 3,
     *               "code": "dfasdagasdfasdfasd",
     *               "type": 1,
     *               "money": "3600.00",
     *               "day": "2015-06-01",
     *               "salon": {
     *                   "salonid": 3,
     *                   "sn":"商铺编号",
     *                   "salonname": "米莱国际造型连锁（田贝店）"
     *               },
     *               "merchant": {
     *                   "id": 2,
     *                   "name": "地对地导弹"
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
        $item = ShopCountApi::insteadReceiveDetail($id);
        return $this->success($item);
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
    
    /**
     * @api {get} /shop_count/delegate_export 12.导出代收单
     * @apiName delegate_export
     * @apiGroup ShopCount
     *
     * @apiParam {Number} key  1 店铺搜索 2 商户搜索 3 店铺编号
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} pay_time_min 付款最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 付款最大时间 YYYY-MM-DD
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','created_at'(创建时间,默认),'code'(代收单号),'type'(代收类型),'money'(代收金额),'day'(代收日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
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
        $header = ['店铺编码','店铺名称','代收单号','代收金额','代收日期'];
        $items = ShopCountApi::getInsteadReceiveCondition($param)->take(5000)->get()->toArray();
        Event::fire('shopcount.delegateExport');
        $res = self::format_ir_data($items);
        ini_set('memory_limit','256M');
        $this->export_xls("代收单".date("Ymd"), $header, $res);
    }
    
    protected static function format_ir_data($datas)
    {
        $res = [];
        foreach ($datas as $data) {
            $salon_name = isset($data['salon']['salonname']) ? $data['salon']['salonname'] : '';
            $salon_id = isset($data['salon']['salonid']) ? $data['salon']['salonid'] : '';
            $salon_sn = isset($data['salon']['sn']) ? $data['salon']['sn'] : '';
            $res[] = [
                $salon_sn,
                $salon_name,
                $data['code'],
                $data['money'],
                $data['day']
            ];
        }
        return $res;
    }
}
