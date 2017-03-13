<?php
/**
 * 交易后台相关的接口(搜索部分)
 * 
 */
namespace App;

use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use DB;

class TransactionSearchApi
{
    /**
     * 订单列表
     * @param array $params
     */
    public static function searchOfOrder($params)
    {
        $bases = self::getConditionOfOrder($params);
        // 页数
        $page = isset($params['page']) ? max(intval($params['page']), 1) : 1;
        $size = isset($params['page_size']) ? max(intval($params['page_size']), 1) : 20;
        AbstractPaginator::currentPageResolver(function () use($page)
        {
            return $page;
        });
        $total_money = "";        
        $res = $bases->paginate($size)->toArray();
//         if($res['total']<=2000) //两千条以上的不统计
//         {
//             $total_money = self::countOfOrder($params);
//         }
//         $res['total_money'] = $total_money;
        unset($res['next_page_url']);
        unset($res['prev_page_url']);
        return $res;
    }
    
    /**
     * 臭美券列表
     * @param array $params
     */
    public static function searchOfTicket($params)
    {        
        $bases = self::getConditionOfTicket($params);
        // 页数
        $page = isset($params['page']) ? max(intval($params['page']), 1) : 1;
        $size = isset($params['page_size']) ? max(intval($params['page_size']), 1) : 20;
        AbstractPaginator::currentPageResolver(function () use($page)
        {
            return $page;
        });
        $res = $bases->paginate($size)->toArray();
    
        $res['data'] = self::makeTicketOtherInfo(json_decode(json_encode($res['data'],true),true));
//         $money_info = ['priceall_ori'=>'','actuallyPay'=>''];
//         if($res['total']<=2000)
//         {
//             $money_info = self::countOfTicket($params);
//         }
//         $res['all_amount'] = $money_info['priceall_ori'];
//         $res['paied_amount'] = $money_info['actuallyPay'];
        unset($res['next_page_url']);
        unset($res['prev_page_url']);
        return $res;
    }
    
    /**
     * 退款单列表
     * @param array $params
     */
    public static function searchOfRefund($params)
    {
        $bases = self::getConditionOfRefund($params);
        // 页数
        $page = isset($params['page']) ? max(intval($params['page']), 1) : 1;
        $size = isset($params['page_size']) ? max(intval($params['page_size']), 1) : 20;
        AbstractPaginator::currentPageResolver(function () use($page)
        {
            return $page;
        });
        $res = $bases->paginate($size)->toArray();
//         $refund_money = '';
//         if($res['total']<=2000)
//         {
//             $refund_money =  self::countOfRefund($params);
//         }
//         $res['refund_money'] = $refund_money;
        unset($res['next_page_url']);
        unset($res['prev_page_url']);
        return $res;
    }
    
    /**
     * 订单详情
     * @param int $id
     */
    public static function orderDetail($id)
    {
        $base = Order::where('orderid',$id)->select(['ordersn','orderid','user_id','priceall','salonid','actuallyPay','shopcartsn'])->first();
        if(empty($base))
        {
            throw new ApiException("订单 [{$id}] 不存在", ERROR::ORDER_NOT_EXIST);
        }
        $baseArr = $base->toArray();
        $orderItem = OrderItem::where("orderid",$id)->select(['order_item_id','itemname','ordersn'])->first();
        if(empty($orderItem)) //没订单项目
        {
            throw new ApiException("订单项目 orderid [{$id}] 不存在", ERROR::ORDER_ITEM_NOT_EXIST);
        }
        $orderItemArr = $orderItem->toArray();
        $ticket = OrderTicket::where("order_item_id",$orderItemArr['order_item_id'])->select(['order_ticket_id','ticketno','user_id'])->first();
        if(empty($ticket))//没有臭美券
        {
            throw new ApiException("臭美券 order_item_id [".$orderItemArr['order_item_id']."] 不存在", ERROR::TICKET_NOT_EXIST);           
        }
        $ticketArr = $ticket->toArray();
        $res = self::getDetailInfo($baseArr, $orderItemArr, $ticketArr);
        return $res;
    }
    
