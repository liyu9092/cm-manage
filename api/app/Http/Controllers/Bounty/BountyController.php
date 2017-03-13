<?php

namespace App\Http\Controllers\Bounty;

use App\Http\Controllers\Controller;
use App\BountyTask;
use App\User;
use App\Salon;
use App\Hairstylist;
use App\PaymentLog;
use Event;
use Excel;
use App\Exceptions\ERROR;
use App\Exceptions\ApiException;

class BountyController extends Controller {

    /**
     * @api {post} /bounty/index 1.赏金单查询列表
     * @apiName index
     * @apiGroup  bounty
     *
     * @apiParam {Number} isRefund 必选,是否为退款查询：1否 2是.
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} keywordType 必选,搜索关键词类型，可取0 赏金单号/1 用户臭美号/2 用户手机号/3 店铺名称.
     * @apiParam {Number} payType 可选,支付方式：2 支付宝/3 微信/6 优惠券/10 易联.
     * @apiParam {Number} isPay 可选,支付状态：1否 2是
     * @apiParam {Number} btStatus 可选,订单状态：1 待抢单，2 待服务，3 已服务，4 已打赏, 5 不打赏, 9 取消
     * @apiParam {String} refundStatus 可选,退款状态：5申请退款，6退款中，7退款完成, 8拒绝, 9失败
     * @apiParam {String} minAddTime 可选,交易时间左框.
     * @apiParam {String} maxAddTime 可选,交易时间右框.
     * @apiParam {String} minEndTime 可选,退款时间左框.
     * @apiParam {String} maxEndTime 可选,退款时间右框.
     * @apiParam {String} sortKey 可选,排序关键词 "btSn" 赏金单号/ "money" 赏金金额 / "addTime"下单时间.
     * @apiParam {String} sortType 可选,排序 DESC倒序 ASC升序.
     *
     * @apiSuccess {Number} total 总条数.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 最后页面.
     * @apiSuccess {Number} from 起始数.
     * @apiSuccess {Number} to 结束数.
     * @apiSuccess {Number} amount 总金额.
     * @apiSuccess {Number} btId 赏金单Id.
     * @apiSuccess {String} btSn 赏金单号.
     * @apiSuccess {String} tn 三方流水号.
     * @apiSuccess {String} payType 支付方式：2 支付宝/3 微信/6 优惠券/10 易联.
     * @apiSuccess {Number} money 赏金金额/退款金额
     * @apiSuccess {String} addTime 下单时间.
     * @apiSuccess {String} endTime 申请时间.
     * @apiSuccess {String} userName 用户臭美号.
     * @apiSuccess {Number} hairStylistMobile 造型师手机号.
     * @apiSuccess {Number} userMobile 用户手机号.
     * @apiSuccess {String} salonName 商铺名称.
     * @apiSuccess {Number} refundStatus 退款状态：5申请退款，6退款中，7退款完成, 8拒绝, 9失败
     * @apiSuccess {String} isPay 支付状态：1未支付 2已支付	 
     * 
     *
     *
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "data": {
     * 	        "total": 51,
     * 	        "per_page": 10,
     * 	        "records": 510,
     * 	        "current_page": 1,
     * 	        "from": 1,
     * 	        "to": 10,
     *              "amount":12000,
     * 	        "data": [
     * 	            {
     *                  "btId":
     *                  "btSn" :"33833797391"
     *                  "tn" :"1224362901201506096196702838"
     *                  "payType" :1
     *                  "money" :200
     *                  "addTime" :1434332931
     *                  "hairStylistMobile" :18680370905
     *                  "userMobile" :18680370905
     *                  "salonName" :永琪美容美发（南光店）
     *                  "isPay" :2
     * 	            }
     *              ......
     * 	        ]
     * 	    }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "参数有误！"
     * 		}
     */
    function index() {
        $param = $this->param;
        if (isset($param['page']) && !empty($param['page'])) {
            $page = $param['page'];
        } else {
            $page = 1;
        }
        if (isset($param['page_size']) && !empty($param['page_size'])) {
            $size = $param['page_size'];
        } else {
            $size = 20;
        }
        $query = BountyTask::getQueryByParam($param);
        $sortable_keys = ['btSn', 'money', 'addTime'];
        $sortKey = "addTime";
        $sortType = "DESC";
        if (isset($param['sortKey']) && in_array($param['sortKey'], $sortable_keys)) {
            $sortKey = $param['sortKey'];
            $sortType = $param['sortType'];
            if (strtoupper($sortType) != "DESC") {
                $sortType = "ASC";
            }
        }

        $count = BountyTask::getcount($query);

        $bountys = BountyTask::search($query, $page, $size, $sortKey, $sortType);
        if($count<=2000)
        {
            $amount = BountyTask::getAmount($bountys);
        }
        $res = [];
        

        $res["total"] = $count;
        $res["per_page"] = $size;
        $res["current_page"] = $page;
        $res["last_page"] = ceil($count / $size);
        $res["amount"] = array("amount" => number_format($amount, 2));
        $res['data'] = $bountys;
        return $this->success($res);
    }

