<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Exceptions\ERROR;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\DB;

class OrderRefund extends Model {

    protected $table = 'order_refund';
    protected $primaryKey = 'order_refund_id';
    public $timestamps = false;

    public function salon() {
        return $this->belongsTo(\App\Salon::class, 'salonid', 'salonid');
    }

    public function user() {
        return $this->belongsTo(\App\User::class);
    }

    public function fundflow() {
        return $this->hasMany(\App\Fundflow::class, 'record_no', 'ordersn');
    }

    public function voucher() {
        return $this->belongsTo(\App\Voucher::class, 'ordersn', 'vOrderSn');
    }

    public static function serachMakeupRefundList($param, $page, $size) {
        $fields = [
//            'order_refund.order_refund_id',
//            'order_refund.ordersn',
//            'order_refund.user_id',
//            'order_refund.add_time',
//            'order_refund.money',
//            'order_refund.booking_sn',
//            'order_refund.status as refund_status',
            'booking_order.BOOKING_SN as booking_sn',
            'booking_order.ID as booking_id',
            'booking_order.STATUS as book_status',
            'booking_order.USER_ID as user_id',
            'booking_order.ORDER_SN as ordersn',
            'booking_order.BOOKER_NAME as booker_name',
            'booking_order.BOOKER_PHONE as booker_phone',
            'fundflow.pay_type',
        ];


//        $query = BookingOrder::select($fields);
//        $query = self::getRefundView($query, $param);
        $query = self::getRefundViewByBookingOrder($fields, $param);
//        $query = self::whereConditionRefund($query, $param);
        $query = self::whereConditionRefundByBookingOrder($query, $param);
        AbstractPaginator::currentPageResolver(function () use($page) {
            return $page;
        });
        $refundList = $query->paginate($size)->toArray();
        unset($refundList['next_page_url']);
        unset($refundList['prev_page_url']);
        return $refundList;
    }

    private static function getRefundViewByBookingOrder($fields, $param) {
        $query = BookingOrder::getRawBindings();
//        $query=  BookingOrder::selectRaw(" CASE `cm_booking_order`.`STATUS` WHEN 'RFD-OFL' THEN 'RFD' WHEN 'RFD' THEN 'RFD' ELSE 'RFN'  END sort_type ");
        $order_refund_fields = ['order_refund.order_refund_id', 'order_refund.add_time', 'order_refund.status', 'order_refund.money as order_refund_money'];
        $salon_refund_fields = ['booking_salon_refund.id', 'booking_salon_refund.created_at', 'booking_salon_refund.money as salon_refund_money'];
        if (isset($param['initiate_refund']) && !empty($param['initiate_refund'])) {
            if ($param['initiate_refund'] == 1) {  //用户发起的退款
                $query->select(array_merge($fields, $order_refund_fields));
                $query->leftJoin('order_refund', function ($join) {
                    $join->on('order_refund.ordersn', '=', 'booking_order.ORDER_SN')->where('order_refund.status', '!=', 2)->where('item_type', '!=', 'MF');
                });
            } else { //臭美人员
                $query->select(array_merge($fields, $salon_refund_fields));
                $query->leftJoin('booking_salon_refund', 'booking_salon_refund.booking_sn', '=', 'booking_order.BOOKING_SN');
            }
        } else {  //全部
            $query->select(array_merge($fields, $order_refund_fields, $salon_refund_fields));
//            $query->leftJoin('order_refund', 'order_refund.booking_sn', '=', 'booking_order.BOOKING_SN')
            $query->leftJoin('order_refund', function ($join) {
                $join->on('order_refund.ordersn', '=', 'booking_order.ORDER_SN')->where('order_refund.status', '!=', 2)->where('item_type', '!=', 'MF');
            });
            $query->leftJoin('booking_salon_refund', 'booking_salon_refund.booking_sn', '=', 'booking_order.BOOKING_SN');
        }
        $query->leftJoin('fundflow', 'record_no', '=', 'booking_order.ORDER_SN');
        if (isset($param['key']) && isset($param['keyword']) && ($param['key'] == 3) && !empty($param['keyword'])) {
            $query->leftJoin('recommend_code_user', 'recommend_code_user.user_id', '=', 'order_refund.user_id');
        }
        if (isset($param['sort_key']) && !empty($param['sort_key'])) {
            $sort_type = (isset($param['sort_type']) && !empty($param['sort_type']) && in_array($param['sort_type'], ['ASC', 'DESC'])) ? $param['sort_type'] : 'DESC';
            if ($param['sort_key'] == 'book_status') {
                $sort_key = 'sort_type';
            } else {
                $sort_key = $param['sort_key'];
            }
            $query->orderBy($sort_key, $sort_type);
        } else {
            $query->orderBy('booking_order.ID', 'DESC');
        }
        $query->selectRaw(" CASE `cm_booking_order`.`STATUS` WHEN 'RFD-OFL' THEN 'RFD' WHEN 'RFD' THEN 'RFD' ELSE 'RFN'  END sort_type ");
        return $query;
    }