    /**
     * 臭美券详情
     * @param int $id
     */
    public static function ticketDetail($id)
    {
        $base = OrderTicket::where('order_ticket_id',$id)->select(['order_ticket_id','ticketno','user_id','order_item_id'])->first();
        if(empty($base))
        {
            throw new ApiException("臭美券 order_ticket_id [{$id}] 不存在", ERROR::TICKET_NOT_EXIST);
        }
        $baseArr = $base->toArray();
        
        $orderItem = OrderItem::where("order_item_id",$baseArr['order_item_id'])->select(['order_item_id','orderid','itemname','ordersn'])->first();
        if(empty($orderItem))
        {
            throw new ApiException("订单项目 order_item_id [".$baseArr['order_item_id']."] 不存在", ERROR::ORDER_ITEM_NOT_EXIST);
        }
        $orderItemArr = $orderItem->toArray();
        
        $order = Order::where('orderid',$orderItemArr['orderid'])->select(['ordersn','orderid','user_id','priceall','salonid','actuallyPay','shopcartsn'])->first();
        if(empty($order))
        {
            throw new ApiException("订单 orderid [".$orderItemArr['orderid']."] 不存在", ERROR::ORDER_NOT_EXIST);
        }
        $orderArr = $order->toArray();
        $res = self::getDetailInfo($orderArr, $orderItemArr, $baseArr);
        return $res;
    }
    
    /**
     * 退款详情
     * @param int $id
     */    
    public static function refundDetail($id)
    {
        $base = OrderRefund::where('order_refund_id',$id)->select(['ordersn'])->first();
        if(empty($base))
        {
            throw new ApiException("退款单 [{$id}] 不存在", ERROR::REFUND_NOT_EXIST);
        }
        $baseArr = $base->toArray();
        
        $order = Order::where('ordersn',$baseArr['ordersn'])->select(['ordersn','orderid','user_id','priceall','salonid','actuallyPay','shopcartsn'])->first();
        if(empty($order))
        {
            throw new ApiException("订单 ordersn [".$baseArr['ordersn']."] 不存在", ERROR::ORDER_NOT_EXIST);
        }
        $orderArr = $order->toArray();        
        $orderItem = OrderItem::where("orderid",$orderArr['orderid'])->select(['order_item_id','itemname','ordersn'])->first();
        if(empty($orderItem)) //没订单项目
        {
            throw new ApiException("订单项目 orderid [".$orderArr['orderid']."] 不存在", ERROR::ORDER_ITEM_NOT_EXIST);
        }
        $orderItemArr = $orderItem->toArray();
        $ticket = OrderTicket::where("order_item_id",$orderItemArr['order_item_id'])->select(['order_ticket_id','ticketno','user_id'])->first();
        if(empty($ticket))//没有臭美券
        {
            throw new ApiException("臭美券 order_item_id [".$orderItemArr['order_item_id']."] 不存在", ERROR::TICKET_NOT_EXIST);           
        }
        $ticketArr = $ticket->toArray();
        $res = self::getDetailInfo($orderArr, $orderItemArr, $ticketArr);
        return $res;
    }
    
