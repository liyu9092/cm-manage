<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\TransactionSearchApi;
use App\Mapping;
use Event;

class OrderController extends Controller
{
    /**
     * @api {get} /order/index 1.订单列表
     * @apiName index
     * @apiGroup order
     *
     * @apiParam {Number} key  1 订单号  2 臭美券密码 3 用户手机号  4 店铺名称
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} pay_time_min 下单最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 下单最大时间 YYYY-MM-DD
     * @apiParam {String} pay_type 0 全部  1 网银 2 支付宝 3 微信 4 余额 5 红包 6 优惠券 7 积分 8邀请码兑换 10易联
     * @apiParam {String} pay_state 0 全部  1未支付 2已支付
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 []
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} total_money 当前条件总金额.
     * @apiSuccess {String} orderid 订单id
     * @apiSuccess {String} ordersn 订单编号
     * @apiSuccess {String} priceall 交易金额
     * @apiSuccess {String} add_time 下单时间
     * @apiSuccess {String} pay_time 付款时间
     * @apiSuccess {String} user_id 付款人id
     * @apiSuccess {String} ispay 交易状态  1未付款  2 已付款
     * @apiSuccess {String} user 用户信息
     * @apiSuccess {String} user.username 用户臭美号
     * @apiSuccess {String} user.mobilephone 用户手机号
     * @apiSuccess {String} salon 店铺信息
     * @apiSuccess {String} salon.salonname 店铺名称
     * @apiSuccess {String} fundflow 支付信息
     * @apiSuccess {String} fundflow.pay_type 支付方式  1 网银 2 支付宝 3 微信 4 余额 5 红包 6 优惠券 7 积分 8邀请码兑换 10易联
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "token": "",
     *           "data": {
     *               "total": 149196,
     *               "per_page": 20,
     *               "current_page": 1,
     *               "last_page": 7460,
     *               "from": 1,
     *               "to": 20,
     *               "data": [
     *                   {
     *                       "orderid": 708877,
     *                       "ordersn": "4219477511889",
     *                       "priceall": "1.00",
     *                       "salonid": 669,
     *                       "add_time": 1442194775,
     *                       "pay_time": 0,
     *                       "user_id": 878669,
     *                       "ispay": 1,
     *                       "user": {
     *                           "user_id": 878669,
     *                           "username": "10876679",
     *                           "mobilephone": "18588252193"
     *                       },
     *                       "salon": {
     *                           "salonid": 669,
     *                           "salonname": "苏苏美发"
     *                       },
     *                       "fundflow": [
     *                          {
     *                              "record_no": "4187664711988",
     *                              "pay_type": 10
     *                          },
     *                       ]
     *                   },
     *                   {
     *                       "orderid": 708876,
     *                       "ordersn": "4197495931904",
     *                       "priceall": "249.00",
     *                       "salonid": 7,
     *                       "add_time": 1441974959,
     *                       "pay_time": 0,
     *                       "user_id": 878669,
     *                       "ispay": 1,
     *                       "user": {
     *                           "user_id": 878669,
     *                           "username": "10876679",
     *                           "mobilephone": "18588252193"
     *                       },
     *                       "salon": {
     *                           "salonid": 7,
     *                           "salonname": "丝凡达护肤造型会所（麒麟店）"
     *                       },
     *                       "fundflow": []
     *                   }
     *               ],
     *               "total_money": "11574991.90"
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
       $params = $this->parameters([
            'key' => self::T_INT,
            'keyword' => self::T_STRING,
            'pay_time_min' => self::T_STRING,
            'pay_time_max' => self::T_STRING,
            'pay_type' => self::T_STRING,
            'pay_state' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING,
       ]);
       
       $items = TransactionSearchApi::searchOfOrder($params);
       return $this->success($items);
    }

    /**
     * @api {get} /order/show/{id} 2.订单详情
     * @apiName show
     * @apiGroup order
     *
     * @apiSuccess {String} ticket 臭美券信息
     * @apiSuccess {String} ticket.ticketno 臭美券密码
     * @apiSuccess {String} paymentlog 流水信息
     * @apiSuccess {String} paymentlog.tn 第三方流水号
     * @apiSuccess {String} item 项目信息
     * @apiSuccess {String} item.itemname 项目名称
     * @apiSuccess {String} salon 店铺信息
     * @apiSuccess {String} salon.salonname 店铺名称
     * @apiSuccess {String} user 用户信息
     * @apiSuccess {String} user.username 用户臭美号
     * @apiSuccess {String} user.mobilephone 用户手机号
     * @apiSuccess {String} order 订单信息
     * @apiSuccess {String} order.ordersn 订单编号
     * @apiSuccess {String} order.shopcartsn 购物车序号 
     * @apiSuccess {String} order.priceall 订单金额
     * @apiSuccess {String} order.actuallyPay 实付金额
     * @apiSuccess {String} fundflow 金额构成
     * @apiSuccess {String} fundflow.pay_type 支付方式  1 网银 2 支付宝 3 微信 4 余额 5 红包 6 优惠券 7 积分 8邀请码兑换 10易联
     * @apiSuccess {String} fundflow.money 支付金额
     * @apiSuccess {String} trends 臭美券动态 
     * @apiSuccess {String} trends.add_time 臭美券动态.时间  
     * @apiSuccess {String} trends.status 臭美券动态.行为   [2未使用，4使用完成，6申请退款，7退款完成，8退款拒绝,10退款中]
     * @apiSuccess {String} trends.remark  臭美券动态.行为 备注信息,为空时显示 上面status 对应的信息
     * @apiSuccess {String} vouchers 代金券信息  
     * @apiSuccess {String} vouchers.vSn 代金券编码 
     * @apiSuccess {String} vouchers.vcSn 活动编号
     * @apiSuccess {String} vouchers.vUseMoney 金额
     * @apiSuccess {String} vouchers.vUseTime 使用时间  
     * @apiSuccess {String} vouchers.vUseEnd 有效期
     * @apiSuccess {String} vouchers.status 状态 1未使用 2已使用 3待激活 5已失效 10 未上线
     * @apiSuccess {String} commission 佣金信息
     * @apiSuccess {String} commission.amount 佣金金额
     * @apiSuccess {String} commission.rate 佣金率
     * @apiSuccess {String} commission.grade 店铺当前等级 1S 2A 3B 4C 5新落地 6淘汰区
     * @apiSuccess {String} salonRecommendCode 店铺优惠码(店铺)信息
     * @apiSuccess {String} salonRecommendCode.recommend_code 店铺优惠码
     * @apiSuccess {String} recommend_code 店铺优惠码(佣金)信息
     * @apiSuccess {String} recommend_code.recommend_code 店铺优惠码(佣金)
     * @apiSuccess {String} recommend_code.salonname 店铺优惠码(佣金)
     * @apiSuccess {String} platform 设备信息
     * @apiSuccess {String} platform.DEVICE_UUID 设备号
     * @apiSuccess {String} platform.DEVICE_OS 设备系统
     * @apiSuccess {String} platform.DEVICE_MODEL 手机型号
     * @apiSuccess {String} platform.DEVICE_NETWORK 网络
     * @apiSuccess {String} platform.VERSION APP版本
     * @apiSuccess {String} platform.DEVICE_TYPE 终端类型
     * @apiSuccess {String} platform.OPENID 微信OPENID
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "token": "",
     *           "data": {
     *               "order": {
     *                   "ordersn": "4187664711988",
     *                   "orderid": 708851,
     *                   "priceall": "1.00",
     *                   "salonid": 84,
     *                   "actuallyPay": "1.00",
     *                   "shopcartsn": ""
     *               },
     *               "item": {
     *                   "order_item_id": 150256,
     *                   "itemname": "柠檬去味吹发变身柠檬女神",
     *                   "ordersn": "4187664711988"
     *               },
     *               "ticket": {
     *                   "order_ticket_id": 108898,
     *                   "ticketno": "17170134",
     *                   "user_id": 306669
     *               },
     *               "user": {
     *                   "username": "10306576",
     *                   "mobilephone": "18319019483"
     *               },
     *               "salon": {
     *                   "salonname": "苏格护肤造型生活馆（2店）"
     *               },
     *               "paymentlog": {
     *                    "ordersn": "4187664711988",
     *                    "tn": "1224362901341509107433258086"
     *               },
     *               "fundflows": [
     *                   {
     *                       "pay_type": 10,
     *                       "money": "1.00"
     *                   }
     *               ],
     *               "trends": [
     *                   {
     *                       "add_time": 1441876684,
     *                       "status": 2,
     *                       "remark": "未使用"
     *                   }
     *               ],
     *               "vouchers":
     *               {
     *                   "vSn": "CM41678592782",
     *                   "vcSn": "cm164288",
     *                   "vOrderSn": "4196296911121",
     *                   "vUseMoney": 20,
     *                   "vAddTime": 1441962977,
     *                   "vUseEnd": 1442505599,
     *                   "vStatus": 1,
     *               }
     *               "commission": 
     *                {
     *                  "ordersn":"2008481211896",
     *                  "amount":"43.27",
     *                  "rate":"9.09",
     *                  "grade":"0"
     *                },
     *               "recommend_code": {
     *                  "recommend_code":"1168",
     *                  "salonname":"choumeitest店",
     *               },
     *               "salonRecommendCode": {
     *                  "recommend_code":"1168"
     *               },
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
        $id = intval($id);
        $item = TransactionSearchApi::orderDetail($id);
        return $this->success($item);
    }

    /**
     * @api {get} /order/export 3.订单导出
     * @apiName export
     * @apiGroup order
     *
     * @apiParam {Number} key 1 订单号 2 臭美券密码 3 用户手机号 4 店铺名称
     * @apiParam {String} keyword 根据key来的关键字
     * @apiParam {String} pay_time_min 下单最小时间 YYYY-MM-DD
     * @apiParam {String} pay_time_max 下单最大时间 YYYY-MM-DD
     * @apiParam {String} pay_type 0 全部 1 网银 2 支付宝 3 微信 4 余额 5 红包 6 优惠券 7 积分 8邀请码兑换 10易联
     * @apiParam {String} pay_state 0 全部 1未支付 2已支付
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     *
     * @apiErrorExample Error-Response:
     * {
     * "result": 0,
     * "msg": "未授权访问"
     * }
     */
    public function export()
    { 
        $params = $this->parameters([
            'key' => self::T_INT,
            'keyword' => self::T_STRING,
            'pay_time_min' => self::T_STRING,
            'pay_time_max' => self::T_STRING,
            'pay_type' => self::T_STRING,
            'pay_state' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
        ]);
        $page = isset($params['page'])?$params['page']:1;
        $page_size = isset($params['page_size'])?$params['page_size']:20;
        if(empty($page) || $page <1)
        {
            $page = 1;
        }
        if(empty($page_size) || $page_size > 5000)
        {
            $page_size = 20;
        }
        $offset = ($page-1) * $page_size;
        $items = TransactionSearchApi::getConditionOfOrder($params)->take($page_size)->skip($offset)
            ->get()
            ->toArray();
      
        $header = [
            '订单编号',
            '支付方式',
            '交易金额',
            '下单时间',
            '付款时间',
            '用户臭美号',
            '用户手机号',
            '店铺名称',
            '交易状态'
        ];
        $res = self::format_export_data($items);
        if(!empty($res))
        {
            Event::fire("order.export");
        }
        @ini_set('memory_limit', '256M');
        $this->export_xls("普通订单" . date("Ymd"), $header, $res);
    }
    
    
    private static function format_export_data($datas)
    {
        $res = [];
        foreach($datas as $data)
        {
            $pay_types = array_column($data['fundflow'], "pay_type");
            $pay_typename_str = '';
            $pay_names = Mapping::getFundflowPayTypeNames($pay_types);
            if(count($pay_names)>0)
            {
                $pay_typename_str = implode("+", $pay_names);
            }
            $res[] = [
                'ordersn'=>' '.$data['ordersn'],
                'payname'=>$pay_typename_str,
                'money'=>$data['priceall'],
                'add_time'=>date("Y-m-d H:i:s",intval($data['add_time'])),
                'pay_time'=>intval($data['pay_time'])>0?date("Y-m-d H:i:s",intval($data['pay_time'])):"",
                'username'=>isset($data['user'])&&isset($data['user']['username'])?$data['user']['username']:"",
                'mobilephone'=>isset($data['user'])&&isset($data['user']['mobilephone'])?$data['user']['mobilephone']:"",
                'salonname'=>isset($data['salon'])&&isset($data['salon']['salonname'])?$data['salon']['salonname']:"",
                'is_pay'=>Mapping::getOrderIsPayName($data['ispay']),
            ];
        }
        return $res;
    }
}
