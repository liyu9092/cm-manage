<?php

namespace App;

use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\TransactionWriteApi;

class BeautyRefundApi extends TransactionWriteApi {
    /*
     * 拒绝退款
     */

    public static function rejectBeauty($ids) {
        $refundsArr = OrderRefund::whereIn('order_refund_id', $ids)->where('status', self::REFUND_STATUS_OF_NORMAL)->where('item_type', '!=', 'MF')->get(['ordersn', 'booking_sn'])->toArray();
        if (empty($refundsArr)) {
            throw new ApiException("未找到退款单信息", ERROR::REFUND_FLOW_LOST);
        }
        $bookOrdersns = array_column($refundsArr, "booking_sn");
        $ordersns = array_column($refundsArr, "ordersn");
        $bookOrders = BookingOrder::whereIn('ORDER_SN', $ordersns)->where('STATUS', 'RFN')->count();
        if ($bookOrders != count($bookOrdersns)) {
            throw new ApiException("部分退款的预约单状态不正确", ERROR::REFUND_STATE_WRONG);
        }
        $bookingUp = BookingOrder::whereIn('ORDER_SN', $ordersns)->where('STATUS', 'RFN')->update(['STATUS' => 'PYD']);
        $refundUp = OrderRefund::whereIn('order_refund_id', $ids)->where('status', self::REFUND_STATUS_OF_NORMAL)->where('item_type', '!=', 'MF')->update(['status' => self::REFUND_STATUS_OF_CANCLE]);
        //修改接待信息
        $bookingReceiveUp = BookingReceive::whereIn('order_sn', $ordersns)->where('state', '1')->update(['state' => 0]);
        if ($bookingUp !== false && $refundUp !== false) {
            return [];
        } else {
            throw new ApiException("操作失败", ERROR::REFUND_FLOW_LOST);
        }
    }

    /*
     * 拒绝退款 通过booking_sn
     */

    public static function rejectByBookingSn($booking_sns) {
        $booking_order = BookingOrder::whereIn('BOOKING_SN', $booking_sns)->get(['ORDER_SN'])->toArray();
        $refundsArr = [];
        if (!empty($booking_order)) {
            $order_sns = array_column($booking_order, 'ORDER_SN');
            $refundsArr = OrderRefund::whereIn('ordersn', $order_sns)->where('status', self::REFUND_STATUS_OF_NORMAL)->where('item_type', '!=', 'MF')->get(['ordersn', 'booking_sn'])->toArray();
        }
        if (empty($refundsArr)) {
            throw new ApiException("未找到退款单信息", ERROR::REFUND_FLOW_LOST);
        }
        $bookOrdersns = array_column($refundsArr, "booking_sn");
        $ordersns = array_column($refundsArr, "ordersn");
        $bookOrders = BookingOrder::whereIn('ORDER_SN', $ordersns)->where('STATUS', 'RFN')->count();
        if ($bookOrders != count($bookOrdersns)) {
            throw new ApiException("部分退款的预约单状态不正确", ERROR::REFUND_STATE_WRONG);
        }
        $bookingUp = BookingOrder::whereIn('ORDER_SN', $ordersns)->where('STATUS', 'RFN')->update(['STATUS' => 'PYD']);
        $refundUp = OrderRefund::whereIn('ordersn', $order_sns)->where('status', self::REFUND_STATUS_OF_NORMAL)->where('item_type', '!=', 'MF')->update(['status' => self::REFUND_STATUS_OF_CANCLE]);
        //修改接待信息
        $bookingReceiveUp = BookingReceive::whereIn('order_sn', $ordersns)->where('state', '1')->update(['state' => 0]);
        if ($bookingUp !== false && $refundUp !== false) {
            return [];
        } else {
            throw new ApiException("操作失败", ERROR::REFUND_FLOW_LOST);
        }
    }

    /*
     * 确认退款
     */