    public static function getDetailInfo($order,$orderItem,$ticket)
    {
        if(empty($order) || empty($orderItem) || empty($ticket))
        {
            return null;
        }
        $orderid = $order['orderid'];
        $ordersn = $order['ordersn'];
        $ticketno = $ticket['ticketno'];
        $ticket_id = $ticket['order_ticket_id'];
        $uid = $order['user_id'];
        $salon_id = $order['salonid'];
       
        //订单流水
        $paymentlog = PaymentLog::where("ordersn",$ordersn)->select(['ordersn','tn'])->first();
        //用户
        $user = User::where("user_id",$uid)->select(['username','mobilephone'])->first();   
        //店铺    
        $salon = Salon::where('salonid',$salon_id)->select(['salonname'])->first();
        //金额构成
        $fundflows = Fundflow::where("record_no",$ordersn)->where("ticket_no",$ticketno)->where("code_type",2)->get(['pay_type','money']);
        //动态
        $trends = OrderTicketTrends::where("ticketno",$ticketno)->orderBy("add_time","ASC")->get(['add_time','status','remark']);
        //代金券
        $vouchers = Voucher::where("vOrderSn",$ordersn)->select(['vId','vSn','vcSn','vUseMoney','vUseEnd','vStatus','vUseTime','vAddTime'])->first();
      
        //佣金
        $commission = CommissionLog::where('ordersn',$ordersn)->select(['ordersn','amount','rate','grade'])->first();
        
        //店铺邀请码
        $salonRecommendCode = Dividend::where('salon_id',$salon_id)->select(['recommend_code'])->first();

        //用户邀请码
        $recommendCode = RecommendCodeUser::where('user_id',$uid)->join('dividend',function($join){
            $join->on('recommend_code_user.recommend_code','=','dividend.recommend_code')->where('dividend.recommend_code','<>',1);
        })->leftJoin('salon',function($join){
            $join->on('salon.salonid','=','recommend_code_user.salon_id');
        })->select(['recommend_code_user.recommend_code','salon.salonname'])->first();
       
        //设备信息
        $shopcartsn =  $order['shopcartsn'];
        $request_log_code = $ordersn;
        if(!empty($shopcartsn))
        {
            $request_log_code = $shopcartsn;
        }
        $paltform = RequestLog::getLogByOrdersn($request_log_code,['DEVICE_UUID','DEVICE_OS','DEVICE_MODEL','DEVICE_NETWORK','VERSION','OPENID','DEVICE_TYPE']); 
        
        $paymentlogArr = null;
        $userArr = null;
        $salonArr = null;
        $fundflowArr = [];
        $trendArr = [];
        $voucherArr = null;
        $commissionArr = null;
        $recommendCodeArr = null;
        $salonRecommendCodeArr = null;
        if(!empty($paymentlog))
        {
            $paymentlogArr = $paymentlog->toArray();
        }
        if(!empty($user))
        {
            $userArr = $user->toArray();
        }
        if(!empty($salon))
        {
            $salonArr = $salon->toArray();
        }
        if(!empty($fundflows))
        {
            $fundflowArr = $fundflows->toArray();
        }
        if(!empty($trends))
        {
            $trendArr = $trends->toArray();
            foreach ($trendArr as &$trend)
            {
                $status = $trend['status'];
                $remark = $trend['remark'];
                $str = "";
                if($status == 6 ||  $status == 8)
                {
                    if($status == 6)
                    {
                        $str = "申请退款";
                    }
                    if($status == 8)
                    {
                        $str = "退款拒绝";
                    }
                    if(!empty($remark)) 
                    {
                        $str .= "({$remark})";
                    }
                    $trend['remark'] = $str;
                }
            }
        }
        if(!empty($vouchers))
        {
            $voucherArr = $vouchers->toArray();
            if(empty($voucherArr['vUseTime']))
            {
                $vId = $voucherArr['vId'];
                $voucherTrend = VoucherTrend::where('vId',$vId)->where('vStatus',2)->orderBy('vAddTime','DESC')->select(['vAddTime'])->first();
                if(!empty($voucherTrend))
                {
                    $voucherTrendArr  = $voucherTrend->toArray();
                    $voucherArr['vUseTime'] =$voucherTrendArr['vAddTime'];
                }    
            }        
        }
        if(!empty($commission))
        {
            $commissionArr = $commission->toArray();
        }
        if(!empty($recommendCode))
        {
            $recommendCodeArr = $recommendCode->toArray();
        }
        if(!empty($salonRecommendCode))
        {
            $salonRecommendCodeArr = $salonRecommendCode->toArray();
        }
        
        $res = [            
            'order'=>$order,
            'item'=>$orderItem,
            'ticket'=>$ticket,
            'user'=>$userArr,
            'salon'=>$salonArr,
            'paymentlog'=>$paymentlogArr,
            'fundflows'=>$fundflowArr, 
            'trends'=>$trendArr,
            'vouchers'=>$voucherArr,
            'commission'=>$commissionArr,
            'recommend_code'=>$recommendCodeArr,
            'salonRecommendCode'=>$salonRecommendCodeArr,
            'platform'=>$paltform,
        ];
        return $res;
    }
    

