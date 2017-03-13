<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\OrderRefund;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\BeautyRefundApi;
use App\BookingOrderItem;
use App\Utils;
use App\AlipaySimple;
use Event;
use App\BookingOrder;

class BeautyRefundController extends Controller {

    /**
     * @api {get} /beautyrefund/index 1.定妆单退款列表
     * @apiName index
     * @apiGroup beautyrefund
     *
     * @apiParam {Number} key  1手机号 2预约号 3推荐码
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} start_time 开始时间 YYYY-MM-DD
     * @apiParam {String} end_time 结束时间 YYYY-MM-DD
     * @apiParam {String} pay_type 0 全部  2 支付宝 3 微信 7 积分 10 易联支付
     * @apiParam {String} state 订单退款状态  全部(用逗号分隔其他所有状态)  RFN - 退款中，RFD\RFD-OFL - 已退款' RFE - 退款失败 TODO
     * @apiParam {String} sort_key 可选,排序字段 state 退款状态
     * @apiParam {String} sort_type 可选,排序 DESC倒序 ASC升序. 
     * @apiParam {Number} initiate_refund 发起退款 （0 全部  1用户 2臭美人员). 
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} order_refund_id 退款单id
     * @apiSuccess {String} booking_id 定妆单ID(现在列表以此字段的ID为准)
     * @apiSuccess {String} add_time 申请退款时间
     * @apiSuccess {String} ordersn 订单编号
     * @apiSuccess {String} booking_sn 预约号
     * @apiSuccess {String} money 退款金额
     * @apiSuccess {String} retype  退款方式 1原路返还 2退回余额
     * @apiSuccess {String} book_status 退款状态  RFN - 退款中，RFD\RFD-OFL - 已退款 RFE - 退款失败
     * @apiSuccess {String} user_id 付款人id
     * @apiSuccess {String} booker_name 用户姓名
     * @apiSuccess {String} booker_phone 用户手机号
     * @apiSuccess {String} item_name 预约项目名称
     * @apiSuccess {String} pay_type 支付方式  1 网银 2 支付宝 3 微信 4 余额  7 积分  10易联
     * @apiSuccess {Number} initiate_refund 发起退款 1 用户退款 2臭美人员
     *
     * @apiSuccessExample Success-Response:
     * {
     *      "result": 1,
     *     "token": "",
     *     "data": {
     *         "total": 2,
     *         "per_page": 20,
     *         "current_page": 1,
     *         "last_page": 1,
     *         "from": 1,
     *         "to": 2,
     *         "data": [
     *             {
     *                 "order_refund_id": 1756,
     *                 "ordersn": "4902596925807",
     *                 "user_id": 123132132,
     *                 "add_time": "2015-12-01 14:05:39",
     *                 "money": "121123.00",
     *                 "booking_sn": "",
     *                 "refund_status": 1,
     *                 "status": "RFN",
     *                 "booker_name": "老黄（女士）",
     *                 "booker_phone": "",
     *                 "pay_type": null,
     *                "booking_order_item": [
     *                     {
     *                        "order_sn": "4902596925807",
     *                        "item_name": "韩式提拉"
     *                     }
     *                ]
     *             },
     *             {
     *                 "order_refund_id": 19,
     *                 "ordersn": "3891556931672",
     *                 "user_id": 306818,
     *                 "add_time": "2015-01-02 16:11:47",
     *                 "money": "290.00",
     *                 "booking_sn": "sad2323232",
     *                 "refund_status": 1,
     *                 "status": "RFN",
     *                 "booker_name": "",
     *                 "booker_phone": null,
     *                  "pay_type": 2,
     *                 "booking_order_item": [
     *                      {
     *                         "order_sn": "3891556931672",
     *                         "item_name": "测试时"
     *                      },
     *                      {
     *                          "order_sn": "3891556931672",
     *                          "item_name": "韩式提拉"
     *                      }
     *                  ]
     *              }
     *          ]
     *      }
     *  }
     *        
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function index() {
        $param = $this->parameters([
            'key' => self::T_INT, // 1手机号 2预约号  3推荐码
            'keyword' => self::T_STRING,
            'start_time' => self::T_STRING,
            'end_time' => self::T_STRING,
            'pay_type' => self::T_INT,
            'state' => self::T_STRING, //state 0 全部  状态：NEW - 未支付,PYD - 已支付,CSD - 已消费,RFN - 申请退款(退款中),RFD - 已退款',
            'page' => self::T_INT,
            'page_size' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING,
            'initiate_refund' => self::T_INT,
        ]);
        $param['startTime'] = isset($param['start_time']) ? $param['start_time'] : '';
        $param['end_time'] = isset($param['end_time']) ? $param['end_time'] : '';
        $page = isset($param['page']) ? max(intval($param['page']), 1) : 1;
        $size = isset($param['page_size']) ? max(intval($param['page_size']), 1) : 20;
//        print_r($param);exit;
        $refundList = OrderRefund::serachMakeupRefundList($param, $page, $size);
        $data = [];
        foreach ($refundList['data'] as &$value) {
            $value['add_time'] = !empty($value['add_time']) ? date("Y-m-d H:i:s", $value['add_time']) : $value['created_at'];
            // 查找项目信息
            $booking_order_items = BookingOrderItem::where('order_sn', $value['ordersn'])->get(['order_sn', 'item_name'])->toArray();
            $value['booking_order_item'] = $booking_order_items;
            unset($booking_order_item);
            if (empty($value['id'])) {
                $value['initiate_refund'] = 1;
                $value['money'] = $value['order_refund_money'];
                //如果在退款中   查询是否是退款失败
                if ($value['book_status'] == 'RFN' && $value['status'] == 3) {
                    $value['book_status'] = 'RFE';
                }
            } else {
                $value['initiate_refund'] = 2;
                $value['money'] = $value['salon_refund_money'];
                $value['order_refund_id'] = 0;
            }
        }
        return $this->success($refundList);
    }

    /**
     * @api {get} /beautyrefund/show/{id} 2.定妆单退款详情
     * @apiName show
     * @apiGroup beautyrefund
     *
     * @apiSuccess {String} order 预约单
     * @apiSuccess {String} order.ID 预约单ID
     * @apiSuccess {String} order.ORDER_SN 订单号
     * @apiSuccess {String} order.BOOKING_SN 预约号
     * @apiSuccess {String} order.BOOKING_DATE 预约日期
     * @apiSuccess {String} order.UPDATED_BOOKING_DATE 修改后的预约日期 / 客服调整的预约日期
     * @apiSuccess {String} order.QUANTITY 数量
     * @apiSuccess {String} order.AMOUNT 订单金额
     * @apiSuccess {String} order.PAYABLE 应付金额(已支付)
     * @apiSuccess {String} order.BOOKER_NAME 预约人姓名
     * @apiSuccess {String} order.BOOKER_SEX 预约人性别
     * @apiSuccess {String} order.BOOKER_PHONE 预约人电话
     * @apiSuccess {String} order.BOOKER_NAME 预约人姓名
     * @apiSuccess {String} order.STATUS 订单状态 NEW - 未支付,PYD - 已支付,CSD - 已消费,RFN - 申请退款,RFD - 已退款  RFD-OFL 线下已退款 RFE退款失败
     * @apiSuccess {String} order.PAIED_TIME 支付时间
     * @apiSuccess {String} order.CONSUME_TIME 消费时间
     * @apiSuccess {String} order.CREATE_TIME 预约时间
     * @apiSuccess {String} order.UPDATE_TIME 最近修改时间
     * @apiSuccess {String} order.BOOKING_DESC 预约时间  DEF-未选择，MORNING - 上午，AFTERNOON下午
     * @apiSuccess {Object} order.manager 客服信息
     * @apiSuccess {String} order.item_amount 项目总价
     * @apiSuccess {String} order_item 预约项目信息
     * @apiSuccess {String} order_item.ID 项目ID
     * @apiSuccess {String} order_item.ITEM_NAME 项目名称
     * @apiSuccess {String} order_item.AMOUNT 预约金总额
     * @apiSuccess {String} order_item.PAYABLE 应付总额
     * @apiSuccess {String} beauty_order_item 实做项目信息
     * @apiSuccess {String} beauty_order_item.item_id 项目ID
     * @apiSuccess {String} beauty_order_item.item_name 项目名称
     * @apiSuccess {String} beauty_order_item.amout 总额
     * @apiSuccess {String} beauty_order_item.to_pay_amount 应付总额
     * @apiSuccess {String} beauty_order_item.norm_id 项目规格ID
     * @apiSuccess {String} beauty_order_item.norm_name 项目规格名称
     * @apiSuccess {String} fundflow 金额构成
     * @apiSuccess {String} fundflow.pay_type 支付方式  1 网银 2 支付宝 3 微信 4 余额 5 红包 6 优惠券 7 积分 8邀请码兑换 10易联
     * @apiSuccess {String} paymentlog 流水信息
     * @apiSuccess {String} paymentlog.tn 第三方流水号
     * @apiSuccess {String} recommend 推荐信息
     * @apiSuccess {String} recommend.recommend_code 推荐码
     * @apiSuccess {String} makeup 补妆信息
     * @apiSuccess {String} makeup.remark 说明
     * @apiSuccess {String} makeup.work_at 补妆时间
     * @apiSuccess {String} makeup.created_at 操作时间
     * @apiSuccess {String} makeup.manager 操作人信息
     * @apiSuccess {String} makeup.expert 专家信息
     * @apiSuccess {String} makeup.assistant 助理信息
     * @apiSuccess {String} booking_bill 发票信息
     * @apiSuccess {String} booking_bill.created_at 开发票时间
     * @apiSuccess {String} booking_bill.manager 开发票操作人信息
     * @apiSuccess {String} booking_cash 收银信息
     * @apiSuccess {String} booking_cash.pay_type 支付方式1:微信2:支付宝3:POS机,4:现金,5:微信+现金6:支付宝+现金7:POS机+现金
     * @apiSuccess {String} booking_cash.other_money 除现金外的其他支付金额
     * @apiSuccess {String} booking_cash.cash_money 现金金额
     * @apiSuccess {String} booking_cash.deduction_money 现金金额
     * @apiSuccess {String} booking_cash.created_at 收银时间
     * @apiSuccess {String} booking_cash.manager 操作人信息
     * @apiSuccess {String} booking_cash.expert 专家信息
     * @apiSuccess {String} booking_cash.assistant 助理信息
     * @apiSuccess {String} booking_receive 接待信息
     * @apiSuccess {String} booking_receive.update_booking_date 实际接待日期
     * @apiSuccess {String} booking_receive.remark 沟通记录
     * @apiSuccess {String} booking_receive.arrive_at 到店时间
     * @apiSuccess {String} booking_receive.created_at 接待时间
     * @apiSuccess {String} booking_receive.state 接待状态 0:失效,1正常
     * @apiSuccess {String} booking_receive.manager 接待人信息
     * @apiSuccess {String} booking_salon_refund 退款信息(特殊退款)
     * @apiSuccess {String} booking_salon_refund.back_to 退款方式1:微信2:支付宝3:银联,4:现金
     * @apiSuccess {String} booking_salon_refund.money 退款金额
     * @apiSuccess {String} booking_salon_refund.remark 退款说明
     * @apiSuccess {String} booking_salon_refund.created_at 退款时间
     * @apiSuccess {String} booking_salon_refund.manager 退款人信息     
     * @apiSuccess {String} order_refund 退款信息(客服申请的退款)
     * @apiSuccess {String} order_refund.add_time 申请退款时间
     * @apiSuccess {String} order_refund.opt_time 退款审批时间
     * @apiSuccess {String} order_refund.manager  审批人信息    
     * 
     * @apiSuccessExample Success-Response:
     *       {
     *         "result": 1,
     *         "token": "",
     *         "data": {
     *           "order": {
     *             "ID": 1,
     *             "ORDER_SN": "3891556931672",
     *             "BOOKING_SN": "sad2323232",
     *             "USER_ID": 1,
     *             "BOOKING_DATE": "2015-12-07",
     *             "UPDATED_BOOKING_DATE": "2015-12-03",
     *             "QUANTITY": 0,
     *             "AMOUNT": "100.00",
     *             "PAYABLE": "100.00",
     *             "BOOKER_NAME": "预约人",
     *             "BOOKER_SEX": "F",
     *             "BOOKER_PHONE": "18611112222",
     *             "STATUS": "RFN",
     *             "INSTRUCTIONS": "1",
     *             "PAIED_TIME": "2015-10-12 00:00:00",
     *             "CONSUME_TIME": "2015-12-03 16:15:32",
     *             "CREATE_TIME": "2015-12-01 17:18:23",
     *             "UPDATE_TIME": "2015-12-03 16:23:01",
     *             "item_amount": 120
     *           },
     *           "order_item": [
     *             {
     *               "ORDER_SN": "3891556931672",
     *               "ITEM_ID": 111,
     *               "ITEM_NAME": "测试时",
     *               "AMOUNT": "0.00",
     *               "PAYABLE": "0.00"
     *             },
     *             {
     *               "ORDER_SN": "3891556931672",
     *               "ITEM_ID": 2,
     *               "ITEM_NAME": "韩式提拉",
     *               "AMOUNT": "0.00",
     *               "PAYABLE": "0.00"
     *             }
     *           ],
     *           "fundflow": [
     *             {
     *               "record_no": "3891556931672",
     *               "pay_type": 2
     *             }
     *           ],
     *           "payment_log": {
     *             "ordersn": "3891556931672",
     *             "tn": "1002360799201508070568495032",
     *             "amount": "1.00"
     *           },
     *           "recommend": {
     *              "recommend_code":"123456789",
     *           },
     *           "beauty_order_item": [
     *             {
     *               "order_sn": "3891556931672",
     *               "item_id": 3,
     *               "item_name": "韩式无痛水光针",
     *               "amount": "850.00",
     *               "to_pay_amount": "120.00",
     *               "norm_id":0,
     *               "norm_name":"",
     *             }
     *           ],
     *           "makeup": {
     *             "id": 1,
     *             "booking_id": 1,
     *             "booking_sn": "fasdfasdf",
     *             "order_sn": "fasdfasdf",
     *             "expert_uid": 18,
     *             "assistant_uid": 17,
     *             "work_at": "2015-12-06",
     *             "remark": "fasdfadfasdfasdfasdfasdfasdfasdf",
     *             "uid": 1,
     *             "created_at": "2015-12-03 00:00:00",
     *             "manager": {
     *               "id": 1,
     *               "name": "超级管理员"
     *             },
     *             "expert": {
     *               "artificer_id": 18,
     *               "name": "ddfdsfs",
     *               "number": "M222"
     *             },
     *             "assistant": {
     *               "artificer_id": 17,
     *               "name": "fsfsffff",
     *               "number": "M982"
     *             }
     *           },
     *           "booking_bill": {
     *             "id": 1,
     *             "booking_id": 1,
     *             "booking_sn": "sad2323232",
     *             "order_sn": "3891556931672",
     *             "created_at": "2015-12-01 00:00:00",
     *             "uid": 1,
     *             "manager": {
     *               "id": 1,
     *               "name": "超级管理员"
     *             }
     *           },
     *           "booking_cash": {
     *             "id": 1,
     *             "booking_id": 1,
     *             "booking_sn": "fasdfasdf",
     *             "order_sn": "fasdfasdfasdf",
     *             "pay_type": 1,
     *             "other_money": "10.00",
     *             "cash_money": "20.00",
     *             "deduction_money": "30.00",
     *             "expert_uid": 17,
     *             "assistant_uid": 18,
     *             "created_at": "2015-12-02 00:00:00",
     *             "uid": 1,
     *             "manager": {
     *               "id": 1,
     *               "name": "超级管理员"
     *             },
     *             "expert": {
     *               "artificer_id": 17,
     *               "name": "fsfsffff",
     *               "number": "M982"
     *             },
     *             "assistant": {
     *               "artificer_id": 18,
     *               "name": "ddfdsfs",
     *               "number": "M222"
     *             }
     *           },
     *           "booking_receive": {
     *             "id": 1,
     *             "booking_id": 1,
     *             "booking_sn": "sad23232321",
     *             "order_sn": "fasdfasdf",
     *             "update_booking_date": "2015-12-03",
     *             "remark": "fasdfasdfasdfafasdf",
     *             "arrive_at": "2015-12-01 00:00:00",
     *             "created_at": "2015-12-10 00:00:00",
     *             "state":1,
     *             "uid": 1,
     *             "manager": {
     *               "id": 1,
     *               "name": "超级管理员"
     *             }
     *           },
     *           "booking_salon_refund": {
     *             "id": 1,
     *             "booking_id": 1,
     *             "uid": 1,
     *             "booking_sn": "2334",
     *             "order_sn": "fasdfadfasdf",
     *             "back_to": 1,
     *             "money": "2323.00",
     *             "remark": "fasdfasdfas",
     *             "created_at": "2015-12-03 00:00:00",
     *             "manager": {
     *               "id": 1,
     *               "name": "超级管理员"
     *             }
     *           },
     *           "order_refund": {
     *             "ordersn": "3891556931672",
     *             "user_id": 720005,
     *             "money": "1.00",
     *             "opt_user_id": 0,
     *             "rereason": "2",
     *             "add_time": 1438926590,
     *             "opt_time": 0,
     *             "status": 2,
     *             "booking_sn": "",
     *             "item_type": "MF",
     *             "manager": null
     *           }
     *         }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function show($id) {
        $detail = OrderRefund::detail($id);
        if (!empty($detail)) {//记录日志
            Event::fire("BeautyRefund.show");
        }
        return $this->success($detail);
    }

    /**
     * @api {post} /beautyrefund/reject 3.退款拒绝
     * @apiName reject
     * @apiGroup beautyrefund
     *
     * @apiSuccess {String} booking_sn 定妆单订单号(多个用','隔开).
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function reject() {
        $param = $this->parameters([
            'booking_sn' => self::T_STRING,
                ], true);
        $booking_sns = array_filter(explode(",", $param['booking_sn']));
//        $booking_sns = array_map("intval", $ids);
        if (count($booking_sns) < 1) {
            throw new ApiException("ids参数不能为空", ERROR::PARAMS_LOST);
        }
        $res = BeautyRefundApi::rejectByBookingSn($booking_sns);
        Event::fire("BeautyRefund.reject");
        return $this->success($res);
    }

    /**
     * @api {post} /beautyrefund/accept 4.退款通过
     * @apiName accept
     * @apiGroup beautyrefund
     *
     * @apiParam {String} booking_sn 定妆单订单sn(多个用','隔开).
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
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function accept() {
        $params = $this->parameters(['booking_sn' => self::T_STRING], true);
        $booking_sns = array_filter(explode(",", $params['booking_sn']));
//        $ids = array_map("intval", $ids);
        if (count($booking_sns) < 1) {
            throw new ApiException("ids 参数不能为空", ERROR::PARAMS_LOST);
        }
        //操作人ID
        if (!empty($this->user)) {
            $opt_user_id = $this->user->id;
        } else {
            $opt_user_id = 0;
        }
        $info = BeautyRefundApi::accpetByBookingSn($booking_sns, $opt_user_id);
        //记录日志
        Event::fire("BeautyRefund.accept");
        return $this->success($info);
    }

    /**
     * 支付宝的回调
     */
    public function beauty_call_back_of_alipay() {
        $input = [
            'GET' => $_GET,
            "POST" => $_POST
        ];
        Utils::log('pay', date("Y-m-d H:i:s") . "\t order " . json_encode($input, JSON_UNESCAPED_UNICODE) . "\t\n", "alipay_callback");

        $is_debug = false;
        if (isset($_GET['is_debug']) && $_GET['is_debug'] == 1) {
            $is_debug = true;
        }
        if (isset($_POST['is_debug']) && $_POST['is_debug'] == 1) {
            $is_debug = true;
        }

        //以下为debug的写法
        //$ret = AlipaySimple::callback(array(D("Refund"),"alipayRefundCallback"),[],false);
        //以下为正式的写法
        $ret = AlipaySimple::callback(function($args) {
                    return BeautyRefundApi::callBackOfAlipay($args);
                }, [], $is_debug);

        if ($ret) {
            Utils::log('pay', date("Y-m-d H:i:s") . "\t callback success \t " . json_encode($input, JSON_UNESCAPED_UNICODE) . "\t\n", "alipay_callback");
            echo "success";
        } else {
            Utils::log('pay', date("Y-m-d H:i:s") . "\t callback fail \t " . json_encode($input, JSON_UNESCAPED_UNICODE) . "\t\n", "alipay_callback");
            echo "fail";
        }
        die();
    }

}