    /**
     * @api {post} /bounty/show 2.赏金单详情
     * @apiName show
     * @apiGroup  bounty
     *
     * @apiParam {Number} no 必选,赏金单号.	 
     *
     * @apiSuccess {Number} btId 赏金单Id.
     * @apiSuccess {String} btSn 赏金单号.
     * @apiSuccess {String} userName 用户臭美号.
     * @apiSuccess {String} userMobile 用户手机号.
     * @apiSuccess {String} needsStr 任务需求.
     * @apiSuccess {String} remark 我的需求.
     * @apiSuccess {String} selectType 选择发型师类型.
     * @apiSuccess {String} payType 支付方式：2 支付宝/3 微信/6 优惠券/10 易联.
     * @apiSuccess {Number} money 赏金金额
     * @apiSuccess {String} isPay 是否支付.
     * @apiSuccess {String} addTime 下单时间.
     * @apiSuccess {String} payTime 支付时间.
     * @apiSuccess {String} tn 三方流水号.   
     * @apiSuccess {String} cancelTime 取消时间.
     * @apiSuccess {String} endTime 完成时间.
     * @apiSuccess {String} salonName 商铺名称.
     * @apiSuccess {String} district 服务区域.
     * @apiSuccess {String} bountyType 店铺类型.
     * @apiSuccess {String} grade 造型师类型.
     * @apiSuccess {String} stylistName 造型师帐号.
     * @apiSuccess {Number} hairStylistMobile 造型师手机号.
     * @apiSuccess {String} btStatus 订单状态：1 待抢单，2 待服务，3 已服务，4 已打赏, 5 不打赏, 9 取消
     * 
     *
     *
     * @apiSuccessExample Success-Response:
     *      {
     *           "result": 1,
     *           "token": "",
     *           "data":{
     *                      "btId": 1903,
     *                      "btSn": "3468005511776",
     *                      "needsStr": "洗剪吹",
     *                      "remark": "默默默默默默哦默默",
     *                      "selectType": "自己选",
     *                      "payType": "微信",
     *                      "money": 230,
     *                      "isPay": "已支付",
     *                      "addTime": "2015-06-19 10:14:15",
     *                      "payTime": "2015-06-19 10:14:00",
     *                      "endTime": "2015-06-19 10:15:06",
     *                      "btStatus": "不打赏",
     *                      "userName": "10306986",
     *                      "userMobile": 1111111111,
     *                      "tn": "1224362901201506196029751743",
     *                      "salonName": "choumeitest_salon",
     *                      "bountyType": "",
     *                      "district": "全城全区",
     *                      "grade": "美发大师",
     *                      "stylistName": "欧阳",
     *                      "hairStylistMobile": "17875827821"
     *                 }
     *       }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "message": "参数有误！"
     * 		}
     */
    function show() {
        $param = $this->param;
        if (empty($param['no'])) {
            throw new ApiException('没有id传递！', ERROR::BOUNTY_ID_NOT_PASS);
        }
        $id = $param['no'];
        $detail = BountyTask::detail($id);
        if (!$detail) {
            throw new ApiException('找不到赏金单！', ERROR::BOUNTY_NOT_FOUND);
        }
        return $this->success($detail);
    }