    public static  function getConditionOfOrder($params)
    {
        $salon_fields = [
            'salonid',
            'salonname'
        ];
        $fundflow_fields = [
            'record_no',
            'pay_type',
        ];
        $user_fields = [
            'user_id',
            'username',
            'mobilephone',
        ];
        $base_fields = [
            'order.orderid',
            'order.ordersn',
            'order.priceall',
            'order.salonid',
            'order.add_time',
            'order.pay_time',
            'order.user_id',
            'order.ispay',
        ];
        
        $order_by_fields = [
            'orderid',
            'ordersn',
            'priceall',
            'add_time',
            'pay_time',
            'ispay',
        ];
        
        $orderBase = Order::select($base_fields);
        
        self::makeWhereOfOrder($orderBase, $params);
        
        $orderBase->with([
            'user' => function ($q) use($user_fields)
            {
                $q->get($user_fields);
            }
        ]);
        
        $orderBase->with([
            'salon' => function ($q) use($salon_fields)
            {
                $q->get($salon_fields);
            }
        ]);
        
        $orderBase->with([
            'fundflow' => function ($q) use($fundflow_fields)
            {
                $q->where("code_type",TransactionWriteApi::REFUND_CODE_TYPE_OF_CUSTOM)->get($fundflow_fields);
            }
        ]);
        
        
        // 排序
        if (isset($params['sort_key']) && in_array($params['sort_key'], $order_by_fields)) {
            $order = $params['sort_key'];
        } else {
            $order = "orderid";
        }
        
        if (isset($params['sort_type']) && strtoupper($params['sort_type']) == "ASC") {
            $order_by = "ASC";
        } else {
            $order_by = "DESC";
        }
        
        return $orderBase->orderBy($order, $order_by);
    }
    
    public static  function getConditionOfTicket($params)
    {
        $select_fields = [
            'cm_order_ticket.order_ticket_id as order_ticket_id',
            'cm_order_ticket.order_item_id as order_item_id',
            'cm_order_ticket.ticketno as ticketno',
            'cm_order_ticket.add_time as add_time',
            'cm_order_ticket.use_time as use_time',
            'cm_order_ticket.user_id as user_id',
            'cm_order_ticket.status as status',
//             'cm_order_item.ordersn as ordersn',
//             'cm_order_item.salonid as salonid',
//             'cm_order.priceall_ori as priceall_ori',
//             'cm_order.priceall as priceall',
//             'cm_order.actuallyPay as actuallyPay',
//             'cm_order.shopcartsn as shopcartsn',
        ];
        
        $base = OrderTicket::selectRaw(implode(',',$select_fields));
        
        
        self::makeWhereOfTicket($base, $params);
        $salon_fields = [
            'salonid',
            'salonname'
        ];
        $fundflow_fields = [
            'record_no',
            'pay_type',
        ];
        
        $voucher_fields = [
            'vOrderSn',
            'vcSn',
            'vSn',
            'vUseMoney',
        ];
        
        $user_fields = [
            'user_id',
            'username',
            'mobilephone',
        ];
        
//         $base->with([
//             'user' => function ($q) use($user_fields)
//             {
//                 $q->get($user_fields);
//             }
//         ]);
        
//         $base->with([
//             'salon' => function ($q) use($salon_fields)
//             {
//                 $q->get($salon_fields);
//             }
//         ]);
        
//         $base->with([
//             'fundflow' => function ($q) use($fundflow_fields)
//             {
//                 $q->get($fundflow_fields);
//             }
//         ]);
        
//         $base->with([
//             'voucher' => function ($q) use($voucher_fields)
//             {
//                 $q->where('vStatus',2)->get($voucher_fields);
//             }
//         ]);        
        $base->orderBy('order_ticket_id','DESC');
        return $base;
    }
    
    public static function getRefundDataView()
    {
        return OrderRefund::leftJoin('order',function($join){
            $join->on('order.ordersn','=','order_refund.ordersn');
        })
        ->leftJoin('order_item',function($join){
            $join->on('order.orderid','=','order_item.orderid');
        });
    }
    