    private static function getRefundView($query, $param) {

        $query->leftJoin('booking_order', 'booking_order.ORDER_SN', '=', 'order_refund.ordersn')
                ->leftJoin('fundflow', 'record_no', '=', 'order_refund.ordersn');
        if (isset($param['key']) && isset($param['keyword']) && ($param['key'] == 3) && !empty($param['keyword'])) {
            $query->leftJoin('recommend_code_user', 'recommend_code_user.user_id', '=', 'order_refund.user_id');
        }
        if (isset($param['sort_key']) && !empty($param['sort_key'])) {
            $sort_type = (isset($param['sort_type']) && !empty($param['sort_type']) && in_array($param['sort_type'], ['ASC', 'DESC'])) ? $param['sort_type'] : 'DESC';
            if ($param['sort_key'] == 'state') {
                $sort_key = 'booking_order.STATUS';
            } else {
                $sort_key = $param['sort_key'];
            }
            $query->orderBy($sort_key, $sort_type);
        } else {
            $query->orderBy('order_refund_id', 'DESC');
        }
        return $query;
    }

    private static function whereConditionRefund($query, $param) {
        //必要条件 全部为退款状态
        $query->whereNotIn('booking_order.STATUS', ['NEW', 'PYD', 'CSD']);

        // 按时间搜索
        if (!empty($param['start_time'])) {
            $query->where('order_refund.add_time', '>=', strtotime($param['start_time'] . "00:00:00"));
        }
        if (!empty($param['end_time'])) {
            $query->where('order_refund.add_time', '<=', strtotime($param['end_time'] . "23:59:59"));
        }
        //支付方式
        if (isset($param['pay_type']) && $param['pay_type']) {
            $query->where('fundflow.pay_type', $param['pay_type']);
        }
        //付款状态
        if (isset($param['state']) && $param['state']) {
            $state_str = explode(",", $param['state']);
            if (count($state_str) == 3) { //全选
                $query->whereIn('booking_order.STATUS', $state_str)->where("order_refund.status", '!=', 2);
            } elseif (count($state_str) == 1 && $state_str[0] == 'RFE') {  //仅选择了退款失败
                $query->where("order_refund.status", 3);
            } else {
                $query->whereIn('booking_order.STATUS', $state_str)->where("order_refund.status", 1);
            }
        }
        if (isset($param['key']) && isset($param['keyword']) && $param['key'] && !empty($param['keyword'])) {
            switch ($param['key']) {
                case 1:
                    $query->where('booking_order.booker_phone', 'like', '%' . $param['keyword'] . '%');
                    break;
                case 2:
                    // TODO  预约号
                    $query->where('booking_order.BOOKING_SN', 'like', '%' . $param['keyword'] . '%');
                    break;
                case 3:
                    //TODO  推荐码
                    $query->where('recommend_code_user.recommend_code', 'like', '%' . $param['keyword'] . '%');
                    break;
                default :
                    break;
            }
        }
        return $query;
    }