    /**
     * @api {post} /bounty/refundShow 3.赏金退款单详情
     * @apiName refundShow
     * @apiGroup  bounty
     *
     * @apiParam {Number} no 必选,赏金单号.	 
     *
     * @apiSuccess {Number} btId 赏金单Id.
     * @apiSuccess {String} btSn 赏金单号.
     * @apiSuccess {String} btStatus 赏金单状态.
     * @apiSuccess {String} tn 三方流水号.
     * @apiSuccess {String} endTime 申请时间. 
     * @apiSuccess {String} userName 用户臭美号. 
     * @apiSuccess {String} userMobile 用户手机号.
     * @apiSuccess {String} payType 支付方式：2 支付宝/3 微信/6 优惠券/10 易联.
     * @apiSuccess {Number} money 赏金金额/退款金额
     * @apiSuccess {String} refundType 退款方式.
     * @apiSuccess {String} refundStatus 退款状态：5申请退款，6退款中，7退款完成, 8拒绝, 9失败
     * 
     *
     *
     * @apiSuccessExample Success-Response:
     * 	{
     *      "result": 1,
     *      "token": "",
     *      "data":{
     *              "btID": 1903,
     *              "btSn": "3468005511776",
     *              "btStatus": "不打赏",
     *              "endTime": "2015-06-19 10:15:06",
     *              "payType": "微信",
     *              "money": 230,
     *              "refundStatus": 5,
     *              "tn": "1224362901201506196029751743",
     *              "userName": "10306986",
     *              "userMobile": 1111111111,
     *              "refundType": "原路返还"
     *          }
     *  }
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "参数有误！"
     * 		}
     */
    function refundShow() {
        $param = $this->param;
        if (empty($param['no'])) {
            throw new ApiException('赏金单详情没有id传值！', ERROR::BOUNTY_ID_NOT_PASS);
        }
        $id = $param['no'];
        $detail = BountyTask::refundDetail($id);
        if (!$detail) {
            throw new ApiException('找不到赏金单！', ERROR::BOUNTY_NOT_FOUND);
        }
        return $this->success($detail);
    }

    /**
     * @api {post} /bounty/accept 4.赏金单退款通过
     * @apiName accept
     * @apiGroup  bounty
     *
     * @apiParam {Array} ids 必选,赏金单Id数列.	   
     *  
     * @apiSuccess {String} msg 退款信息
     * @apiSuccess {String} alipay 微信
     *
     * @apiSuccessExample Success-Response:
     *     {
     *      "result": 1,
     *      "token": "",
     *      "data":{
     *          "log": "",
     *          "alipay":{
     *              "_input_charset": "utf-8",
     *              "batch_no": "20150923114532",
     *              "batch_num": "1",
     *              "detail_data": "2015092300001000880060818378^200^协商退款",
     *              "notify_url": "http://192.168.13.46:8140/AlipayRefundNotify/callback_alipay",
     *              "partner": "2088701753684258",
     *              "refund_date": "2015-09-23 11:45:32",
     *              "seller_email": "zfb@choumei.cn",
     *              "service": "refund_fastpay_by_platform_pwd",
     *              "sign": "650df5a814393a8772a7e469daa84006",
     *              "sign_type": "MD5"
     *              }
     *          }
     * ｝
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    function accept() {
        $param = $this->param;
        if (empty($param['ids'])) {
            throw new ApiException('赏金单没有id传值！', ERROR::BOUNTY_ID_NOT_PASS);
        }
        $ids = $param['ids'];
        $ids = array_map("intval", $ids);
        $accept_info = null;
        $ret = BountyTask::accept($ids, $accept_info);

        $res = [];
        if ($ret) { //执行成功
            $res['msg'] = nl2br($accept_info['info']);
            if (!empty($accept_info['alipay_form_args'])) {
                $res['alipay'] = $accept_info['alipay_form_args'];
            }
            return $this->success($res);
        } else {
            $res['msg'] = nl2br($accept_info['err_info']);
            return $this->success($res);
        }
    }

    /**
     * @api {post} /bounty/reject 5.赏金单退款拒绝
     * @apiName reject
     * @apiGroup  bounty
     *
     * @apiParam {Array} ids 必选,赏金单Id数列.	  
     * @apiParam {String} reason 必选，拒绝退款理由. 
     * @apiSuccess {String} msg 退款信息.
     *
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "data": {
     * 	        "log": "执行成功!",
     * 	        ]
     * 	    }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "拒绝原因不能为空！"
     * 		}
     */
    function reject() {
        $param = $this->param;
        if (empty($param['ids'])) {
            throw new ApiException('赏金单没有id传值！', ERROR::BOUNTY_ID_NOT_PASS);
        }
        $ids = $param['ids'];
        $ids = array_map("intval", $ids);
        if (empty($param['reason'])) {
            throw new ApiException('拒接退款需要理由！', ERROR::BOUNTY_REJECT_NOREASON);
        }
        $reason = $param['reason'];
        $reject_info = null;
        $ret = BountyTask::reject($ids, $reject_info, $reason);
        $res = [];
        if ($ret) { //执行成功
            $res['msg'] = nl2br($reject_info['info']);

            return $this->success(json_encode($res, JSON_UNESCAPED_UNICODE));
        } else {
            $res['msg'] = nl2br($reject_info['err_info']);
            return $this->success(json_encode($res, JSON_UNESCAPED_UNICODE));
        }
    }