    public static  function getConditionOfRefund($params)
    {

        $select_fields = [
            'cm_order_refund.order_refund_id as order_refund_id',
            'cm_order_refund.ordersn as ordersn',
            'cm_order_refund.ticketno as ticketno',
            'cm_order_refund.user_id as user_id',
            'cm_order_refund.salonid as salonid',
            'cm_order_refund.add_time as add_time',
            'cm_order_refund.money as refund_money',
            'cm_order_refund.retype as retype',
            'cm_order.priceall_ori as priceall_ori',
            'cm_order.actuallyPay as actuallyPay',
            'cm_order.shopcartsn as shopcartsn',
            'cm_order.add_time as buy_time',
            'cm_order.status as order_status',
        ];
        $base = self::getRefundDataView();
        
        $base->selectRaw(implode(",", $select_fields));
        
       self::makeWhereOfRefund($base, $params);
       
       $salon_fields = [
           'salonid',
           'salonname'
       ];
       $fundflow_fields = [
           'record_no',
           'pay_type',
       ];
       
       $voucher_fields = [
           'vOrderSn',
           'vcSn',
           'vSn',
       ];
       
       $user_fields = [
           'user_id',
           'username',
           'mobilephone',
       ];
       
       $base->with([
           'user' => function ($q) use($user_fields)
           {
               $q->get($user_fields);
           }
       ]);
       
       $base->with([
           'salon' => function ($q) use($salon_fields)
           {
               $q->get($salon_fields);
           }
       ]);
       
       $base->with([
           'fundflow' => function ($q) use($fundflow_fields)
           {
               $q->where("code_type",TransactionWriteApi::REFUND_CODE_TYPE_OF_CUSTOM)->get($fundflow_fields);
           }
       ]);
       
       $base->with([
           'voucher' => function ($q) use($voucher_fields)
           {
               $q->where('vStatus',2)->get($voucher_fields);
           }
       ]);
       $base->orderBy('order_refund_id','DESC');
       return $base;
    }
    
    public static function countOfOrder($params)
    {
        $base = Order::selectRaw("SUM(`priceall`) as `priceall`");
        self::makeWhereOfOrder($base, $params);
        $res = $base->first();
        if(!empty($res))
        {
             return floatval($res->priceall);
        }
        return 0;
    }
    
    public static function countOfTicket($params)
    {
        $base=OrderTicket::selectRaw("SUM(`cm_order`.`actuallyPay`) as `actuallyPay`,SUM(`cm_order`.`priceall_ori`) as `priceall_ori`");
        self::makeWhereOfTicket($base, $params);
        $had_join_order_item = false;
        $joins = $base->getQuery()->joins;
        if(!empty($joins))
        {  
            foreach($joins as $join)
            {
                if($join->table == "order_item")
                {
                    $had_join_order_item =true;
                    break;
                }
            }
        }
        if(!$had_join_order_item)
        {
            $base->join('order_item',function($join){
                $join->on('order_ticket.order_item_id','=','order_item.order_item_id');
            });
        }
        $base->join('order',function($join){
            $join->on('order.orderid','=','order_item.orderid');
        });
        $res = $base->first();
        if(!empty($res))
        {
            return ['actuallyPay'=>floatval($res->actuallyPay),'priceall_ori'=>floatval($res->priceall_ori)];
        }
        return ['actuallyPay'=>0,'priceall_ori'=>0];
    }
    
    public static function countOfRefund($params)
    {
        $base = self::getRefundDataView();
        $base->selectRaw("SUM(`cm_order_refund`.`money`) as `refund_money`");
        self::makeWhereOfRefund($base, $params);
        $res = $base->first();
        if(!empty($res))
        {
            return floatval($res->refund_money);
        }
        return 0;
    }
    
