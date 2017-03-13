<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\TransactionSearchApi;
use App\Mapping;
use App\RequestLog;
use Event;
use App\PaymentLog;

class TicketController extends Controller
{
    /**
     * @api {get} /ticket/index 1.臭美券列表
     * @apiName index
     * @apiGroup ticket
     *
     * @apiParam {Number} key  1 臭美券密码 2 用户手机号  3 店铺名称  4 用户设备号  5 代金券编码  6 活动编码
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} time_key  1 消费时间  2付款时间
     * @apiParam {String} min_time 最小时间 YYYY-MM-DD
     * @apiParam {String} max_time 最大时间 YYYY-MM-DD
     * @apiParam {String} state 0 全部  2 未消费 4 已消费 6申请退款 7 退款完成  8 退款中 9 退款失败
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','updated_at'(创建时间,默认),'code'(付款单号),'type'(付款类型),'pay_money'(付款金额),'cost_money'(换算消费额),'day'(付款日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} all_amount 当前条件总应付金额.
     * @apiSuccess {Number} paied_amount 当前条件实付金额.
     * @apiSuccess {String} order_ticket_id 臭美券id
     * @apiSuccess {String} ticketno 臭美券密码
     * @apiSuccess {String} add_time 付款时间
     * @apiSuccess {String} use_time 使用时间
     * @apiSuccess {String} ordersn 订单编号
     * @apiSuccess {String} priceall_ori 应付金额
     * @apiSuccess {String} actuallyPay 实付金额
     * @apiSuccess {String} user_id 付款人id
     * @apiSuccess {String} status 状态  2未使用，4使用完成，6申请退款，7退款完成,8退款中
     * @apiSuccess {String} shopcartsn 购物车号
     * @apiSuccess {String} user 用户信息
     * @apiSuccess {String} user.username 用户臭美号
     * @apiSuccess {String} user.mobilephone 用户手机号
     * @apiSuccess {String} salon 店铺信息
     * @apiSuccess {String} salon.salonname 店铺名称
     * @apiSuccess {String} fundflow 支付信息
     * @apiSuccess {String} fundflow.pay_type 支付方式  1 网银 2 支付宝 3 微信 4 余额 5 红包 6 优惠券 7 积分 8邀请码兑换 10易联
     * @apiSuccess {String} voucher 佣金信息
     * @apiSuccess {String} voucher.vcSn 活动编号
     * @apiSuccess {String} voucher.vSn 代金券编号
     * @apiSuccess {String} platform 设备信息
     * @apiSuccess {String} platform.DEVICE_UUID 设备号
     * 
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "token": "",
     *           "data": {
     *               "total": 108327,
     *               "per_page": 20,
     *               "current_page": 1,
     *               "last_page": 5417,
     *               "from": 1,
     *               "to": 20,
     *               "data": [
     *                   {
     *                       "order_ticket_id": 108900,
     *                       "ticketno": "24610518",
     *                       "add_time": 1441963005,
     *                       "use_time": 0,
     *                       "user_id": 707716,
     *                       "status": 2,
     *                       "ordersn": "4196296911121",
     *                       "salonid": 669,
     *                       "priceall_ori": "20.00",
     *                       "actuallyPay": "0.00",
     *                       "shopcartsn": "",
     *                       "user": {
     *                           "user_id": 707716,
     *                           "username": "10705726",
     *                           "mobilephone": "18576617068"
     *                       },
     *                       "salon": {
     *                           "salonid": 669,
     *                           "salonname": "苏苏美发"
     *                       },
     *                       "fundflow": [
     *                           {
     *                               "record_no": "4196296911121",
     *                               "pay_type": 9
     *                           }
     *                       ],
     *                       "voucher": {
     *                           "vOrderSn": "4196296911121",
     *                           "vcSn": "cm164288",
     *                           "vSn": "CM41678592782"
     *                       },
     *                       "platform":{
     *                          "ORDER_SN":"4196296911121",
     *                          "DEVICE_UUID":"XXXXXXXXXXX"
     *                       }
     *                   },
     *                   {
     *                       "order_ticket_id": 108899,
     *                       "ticketno": "31789371",
     *                       "add_time": 1441961991,
     *                       "use_time": 0,
     *                       "user_id": 707716,
     *                       "status": 2,
     *                       "ordersn": "4196193611328",
     *                       "salonid": 7,
     *                       "priceall_ori": "148.00",
     *                       "actuallyPay": "49.00",
     *                       "shopcartsn": "",
     *                       "user": {
     *                           "user_id": 707716,
     *                           "username": "10705726",
     *                           "mobilephone": "18576617068"
     *                       },
     *                       "salon": {
     *                           "salonid": 7,
     *                           "salonname": "丝凡达护肤造型会所（麒麟店）"
     *                       },
     *                       "fundflow": [
     *                           {
     *                               "record_no": "4196193611328",
     *                               "pay_type": 9
     *                           },
     *                           {
     *                               "record_no": "4196193611328",
     *                               "pay_type": 4
     *                           }
     *                       ],
     *                       "voucher": {
     *                           "vOrderSn": "4196193611328",
     *                           "vcSn": "cm964309",
     *                           "vSn": "CM41520035796"
     *                       }
     *                   }
     *               ],
     *               "all_amount": "11240369.00",
     *               "paied_amount": "42359.01"
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
            'min_time' => self::T_STRING,
            'max_time' => self::T_STRING,
            'state' => self::T_INT,
            'time_key' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING,
        ]);
        $items = TransactionSearchApi::searchOfTicket($params);
        return $this->success($items);
    }
    
    /**
     * @api {get} /ticket/show/{id} 2.臭美券详情
     * @apiName detail
     * @apiGroup ticket
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
        $item = TransactionSearchApi::ticketDetail($id);
        return $this->success($item);
    }
    
    /**
     * @api {get} /ticket/export 3.臭美券导出
     * @apiName export
     * @apiGroup ticket
     *
     * @apiParam {Number} key  1 臭美券密码 2 用户手机号  3 店铺名称  4 用户设备号  5 代金券编码  6 活动编码
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} time_key  1 消费时间  2付款时间
     * @apiParam {String} min_time 最小时间 YYYY-MM-DD
     * @apiParam {String} max_time 最大时间 YYYY-MM-DD
     * @apiParam {String} state 0 全部  2 未消费 4 已消费 6申请退款 7 退款完成  8 退款中 9 退款失败
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','updated_at'(创建时间,默认),'code'(付款单号),'type'(付款类型),'pay_money'(付款金额),'cost_money'(换算消费额),'day'(付款日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     * 
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
        $start_time = microtime(true);
        $params = $this->parameters([
            'key' => self::T_INT,
            'keyword' => self::T_STRING,
            'min_time' => self::T_STRING,
            'max_time' => self::T_STRING,
            'state' => self::T_INT,
            'time_key' => self::T_INT,
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
        $items = TransactionSearchApi::getConditionOfTicket($params)->take($page_size)->skip($offset)
        ->get()
        ->toArray();
     
        $items = TransactionSearchApi::makeTicketOtherInfo($items); 
        
        //用户设备信息
        $ordersns = array_column($items, "ordersn");
        $paymentlogs = PaymentLog::whereIn('ordersn',$ordersns)->select(['ordersn','tn'])->get()->toArray();
        $items = TransactionSearchApi::addPaymentLogInfos($items, $paymentlogs);
    
        $header = [
            '臭美券密码',
            '订单编号',
            '支付方式',
            '付款时间',
            '消费时间',
            '用户臭美号',
            '用户手机号',
            '用户设备号',
            '店铺名称',
            '项目名称',
            '订单金额',
            '活动编码',
            '现金券编号',
            '现金券面额',
            '抵扣金额',
            '实付金额',
            '购物车序号',
            '第三方流水',
        ];
        $res = self::format_export_data($items);
        if(!empty($res))
        {
            Event::fire("ticket.export");
        }
        @ini_set('memory_limit', '256M');
        $this->export_xls("臭美券" . date("Ymd"), $header, $res);
       
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
                'ticketno'=>self::mask_ticketno($data['ticketno']),
                'ordersn'=>' '.$data['ordersn'],
                'payname'=>$pay_typename_str,              
                'add_time'=>date("Y-m-d H:i:s",intval($data['add_time'])),
                'use_time'=>intval($data['use_time'])>0?date("Y-m-d H:i:s",intval($data['use_time'])):"", 
                'username'=>isset($data['user'])&&isset($data['user']['username'])?$data['user']['username']:"",
                'mobilephone'=>isset($data['user'])&&isset($data['user']['mobilephone'])?$data['user']['mobilephone']:"",
                'platform_no'=>isset($data['platform'])&&isset($data['platform']['DEVICE_UUID'])? $data['platform']['DEVICE_UUID'] :'',
                'salonname'=>isset($data['salon'])&&isset($data['salon']['salonname'])?$data['salon']['salonname']:"",
                'itemname'=>$data['itemname'],
                'priceall_ori'=>$data['priceall'],
                'vcSn'=>isset($data['voucher'])&&isset($data['voucher']['vcSn'])?$data['voucher']['vcSn']:"",
                'vSn'=>isset($data['voucher'])&&isset($data['voucher']['vSn'])?$data['voucher']['vSn']:"",
                'voucher_money'=>isset($data['voucher'])&&isset($data['voucher']['vUseMoney'])?$data['voucher']['vUseMoney']:'',
                'voucher_money_used'=>isset($data['voucher'])&&isset($data['voucher']['vUseMoney'])?self::get_voucher_used($data['voucher']['vUseMoney'], $data['priceall']):'',
                'actuallyPay'=>$data['actuallyPay'],
                'shopcartsn'=>' '.$data['shopcartsn'],
                'tn'=>isset($data['payment_log'])&&isset($data['payment_log']['tn'])?' '.$data['payment_log']['tn']:"",
            ];
        }
        return $res;
    }
    
    private static function mask_ticketno($ticketno)
    {
        return $ticketno;//需求更改不加密
        //return substr($ticketno, 0,2)."*****".substr($ticketno, strlen($ticketno)-3);
    }
    
    private static function get_voucher_used($voucher_money,$order_money)
    {
        $voucher_money = floatval($voucher_money);
        $order_money = floatval($order_money);
        if($voucher_money >=$order_money)
        {
            return $order_money;
        }
        return $voucher_money;
    }
}