    public static function accpetBeauty($ids, $opt_user_id) {
        $refunds = self::checkBeautyRefund($ids);
        $ordersns = array_column($refunds, 'ordersn');
//        print_r($ordersns);exit;
        $refund_indexes = Utils::column_to_key("ordersn", $refunds);
        $fundflow = Fundflow::where('code_type', self::REFUND_CODE_TYPE_OF_CUSTOM)
                        ->where('refund_state', '!=', self::FUNDFLOW_REFUND_STATE_OF_COMPLETED)
                        ->whereIn('record_no', $ordersns)
                        ->select(['record_no', 'pay_type', 'user_id', 'money'])
                        ->get()->toArray();
        if (empty($fundflow)) {
            throw new ApiException("找不到退款的支付流水信息", ERROR::REFUND_FLOW_LOST);
        }
        $payments = PaymentLog::whereIn('ordersn', $ordersns)->get(['ordersn', 'tn', 'device', 'batch_no', 'alipay_updated'])->toArray();
        if (empty($payments)) {
            throw new ApiException("退款单找不到对应的payment log 信息", ERROR::REFUND_PAYMENT_LOG_LOST);
        }
        $payment_indexes = Utils::column_to_key("ordersn", $payments);
        $refund_items = self::getBeautyRefundItems($fundflow, $payment_indexes, $refund_indexes);
//        print_r($refund_items);exit;
        //添加审批人ID
        self::modifBookingOrderRefundOptUser($ids, $opt_user_id);
        //循环时  有可能超时
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        //微信
        if (isset($refund_items['wx']) && count($refund_items['wx']) > 0) {
            $res['wx'] = self::refundOfWx($refund_items['wx']);
        }
        //易联
        if (isset($refund_items['yilian']) && count($refund_items['yilian']) > 0) {
            $res['yilian'] = self::refundOfYilian($refund_items['yilian']);
        }
        //支付宝
        if (isset($refund_items['alipay']) && count($refund_items['alipay']) > 0) {
            $res['alipay'] = self::refundOfAlipay($refund_items['alipay']);
        }
        return $res;
    }

    /*
     * 确认退款 通过booking_sn
     * @param $booking_sn 定妆单SN
     * @param $opt_user_id 操作人ID
     */

    public static function accpetByBookingSn($booking_sns, $opt_user_id = 0) {
        $refunds = self::checkRefund($booking_sns);
        $ordersns = array_column($refunds, 'ordersn');
//        print_r($ordersns);exit;
        $refund_indexes = Utils::column_to_key("ordersn", $refunds);
        $fundflow = Fundflow::where('code_type', self::REFUND_CODE_TYPE_OF_CUSTOM)
                        ->where('refund_state', '!=', self::FUNDFLOW_REFUND_STATE_OF_COMPLETED)
                        ->whereIn('record_no', $ordersns)
                        ->select(['record_no', 'pay_type', 'user_id', 'money'])
                        ->get()->toArray();
        if (empty($fundflow)) {
            throw new ApiException("找不到退款的支付流水信息", ERROR::REFUND_FLOW_LOST);
        }
        $payments = PaymentLog::whereIn('ordersn', $ordersns)->get(['ordersn', 'tn', 'device', 'batch_no', 'alipay_updated'])->toArray();
        if (empty($payments)) {
            throw new ApiException("退款单找不到对应的payment log 信息", ERROR::REFUND_PAYMENT_LOG_LOST);
        }
        $payment_indexes = Utils::column_to_key("ordersn", $payments);
        $refund_items = self::getBeautyRefundItems($fundflow, $payment_indexes, $refund_indexes);
//        print_r($refund_items);exit;
        //添加审批人ID
        $opt_user_id && self::modifBookingOrderRefundOptUser($ordersns, $opt_user_id);
        //循环时  有可能超时
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        //微信
        if (isset($refund_items['wx']) && count($refund_items['wx']) > 0) {
            $res['wx'] = self::refundOfWx($refund_items['wx']);
        }
        //易联
        if (isset($refund_items['yilian']) && count($refund_items['yilian']) > 0) {
            $res['yilian'] = self::refundOfYilian($refund_items['yilian']);
        }
        //支付宝
        if (isset($refund_items['alipay']) && count($refund_items['alipay']) > 0) {
            $res['alipay'] = self::refundOfAlipay($refund_items['alipay']);
        }
        return $res;
    }

    private static function checkBeautyRefund($ids) {
        $countIds = count($ids);
        $refunds = OrderRefund::whereIn('order_refund_id', $ids)->where('status', '!=', self::REFUND_STATUS_OF_CANCLE)->get(['ordersn', 'booking_sn', 'retype', 'rereason', 'other_rereason'])->toArray();
        if (empty($refunds)) {
            throw new ApiException("退款id下取不到信息", ERROR::PARAMS_LOST);
        }
        if ($countIds != count($refunds)) {
            throw new ApiException("您选的部分订单不存在退款信息", ERROR::REFUND_STATE_WRONG);
        }
        $ordersns = array_column($refunds, 'ordersn');
        $orderNum = BookingOrder::whereIn('ORDER_SN', $ordersns)->where('STATUS', 'RFN')->count();
        if (intval($orderNum) !== $countIds) {
            throw new ApiException("关联的订单状态不正确", ERROR::REFUND_STATE_WRONG);
        }
        return $refunds;
    }