    public static function makeWhereOfOrder(&$orderBase,$params)
    {
        // 按时间搜索
        if (isset($params['pay_time_min']) && !empty($params['pay_time_min']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($params['pay_time_min']))) {
            $orderBase->where('order.add_time', ">=", strtotime(trim($params['pay_time_min'])));
        }
        if (isset($params['pay_time_max']) && !empty($params['pay_time_min']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($params['pay_time_max']))) {
            $orderBase->where('order.add_time', "<=", strtotime(trim($params['pay_time_max'])) + 86399 );
        }
        
        //支付方式
        if(isset($params['pay_type']) && !empty($params['pay_type']))
        {
            $pay_types = explode(",", $params['pay_type']);
            $pay_types = array_map("intval", $pay_types);
            $pay_types = array_unique($pay_types);
            
            if(count($pay_types) == 1)
            {
                if($pay_types[0] !== 0)
                {
                    $pay_type = $pay_types[0];
                    $orderBase->join('fundflow',function($join) use ($pay_type){
                        $join->on('order.ordersn','=','fundflow.record_no')->where('fundflow.code_type','=',2)->where('fundflow.pay_type','=',$pay_type);
                    });
                }
            }
            if(count($pay_types) > 1)//多种支付方式都需要存在时
            {
                $fundflow_str = null;
                foreach ($pay_types as $pay_type)
                {      
                    if(empty($fundflow_str))
                    {
                        $fundflow_str = "select `record_no` from `cm_fundflow` where `code_type` = 2 and `pay_type`  = {$pay_type} AND `record_no`";
                    }  
                    else 
                    {
                        $fundflow_str = "select `record_no` from `cm_fundflow` where `code_type` = 2 and `pay_type`  = {$pay_type} AND `record_no` IN (".$fundflow_str.") ";
                    }                 
                }
                if(!empty($fundflow_str))
                {
                     $orderBase->whereRaw("cm_order.ordersn IN ($fundflow_str)");
                }               
            }
        }
        
        // 付款状态
        if(isset($params['pay_state']) && !empty($params['pay_state']))
        {
            $orderBase->where('order.ispay', $params['pay_state']);
        }
        
        // 关键字搜索
        if (isset($params['key']) && ! empty($params['key']) && isset($params['keyword']) && ! empty(trim($params['keyword']))) {
            $key = intval($params['key']);
            $word = trim($params['keyword']);
            $keyword = '%' . str_replace([
                "%",
                "_"
            ], [
                "\\%",
                "\\_"
            ],$word ) . "%";
            
            if ($key == 1) //订单号
            {                
                $orderBase->where("order.ordersn",'like',$keyword);
            }
            elseif ($key == 2) //用户臭美号
            {
                $orderBase->join('user',function($join) use($word){
                    $join->on('order.user_id','=','user.user_id')->where('user.username','=',$word);
                });
            }
            elseif ($key == 3) //用户手机号
            {
                $orderBase->join('user',function($join) use($word){
                    $join->on('order.user_id','=','user.user_id')->where('user.mobilephone','=',$word);
                });
            }
            elseif ($key == 4) //店铺名
            {
                $orderBase->join('salon',function($join) use($keyword){
                    $join->on('order.salonid','=','salon.salonid')->where('salon.salonname','like',$keyword);
                });
            }
        }        
    }
    
