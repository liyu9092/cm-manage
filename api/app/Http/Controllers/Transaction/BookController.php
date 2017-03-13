<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\TransactionSearchApi;
use App\Mapping;
use Event;
use App\BookingOrder;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\BookingSalonRefund;
use App\BeautyMakeup;
use App\BookingBill;
use App\BookingCash;
use App\BookingReceive;
use App\RecommendCodeUser;
use App\User;
use App\Http\Controllers\Powder\PowderArticlesController;
use App\Order;
use App\Dividend;
use App\Utils;
use App\BeautyRefundApi;
use App\BookingCalendar;
use App\BookingOrderItem;
use App\BeautyOrderItem;
use App\Manager;

class BookController extends Controller
{
    /**
     * @api {get} /book/index 1.预约单列表
     * @apiName index
     * @apiGroup book
     *
     * @apiParam {Number} key  1手机号  2预约号 3推荐码
     * @apiParam {String} keyword  根据key来的关键字
     * @apiParam {String} min_time 预约时间 YYYY-MM-DD
     * @apiParam {String} max_time 预约时间 YYYY-MM-DD
     * @apiParam {String} pay_type 0 全部 2 支付宝 3 微信 10易联
     * @apiParam {String} status 0 全部  NEW - 未支付  PYD - 已支付  (未消费)CSD - 已消费  RFN - 申请退款(退款中)  RFD - 已退款  Y已补妆 退款失败FAILD
     * @apiParam {Number} item_id  项目id
     * @apiParam {Number} beautician_id  专家id
     * @apiParam {Number} assistant_id  助理id
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
     * @apiSuccess {String} order 预约单
     * @apiSuccess {String} order.ID 预约单ID
     * @apiSuccess {String} order.ORDER_SN 订单号
     * @apiSuccess {String} order.BOOKING_SN 预约号
     * @apiSuccess {String} order.BOOKING_DATE 预约日期
     * @apiSuccess {String} order.UPDATED_BOOKING_DATE 修改后的预约日期
     * @apiSuccess {String} order.QUANTITY 数量
     * @apiSuccess {String} order.AMOUNT 订单金额
     * @apiSuccess {String} order.PAYABLE 应付金额
     * @apiSuccess {String} order.BOOKER_NAME 预约人姓名
     * @apiSuccess {String} order.BOOKER_PHONE 预约人电话
     * @apiSuccess {String} order.BOOKER_NAME 预约人姓名
     * @apiSuccess {String} order.STATUS 订单状态 NEW - 未支付,PYD - 已支付,CSD - 已消费,RFN - 申请退款,RFD - 已退款
     * @apiSuccess {String} order.TOUCHED_UP 是否已补妆 Y:是 N(空):否
     * @apiSuccess {String} order.PAIED_TIME 支付时间
     * @apiSuccess {String} order.CONSUME_TIME 消费时间
     * @apiSuccess {String} order.CREATE_TIME 预约时间
     * @apiSuccess {String} order.UPDATE_TIME 最近修改时间
     * @apiSuccess {String} order.pay_type 支付方式:1 网银/2 支付宝/3 微信/4 余额/5 红包/6 优惠券/7 积分/8邀请码兑换 /9 现金券/10 易联支付
     * @apiSuccess {String} order.recommend_code 推荐码
     * @apiSuccess {String} order.beauty_makeup_id 是否已补妆 为空时为否 
     * @apiSuccess {String} order.refund_status 为3时为退款失败
     * @apiSuccess {String} booking_order_item 预约项目信息
     * @apiSuccess {String} booking_order_item.ITEM_NAME 项目名称
     * @apiSuccess {String} beauty_order_item 实际项目信息
     * @apiSuccess {String} beauty_order_item.item_name 项目名称
     * @apiSuccess {String} beauty_order_item.norm_name 项目规格名称
     *
     * @apiSuccessExample Success-Response:
     *       {
     *         "result": 1,
     *         "token": "",
     *         "data": {
     *           "total": 1,
     *           "per_page": 20,
     *           "current_page": 1,
     *           "last_page": 1,
     *           "from": 1,
     *           "to": 1,
     *           "data": [
     *             {
     *               "ID": 1,
     *               "ORDER_SN": "3891556931672",
     *               "BOOKING_SN": "sad2323232",
     *               "USER_ID": 1,
     *               "BOOKING_DATE": "2015-12-07",
     *               "UPDATED_BOOKING_DATE": null,
     *               "QUANTITY": 0,
     *               "AMOUNT": "0.00",
     *               "PAYABLE": "0.00",
     *               "BOOKER_NAME": "",
     *               "BOOKER_PHONE": null,
     *               "STATUS": "RFN",
     *               "TOUCHED_UP": null,
     *               "INSTRUCTIONS": null,
     *               "PAIED_TIME": "2015-10-12 00:00:00",
     *               "CONSUME_TIME": null,
     *               "CREATE_TIME": "2015-12-01 17:18:23",
     *               "UPDATE_TIME": "0000-00-00 00:00:00",
     *               "record_no": "3891556931672",
     *               "pay_type": 2,
     *               "beauty_makeup_id":null,
     *               "refund_status":1,
     *               "recommend_code":null,
     *               "booking_order_item": [
     *                 {
     *                   "ORDER_SN": "3891556931672",
     *                   "ITEM_NAME": "测试时"
     *                 },
     *                 {
     *                   "ORDER_SN": "3891556931672",
     *                   "ITEM_NAME": "韩式提拉"
     *                 }
     *               ]
     *               "beauty_order_item": [
     *                 {
     *                   "order_sn": "3891556931672",
     *                   "item_name": "测试时"
     *                 },
     *                 {
     *                   "order_sn": "3891556931672",
     *                   "item_name": "韩式提拉"
     *                 }
     *               ]
     *             }
     *           ]
     *         }
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
            'pay_type' => self::T_INT,
            'status' => self::T_STRING,
            'item_id' => self::T_INT,
            'beautician_id' => self::T_INT,
            'assistant_id' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING,
       ]);
       
       $items = BookingOrder::search($params);
       return $this->success($items);
    }

    /**
     * @api {get} /book/show/{id} 2.预约单详情
     * @apiName show
     * @apiGroup book
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
     * @apiSuccess {String} order.RECORD_TIME 客服调整时间
     * @apiSuccess {Object} order.manager 客服信息
     * @apiSuccess {Object} order.item_amount 项目总额
     * @apiSuccess {Object} order.gift_num 用户已经赠送的次数
     * @apiSuccess {Object} help_info 代预约信息
     * @apiSuccess {String} help_info.from 渠道
     * @apiSuccess {String} help_info.recommend_code 推荐码
     * @apiSuccess {String} help_info.mobilephone 手机号
     * @apiSuccess {String} order.item_amount 项目总价
     * @apiSuccess {String} order_item 预约项目信息
     * @apiSuccess {String} order_item.ID 项目ID
     * @apiSuccess {String} order_item.ITEM_NAME 项目名称
     * @apiSuccess {String} order_item.AMOUNT 预约金总额
     * @apiSuccess {String} order_item.PAYABLE 应付总额
     * @apiSuccess {String} beauty_order_item 实做项目信息
     * @apiSuccess {String} beauty_order_item.id 项目主ID(用于编辑方案,删除等)
     * @apiSuccess {String} beauty_order_item.item_id 项目ID
     * @apiSuccess {String} beauty_order_item.item_name 项目名称
     * @apiSuccess {String} beauty_order_item.amout 总额
     * @apiSuccess {String} beauty_order_item.to_pay_amount 应付总额
     * @apiSuccess {String} beauty_order_item.norm_id 项目规格ID
     * @apiSuccess {String} beauty_order_item.norm_name 项目规格名称
     * @apiSuccess {String} beauty_order_item.guide_user 项目方案信息 美导信息
     * @apiSuccess {String} beauty_order_item.beautician_user 项目方案信息 美容师信息
     * @apiSuccess {String} beauty_order_item.dean_user 项目方案信息 院长信息
     * @apiSuccess {String} beauty_order_item.assistant_user 项目方案信息 助理信息
     * @apiSuccess {String} beauty_order_item.attr 项目方案信息
     * @apiSuccess {string} beauty_order_item.attr.eyebrow_method 眉毛技法
     * @apiSuccess {string} beauty_order_item.attr.eyebrow_design 眉型设计 
     * @apiSuccess {string} beauty_order_item.attr.color_pro 色号及调配比例
     * @apiSuccess {string} beauty_order_item.attr.needle_type 针的种类
     * @apiSuccess {string} beauty_order_item.attr.note 其他说明
     * @apiSuccess {string} beauty_order_item.attr.operation 施术方案
     * @apiSuccess {string} beauty_order_item.attr.eyeliner_design 眼线设计
     * @apiSuccess {string} beauty_order_item.attr.hair_color 发际线颜色
     * @apiSuccess {string} beauty_order_item.attr.treat_body 治疗部位
     * @apiSuccess {string} beauty_order_item.attr.befor_size 治疗前尺寸
     * @apiSuccess {string} beauty_order_item.attr.after_size 治疗后尺寸
     * @apiSuccess {string} beauty_order_item.attr.treat_num 治疗能力发数
     * @apiSuccess {string} beauty_order_item.attr.treat_time 治疗时间
     * @apiSuccess {string} beauty_order_item.attr.lip_design 唇型设计
     * @apiSuccess {string} beauty_order_item.attr.remark 备注
     * @apiSuccess {string} beauty_order_item.attr.skinConcerns 皮肤状况
     * @apiSuccess {string} beauty_order_item.attr.stoste 原液
     * @apiSuccess {string} beauty_order_item.attr.opt_date 操作时间 YYYY-mm-dd
     * @apiSuccess {string} beauty_order_item.attr.opt_time 操作时间 YYYY-mm-dd HH:ii:ss
     * @apiSuccess {String} beauty_order_item.makeup1 项目补色信息
     * @apiSuccess {String} beauty_order_item.makeup1.guide_user 项目补色信息 美导信息
     * @apiSuccess {String} beauty_order_item.makeup1.beautician_user 项目补色信息 美容师信息
     * @apiSuccess {String} beauty_order_item.makeup1.dean_user 项目补色信息 院长信息
     * @apiSuccess {String} beauty_order_item.makeup1.assistant_user 项目补色信息 助理信息
     * @apiSuccess {string} beauty_order_item.makeup1.eyebrow_method 眉毛技法
     * @apiSuccess {string} beauty_order_item.makeup1.eyebrow_design 眉型设计 
     * @apiSuccess {string} beauty_order_item.makeup1.color_pro 色号及调配比例
     * @apiSuccess {string} beauty_order_item.makeup1.needle_type 针的种类
     * @apiSuccess {string} beauty_order_item.makeup1.note 其他说明
     * @apiSuccess {string} beauty_order_item.makeup1.operation 施术方案
     * @apiSuccess {string} beauty_order_item.makeup1.eyeliner_design 眼线设计
     * @apiSuccess {string} beauty_order_item.makeup1.hair_color 发际线颜色
     * @apiSuccess {string} beauty_order_item.makeup1.treat_body 治疗部位
     * @apiSuccess {string} beauty_order_item.makeup1.befor_size 治疗前尺寸
     * @apiSuccess {string} beauty_order_item.makeup1.after_size 治疗后尺寸
     * @apiSuccess {string} beauty_order_item.makeup1.treat_num 治疗能力发数
     * @apiSuccess {string} beauty_order_item.makeup1.treat_time 治疗时间
     * @apiSuccess {string} beauty_order_item.makeup1.lip_design 唇型设计
     * @apiSuccess {string} beauty_order_item.makeup1.remark 备注
     * @apiSuccess {string} beauty_order_item.makeup1.skinConcerns 皮肤状况
     * @apiSuccess {string} beauty_order_item.makeup1.stoste 原液
     * @apiSuccess {string} beauty_order_item.makeup1.opt_date 操作时间 YYYY-mm-dd
     * @apiSuccess {string} beauty_order_item.makeup1.opt_time 操作时间 YYYY-mm-dd HH:ii:ss
     * @apiSuccess {String} beauty_order_item.makeup2 项目二次补色信息
     * @apiSuccess {String} beauty_order_item.makeup2.guide_user 项目二次补色信息 美导信息
     * @apiSuccess {String} beauty_order_item.makeup2.beautician_user 项目二次补色信息 美容师信息
     * @apiSuccess {String} beauty_order_item.makeup2.dean_user 项目二次补色信息 院长信息
     * @apiSuccess {String} beauty_order_item.makeup2.assistant_user 项目二次补色信息 助理信息
     * @apiSuccess {string} beauty_order_item.makeup2.eyebrow_method 眉毛技法
     * @apiSuccess {string} beauty_order_item.makeup2.eyebrow_design 眉型设计 
     * @apiSuccess {string} beauty_order_item.makeup2.color_pro 色号及调配比例
     * @apiSuccess {string} beauty_order_item.makeup2.needle_type 针的种类
     * @apiSuccess {string} beauty_order_item.makeup2.note 其他说明
     * @apiSuccess {string} beauty_order_item.makeup2.operation 施术方案
     * @apiSuccess {string} beauty_order_item.makeup2.eyeliner_design 眼线设计
     * @apiSuccess {string} beauty_order_item.makeup2.hair_color 发际线颜色
     * @apiSuccess {string} beauty_order_item.makeup2.treat_body 治疗部位
     * @apiSuccess {string} beauty_order_item.makeup2.befor_size 治疗前尺寸
     * @apiSuccess {string} beauty_order_item.makeup2.after_size 治疗后尺寸
     * @apiSuccess {string} beauty_order_item.makeup2.treat_num 治疗能力发数
     * @apiSuccess {string} beauty_order_item.makeup2.treat_time 治疗时间
     * @apiSuccess {string} beauty_order_item.makeup2.lip_design 唇型设计
     * @apiSuccess {string} beauty_order_item.makeup2.remark 备注
     * @apiSuccess {string} beauty_order_item.makeup2.skinConcerns 皮肤状况 
     * @apiSuccess {string} beauty_order_item.makeup2.stoste 原液
     * @apiSuccess {string} beauty_order_item.makeup2.opt_date 操作时间 YYYY-mm-dd
     * @apiSuccess {string} beauty_order_item.makeup2.opt_time 操作时间 YYYY-mm-dd HH:ii:ss
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
     *             "QUANTITY": 2,
     *             "AMOUNT": "100.00",
     *             "PAYABLE": "100.00",
     *             "BOOKER_NAME": "预约人",
     *             "BOOKER_SEX": "F",
     *             "BOOKER_PHONE": "18611112222",
     *             "RECOMMENDER": "llllllxxxxx",
     *             "STATUS": "CSD",
     *             "INSTRUCTIONS": "1",
     *             "PAIED_TIME": "2014-10-12 00:00:00",
     *             "CONSUME_TIME": "2015-12-25 10:16:31",
     *             "CREATE_TIME": "2015-12-01 17:18:23",
     *             "UPDATE_TIME": "2015-12-05 14:20:08",
     *             "BOOKING_DESC": "DEF",
     *             "COME_SHOP": "COME",
     *             "SUBSTITUTOR": 0,
     *             "MANAGER_UID": 0,
     *             "CUSTOMER_SERVICE_ID": 300,
     *             "RECORD_TIME": null,
     *             "CONSUMED": 0,
     *             "manager": null,
     *             "item_amount": 4465,
     *             "gift_num": 0
     *           },
     *           "help_info": null,
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
     *           "recommend": null,
     *           "beauty_order_item": [
     *             {
     *               "id": 10,
     *               "order_sn": "3891556931672",
     *               "beauty_id": 1,
     *               "item_id": 2,
     *               "item_name": "韩式提拉",
     *               "norm_id": 0,
     *               "norm_name": "",
     *               "quantity": 1,
     *               "price": "0.00",
     *               "discount_price": "0.00",
     *               "amount": "0.00",
     *               "to_pay_amount": "0.00",
     *               "attr": {
     *                 "eyebrow_method": [
     *                   1,
     *                   2,
     *                   3
     *                 ]
     *               },
     *               "makeup1": {
     *                 "color_pro": [
     *                   1,
     *                   2
     *                 ],
     *                 "needle_type": "dsss",
     *                 "opt_date": "016-05-05",
     *                 "guideId": 21,
     *                 "beauticianId": 18,
     *                 "guide_user": {
     *                   "artificer_id": 21,
     *                   "name": "ffffffff",
     *                   "number": "1162"
     *                 },
     *                 "beautician_user": {
     *                   "artificer_id": 18,
     *                   "name": "ddfdsfs",
     *                   "number": "M222"
     *                 }
     *               },
     *               "makeup2": {
     *                 "color_pro": [
     *                   1,
     *                   2
     *                 ],
     *                 "needle_type": "dsss",
     *                 "opt_date": "016-05-05",
     *                 "guideId": 21,
     *                 "beauticianId": 18,
     *                 "guide_user": {
     *                   "artificer_id": 21,
     *                   "name": "ffffffff",
     *                   "number": "1162"
     *                 },
     *                 "beautician_user": {
     *                   "artificer_id": 18,
     *                   "name": "ddfdsfs",
     *                   "number": "M222"
     *                 }
     *               },
     *               "guideId": 17,
     *               "beauticianId": 18,
     *               "deanId": 21,
     *               "assistantId": 23,
     *               "created_at": "2015-12-05 14:15:05",
     *               "guide_user": {
     *                 "artificer_id": 17,
     *                 "name": "fsfsffff",
     *                 "number": "M982"
     *               },
     *               "beautician_user": {
     *                 "artificer_id": 18,
     *                 "name": "ddfdsfs",
     *                 "number": "M222"
     *               },
     *               "dean_user": {
     *                 "artificer_id": 21,
     *                 "name": "ffffffff",
     *                 "number": "1162"
     *               },
     *               "assistant_user": {
     *                 "artificer_id": 23,
     *                 "name": "dddd",
     *                 "number": ""
     *               }
     *             },
     *             {
     *               "id": 11,
     *               "order_sn": "3891556931672",
     *               "beauty_id": 1,
     *               "item_id": 3,
     *               "item_name": "韩式无痛水光针",
     *               "norm_id": 0,
     *               "norm_name": "",
     *               "quantity": 1,
     *               "price": "850.00",
     *               "discount_price": "120.00",
     *               "amount": "850.00",
     *               "to_pay_amount": "120.00",
     *               "attr": null,
     *               "makeup1": null,
     *               "makeup2": null,
     *               "guideId": 17,
     *               "beauticianId": 18,
     *               "deanId": 21,
     *               "assistantId": 23,
     *               "created_at": "2015-12-05 14:15:05",
     *               "guide_user": {
     *                 "artificer_id": 17,
     *                 "name": "fsfsffff",
     *                 "number": "M982"
     *               },
     *               "beautician_user": {
     *                 "artificer_id": 18,
     *                 "name": "ddfdsfs",
     *                 "number": "M222"
     *               },
     *               "dean_user": {
     *                 "artificer_id": 21,
     *                 "name": "ffffffff",
     *                 "number": "1162"
     *               },
     *               "assistant_user": {
     *                 "artificer_id": 23,
     *                 "name": "dddd",
     *                 "number": ""
     *               }
     *             },
     *             {
     *               "id": 12,
     *               "order_sn": "3891556931672",
     *               "beauty_id": 1,
     *               "item_id": 4,
     *               "item_name": "韩式纤体",
     *               "norm_id": 0,
     *               "norm_name": "",
     *               "quantity": 1,
     *               "price": "4354.00",
     *               "discount_price": "4345.00",
     *               "amount": "4354.00",
     *               "to_pay_amount": "4345.00",
     *               "attr": null,
     *               "makeup1": null,
     *               "makeup2": null,
     *               "guideId": 17,
     *               "beauticianId": 18,
     *               "deanId": 21,
     *               "assistantId": 23,
     *               "created_at": "2015-12-05 14:15:05",
     *               "guide_user": {
     *                 "artificer_id": 17,
     *                 "name": "fsfsffff",
     *                 "number": "M982"
     *               },
     *               "beautician_user": {
     *                 "artificer_id": 18,
     *                 "name": "ddfdsfs",
     *                 "number": "M222"
     *               },
     *               "dean_user": {
     *                 "artificer_id": 21,
     *                 "name": "ffffffff",
     *                 "number": "1162"
     *               },
     *               "assistant_user": {
     *                 "artificer_id": 23,
     *                 "name": "dddd",
     *                 "number": ""
     *               }
     *             }
     *           ],
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
     *             "id": 4,
     *             "booking_id": 1,
     *             "booking_sn": "3891556931672",
     *             "order_sn": "3891556931672",
     *             "pay_type": 2,
     *             "other_money": "4365.00",
     *             "cash_money": "0.00",
     *             "deduction_money": "0.00",
     *             "expert_uid": 26,
     *             "assistant_uid": 17,
     *             "created_at": "2015-12-05 14:15:24",
     *             "uid": 1,
     *             "manager": {
     *               "id": 1,
     *               "name": "超级管理员"
     *             }
     *           },
     *           "booking_receive": {
     *             "id": 1,
     *             "booking_id": 1,
     *             "booking_sn": "sad23232321",
     *             "order_sn": "3891556931672",
     *             "update_booking_date": "2015-12-03",
     *             "remark": "不好不收钱",
     *             "arrive_at": "2015-12-01 00:00:00",
     *             "name": "dsssdd",
     *             "birthday": "1956-02-03",
     *             "wx_code": "dsddda123123123",
     *             "age": 20,
     *             "created_at": "2015-12-10 00:00:00",
     *             "uid": 1,
     *             "guideId": 21,
     *             "record_time": "2016-02-03 12:12:12",
     *             "ext": {
     *               "pro_intro": [
     *                 1,
     *                 2
     *               ],
     *               "conc_intro": [
     *                 1,
     *                 2
     *               ],
     *               "flow_intro": [
     *                 1,
     *                 2
     *               ],
     *               "ques_intro": [
     *                 1,
     *                 2
     *               ]
     *             },
     *             "state": 1,
     *             "manager": {
     *               "id": 1,
     *               "name": "超级管理员"
     *             },
     *             "guider": {
     *               "artificer_id": 21,
     *               "name": "ffffffff",
     *               "number": "1162"
     *             }
     *           },
     *           "booking_salon_refund": null,
     *           "order_refund": {
     *             "ordersn": "3891556931672",
     *             "user_id": 720005,
     *             "money": "1.00",
     *             "opt_user_id": 0,
     *             "rereason": "买错了／买多了,",
     *             "add_time": "2015-08-08 14:09:41",
     *             "opt_time": "",
     *             "status": 1,
     *             "booking_sn": "",
     *             "item_type": "MF",
     *             "other_rereason": "",
     *             "manager": null,
     *             "refund_desc": "买错了／买多了,",
     *             "complete_time": ""
     *           }
     *         }
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
        $item = BookingOrder::detail($id);
        return $this->success($item);
    }
    
    /**
     * @api {get} /book/create 3.预约单--新增代客预约单
     * @apiName create
     * @apiGroup book
     *
     * @apiParam {string}  phone 手机
     * @apiParam {string}  name 姓名
     * @apiParam {string}  sex 1.男 2女
     * @apiParam {string}  item_ids 预约项目(多个用','隔开)
     * @apiParam {string}  date 预约日期 YYYY-MM-DD
     * @apiParam {string}  recomment_code 推荐码
     *
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function create()
    {
        $params = $this->parameters([
            'phone'=>self::T_STRING,
            'name'=>self::T_STRING,
            'sex'=>self::T_INT,
            'item_ids'=>self::T_STRING,
            'date'=>self::T_STRING,
            'recomment_code'=>self::T_STRING,
        ]);        
        $params['item_ids'] = explode(",", $params['item_ids']);
        //$params['item_ids'] = isset($this->param['item_ids'])?$this->param['item_ids']:null;
      
        if( is_array($params['item_ids'])&& count($params['item_ids'])<1)
        {
            throw new ApiException("预约项目不能为空!",ERROR::PARAMETER_ERROR);
        }
        //for test
        //$params['manager_uid'] = 1;
        $params['manager_uid'] = $this->user->id;
        $book = BookingOrder::book($params);
        
        Event::fire('booking.create',"预约号".$book['BOOKING_SN']." "."订单号".$book['ORDER_SN']);
        return $this->success($book);
    }
    
    /**
     * @api {get} /book/receive/{id} 4.预约单--接待
     * @apiName receive
     * @apiGroup book
     * 
     * @apiParam {String} record_time 到店时间   YYYY-MM-DD
     * @apiParam {Number} guideId 美导id
     * @apiParam {String} remark 沟通记录
     * @apiParam {String} ext 额外信息  (json) {pro_intro:[1,2],conc_intro:[1,2],flow_intro:[1,2],ques_intro:[1,2],...} 
     * @apiParam {String} ext.xx pro_intro(中心项目介绍): [1:定妆,2:优立塑,3无创水光] ,conc_intro(设计理念介绍):[1:根据脸型、肤色、风格设计 2:和谐的设计原则],flow_intro(操作流程介绍):[1:定妆流程,2:优立塑流程,3:无创水光流程],,ques_intro(高频问题介绍):[1:设计满意再付款,2:疼痛,3:时间]
     * 
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function receive($id)
    {
        $params = $this->parameters([
            'guideId' => self::T_INT,
            'record_time' => self::T_STRING,
            'remark' => self::T_STRING,
            'ext' => self::T_STRING,
       ]);
       if(isset($params['record_time']) && !empty($params['record_time']))
       {
            $params['record_time'] = date("Y-m-d H:i:s",strtotime($params['record_time']));
       }
       $params['uid'] = $this->user->id;
       $ext = @json_decode($params['ext'],true);
       if(! is_array($ext))
       {
           throw new ApiException("参数不正确", ERROR::PARAMETER_ERROR);
       }
       $params['ext'] = json_encode($ext,JSON_UNESCAPED_UNICODE);
       $book = BookingReceive::receive_base($id,$params);
       Event::fire('booking.receive',"预约号".$book['BOOKING_SN']." "."订单号".$book['ORDER_SN']);
       return $this->success(['id'=>$id]);
    }
    
    /**
     * @api {get} /book/change/{id} 5.预约单--接待(修改项目信息等)
     * @apiName change
     * @apiGroup book
     *
     * @apiParam {String} birthday 出生日期   YYYY-MM-DD
     * @apiParam {Number} age 年龄
     * @apiParam {String} wx_code 微信号
     * @apiParam {String} name 名字
     *
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function change($id)
    {
        $params = $this->parameters([
            'birthday' => self::T_STRING,
            'age' => self::T_INT,
            'wx_code' => self::T_STRING,
            'name' => self::T_STRING,
        ]);
        $params['uid'] = $this->user->id;       
        $book = BookingReceive::receive_base($id,$params);
        Event::fire('booking.change',"预约号".$book['BOOKING_SN']." "."订单号".$book['ORDER_SN']);
        return $this->success(['id'=>$id]);
    }
    
    /**
     * @api {get} /book/add_item/{id} 6.预约单--新增项目
     * @apiName add_item
     * @apiGroup book
     *
     * @apiParam {array} item_ids 预约项目修改 itemId[_normId]
     *
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function addItem($id)
    {
        $params = $this->parameters([
            'item_ids'=>self::T_STRING,
        ]);
        $params['item_ids'] = explode(",", $params['item_ids']);
        $item_ids =  $params['item_ids'];
        if(!is_array($item_ids))
        {
            throw new ApiException('参数格式不正确', ERROR::PARAMETER_ERROR);
        }
        $base = BookingOrder::where('id',$id)->first();
        if(empty($base))
        {
            throw new ApiException('操作的定妆单已经不存在', ERROR::PARAMETER_ERROR);
        }
        $uid = $this->user->id;
        $use_bargain_price = empty($base->RECOMMENDER);
        BookingReceive::addBeautyItems($id,$uid,$item_ids,$use_bargain_price);
        return $this->success($id);
    }
    
    /**
     * @api {get} /book/remove_item/{id} 7.预约单--删除项目
     * @apiName remove_item
     * @apiGroup book
     * 
     * @apiParam {String} ids 项目的主id (",分隔")
     *
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function removeItem($id)
    {
        $params = $this->parameters([
            'ids'=>self::T_STRING,
        ]);
        $ids = explode(",", $params['ids']);        
        if(!is_array($ids))
        {
            throw new ApiException('参数格式不正确', ERROR::PARAMETER_ERROR);
        }
        $ids =array_map('intval', $ids);
        $base = BookingOrder::where('id',$id)->first();
        if(empty($base))
        {
            throw new ApiException('操作的定妆单已经不存在', ERROR::PARAMETER_ERROR);
        }
        BeautyOrderItem::whereIn('id',$ids)->where('order_sn',$base->ORDER_SN)->delete();
        return $this->success($id);
    }
    
    /**
     * @api {get} /book/cash/{id} 6.预约单--收银
     * @apiName cash
     * @apiGroup book
     *
     * @apiParam {Number} pay_type 支付方式1:微信2:支付宝3:POS机,4:现金,5:微信+现金6:支付宝+现金7:POS机+现金
     * @apiParam {Number} other_money 其他方式的支付金额
     * @apiParam {Number} cash_money 现金金额
     * @apiParam {Number} deduction_money 抵扣金额
     * 
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function cash($id)
    {
        $params = $this->parameters([
            'pay_type' => self::T_INT,
            'other_money' => self::T_FLOAT,
            'cash_money' => self::T_FLOAT,
            'deduction_money' => self::T_FLOAT,
        ]);
        $params['uid'] = $this->user->id;
        $origin_cash = BookingCash::where('booking_id',$id)->first(); 
        $book = BookingCash::cash($id,$params);
        $custom_uid = $book['USER_ID'];
        $first_book = BookingOrder::where("USER_ID",$custom_uid)->useWritePdo()->whereIn("STATUS",["CSD","RFD-OFL"])->orderBy("CONSUME_TIME","ASC")->first();
        $is_first = false;
        if(! empty($first_book) && $first_book->USER_ID == $custom_uid)
        {
            $is_first = true;
        }
        $first_time = BookingOrder::where("USER_ID",$custom_uid)->orderBy("CREATE_TIME","ASC")->first();

        self::make_recommend($custom_uid,$book['RECOMMENDER']);
        if (empty($origin_cash)) {
            try {
                
                self::givePresent($custom_uid, $book['ORDER_SN'], $is_first, $first_time->CREATE_TIME);
            } 

            catch (\Exception $e) {
                Utils::log("present", date("Y-m-d H:i:s") . $e->getMessage() . "\n");
            }
        }
        Event::fire('booking.cash',"预约号".$book['BOOKING_SN']." "."订单号".$book['ORDER_SN']);
        return $this->success(['id'=>$id]);
    }
    
    /**
     * @api {get} /book/bill/{id} 7.预约单--开发票
     * @apiName bill
     * @apiGroup book
     *
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function bill($id)
    {      
        $base = BookingOrder::where("ID",$id)->first();
        if(empty($base))
        {
            throw new ApiException("定妆单[{$id}]不存在或者已经被删除", ERROR::ORDER_NOT_EXIST);
        }
        $state = $base->STATUS;
        if($state != "CSD")
        {
            throw new ApiException("定妆单[{$id}]状态不正确", ERROR::ORDER_STATUS_WRONG);
        }       
        $bill = BookingBill::where('booking_id',$id)->first();
        if(!empty($bill))
        {
            throw new ApiException("定妆单[{$id}]已经开过发票", ERROR::ORDER_STATUS_WRONG);
        }
        BookingBill::create([
        'booking_id'=>$id,
        'uid'=>$this->user->id,
        'booking_sn'=>$base->BOOKING_SN,
        'order_sn'=>$base->ORDER_SN,
        'created_at'=>date("Y-m-d H:i:s"),
        ]);
        Event::fire('booking.bill',"预约号".$base->BOOKING_SN." "."订单号".$base->ORDER_SN);
        return $this->success(['id'=>$id]);
    }
    
    /**
     * @api {get} /book/relatively/{id} 8.预约单--补色
     * @apiName relatively
     * @apiGroup book
     * 
     * @apiParam {number} base_id  项目的主id
     * @apiParam {string} attr 属性  格式{attr1:val1,attr2:{attr3:val3,attr4:val4},...}
     * @apiParam {string} attr.guideId 属性  美导id
     * @apiParam {string} attr.beauticianId 属性 美容师id
     * @apiParam {string} attr.deanId 属性  院长id
     * @apiParam {string} attr.assistantId 属性  助理id
     * @apiParam {string} attr.eyebrow_method 眉毛技法  [1:Combo,2:ambo,3:machine,4:그라데이션]
     * @apiParam {string} attr.eyebrow_design 眉型设计 [1:自然眉 ,2:一字眉]
     * @apiParam {string} attr.color_pro 色号及调配比例
     * @apiParam {string} attr.needle_type 针的种类
     * @apiParam {string} attr.note 其他说明
     * @apiParam {string} attr.operation 施术方案
     * @apiParam {string} attr.eyeliner_design 眼线设计 [1:自然型    ,2:尾部加长型]
     * @apiParam {string} attr.hair_color 发际线颜色 [1:黑色 ,2:咖啡色]
     * @apiParam {string} attr.treat_body 治疗部位 [1:腰部   ,2:腹部  ,3:上腹部   ,4:大腿   ,5:小腿   ,6:背部 ,7:臀部  ]
     * @apiParam {string} attr.befor_size 治疗前尺寸
     * @apiParam {string} attr.after_size 治疗后尺寸
     * @apiParam {string} attr.treat_num 治疗能力发数
     * @apiParam {string} attr.treat_time 治疗时间
     * @apiParam {string} attr.lip_design 唇型设计 [1:红色 ,2:粉色 ,3:橘色]
     * @apiParam {string} attr.remark 备注
     * @apiParam {string} attr.skinConcerns 皮肤状况 [1 腰毛粗大 2皮肤暗沉 3 敏感脆弱 4干燥粗糙 5 细小皱纹 6痤疮痕迹 7皮肤松弛 8其他]
     * @apiParam {string} attr.stoste 原液 ["type"="","nums"=>""] type:[1:N5 2:N10 3:MITOCELL HOME 4:PRODERM ]
     * @apiParam {string} attr.opt_date 操作时间 YYYY-mm-dd
     * @apiParam {string} attr.opt_time 操作时间 YYYY-mm-dd HH:ii:ss
     * 
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function relatively($id)
    {
        $params = $this->parameters([
            'base_id' => self::T_INT,
            'attr' => self::T_STRING,
        ]);
        $makeup = @json_decode($params['attr'],true);
        if(!is_array($makeup))
        {
            throw new ApiException("补色参数不正确", ERROR::PARAMETER_ERROR);
        }
        $base = BookingOrder::where("ID",$id)->first();
        if(empty($base))
        {
            throw new ApiException("定妆单[{$id}]不存在或者已经被删除", ERROR::ORDER_NOT_EXIST);
        }
        
        $item = BeautyOrderItem::where('id',$params['base_id'])->first();
        if(empty($item))
        {
            throw new ApiException("修改的项目已经不存在或者被删除", ERROR::ORDER_NOT_EXIST);
        }
        
        $origin = BeautyMakeup::where('booking_id',$id)->first();
        if(empty($origin))
        {
           BeautyMakeup::create([
            'booking_id'=>$id,
            'uid'=>$this->user->id,
            'booking_sn'=>$base->BOOKING_SN,
            'order_sn'=>$base->ORDER_SN,
            'work_at'=>date("Y-m-d"),
            'remark'=>"",
            'expert_uid'=>isset($makeup['deanId'])?$makeup['deanId']:0,
            'assistant_uid'=>isset($makeup['assistantId'])?$makeup['assistantId']:0,
            'created_at'=>date("Y-m-d H:i:s"),
            ]);
        }
        $item->update(['makeup1'=>json_encode($makeup,JSON_UNESCAPED_UNICODE)]);
        Event::fire('booking.relatively',"预约号".$base->BOOKING_SN." "."订单号".$base->ORDER_SN);
        return $this->success(['id'=>$id]);
    }
    
    /**
     * @api {get} /book/relatively2/{id} 8.预约单--补色(二次补色)
     * @apiName relatively2
     * @apiGroup book
     *
     * @apiParam {number} base_id  项目的主id
     * @apiParam {string} attr 属性  格式{attr1:val1,attr2:{attr3:val3,attr4:val4},...}
     * @apiParam {string} attr.guideId 属性  美导id
     * @apiParam {string} attr.beauticianId 属性 美容师id
     * @apiParam {string} attr.deanId 属性  院长id
     * @apiParam {string} attr.eyebrow_method 眉毛技法  [1:Combo,2:ambo,3:machine,4:그라데이션]
     * @apiParam {string} attr.eyebrow_design 眉型设计 [1:自然眉 ,2:一字眉]
     * @apiParam {string} attr.color_pro 色号及调配比例
     * @apiParam {string} attr.needle_type 针的种类
     * @apiParam {string} attr.note 其他说明
     * @apiParam {string} attr.operation 施术方案
     * @apiParam {string} attr.eyeliner_design 眼线设计 [1:自然型    ,2:尾部加长型]
     * @apiParam {string} attr.hair_color 发际线颜色 [1:黑色 ,2:咖啡色]
     * @apiParam {string} attr.treat_body 治疗部位 [1:腰部   ,2:腹部  ,3:上腹部   ,4:大腿   ,5:小腿   ,6:背部 ,7:臀部  ]
     * @apiParam {string} attr.befor_size 治疗前尺寸
     * @apiParam {string} attr.after_size 治疗后尺寸
     * @apiParam {string} attr.treat_num 治疗能力发数
     * @apiParam {string} attr.treat_time 治疗时间
     * @apiParam {string} attr.lip_design 唇型设计 [1:红色 ,2:粉色 ,3:橘色]
     * @apiParam {string} attr.remark 备注
     * @apiParam {string} attr.skinConcerns 皮肤状况 [1 腰毛粗大 2皮肤暗沉 3 敏感脆弱 4干燥粗糙 5 细小皱纹 6痤疮痕迹 7皮肤松弛 8其他]
     * @apiParam {string} attr.stoste 原液 ["type"="","nums"=>""] type:[1:N5 2:N10 3:MITOCELL HOME 4:PRODERM ]
     * @apiParam {string} attr.opt_date 操作时间 YYYY-mm-dd
     * @apiParam {string} attr.opt_time 操作时间 YYYY-mm-dd HH:ii:ss
     *
     * @apiSuccessExample Success-Response:
     *       {
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function relatively2($id)
    {
        $params = $this->parameters([
            'base_id' => self::T_INT,
            'attr' => self::T_STRING,
        ]);
        $makeup = json_decode($params['attr'],true);
        if(!is_array($makeup))
        {
            throw new ApiException("补色参数不正确", ERROR::PARAMETER_ERROR);
        }
        $base = BookingOrder::where("ID",$id)->first();
        if(empty($base))
        {
            throw new ApiException("定妆单[{$id}]不存在或者已经被删除", ERROR::ORDER_NOT_EXIST);
        }

        $item = BeautyOrderItem::where('id',$params['base_id'])->first();
        if(empty($item))
        {
            throw new ApiException("修改的项目已经不存在或者被删除", ERROR::ORDER_NOT_EXIST);
        }
        if(empty($item->makeup1))
        {
            throw new ApiException("请先完成第一次补色", ERROR::ORDER_NOT_EXIST);
        }
        $item->update(['makeup2'=>json_encode($makeup,JSON_UNESCAPED_UNICODE)]);
        Event::fire('booking.relatively2',"预约号".$base->BOOKING_SN." "."订单号".$base->ORDER_SN);
        return $this->success(['id'=>$id]);
    }
    
    /**
     * @api {get} /book/refund/{id} 9.预约单--退款
     * @apiName refund
     * @apiGroup book
     * 
     * @apiParam {Number} back_to 退款方式1:微信2:支付宝3:银联,4:现金
     * @apiParam {Number} money 金额
     * @apiParam {String} remark 说明
     *
     * @apiSuccess {Object} alipay 支付宝
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
    public function refund($id)
    {
        $params = $this->parameters([
            'remark' => self::T_STRING,
            'money' => self::T_FLOAT,
            'back_to' => self::T_INT,
        ]);
        $base = BookingOrder::where("ID",$id)->first();
        if(empty($base))
        {
            throw new ApiException("定妆单[{$id}]不存在或者已经被删除", ERROR::ORDER_NOT_EXIST);
        }
        $state = $base->STATUS;
        if($state != "CSD")
        {
            throw new ApiException("定妆单[{$id}]状态不正确,不允许退款", ERROR::ORDER_STATUS_WRONG);
        }
        $refund = BookingSalonRefund::where('id',$id)->first();
        if(!empty($refund))
        {
            throw new ApiException("定妆单[{$id}]已经退款,不允许重复退款", ERROR::ORDER_STATUS_WRONG);
        }
        
        $cash = BookingCash::where('booking_id',$id)->first();
        
        if(empty($cash))
        {
            throw new ApiException("定妆单[{$id}]状态不正确,找不到收银信息,不允许退款", ERROR::ORDER_STATUS_WRONG);
        }
        
        $max_return  = bcadd($cash->other_money, $cash->cash_money,2);
        if($params['money'] > $max_return)
        {
            throw new ApiException("定妆单[{$id}] 最大允许退款 {$max_return}. 退款金额错误，请查询", ERROR::PARAMETER_ERROR);
        }        
        
        $time = time();
        $datetime = date("Y-m-d H:i:s",$time);
        $attr = [
            'booking_id'=>$id,
            'uid'=>$this->user->id,
            'booking_sn'=>$base->BOOKING_SN,
            'order_sn'=>$base->ORDER_SN,
            'back_to'=>$params['back_to'],
            'money'=>$params['money'],
            'remark'=>$params['remark'],
            'created_at'=>$datetime,
        ];
        BookingSalonRefund::create($attr);
        $res = null;
        if($base->MANAGER_UID == 0)
        {
            try{
                BookingOrder::where('ID',$id)->update(['STATUS'=>'RFN']);
                $res = BeautyRefundApi::accpetByBookingSn([$base->BOOKING_SN], $this->user->id);
            }
            catch (\Exception $e)
            {
                Utils::log("pay", date("Y-m-d H:i:s")." ".$e->getMessage()."\n","offline_refund");
            }
        }
        else 
        {   
            $old_booking_date = empty($base->UPDATED_BOOKING_DATE)?$base->BOOKING_DATE:$base->UPDATED_BOOKING_DATE;
            $item_ids = BookingReceive::getBookingOrderItemIds($base->ORDER_SN); 
            BookingCalendar::change_items_date($item_ids, $old_booking_date,BookingCalendar::CHANGE_TYPE_OF_DEL);
            BookingOrder::where('ID',$id)->update(['STATUS'=>'RFD-OFL','UPDATE_TIME'=>$datetime]); 
        }  

        if(!empty($base->RECOMMENDER))
        {
            RecommendCodeUser::where('user_id',$base->USER_ID)->where('recommend_code',$base->RECOMMENDER)->delete();
        }
         
        Event::fire('booking.refund',"预约号".$base->BOOKING_SN." "."订单号".$base->ORDER_SN);
        
        return $this->success($res);
    }
    
    /**
     * @api {get} /book/refund_booking/{id} 9.预约单--退定金
     * @apiName refund_booking
     * @apiGroup book
     *
     * @apiSuccess {Object} alipay 支付宝
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
    public function refund_booking($id)
    {
        $base = BookingOrder::where("ID",$id)->first();
        if(empty($base))
        {
            throw new ApiException("定妆单[{$id}]不存在或者已经被删除", ERROR::ORDER_NOT_EXIST);
        }
        $state = $base->STATUS;
        if($state != "CSD")
        {
            throw new ApiException("定妆单[{$id}]状态不正确,不允许退款", ERROR::ORDER_STATUS_WRONG);
        }
        $refund = BookingSalonRefund::where('id',$id)->first();
        if(!empty($refund))
        {
            throw new ApiException("定妆单[{$id}]已经退款,不允许重复退款", ERROR::ORDER_STATUS_WRONG);
        }
   
        $res = null;
        if($base->MANAGER_UID == 0)
        {
            try{
                BookingOrder::where('ID',$id)->update(['STATUS'=>'RFN']);
                $res = BeautyRefundApi::accpetByBookingSn([$base->BOOKING_SN], $this->user->id);
            }
            catch (\Exception $e)
            {
                Utils::log("pay", date("Y-m-d H:i:s")." ".$e->getMessage()."\n","offline_refund");
            }
        }
         
        Event::fire('booking.refund',"退定金,预约号".$base->BOOKING_SN." "."订单号".$base->ORDER_SN);
    
        return $this->success($res);
    }
    
    /**
     * @api {get} /book/update/{id} 10.预约单项目编辑
     * @apiName update
     * @apiGroup book
     * 
     * @apiParam {string} guideId 属性  美导id
     * @apiParam {string} beauticianId 属性 美容师id
     * @apiParam {string} deanId 属性  院长id
     * @apiParam {string} assistantId 属性  助理id
     * @apiParam {string} attr 属性  格式{attr1:val1,attr2:{attr3:val3,attr4:val4},...}
     * @apiParam {string} attr.eyebrow_method 眉毛技法  [1:Combo,2:ambo,3:machine,4:그라데이션]
     * @apiParam {string} attr.eyebrow_design 眉型设计 [1:自然眉 ,2:一字眉]
     * @apiParam {string} attr.color_pro 色号及调配比例
     * @apiParam {string} attr.needle_type 针的种类
     * @apiParam {string} attr.note 其他说明
     * @apiParam {string} attr.operation 施术方案
     * @apiParam {string} attr.eyeliner_design 眼线设计 [1:自然型    ,2:尾部加长型]
     * @apiParam {string} attr.hair_color 发际线颜色 [1:黑色 ,2:咖啡色]
     * @apiParam {string} attr.treat_body 治疗部位 [1:腰部   ,2:腹部  ,3:上腹部   ,4:大腿   ,5:小腿   ,6:背部 ,7:臀部  ]
     * @apiParam {string} attr.befor_size 治疗前尺寸
     * @apiParam {string} attr.after_size 治疗后尺寸
     * @apiParam {string} attr.treat_num 治疗能力发数
     * @apiParam {string} attr.treat_time 治疗时间
     * @apiParam {string} attr.lip_design 唇型设计 [1:红色 ,2:粉色 ,3:橘色]
     * @apiParam {string} attr.remark 备注
     * @apiParam {string} attr.skinConcerns 皮肤状况 [1 腰毛粗大 2皮肤暗沉 3 敏感脆弱 4干燥粗糙 5 细小皱纹 6痤疮痕迹 7皮肤松弛 8其他]
     * @apiParam {string} attr.stoste 原液 ["type"="","nums"=>""] type:[1:N5 2:N10 3:MITOCELL HOME 4:PRODERM ]
     * @apiParam {string} attr.opt_date 操作时间 YYYY-mm-dd
     * @apiParam {string} attr.opt_time 操作时间 YYYY-mm-dd HH:ii:ss
     * 
     * @apiSuccessExample Success-Response:
     *     {
     *        "result": 1,
     *        "token": "",
     *        "data": {
     *        }
     *    }
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     * 
     */
    public function update($id)
    {
        $params =  $this->parameters([
            'guideId' => self::T_INT,
            'beauticianId' => self::T_INT,
            'deanId' => self::T_INT,
            'assistantId' => self::T_INT,
            'attr' => self::T_STRING,
        ]);
        $base = @json_decode($params['attr'],true);     
        if(! is_array($base))
        {
            throw new ApiException('属性参数不合法',ERROR::PARAMETER_ERROR);
        }
        $item = BeautyOrderItem::where('id',$id)->first();
        if(empty($item))
        {
            throw new ApiException('项目不存在或者已经被删除',ERROR::PARAMETER_ERROR);
        }

        $record = ['attr'=>json_encode($base,JSON_UNESCAPED_UNICODE)];
        if(isset($params['guideId']) && !empty($params['guideId']))
        {
            $record['guideId'] = $params['guideId'];
        }
        if(isset($params['beauticianId']) && !empty($params['beauticianId']))
        {
            $record['beauticianId'] = $params['beauticianId'];
        }
        if(isset($params['deanId']) && !empty($params['deanId']))
        {
            $record['deanId'] = $params['deanId'];
        }
        if(isset($params['assistantId']) && !empty($params['assistantId']))
        {
            $record['assistantId'] = $params['assistantId'];
        }
        $res = $item->update($record);
        if($res)
        {
           Event::fire("order.update");
        }
        return $this->success($res);
    }
    