    private static function checkRefund($booking_sns) {
        $countIds = count($booking_sns);
        $refunds = BookingSalonRefund::whereIn('booking_sn', $booking_sns)->get(['order_sn as ordersn', 'booking_sn'])->toArray();
        if (!empty($refunds)) {  //线下退款
            $orderNum = BookingOrder::whereIn('BOOKING_SN', $booking_sns)->count();
            if (intval($orderNum) !== $countIds) {
                throw new ApiException("关联的订单状态不正确", ERROR::REFUND_STATE_WRONG);
            }
        } else { //线上退款
//            $booking_order = BookingOrder::whereIn('BOOKING_SN', $booking_sns)->where('STATUS', 'RFN')->get(['ORDER_SN'])->toArray();
//            if (count($booking_order) !== $countIds) {
//                throw new ApiException("关联的订单状态不正确", ERROR::REFUND_STATE_WRONG);
//            }
//            $order_sns = array_column($booking_order, 'ORDER_SN');
            $refunds = OrderRefund::select(['order_refund.ordersn', 'booking_order.BOOKING_SN as booking_sn', 'order_refund.retype', 'order_refund.rereason', 'order_refund.other_rereason'])
                            ->leftJoin('booking_order', 'booking_order.ORDER_SN', '=', 'order_refund.ordersn')
                            ->where('order_refund.item_type', 'MZ')
                            ->where('order_refund.status', '!=', self::REFUND_STATUS_OF_CANCLE)
                            ->whereIn('booking_order.BOOKING_SN', $booking_sns)
                            ->get()->toArray();
            if ($countIds != count($refunds)) {
                throw new ApiException("您选的部分订单不存在退款信息", ERROR::REFUND_STATE_WRONG);
            }
        }
        if (empty($refunds)) {
            throw new ApiException("退款id下取不到信息", ERROR::PARAMS_LOST);
        }

        return $refunds;
    }

    private static function getBeautyRefundItems($fundflow, $payments, $refunds) {
        $wx_refund_url_of_web = env("WX_REFUND_URL_OF_WEB", null);
        $wx_refund_url_of_sdk = env("WX_REFUND_URL_OF_SDK", null);
        if (empty($wx_refund_url_of_web) || empty($wx_refund_url_of_sdk)) {
            throw new ApiException("获取配置信息 [WX_REFUND_URL_OF_WEB] 或者 [WX_REFUND_URL_OF_SDK] 出错", ERROR::CONFIG_LOST);
        }
        $res = [
            'union' => [],
            'alipay' => [],
            'wx' => [],
            'yilian' => []
        ];
        foreach ($fundflow as $flow) {
            $ordersn = $flow['record_no'];
            if (!isset($refunds[$ordersn]) || !isset($payments[$ordersn])) {
                throw new ApiException("退款的关键信息不全", ERROR::REFUND_LOST_PRIMARY_INFO);
            }
            $booking_sn = $refunds[$ordersn]['booking_sn'];
            if (!isset($booking_sn) || empty($booking_sn)) {
                throw new ApiException("退款的关键信息不全", ERROR::REFUND_LOST_PRIMARY_INFO);
            }
            $pay_type = $flow['pay_type']; //1原路退款 2 为退回余额
            $user_id = $flow['user_id'];
            $money = $flow['money'];
//            $reason = implode(',', Mapping::BeautyRefundRereasonNames(explode(',', $refunds[$ordersn]['rereason'])));
            if (isset($refunds[$ordersn]['rereason'])) {
                $reason = implode(',', Mapping::getRefundRereasonNames(explode(',', $refunds[$ordersn]['rereason'])));
                $reason = !empty($reason) ? $reason . "," . $refunds[$ordersn]['other_rereason'] : $refunds[$ordersn]['other_rereason'];
                $reason = mb_substr($reason, 0, 50);  //退款愿意不能超过250个字节  粗处粗略截取
            } else {
                $reason = '用户不想做了';
            }

            $device = null;
            $tn = '';
            $batch_no = '';
            $alipay_updated = 0;
            if (isset($payments[$ordersn])) {
                $tn = $payments[$ordersn]['tn'];
                $device = $payments[$ordersn]['device'];
                $batch_no = $payments[$ordersn]['batch_no'];
                $alipay_updated = $payments[$ordersn]['alipay_updated'];
            }
            if (empty($tn)) {
                throw new ApiException("ordersn '{$ordersn}' can not find tn", ERROR::REFUND_CANT_FIND_TN);
            }
            switch ($pay_type) {
                case self::REFUND_TO_UNION:
                    $res['union'][] = [
                        'ordersn' => $ordersn,
                        'user_id' => $user_id,
                        'money' => $money,
                        'tn' => $tn
                    ];
                    break;
                case self::REFUND_TO_ALIPAY:
                    //方便测试   暂时去掉;
                    $env = strtolower(env('APP_ENV'));
                    if ($env == 'prod') {
                        if (!empty($batch_no) && (time() - $alipay_updated) < 7200) {
                            throw new ApiException("ordersn '{$ordersn}' 已于" . date("Y-m-d H:i:s", $alipay_updated) . "开始退款.请不要请求太频繁", ERROR::UNKNOWN_ERROR);
                        }
                    }
                    $res['alipay'][] = [
                        'tn' => $tn,
                        'money' => $money,
                        'reason' => $reason,
                        'ordersn' => $ordersn,
                        'booking_sn' => $booking_sn
                    ];
                    break;
                case self::REFUND_TO_WX:
                    $url = $device == 2 ? $wx_refund_url_of_web : $wx_refund_url_of_sdk;
                    $res['wx'][] = [
                        'tn' => $tn,
                        'money' => $money,
                        'ordersn' => $ordersn,
                        'user_id' => $user_id,
                        'url' => $url,
                        'booking_sn' => $booking_sn
                    ];
                    break;
                case self::REFUND_TO_YILIAN:
                    $res['yilian'][] = [
                        'tn' => $tn,
                        'amount' => $money,
                        'ordersn' => $ordersn,
                        'user_id' => $user_id,
                        'booking_sn' => $booking_sn
                    ];
            }
            $res['specail'][] = $ordersn;
        }
        return $res;
    }