    public static function makeWhereOfTicket(&$base,$params)
    {       
        // 按时间搜索
        $time_key_str = "";
        if(isset($params['time_key']))
        {
            if($params['time_key'] == 1)
            {
                $time_key_str = "order_ticket.use_time";
            }
            if($params['time_key'] == 2)
            {
                $time_key_str = "order_ticket.add_time";
            }
        }
        if (isset($params['min_time']) && !empty($params['min_time']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($params['min_time']))) {
            $base->where($time_key_str, ">=", strtotime(trim($params['min_time'])));
        }
        if (isset($params['max_time']) && !empty($params['max_time']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($params['max_time']))) {
            $base->where($time_key_str, "<=", strtotime(trim($params['max_time'])) + 86399 )->where($time_key_str, ">", 0 );
        }
        
        // 付款状态
        if(isset($params['state']) && !empty($params['state']))
        {
            $base->where("order_ticket.status", $params['state']);
        }
        
        // 关键字搜索
        if (isset($params['key']) && ! empty($params['key']) && isset($params['keyword']) && ! empty(trim($params['keyword']))) {
            $key = intval($params['key']);
            $word = trim($params['keyword']);
            $keyword = '%' . str_replace([
                "%",
                "_"
            ], [
                "\\%",
                "\\_"
            ], $word) . "%";
            if ($key == 1) //臭美券密码
            {
                $base->where("order_ticket.ticketno",'like',$keyword);
            }
            elseif ($key == 2) //用户手机号
            {                
                $base->join('user',function($join) use($word){
                    $join->on('order_ticket.user_id','=','user.user_id')->where('user.mobilephone','=',$word);
                });
            }
            elseif ($key == 3) //店铺名
            {
                $base->join('order_item',function($join){
                    $join->on('order_ticket.order_item_id','=','order_item.order_item_id');
                })->join('salon',function($join) use ($keyword){
                    $join->on('salon.salonid','=','order_item.salonid')->where('salon.salonname','like',$keyword);
                });
                //$base->whereRaw("cm_order_item.salonid IN (SELECT `salonid` FROM `cm_salon` WHERE `salonname` LIKE '{$keyword}')");
                
            }
            elseif ($key == 4) //用户设备号
            {
                $base->join('order_item',function($join){
                    $join->on('order_ticket.order_item_id','=','order_item.order_item_id');
                })->join('request_log',function($join) use ($keyword){
                    $join->on('request_log.ORDER_SN','=','order_item.ordersn')->where('request_log.DEVICE_UUID','like',$keyword)->where('request_log.TYPE','=','PLC');
                });
                //$base->whereRaw("cm_order_item.ordersn in (SELECT distinct(`ORDER_SN`) FROM `cm_request_log` WHERE `TYPE` = 'PLC' AND `DEVICE_UUID` LIKE '{$keyword}')");
            }
            elseif ($key == 5) //代金券编码
            {
                $base->join('order_item',function($join){
                    $join->on('order_ticket.order_item_id','=','order_item.order_item_id');
                })->join('voucher',function($join) use ($keyword){
                    $join->on('voucher.vOrderSn','=','order_item.ordersn')->where('voucher.vSn','like',$keyword);
                });
                //$base->whereRaw("cm_order_item.ordersn in (SELECT `vOrderSn` FROM `cm_voucher` WHERE `vSn` LIKE '{$keyword}')");
            }
            elseif ($key == 6) //活动编码
            {
                $base->join('order_item',function($join){
                    $join->on('order_ticket.order_item_id','=','order_item.order_item_id');
                })->join('voucher',function($join) use ($keyword){
                    $join->on('voucher.vOrderSn','=','order_item.ordersn')->where('voucher.vcSn','like',$keyword);
                });
                //$base->whereRaw("cm_order_item.ordersn in (SELECT `vOrderSn` FROM `cm_voucher` WHERE `vcSn` LIKE '{$keyword}')");
            }
        }
    }
    
    public static function makeWhereOfRefund(&$base,$params)
    {
        //必要条件 退款状态 为可用   必须为美发
        $base->where("order_refund.status", TransactionWriteApi::REFUND_STATUS_OF_NORMAL)->where("order_refund.item_type",'MF');
        
        // 按时间搜索
        if (isset($params['refund_min_time']) && !empty($params['refund_min_time']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($params['refund_min_time']))) {
            $base->where("order_refund.add_time", ">=", strtotime(trim($params['refund_min_time'])));
        }
        if (isset($params['refund_max_time']) && !empty($params['refund_max_time']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($params['refund_max_time']))) {
            $base->where("order_refund.add_time", "<=", strtotime(trim($params['refund_max_time'])) + 86399 );
        }
    
        // 付款状态
        if(isset($params['state']) && !empty($params['state']))
        {
           $state_ids = explode(",", $params['state']);
           $state_ids = array_map("intval",$state_ids);
           $base->whereIn('order.status', $state_ids);    
        }
    
        // 关键字搜索
        if (isset($params['key']) && ! empty($params['key']) && isset($params['keyword']) && ! empty(trim($params['keyword']))) {
            $key = intval($params['key']);
            $word = trim($params['keyword']);
            $keyword = '%' . str_replace([
                "%",
                "_"
            ], [
                "\\%",
                "\\_"
            ], $word) . "%";
            if ($key == 1) //订单号
            {
                $base->where("order_refund.ordersn",'like',$keyword);
            }
            elseif ($key == 2) //用户手机号
            {
                
                $base->join('user',function($join) use($word){
                    $join->on('order_refund.user_id','=','user.user_id')->where('user.mobilephone','=',$word);
                });              
            }
            elseif ($key == 3) //用户臭美号
            {
                $base->join('user',function($join) use($word){
                    $join->on('order_refund.user_id','=','user.user_id')->where('user.username','=',$word);
                });
            }
            elseif ($key == 4) //店铺名
            {
                $base->join('salon',function($join) use($keyword){
                    $join->on('order_refund.salonid','=','salon.salonid')->where('salon.salonname','like',$keyword);
                });
            }
        }
    }
    