    private static function whereConditionRefundByBookingOrder($query, $param) {

        $initiate_refund = 0; //发起退款  默认全部
        if (isset($param['initiate_refund']) && !empty($param['initiate_refund'])) {
            if ($param['initiate_refund'] == 1) {  //用户
                $initiate_refund = 1;
            } else {
                $initiate_refund = 2;
            }
        }


        //必要条件 全部为退款状态
        $query->whereNotIn('booking_order.STATUS', ['NEW', 'PYD', 'CSD']);

        // 按时间搜索

        if ($initiate_refund == 1) {  //用户
            if (!empty($param['start_time'])) {
                $query->where('order_refund.add_time', '>=', strtotime($param['start_time'] . "00:00:00"));
            }
            if (!empty($param['end_time'])) {
                $query->where('order_refund.add_time', '<=', strtotime($param['end_time'] . "23:59:59"));
            }
            $query->whereNotNull('order_refund.order_refund_id');
        } elseif ($initiate_refund == 2) {  //臭美人员
            if (!empty($param['start_time'])) {
                $query->where('booking_salon_refund.created_at', '>=', $param['start_time'] . "00:00:00");
            }
            if (!empty($param['end_time'])) {
                $query->where('booking_salon_refund.created_at', '<=', $param['start_time'] . "23:59:59");
            }
            $query->whereNotNull('booking_salon_refund.id');
        } else {  //全部
            if (!empty($param['start_time'])) {
                $query->where('booking_salon_refund.created_at', '>=', $param['start_time'] . "00:00:00")
                        ->where('order_refund.add_time', '>=', strtotime($param['start_time'] . "00:00:00"));
            }
            if (!empty($param['end_time'])) {
                $query->where('booking_salon_refund.created_at', '<=', $param['end_time'] . "23:59:59")
                        ->where('booking_salon_refund.created_at', '<=', $param['end_time'] . "23:59:59");
            }
        }

        //支付方式
        if (isset($param['pay_type']) && $param['pay_type']) {
            $query->where('fundflow.pay_type', $param['pay_type']);
        }
        //付款状态
//        if (isset($param['state']) && $param['state']) {
//            $state_str = explode(",", $param['state']);
//            if (count($state_str) == 3) { //全选
//                $query->whereIn('booking_order.STATUS', $state_str)->where("order_refund.status", '!=', 2);
//            } elseif (count($state_str) == 1 && $state_str[0] == 'RFE') {  //仅选择了退款失败
//                $query->where("order_refund.status", 3);
//            } else {
//                $query->whereIn('booking_order.STATUS', $state_str)->where("order_refund.status", 1);
//            }
//        }
        if (isset($param['state']) && $param['state']) {
            $state_str = explode(",", $param['state']);
            if (count($state_str) == 3) { //全选
                $state_str[] = "RFD-OFL";
                $query->whereIn('booking_order.STATUS', $state_str);
            } elseif ($param['state'] == "RFE") {//失败
                if ($initiate_refund != 2) {
                    $query->where("order_refund.status", 3);
                } else {
                    $query->where('booking_order.STATUS', $param['state']);
                }
            } elseif ($param['state'] == "RFD") {//完成
                $query->whereIn('booking_order.STATUS', [
                    'RFD',
                    'RFD-OFL'
                ]);
            } elseif ($param['state'] == "RFN") { //退款中(申请退款)
                $query->where('booking_order.STATUS', $param['state']);
            }
        }


        if (isset($param['key']) && isset($param['keyword']) && $param['key'] && !empty($param['keyword'])) {
            switch ($param['key']) {
                case 1:
                    $query->where('booking_order.booker_phone', 'like', '%' . $param['keyword'] . '%');
                    break;
                case 2:
                    // TODO  预约号
                    $query->where('booking_order.BOOKING_SN', 'like', '%' . $param['keyword'] . '%');
                    break;
                case 3:
                    //TODO  推荐码
                    $query->where('recommend_code_user.recommend_code', 'like', '%' . $param['keyword'] . '%');
                    break;
                default :
                    break;
            }
        }
        return $query;
    }