    /*
     * @deprecated
     * 更新退款操作人信息
     */

    private static function modifBookingOrderRefundOptUser($order_sns, $opt_user_id) {
        $isSalonRefund = BookingSalonRefund::whereIn('order_sn', $order_sns)->count();
        if (!$isSalonRefund) {
            OrderRefund::whereIn('ordersn', $order_sns)->where('item_type', 'MZ')->where('status', '!=', 2)->update(['opt_user_id' => $opt_user_id, 'opt_time' => time()]);  // 状态必须为申请退款 更改为退款中  TODO
        }
        return true;
    }

    /*
     * 支付宝回调
     */

    public static function callBackOfAlipay() {
        $args = func_get_args();
        //成功 则改变退款的状态
        if (isset($args[0]) && isset($args[0]['success']) && isset($args[0]['batch_no']) && isset($args[0]['success_num']) && !empty($args[0]['batch_no']) && count($args[0]['success']) > 0) {
            $items = $args[0]['success'];
            $tns = Utils::get_column_array("tn", $items);
            $batch_no = $args[0]['batch_no'];
            if (count($tns) > 0) {
                $logs = PaymentLog::whereIn("tn", $tns)->where('batch_no', $batch_no)->get(['tn', 'ordersn']);
                if (!empty($logs)) {
                    $logArr = $logs->toArray();
                    $ordersns = array_column($logArr, "ordersn");
                    self::modifBookingOrderStatusRefundCompleted($ordersns);
                }
            }
        }
    }

    /*
     * 支付宝退款完成  修改booking_order表的数据
     */

    public static function modifBookingOrderStatusRefundCompleted($ordersns) {
        $ordersns = array_unique($ordersns);
        if (count($ordersns) < 1) {
            return false;
        }
        $now_time = time();
        OrderRefund::whereIn('ordersn', $ordersns)->update(['opt_time' => $now_time]);
        BookingOrder::whereIn('ORDER_SN', $ordersns)->update(['status' => 'RFD']);  //退款完成
        self::modifFundflowCompleted($ordersns);
        //调用refundUpdateCalendar 此处有Java在客户申请退款的时候处理
//         foreach ($ordersns as $val) {
//             BookingCalendar::refundUpdateCalendar($val);
//         }
        return true;
    }

}