    /**
     * @api {get} /book/gift/{id} 11.预约单赠送水光针
     * @apiName gift
     * @apiGroup book
     *
     * @apiSuccessExample Success-Response:
     *     {
     *        "result": 1,
     *        "token": "",
     *        "data": {
     *        }
     *    }
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function gift($id)
    {
        $book = BookingOrder::where('id',$id)->first();
        if(empty($book))
        {
            throw new ApiException('预约单不存在',ERROR::PARAMETER_ERROR);
        }
        $ordersn = $book->ORDER_SN;
        $uid = $book->USER_ID;
        Utils::log("present",date("Y-m-d H:i:s")." ordersn:{$ordersn} uid:{$uid} (send by admin!) \n","send_present");
        PowderArticlesController::addReservateSnAfterConsume($ordersn,$uid);
        Event::fire("order.gift");
        return $this->success($id);
    }

    /**
     * @api {get} /book/export 12.预约单导出
     * @apiName export
     * @apiGroup book
     *
     * @apiParam {Number} key 1手机号 2预约号 3推荐码
     * @apiParam {String} keyword 根据key来的关键字
     * @apiParam {String} min_time 预约时间 YYYY-MM-DD
     * @apiParam {String} max_time 预约时间 YYYY-MM-DD
     * @apiParam {String} pay_type 0 全部 2 支付宝 3 微信 10易联
     * @apiParam {String} status 0 全部 NEW - 未支付 PYD - 已支付 (未消费)CSD - 已消费 RFN - 申请退款(退款中) RFD - 已退款 Y已补妆 退款失败FAILD
     * @apiParam {Number} item_id  项目id
     * @apiParam {Number} beautician_id  专家id
     * @apiParam {Number} assistant_id  助理id
     * @apiParam {Number} page 可选,页数. (从1开始)
     * @apiParam {Number} page_size 可选,分页大小.(最小1 最大500,默认20)
     * @apiParam {String} sort_key 排序的键 []
     * @apiParam {String} sort_type 排序的方式 ASC正序 DESC倒叙 (默认)
     *
     * @apiSuccess {File} downloadFile
     */
    public function export()
    {
        $params = $this->parameters([
            'key' => self::T_INT,
            'keyword' => self::T_STRING,
            'min_time' => self::T_STRING,
            'max_time' => self::T_STRING,
            'pay_type' => self::T_INT,
            'status' => self::T_STRING,
            'item_id' => self::T_INT,
            'beautician_id' => self::T_INT,
            'assistant_id' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
            'sort_key' => self::T_STRING,
            'sort_type' => self::T_STRING
        ]);
        $page = isset($params['page']) ? $params['page'] : 1;
        $page_size = isset($params['page_size']) ? $params['page_size'] : 20;
        if (empty($page) || $page < 1) {
            $page = 1;
        }
        if (empty($page_size) || $page_size > 5000) {
            $page_size = 20;
        }
        $offset = ($page - 1) * $page_size;
        $items = BookingOrder::getConditionOfExport($params)->take($page_size)
            ->skip($offset)
            ->get()
            ->toArray();      

        $res = self::format_export_data($items);
        
        Event::fire("order.export");
      
        @ini_set('memory_limit', '256M');
        $header = [
            '订单号',
            '预约号',
            '手机号',
            '姓名',
            '性别',
            '预约项目',
            '项目价格',
            '预约日期',
            '推荐码',
            '订单状态',
            '预约金支付方式',
            '预约金支付金额',
            '预约金支付时间',
            '预约金流水号',
            '预约金支付状态',
            '实做项目',
            '项目总额',
            '已付订金',
            '应收金额',
            '现场支付方式1',
            '支付金额1',
            '现场支付方式2',
            '支付金额2',
            '抵扣金额',
            '收银人',
            '收银时间',
            '是否开发票',
            '发起退款',
            '预退款原因',
            '退款说明',
            '预约金退款方式',
            '预约金退款金额',
            '预约金退款状态',
            '现场退款方式',
            '现场退款金额',
            '提款人',
            '退款时间'
        ];
        $this->export_xls("韩式定妆收银表格-" . date("m月d日"), $header, $res);
    }
    