    public static function makeTicketOtherInfo($datas)
    {
        $start_time = microtime(true);
        $order_item_fields = [
            'order_item_id',
            'salonid',
            'ordersn',
            'itemname',
        ];
        $order_fields = [
            'ordersn',
            'priceall as priceall_ori',
            'priceall',
            'actuallyPay',
            'shopcartsn'
        ];
        $salon_fields = [
            'salonid',
            'salonname'
        ];
        $fundflow_fields = [
            'record_no',
            'pay_type',
        ];
        
        $voucher_fields = [
            'vOrderSn',
            'vcSn',
            'vSn',
            'vUseMoney',
        ];
        
        $user_fields = [
            'user_id',
            'username',
            'mobilephone',
        ];
        $others = [
            'order_item'=>['add_to_base'=>['salonid','ordersn','itemname'],'relation'=>'order_item_id'],
            'order'=>['add_to_base'=>['priceall_ori','priceall','actuallyPay','shopcartsn'],'relation'=>'ordersn'],
            'salon'=>['relation'=>'salonid'],
            'fundflow'=>['relation'=>'ordersn','make_by'=>Utils::GROUP_MAKE_BY_ONE_TO_MANY],
            'voucher'=>['relation'=>'ordersn'],
            'user'=>['relation'=>'user_id'],
            'platform'=>['relation'=>'ordersn'],
        ];
        $uids = array_column($datas, "user_id");
     
        $order_item_ids = array_column($datas, "order_item_id");
        $order_item_res = OrderItem::whereIn("order_item_id",$order_item_ids)->select($order_item_fields)->get()->toArray();
        $ordersns = array_column($order_item_res, "ordersn");
        $salon_ids = array_column($order_item_res,"salonid");
        $others['order_item']['datas'] = Utils::column_to_key("order_item_id",$order_item_res);
        unset($order_item_res);
        
        $order_res = Order::whereIn("ordersn",$ordersns)->select($order_fields)->get()->toArray();
        $others['order']['datas'] = Utils::column_to_key("ordersn",$order_res);
        unset($order_res);
        
        $salon_res = Salon::whereIn("salonid",$salon_ids)->select($salon_fields)->get()->toArray();
        $others['salon']['datas'] =  Utils::column_to_key("salonid",$salon_res);
        unset($salon_res);
        
        $fundflow_res = Fundflow::whereIn("record_no",$ordersns)->where('code_type',TransactionWriteApi::REFUND_CODE_TYPE_OF_CUSTOM)->select($fundflow_fields)->get()->toArray();
        $others['fundflow']['datas'] =  Utils::column_to_group("record_no",$fundflow_res);
        unset($fundflow_res);
        
        $voucher_res =  Voucher::whereIn("vOrderSn",$ordersns)->select($voucher_fields)->get()->toArray();
        $others['voucher']['datas'] =  Utils::column_to_key("vOrderSn",$voucher_res);
        unset($voucher_res);
        
        $user_res =  User::whereIn("user_id",$uids)->select($user_fields)->get()->toArray();
        $others['user']['datas'] =  Utils::column_to_key("user_id",$user_res);
        unset($user_res);
        
        $platform_res =  RequestLog::getLogsByOrdersns($ordersns,['ORDER_SN','DEVICE_UUID']);
        $others['platform']['datas'] =  Utils::column_to_key("ORDER_SN",$platform_res);
        unset($platform_res);
             
        return Utils::groupMake($datas, $others);
    }
    
    public static function addPaymentLogInfos($bases,$paymentlog)
    {
        $payment_index = Utils::column_to_key("ordersn",$paymentlog);
        foreach($bases as &$base)
        {
            $ordersn = $base['ordersn'];
            if(isset($payment_index[$ordersn]))
            {
                $base['payment_log'] = $payment_index[$ordersn];
            }
            else
            {
                $base['payment_log'] = null;
            }
        }
        return $bases;
    }
}