    /**
     * @api {get} /bounty/exportBounty 6.导出赏金单列表
     * @apiName exportBounty
     * @apiGroup bounty
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} keywordType 必选,搜索关键词类型，可取"btSn","userName","mobile","salonName".
     * @apiParam {Number} payType 可选,支付方式：2 支付宝/3 微信/6 优惠券/10 易联.
     * @apiParam {Number} isPay 可选,支付状态：1否 2是
     * @apiParam {Number} btStatus 可选,订单状态：1 待抢单，2 待服务，3 已服务，4 已打赏, 5 不打赏, 9 取消
     * @apiParam {Number} refundStatus 可选,退款状态：5申请退款，6退款中，7退款完成, 8拒绝, 9失败
     * @apiParam {String} minAddTime 可选,交易时间左框.
     * @apiParam {String} maxAddTime 可选,交易时间右框.
     * @apiParam {String} sortKey 可选,排序关键词 "btSn" 赏金单号/ "money" 赏金金额 / "addTime"下单时间.
     * @apiParam {String} sortType 可选,排序 DESC倒序 ASC升序.
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function exportBounty() {
        $param = $this->param;
        $param['isRefund'] = 1;
        if (isset($param['page']) && !empty($param['page'])) {
            $page = $param['page'];
        } else {
            $page = 1;
        }
        if (isset($param['page_size']) && !empty($param['page_size'])) {
            $size = $param['page_size'];
        } else {
            $size = 20;
        }
        $query = BountyTask::getQueryByParam($param);
        $sortable_keys = ['btSn', 'money', 'addTime'];
        $sortKey = "addTime";
        $sortType = "DESC";
        if (isset($param['sortKey']) && in_array($param['sortKey'], $sortable_keys)) {
            $sortKey = $param['sortKey'];
            $sortType = $param['sortType'];
            if (strtoupper($sortType) != "DESC") {
                $sortType = "ASC";
            }
        }
        $bountys = BountyTask::search($query, $page, $size, $sortKey, $sortType);
        $header = ['赏金单号', '三方流水号', '支付方式','赏金金额', '下单时间', '造型师手机号', '用户手机号', '店铺名称', '支付状态'];
        Event::fire('bounty.export');
        $this->export_xls("赏金单" . date("Ymd"), $header, BountyTask::format_exportBounty_data($bountys));
    }

    /**
     * @api {get} /bounty/exportRefund 7.导出赏金单退款列表
     * @apiName exportRefund
     * @apiGroup bounty
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} keywordType 必选,搜索关键词类型，可取"btSn","userName","mobile","salonName".
     * @apiParam {Number} payType 可选,支付方式：2 支付宝/3 微信/6 优惠券/10 易联.
     * @apiParam {Number} isPay 可选,支付状态：1否 2是
     * @apiParam {Number} btStatus 可选,订单状态：1 待抢单，2 待服务，3 已服务，4 已打赏, 5 不打赏, 9 取消
     * @apiParam {String} refundStatus 可选,退款状态：5申请退款，6退款中，7退款完成, 8拒绝, 9失败
     * @apiParam {String} minEndTime 可选,退款时间左框.
     * @apiParam {String} maxEndTime 可选,退款时间右框.
     * @apiParam {String} sortKey 可选,排序关键词 "btSn" 赏金单号/ "money" 赏金金额 / "addTime"下单时间.
     * @apiParam {String} sortType 可选,排序 DESC倒序 ASC升序.
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function exportRefund() {
        $param = $this->param;
        $param['isRefund'] = 2;
        if (isset($param['page']) && !empty($param['page'])) {
            $page = $param['page'];
        } else {
            $page = 1;
        }
        if (isset($param['page_size']) && !empty($param['page_size'])) {
            $size = $param['page_size'];
        } else {
            $size = 20;
        }
        $query = BountyTask::getQueryByParam($param);
        $sortable_keys = ['btSn', 'money', 'addTime'];
        $sortKey = "addTime";
        $sortType = "DESC";
        if (isset($param['sortKey']) && in_array($param['sortKey'], $sortable_keys)) {
            $sortKey = $param['sortKey'];
            $sortType = $param['sortType'];
            if (strtoupper($sortType) != "DESC") {
                $sortType = "ASC";
            }
        }
        $bountys = BountyTask::search($query, $page, $size, $sortKey, $sortType);
        $header = ['赏金单号', '支付方式', '退款金额', '申请时间', '用户臭美号', '用户手机号', '店铺名称', '退款状态'];
        Event::fire('bountyRefund.export');
        $this->export_xls("赏金退款单" . date("Ymd"), $header, BountyTask::format_exportRefund_data($bountys));
    }

}