    /**
     * @api {get} /book/destroy/{id} 13.预约单删除
     * @apiName destroy
     * @apiGroup book
     * 
     * @apiSuccessExample Success-Response:
     *     {
     *        "result": 1,
     *        "token": "",
     *        "data": {
     *        }
     *    }
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function destroy($id)
    {
        $base = BookingOrder::where('ID',$id)->where('MANAGER_UID','<>',0)->whereNotIn('STATUS',['RFN','RFD','RFD-OFL'])->first();
        if(empty($base))
        {
            throw new ApiException("指定的订单不存在或者不允许删除", ERROR::PARAMETER_ERROR);
        }
        $cash = BookingCash::where('booking_id',$id)->first();
        if(!$cash)
        {
            throw new ApiException("指定的订单已收银,不允许删除", ERROR::PARAMETER_ERROR);
        }
        $ordersn = $base->ORDER_SN;
        $res = $base->delete();
        BookingOrderItem::where('ORDER_SN',$ordersn)->delete();
        BeautyOrderItem::where('order_sn',$ordersn)->delete();
        BookingReceive::where('booking_id',$id)->delete();
        if (! empty($res)) {
             Event::fire("order.destroy");
        }
        return $this->success(['id'=>$id]);
    }

    private static function format_export_data($datas)
    {
        $res = [];
        $managers = [];
        $uids = array_column($datas, 'salon_refund_uid');
        $uids = array_merge($uids,array_column($datas, 'refund_opt_user_id'));
        $managers = Manager::getBaseInfos($uids);
        foreach ($datas as $data) {
                
            $status_name = Mapping::getBookingOrderStatusName($data['STATUS']);
            if($status_name == "已完成" && !empty($data['beauty_makeup_id']))
            {
                $status_name ='已补妆';
            }
            $pay_name = Mapping::getFundflowPayTypeName($data['pay_type']);
            $refund_info = self::getRefundInfo($data, $pay_name,$managers);
            $cash_pay_type = !empty($data['cash'])?$data['cash']['pay_type']:0;
            $other_pay_method_name = Mapping::getBookingCashPayTypeName($cash_pay_type,[4=>'', 5=>'微信',6=>'支付宝',7=>'POS机']);
            $res[] = [
                'ORDER_SN' => ' '.$data['ORDER_SN'],
                'BOOKING_SN' => $data['BOOKING_SN'],
                'BOOKER_PHONE' => $data['BOOKER_PHONE'],
                'BOOKER_NAME' => $data['BOOKER_NAME'],
                'SEX' => $data['BOOKER_SEX'] == 'F'?'女':'男',
                'book_item_names' => count($data['booking_order_item'])<1?'':implode(',', array_column($data['booking_order_item'], 'ITEM_NAME')),
                'book_item_amout' => count($data['booking_order_item'])<1?'':array_sum(array_map('floatval', array_column($data['booking_order_item'], 'PAYABLE'))),
                'CREATE_TIME' => $data['CREATE_TIME'],
                'RECOMMENDER' => $data['RECOMMENDER'],
                'STATUS' => $status_name,
                'pay_name' => $pay_name,
                'PAYABLE' => $data['PAYABLE'],
                'PAY_TIME' => $data['PAIED_TIME'],
                'record_no' => ' '.$data['record_no'],
                'PAY_STATUS' => (empty($pay_name)&&$data['STATUS']=='NEW')?'未支付':'已支付',
                'beauty_item_names' => count($data['beauty_order_item'])<1?'':implode(',', array_column($data['beauty_order_item'], 'item_name')),
                'beauty_item_amout' => count($data['beauty_order_item'])<1?'':array_sum(array_map('floatval', array_column($data['beauty_order_item'], 'to_pay_amount'))),
                'PAIED' => $data['PAYABLE'],
                'AMOUNT' => $data['AMOUNT'],
                'other_pay_method' => $other_pay_method_name,
                'other_money' => !empty($data['cash'])?$data['cash']['other_money']:'',
                'main_pay_method' => !empty($data['cash'])?'':'',
                'cash_money' => !empty($data['cash'])?$data['cash']['cash_money']:'',
                'deduction_money' => !empty($data['cash'])?$data['cash']['deduction_money']:'',
                'cash_user' => empty($data['cash'])?'':(empty($managers[$data['cash']['uid']])?'':$managers[$data['cash']['uid']]['name']),
                'cash_time' => empty($data['cash'])?'':$data['cash']['created_at'],
                'bill' => empty($data['bill'])?'否':'是',
                'refund_from' => empty($refund_info)?'':$refund_info['refund_from'],
                'refund_reason' => empty($refund_info)?'':$refund_info['refund_reason'],
                'refund_remark' => empty($refund_info)?'':$refund_info['refund_remark'],
                'refund_order_method' => empty($refund_info)?'':$refund_info['refund_order_method'],
                'refund_order_money' => empty($refund_info)?'':$refund_info['refund_order_money'],
                'refund_order_status' => empty($refund_info)?'':$refund_info['refund_order_status'],
                'refund_salon_method' => empty($refund_info)?'':$refund_info['refund_salon_method'],
                'refund_salon_money' => empty($refund_info)?'':$refund_info['refund_salon_money'],
                'refund_user' => empty($refund_info)?'':$refund_info['refund_user'],
                'refund_time' => empty($refund_info)?'':$refund_info['refund_time'],
            ];
        }
        return $res;
    }
        
        
    public static function make_recommend($uid,$recommend_code)
    {
        if(empty($recommend_code))
        {
            return;
        }
        $recommend = RecommendCodeUser::where('user_id',$uid)->whereIn("type",[2,3,4])->first();
        if(!empty($recommend))
        {
            return;
        }
        $attr = [
            'user_id'=>$uid,
            'recommend_code'=>$recommend_code,
            'add_time'=>time(),
        ];
        if(strlen($recommend_code) >= 11)
        {
            $attr['type'] = '3';
        }
        else
        {
            $type = '2';
            $dividend = Dividend::where('recommend_code',$recommend_code)->first();
            if(!empty($dividend))
            {
                if($dividend->activity == 1)
                {
                    $type = '4';
                }
            }
            $attr['type'] = $type;
        }
        RecommendCodeUser::create($attr);
    }
    
    public static function givePresent($customer_uid,$ordersn,$is_first = false,$first_consume_time = 0)
    {
        $query = RecommendCodeUser::where('user_id',$customer_uid);
        $query->useWritePdo();
        $recommends = $query->whereIn("type",[2,3])->get(['recommend_code','type','add_time'])->toArray(); 
        
        if(count($recommends)!=1)
        {
            return ;
        }
        
        $recommend = $recommends[0];
        
        $send_uid = null;
        
        if($recommend['type'] == 2)//店铺推荐 需求修改 不送
        {
            
//             if($recommend['add_time'] <= strtotime($first_consume_time))
//             {
//                 $send_uid = $customer_uid;
//             }
        }
        else 
        {
            if($is_first)
            {
                 $recommend_users = User::where('mobilephone',$recommend['recommend_code'])->get(['user_id'])->toArray();
                 if(count($recommend_users)>0)
                 {
                     $send_uid = $recommend_users[0]['user_id'];
                 }
            }
        }
        
        if(!empty($send_uid))
        {
            Utils::log("present",date("Y-m-d H:i:s")." ordersn:{$ordersn} uid:{$send_uid} \n","send_present");
            PowderArticlesController::addReservateSnAfterConsume($ordersn,$send_uid);
        }
    }
    
    private static function getRefundInfo($data,$pay_type_name,$manager)
    {
        $refund_info = [];
        $refund_info['refund_from'] = "用户";
        $refund_info['refund_reason'] = "";
        $refund_info['refund_remark'] = "";
        $refund_info['refund_order_method'] = $pay_type_name;
        $refund_info['refund_order_money'] = floatval($data['refund_money'])>0?$data['refund_money']:'';
        $refund_info['refund_order_status'] = '';
        $refund_info['refund_salon_method'] = '';
        $refund_info['refund_salon_money'] = '';        
        $refund_info['refund_user'] = '';
        $refund_info['refund_time'] = '';
        
        if(!empty($data['salon_refund_id']))
        {
            $refund_info['refund_from'] = "臭美公司";
        }
        if(in_array($data['STATUS'],['RFN','RFD','RFD-OFL']))  
        {
            $refund_info['refund_order_status'] = Mapping::getBookingOrderStatusName($data['STATUS']);
        }      
        $reason = str_replace(array_keys(Mapping::BeautyRefundRereasonNames()), array_values(Mapping::BeautyRefundRereasonNames()), $data['rereason']);
        $reason = !empty($reason) ? $reason . "," . $data['other_rereason'] : $data['other_rereason'];
        if(empty($reason) && !empty($data['salon_refund_remark']))
        {
            $reason = $data['salon_refund_remark'];
        }
       
        $refund_info['refund_salon_method'] = Mapping::getBookingSalonRefundBacktoName($data['salon_refund_back_to']);
        $refund_info['refund_salon_money'] = $data['salon_refund_money'];
        $refund_info['refund_time'] = $data['salon_refund_created_at'];
        $refund_info['refund_reason'] = $reason;
        $refund_info['refund_remark'] = $reason;
        $salon_uid = $data['salon_refund_uid'];
        $refund_uid = $data['refund_opt_user_id'];
        if(isset($manager[$salon_uid]))
        {
            $refund_info['refund_user'] = $manager[$salon_uid]['name'];
        }
        if(isset($manager[$refund_uid]))
        {
            $refund_info['refund_user'] = $manager[$refund_uid]['name'];
        }
        if($data['refund_retype'] == 2)
        {
            $refund_info['refund_order_method'] = '余额';
        }
        return $refund_info;
    }
}
