<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\TransactionSearchApi;
use App\TransactionWriteApi;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\Utils;
use App\AlipaySimple;
use App\Mapping;
use Event;
use App\OrderRefund;

class OrderRefundController extends Controller
{
    /**
     * @api {get} /refund/index 1.退款列表
     * @apiName index
     * @apiGroup refund
     *
     * @apiParam {Number} key  1订单号 2用户手机号 3用户臭美号   4店铺名称 
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} refund_min_time 最小时间 YYYY-MM-DD
     * @apiParam {String} refund_max_time 最大时间 YYYY-MM-DD
     * @apiParam {String} state 0 全部  6待审核 7已退款 10退款中  12拒绝退款 (多个用','隔开)
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
     * @apiSuccess {String} order_refund_id 退款单id
     * @apiSuccess {String} ticketno 臭美券密码
     * @apiSuccess {String} add_time 申请退款时间
     * @apiSuccess {String} buy_time 购买时间
     * @apiSuccess {String} ordersn 订单编号
     * @apiSuccess {String} priceall_ori 应付金额
     * @apiSuccess {String} actuallyPay 实付金额
     * @apiSuccess {String} refund_money 退款金额
     * @apiSuccess {String} retype  退款方式 1原路返还 2退回余额
     * @apiSuccess {String} order_status 退款状态  6待审核,7退款完成,10退款中
     * @apiSuccess {String} user_id 付款人id
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
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "token": "",
     *           "data": {
     *               "total": 1672,
     *               "per_page": 20,
     *               "current_page": 1,
     *               "last_page": 84,
     *               "from": 1,
     *               "to": 20,
     *               "data": [
     *                   {
     *                       "order_refund_id": 1693,
     *                       "ordersn": "2200506921873",
     *                       "ticketno": "x0000846",
     *                       "user_id": 650140,
     *                       "salonid": 630,
     *                       "add_time": 1441597129,
     *                       "status": 1,
     *                       "refund_money": "20.00",
     *                       "retype": 1,
     *                       "priceall_ori": "1.00",
     *                       "actuallyPay": "20.00",
     *                       "shopcartsn": "",
     *                       "buy_time": 1422005069,
     *                       "order_status": 2,
     *                       "user": {
     *                           "user_id": 650140,
     *                           "username": "10648176",
     *                           "mobilephone": "18565690118"
     *                       },
     *                       "salon": {
     *                           "salonid": 630,
     *                           "salonname": "choumeitest_salon"
     *                       },
     *                       "fundflow": [
     *                           {
     *                               "record_no": "2200506921873",
     *                               "pay_type": 2
     *                           }
     *                       ],
     *                       "voucher": null
     *                   },
     *                   {
     *                       "order_refund_id": 1692,
     *                       "ordersn": "2200506921873",
     *                       "ticketno": "x0000846",
     *                       "user_id": 650140,
     *                       "salonid": 630,
     *                       "add_time": 1441596981,
     *                       "status": 1,
     *                       "refund_money": "20.00",
     *                       "retype": 1,
     *                       "priceall_ori": "1.00",
     *                       "actuallyPay": "20.00",
     *                       "shopcartsn": "",
     *                       "buy_time": 1422005069,
     *                       "order_status": 2,
     *                       "user": {
     *                           "user_id": 650140,
     *                           "username": "10648176",
     *                           "mobilephone": "18565690118"
     *                       },
     *                       "salon": {
     *                           "salonid": 630,
     *                           "salonname": "choumeitest_salon"
     *                       },
     *                       "fundflow": [
     *                           {
     *                               "record_no": "2200506921873",
     *                               "pay_type": 2
     *                           }
     *                       ],
     *                       "voucher": null
     *                   }
     *               ],
     *               "refund_money": "10155643.77"
     *           }
     *       }
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
            'refund_min_time' => self::T_STRING,
            'refund_max_time' => self::T_STRING,
            'state' => self::T_STRING,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING,
        ]);
        $items = TransactionSearchApi::searchOfRefund($params);
        return $this->success($items);
    }
    
    /**
     * @api {get} /refund/show/{id} 2.退款详情
     * @apiName show
     * @apiGroup refund
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
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function show($id)
    {
        $id = intval($id);
        $item = TransactionSearchApi::refundDetail($id);
        return $this->success($item);
    }
    
    /**
     * @api {get} /refund/export 3.退款导出
     * @apiName export
     * @apiGroup refund
     *
     * @apiParam {Number} key  1订单号 2用户手机号 3用户臭美号   4店铺名称 
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} refund_min_time 最小时间 YYYY-MM-DD
     * @apiParam {String} refund_max_time 最大时间 YYYY-MM-DD
     * @apiParam {String} state 0 全部  6待审核 7已退款 10退款中  12拒绝退款 (多个用','隔开)
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 ['id','updated_at'(创建时间,默认),'code'(付款单号),'type'(付款类型),'pay_money'(付款金额),'cost_money'(换算消费额),'day'(付款日期)]
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     *
     */
    public function export()
    {
        $params = $this->parameters([
            'key' => self::T_INT,
            'keyword' => self::T_STRING,
            'refund_min_time' => self::T_STRING,
            'refund_max_time' => self::T_STRING,
            'state' => self::T_STRING,
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
        $items = TransactionSearchApi::getConditionOfRefund($params)->take($page_size)->skip($offset)
        ->get()
        ->toArray();
        $header = [
            '臭美券密码',
            '订单编号',
            '购买时间',
            '申请退款时间',
            '店铺名称',
            '用户手机号',
            '现金券编号',
            '支付方式',
            '金额',
            '实付金额',
            '退款金额',
            '退款方式',
            '退款状态',
            '购物车号',
        ];
        $res = self::format_export_data($items);
        if(!empty($res))
        {
            Event::fire("refund.export");
        }
        @ini_set('memory_limit', '256M');
        $this->export_xls("退款单 " . date("Ymd"), $header, $res);
    }
    
    /**
     * @api {post} /refund/accept 4.退款通过
     * @apiName accept
     * @apiGroup refund
     *
     * @apiParam {Number} ids id(多个用','隔开).
     * 
     * @apiSuccess {String} alipay 支付宝
     * @apiSuccess {String} wx 微信
     * @apiSuccess {String} balance 余额
     * @apiSuccess {String} yilian 易联
     *
     * @apiSuccessExample Success-Response:
     *     {
     *        "result": 1,
     *        "token": "",
     *        "data": {
     *            "alipay": {
     *                "form_args": {
     *                    "_input_charset": "utf-8",
     *                    "batch_no": "20150921153317",
     *                    "batch_num": "1",
     *                    "detail_data": "2015091600001000780065371963^25.00^买多了/买错了",
     *                    "notify_url": "http://192.168.13.46:9140/refund/call_back_of_alipay",
     *                    "partner": "2088701753684258",
     *                    "refund_date": "2015-09-21 15:33:17",
     *                    "seller_email": "zfb@choumei.cn",
     *                    "service": "refund_fastpay_by_platform_pwd",
     *                    "sign": "b2eb81f50f8de1b04a86e1fddb260f6e",
     *                    "sign_type": "MD5"
     *                }
     *            },
     *            "wx":{
     *              "info":"退款成功"
     *            },
     *            "balance":{
     *              "info":"退款成功"
     *            },
     *            "yilian":{
     *              "info":"退款失败<br> ordersn:xxxxx tn:xxxxx"
     *            }
     *        }
     *    }
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function accept()
    {
        $params = $this->parameters(['ids'=>self::T_STRING],true);
        $ids = explode(",", $params['ids']);
        $ids = array_map("intval", $ids);
        if(count($ids)<1)
        {
            throw new ApiException("ids 参数不能为空", ERROR::PARAMS_LOST);
        }
        $info = TransactionWriteApi::accpet($ids);
        $refunds = OrderRefund::whereIn("order_refund_id",$ids)->get(['ordersn']);         
        if(!empty($refunds))
        {
            $ordersns = array_column($refunds->toArray(), "ordersn");
            Event::fire("refund.accept",implode(',',$ordersns));
        }
        return $this->success($info);
    }
    
    /**
     * @api {post} /refund/reject 4.退款拒绝
     * @apiName reject
     * @apiGroup refund
     *
     * @apiSuccess {Number} ids id(多个用','隔开).
     * @apiSuccess {Number} reason 拒绝原因
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function reject()
    {
        $params = $this->parameters([
            'ids'=>self::T_STRING,
            'reason'=>self::T_STRING,
        ],true);
        $ids = explode(",", $params['ids']);
        $ids = array_map("intval", $ids);
        if(count($ids)<1)
        {
            throw new ApiException("ids 参数不能为空", ERROR::PARAMS_LOST);
        }        
        $info = TransactionWriteApi::reject($ids,$params['reason']);
       
        $refunds = OrderRefund::whereIn("order_refund_id",$ids)->get(['ordersn']);   
        if(!empty($refunds))
        {
            $ordersns = array_column($refunds->toArray(), "ordersn");
            Event::fire("refund.reject",implode(',',$ordersns));
        }
        return $this->success($info);
    }
    
    /**
     * 支付宝的回调
     */
    public function call_back_of_alipay()
    {
        $input = [
            'GET' => $_GET,
            "POST" => $_POST
        ];
        Utils::log('pay',date("Y-m-d H:i:s") . "\t order " . json_encode($input,JSON_UNESCAPED_UNICODE)."\t\n", "alipay_callback");
        
        $is_debug = false;
        if(isset($_GET['is_debug']) && $_GET['is_debug'] == 1)
        {
            $is_debug = true;
        }
        if(isset($_POST['is_debug']) && $_POST['is_debug'] == 1)
        {
            $is_debug = true;
        }
            
        //以下为debug的写法
        //$ret = AlipaySimple::callback(array(D("Refund"),"alipayRefundCallback"),[],false);
         
        //以下为正式的写法
        $ret = AlipaySimple::callback(function($args){
            return TransactionWriteApi::callBackOfAlipay($args);
        },[],$is_debug);
         
        if($ret)
        {
            Utils::log('pay',date("Y-m-d H:i:s") . "\t callback success \t " . json_encode($input,JSON_UNESCAPED_UNICODE)."\t\n", "alipay_callback");
            echo "success";
        }
        else
        {
            Utils::log('pay',date("Y-m-d H:i:s") . "\t callback fail \t " . json_encode($input,JSON_UNESCAPED_UNICODE)."\t\n", "alipay_callback");
            echo "fail";
        }
        die();
    }

    private static function format_export_data($datas)
    {
        $res = [];
        foreach ($datas as $data) {
            $pay_types = array_column($data['fundflow'], "pay_type");
            $pay_typename_str = '';
            $pay_names = Mapping::getFundflowPayTypeNames($pay_types);
            if(count($pay_names)>0)
            {
                $pay_typename_str = implode("+", $pay_names);
            }
            $res[] = [
                'ticketno' => self::mask_ticketno($data['ticketno']),
                'ordersn' => ' '.$data['ordersn'],
                'buy_time' => date("Y-m-d H:i:s", intval($data['buy_time'])),
                'add_time' => intval($data['add_time']) > 0 ? date("Y-m-d H:i:s", intval($data['add_time'])) : "",
                'salonname' => isset($data['salon']) && isset($data['salon']['salonname']) ? $data['salon']['salonname'] : "",
                'mobilephone' => isset($data['user']) && isset($data['user']['mobilephone']) ? $data['user']['mobilephone'] : "",
                'vSn' => isset($data['voucher']) && isset($data['voucher']['vSn']) ? $data['voucher']['vSn'] : "",
                'payname' => $pay_typename_str,
                'priceall_ori' => $data['priceall_ori'],
                'actuallyPay' => $data['actuallyPay'],
                'refund_money' => $data['refund_money'],
                'retype' => Mapping::getOrderRefundRetypeName($data['retype']),
                'order_status' => Mapping::getOrderStatusName($data['order_status'],[6=>'待审核']),
                'shopcartsn' =>' '.$data['shopcartsn']
            ];
        }
        return $res;
    }

    private static function mask_ticketno($ticketno)
    {
        return $ticketno;//需求更改不加密
        //return substr($ticketno, 0, 2) . "*****" . substr($ticketno, strlen($ticketno) - 3);
    }
}