    public static function makeupRefundDetail($order_refund_id) {
        if (!$order_refund_id) {
            throw new ApiException('找不到退款单信息', ERROR::REFUND_FLOW_LOST);
        }
        $fields = [
            //order_refund
            'order_refund.ordersn',
            'order_refund.user_id',
            'order_refund.money',
            'order_refund.opt_user_id',
            'order_refund.rereason',
            'order_refund.add_time',
            'order_refund.opt_time',
            'order_refund.status',
            'order_refund.booking_sn',
            'order_refund.item_type',
            'order_refund.rereason',
            'order_refund.other_rereason',
        ];
        //booking_order
        $booking_order_fields = [
            'booking_order.booking_date',
            'booking_order.status',
            'booking_order.paied_time',
            'booking_order.booker_name',
            'booking_order.booker_phone',
            'booking_order.booking_date',
            'booking_order.payable',
            'booking_order.paied_time',
            'booking_order.booker_sex',
        ];
        //booking_order_item
        $booking_order_item_fields = [
            'booking_order_item.item_name',
            'booking_order_item.price',
        ];
        // fundflow
        $fundflow_fields = [
            'fundflow.pay_type'
        ];
        // payment_log
        $payment_log_fields = [
            'payment_log.payment_sn',
        ];
        $refund = self::select($fields)->where('order_refund_id', $order_refund_id)->where('item_type', '!=', 'MF')->first();
        if (empty($refund)) {
            throw new ApiException("退款单 [{$order_refund_id}] 不存在", ERROR::REFUND_NOT_EXIST);
        }
        $bookingOrder = BookingOrder::select($booking_order_fields)->where('order_sn', $refund->ordersn)->first();
        $bookingOrderItem = BookingOrderItem::select($booking_order_item_fields)->where('order_sn', $refund->ordersn)->get()->toArray();
        $fundflow = Fundflow::select($fundflow_fields)->where('record_no', $refund->ordersn)->first();
        $paymentLog = PaymentLog::select($payment_log_fields)->where('ordersn', $refund->ordersn)->first();
        $optUser = !empty($refund->opt_user_id) ? Manager::find($refund->opt_user_id)->name : '';
        //推荐码
        $recommendInfo = RecommendCodeUser::where('user_id', $refund->user_id)->whereIn('type', ['2', '3'])->first();
        // 接待信息
        $bookingReceive = BookingReceive::where('booking_sn', $refund->booking_sn)->first();
        $receive = [
            'arrive_at' => '',
            'update_booking_date' => '',
            'remark' => '',
            'receiver' => '',
            'create_at' => ''
        ];
        if (!empty($bookingReceive)) {
            $receive['update_booking_date'] = $bookingReceive->update_booking_date;
            $receive['remark'] = $bookingReceive->remark;
            $receive['receiver'] = !empty($bookingReceive->uid) ? Manager::where('id', $bookingReceive->uid)->first()->name : ''; //TODO 查询具体的人姓名
            $receive['create_at'] = (string) $bookingReceive->created_at; //TODO 查询具体的人姓名
            $receive['arrive_at'] = $bookingReceive->arrive_at;
        }
        //如果在退款中   查询是否是退款失败
        if ($bookingOrder->status == 'RFN' && $refund->status == 3) {
            $bookingOrder->status = 'RFE';
        }
        $reason = implode(',', Mapping::getBeautyRefundRereasonNames(explode(',', $refund->rereason)));
        $reason = !empty($reason) ? $reason . "," . $refund->other_rereason : $refund->other_rereason;
        $res = [
            'ordersn' => $refund->ordersn,
            'booking_sn' => $refund->booking_sn,
            'booker_phone' => $bookingOrder->booker_phone,
            'booker_name' => $bookingOrder->booker_name,
            'booking_order_item' => $bookingOrderItem,
//            'item_name' => $bookingOrderItem->item_name,
//            'price' => $bookingOrderItem->price,
            'booking_date' => $bookingOrder->booking_date,
            'status' => $bookingOrder->status,
            'booker_sex' => $bookingOrder->booker_sex,
            // 预约金支付信息
            'pay_type' => $fundflow->pay_type,
            'payment_sn' => $paymentLog->payment_sn, //流水号
            'payable' => $bookingOrder->payable, //流水号
            'paied_time' => $bookingOrder->paied_time, //流水号
            'pay_status' => 1, //支付状态 
            // 退款信息
            'refund_from' => 1, //用户
            'rereason' => $reason,
            'refund_desc' => $reason,
            'refund_type' => $fundflow->pay_type,
            'money' => $refund->money,
            'add_time' => date("Y-m-d H:i:s", $refund->add_time),
            'opt_time' => !empty($refund->opt_time) ? date("Y-m-d H:i:s", $refund->opt_time) : '', //操作时间
            'complete_time' => !empty($refund->opt_time) ? date("Y-m-d H:i:s", $refund->opt_time) : '', //操作时间
            'opt_user' => $optUser, //审批人
            'recommend_code' => !empty($recommendInfo) ? $recommendInfo->recommend_code : '',
            'receive' => $receive
        ];
        return $res;
    }

    public static function detail($id) {
        $base = BookingOrder::where('ID', $id)->first();

        if (empty($base)) {
            throw new ApiException("预约单{$id} 不存在", ERROR::ORDER_NOT_EXIST);
        }

        $base = $base->toArray();
        $ordersn = $base['ORDER_SN'];
        $manager_id = $base['CUSTOMER_SERVICE_ID'];
        $item_fields = ['ORDER_SN', 'ITEM_ID', 'ITEM_NAME', 'AMOUNT', 'PAYABLE'];
        $beauty_item_fields = ['order_sn', 'item_id', 'item_name', 'norm_id', 'norm_name', 'amount', 'to_pay_amount'];
        $items = BookingOrderItem::where('ORDER_SN', $ordersn)->get($item_fields)->toArray();
        $beauty_items = BeautyOrderItem::where('order_sn', $ordersn)->get($beauty_item_fields)->toArray();
        $fundflows = Fundflow::where('record_no', $ordersn)->get(['record_no', 'pay_type'])->toArray();
        $payment_log = PaymentLog::where('ordersn', $ordersn)->first(['ordersn', 'tn', 'amount']);
        $recommend = RecommendCodeUser::where('user_id', $base['USER_ID'])->whereIn('type', [2, 3])->first(['id', 'user_id', 'recommend_code']);
        $salon_refund = BookingSalonRefund::getByBookingId($id);
        $base['manager'] = null;
        if (!empty($manager_id)) {
            $base['manager'] = Manager::getBaseInfo($manager_id);
        }
        $help_info = BookingOrder::getHelpUserInfo($base['SUBSTITUTOR'], $base['RECOMMENDER']);
        if (empty($payment_log)) {
            $payment_log = NULL;
        } else {
            $payment_log = $payment_log->toArray();
        }

        $change_status = NULL;
        $order_refund = BookingOrder::getOrderRefundInfo($salon_refund, $base, $fundflows, $ordersn, $change_status);
        if (!empty($change_status)) {
            $base['STATUS'] = $change_status;
        }

        $item_amount = 0;
        $is_virtual_beauty = false;
        if (empty($beauty_items)) {
            $is_virtual_beauty = true;
            $item_ids = array_map("intval", array_column($items, "ITEM_ID"));
            $beauty_items = BookingReceive::getItemNormInfoAtFirst($item_ids);
        }
        $to_pay_amounts = array_map("floatval", array_column($beauty_items, "to_pay_amount"));
        $item_amount = array_sum($to_pay_amounts);
        if ($is_virtual_beauty) {
            $beauty_items = [];
        }

        $base['item_amount'] = $item_amount;
        if (empty($recommend)) {
            $recommend = NULL;
        } else {
            $recommend = $recommend->toArray();
        }
        if (!empty($salon_refund)) {
            $salon_refund['manager'] = [];
        }

        return [
            'order' => $base,
            'help_info' => $help_info,
            'order_item' => $items,
            'fundflow' => $fundflows,
            'payment_log' => $payment_log,
            'recommend' => $recommend,
            'beauty_order_item' => $beauty_items,
            'makeup' => BeautyMakeup::getByBookingId($id),
            'booking_bill' => BookingBill::getByBookingId($id),
            'booking_cash' => BookingCash::getByBookingId($id),
            'booking_receive' => BookingReceive::getByBookingId($id),
            'booking_salon_refund' => $salon_refund,
            'order_refund' => $order_refund,
        ];
    }

}
