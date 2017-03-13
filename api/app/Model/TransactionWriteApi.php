<?php
/**
 * 交易后台相关的接口(业务逻辑相关部分)
 *
 */
namespace App;

use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\ThriftHelperModel;
class TransactionWriteApi
{
    /**
     * 退款单状态  正常
     * @var unknown
     */
    CONST REFUND_STATUS_OF_NORMAL = 1;
    
    /**
     * 退款单状态  取消
     * @var unknown
     */
    CONST REFUND_STATUS_OF_CANCLE = 2;
    
    /**
     * 退回的路径 退回余额
     * @var unknown
     */
    CONST REFUND_RETYPE_REFUND_TO_BALANCE = 2;
    
    /**
     * 订单状态 未使用
     * @var unknown
     */
    CONST ORDER_STATUS_OF_UNUSED = 2;
    
    /**
     * 订单状态 使用部分
     * @var unknown
     */
    CONST ORDER_STATUS_OF_USED_PART = 3;
    
    /**
     * 订单状态 已使用
     * @var unknown
     */
    CONST ORDER_STATUS_OF_USED = 4;
    
    /**
     * 订单状态 申请退款
     * @var unknown
     */
    CONST ORDER_STATUS_OF_APPLIED = 6;
    
    /**
     * 订单状态 退款完成
     * @var unknown
     */
    CONST ORDER_STATUS_OF_REFUND_COMPLETED = 7;
    
    /**
     * 退款失败
     * @var unknown
     */    
    CONST ORDER_STATUS_REFUND_FAILED = 9;
    
    /**
     * 订单状态 退款中
     * @var unknown
     */
    CONST ORDER_STATUS_OF_IN_REFUND = 10;
    
    /**
     * 臭美券 状态  未使用
     * @var unknown
     */
    CONST TICKET_STATUS_OF_UNUSED = 2;
    
    /**
     * 臭美券 状态  已使用
     * @var unknown
     */
    CONST TICKET_STATUS_OF_USED = 4;
    
    /**
     * 臭美券 状态   申请退款
     * @var unknown
     */
    CONST TICKET_STATUS_OF_APPLIED = 6;
    
    /**
     * 臭美券 状态   退款完成
     * @var unknown
     */
    CONST TICKET_STATUS_OF_REFUND_COMPLETED = 7;
    
    /**
     * 臭美券 状态   退款中
     * @var unknown
     */
    CONST TICKET_STATUS_OF_IN_REFUND = 8;
    
    /**
     * 退回银联
     * @var unknown
     */
    CONST REFUND_TO_UNION  = 1;
    
    /**
     * 退回支付宝
     * @var unknown
     */
    CONST REFUND_TO_ALIPAY  = 2;
    
    /**
     * 退回支付宝
     * @var unknown
     */
    CONST REFUND_TO_WX  = 3;
    
    /**
     * 退回余额
     * @var unknown
     */
    CONST REFUND_TO_BALANCE = 4;
    
    /**
     * 退回红包
     * @var unknown
     */
    CONST REFUND_TO_HONGBAO = 5;
    
    /**
     * 退回优惠券
     * @var unknown
     */
    CONST REFUND_TO_YOUHUI = 6;

    /**
     * 退回易联
     * @var unknown
     */
    CONST REFUND_TO_YILIAN = 10;
  
    /**
     * 流水类型  消费
     * @var unknown
     */
    CONST REFUND_CODE_TYPE_OF_CUSTOM = 2;
    
    /**
     * 券动态状态  拒绝 
     * @var unknown
     */
    CONST ORDER_TRENDS_STATUS_OF_REJECT = 8;
    
    /**
     * 流水中的退款状态 未退款
     */
    CONST FUNDFLOW_REFUND_STATE_OF_UNCOMPLETED = 1;
    
    /**
     * 流水中的退款状态 已退款
     */
    CONST FUNDFLOW_REFUND_STATE_OF_COMPLETED = 2;
    
    /**
     * 退款通过
     * 
     * @param array $ids            
     */
    public static function accpet($ids)
    {   
        $refunds = self::checkRefundStatus($ids);
        $ordersns = array_column($refunds, "ordersn");
        $refund_indexes = Utils::column_to_key("ordersn",$refunds);
        unset($refunds);
        
        $fundflow = Fundflow::where('code_type',self::REFUND_CODE_TYPE_OF_CUSTOM)->where('refund_state','<>',self::FUNDFLOW_REFUND_STATE_OF_COMPLETED)->whereIn('record_no',$ordersns)->select(['ticket_no','record_no','pay_type','user_id','money'])->get();
        if(empty($fundflow))
        {
            throw new ApiException("找不到退款的支付流水信息",ERROR::REFUND_FLOW_LOST);
        }        
        $fundflowArr = $fundflow->toArray();        
        unset($fundflow);
        
        $ticket_nos = array_column($fundflowArr, "ticket_no");
        $ticket_nos = array_filter($ticket_nos);       
        if (count($ticket_nos) < 1) 
        {
            throw new ApiException("找不到退款的流水中的臭美券信息",ERROR::REFUND_FLOW_LOST);
        }                
        $tickets = OrderTicket::whereIn('ticketno',$ticket_nos)->get();        
        if(empty($tickets))
        {
            throw new ApiException("找不到退款单对应的臭美券",ERROR::REFUND_TICKET_LOST);
        }        
        $ticketArr = $tickets->toArray();
        unset($tickets);
        if(!self::checkArrayColumns($ticketArr, [self::TICKET_STATUS_OF_UNUSED,self::TICKET_STATUS_OF_APPLIED,self::TICKET_STATUS_OF_IN_REFUND], "status"))
        {
            throw new ApiException("退款单对应的臭美券状态不正确",ERROR::REFUND_TICKET_STATE_WRONG);
        }
        
        $payments = PaymentLog::whereIn('ordersn',$ordersns)->select(['ordersn','tn','device','batch_no','alipay_updated'])->get();
        
        if(empty($payments))
        {
            throw new ApiException("退款单找不到对应的payment log 信息",ERROR::REFUND_PAYMENT_LOG_LOST);
        }
        
        $paymentArr = $payments->toArray();
                
        $payment_indexes = Utils::column_to_key("ordersn", $paymentArr);
        
        $refund_items = self::getRefundItems($fundflowArr,$payment_indexes,$refund_indexes);
        // 状态修改为退款中
        self::modifOrderStatusInRefund($ordersns);
        
        //余额
        if(isset($refund_items['balance']) && count($refund_items['balance'])>0)
        {
            $res['balance'] = self::refundOfBalance($refund_items['balance'],$refund_items['specail']);
        }
        //红包
        if(isset($refund_items['hongbao']) && count($refund_items['hongbao'])>0)
        {
            $res['hongbao'] = self::refundOfHongbao($refund_items['hongbao'],$refund_items['specail']);
        }
        
        //优惠码
        if(isset($refund_items['youhui']) && count($refund_items['youhui'])>0)
        {
            $res['youhui'] = self::refundOfYouhui($refund_items['youhui'],$refund_items['specail']);
        }
        
        //网银
        if(isset($refund_items['union']) && count($refund_items['union'])>0)
        {
            $res['union'] = self::refundOfUnion($refund_items['union']);
        }
        
        //微信
        if(isset($refund_items['wx']) && count($refund_items['wx'])>0)
        {
            $res['wx'] = self::refundOfWx($refund_items['wx']);
        }
        //易联
        if(isset($refund_items['yilian']) && count($refund_items['yilian'])>0)
        {
            $res['yilian'] = self::refundOfYilian($refund_items['yilian']);
        }
        //支付宝
        if(isset($refund_items['alipay']) && count($refund_items['alipay'])>0)
        {
            $res['alipay'] = self::refundOfAlipay($refund_items['alipay']);
        }
        return $res;
    }

    /**
     * 退款拒绝
     * 
     * @param array $ids            
     */
    public static function reject($ids, $remark)
    {
        $refunds = OrderRefund::whereIn("order_refund_id",$ids)->where('status',self::REFUND_STATUS_OF_NORMAL)->get(['ordersn','ticketno']);
        if(empty($refunds))
        {
            throw  new ApiException("退款id下取不到信息", ERROR::PARAMS_LOST);
        }
        $refundsArr = $refunds->toArray();
        unset($refunds);
        if(count($refundsArr)<1)
        {
            throw  new ApiException("找不到对应的退款信息", ERROR::PARAMS_LOST);
        }
        $ticketnos = array_column($refundsArr, "ticketno");
        $ordersns = array_column($refundsArr, "ordersn");
        $ticket_num = OrderTicket::whereIn("ticketno",$ticketnos)->whereNotIn("status",[self::TICKET_STATUS_OF_USED,self::TICKET_STATUS_OF_REFUND_COMPLETED])->count();
        if($ticket_num !== count($ticketnos))
        {
            throw  new ApiException("部分退款的臭美券状态不正确", ERROR::REFUND_STATE_WRONG);
        }
        
        OrderTicket::whereIn("ticketno",$ticketnos)->whereNotIn("status",[self::TICKET_STATUS_OF_USED,self::TICKET_STATUS_OF_REFUND_COMPLETED])->update(['status'=>self::TICKET_STATUS_OF_UNUSED]);
        
        Order::whereIn("ordersn",$ordersns)->whereNotIn("status",[self::ORDER_STATUS_OF_USED,self::ORDER_STATUS_OF_USED_PART,self::ORDER_STATUS_OF_REFUND_COMPLETED])->update(['status'=>self::ORDER_STATUS_OF_UNUSED]);
        
        OrderRefund::whereIn("ticketno",$ticketnos)->where("status",self::REFUND_STATUS_OF_NORMAL)->update(['status'=>self::REFUND_STATUS_OF_CANCLE]);
        $now_time = time();
        foreach($refundsArr as $refund)
        {
            $ordersn = $refund['ordersn'];
            $ticketno = $refund['ticketno'];
            OrderTicketTrends::create([
            'ordersn'=>$ordersn,
            'ticketno'=>$ticketno,
            'add_time'=>$now_time,
            'status'=>self::ORDER_TRENDS_STATUS_OF_REJECT,
            'remark'=>$remark,
            ]);
        }    
        return true;
    }

    /**
     * 退款的回调
     */
    public static function callBackOfAlipay()
    {
        $args = func_get_args();        
        //成功 则改变退款的状态
        if(isset($args[0])
            && isset($args[0]['success'])
            && isset($args[0]['batch_no'])
            && isset($args[0]['success_num'])
            && !empty($args[0]['batch_no'])
            && count($args[0]['success']) > 0)
        {
            $items = $args[0]['success'];
            $tns = Utils::get_column_array("tn",$items);
            $batch_no = $args[0]['batch_no'];
            if (count($tns) > 0) {
                $logs = PaymentLog::whereIn("tn", $tns)->where('batch_no', $batch_no)->get(['tn', 'ordersn']);
                if (!empty($logs)) {
                    $logArr = $logs->toArray();
                    $ordersns = array_column($logArr, "ordersn");
                    self::modifOrderStatusRefundCompleted($ordersns, "支付宝退款完成");
                    self::modiSoldNumMinus($ordersns);
                }
            }
        }
    }
    
    /**
     * 将订单标记为退款中状态
     * @param array $ordersns
     * @return boolean
     */
    public static function modifOrderStatusInRefund($ordersns)
    {
        $ordersns = array_unique($ordersns);
        $count_ordersns= count($ordersns);
        if($count_ordersns < 1)
        {
            return false;
        }
         
        //判断当前流水表中是否有记录存在
        $fundflows = Fundflow::whereIn('record_no',$ordersns)->where('code_type',self::REFUND_CODE_TYPE_OF_CUSTOM)->get(['record_no','ticket_no']);
        if(empty($fundflows))
        {
            throw new ApiException("找不到订单流水信息",ERROR::REFUND_FLOW_LOST);
        }
        $fundflowsArr = $fundflows->toArray();
        unset($fundflows);
        $fundflow_ordersns = array_column($fundflowsArr, "record_no");
        $fundflow_ordersns = array_unique($fundflow_ordersns);
        
        $diff = array_diff($ordersns, $fundflow_ordersns);       
         
        if(count($diff) > 0) //有订单的流水不存在
        {
            throw new ApiException("部分订单流水信息丢失",ERROR::REFUND_FLOW_LOST);
        }
         
        Order::whereIn('status',[
        self::ORDER_STATUS_OF_UNUSED,
        self::ORDER_STATUS_OF_APPLIED,
        self::ORDER_STATUS_REFUND_FAILED,
        11
        ])->whereIn('ordersn',$ordersns)->update(['status'=>self::ORDER_STATUS_OF_IN_REFUND]);
        //改为键 避免重复
        $ticketnos = array_column($fundflowsArr, "ticket_no");
        $fund_flows = Utils::column_to_key('record_no',$fundflowsArr);        
        $now_time = time();
        OrderTicket::whereIn('ticketno',$ticketnos)->update(['status'=>self::TICKET_STATUS_OF_IN_REFUND]);   
        foreach($fund_flows as $ordersn => $flow)
        {
            $ticketno = $flow['ticket_no'];
            OrderTicketTrends::create([
            'ordersn'=>$ordersn,
            "ticketno"=>$ticketno,
             "add_time"=>$now_time,
             "status"=>self::ORDER_STATUS_OF_IN_REFUND,
             "remark"=>"退款中",
            ]);        
        }
        return true;
    }
    
    /**
     * 将订单改为已退款状态
     * @param array $ordersns
     */
    public static function modifOrderStatusRefundCompleted($ordersns,$remark="退款完成")
    {
        $ordersns = array_unique($ordersns);
        if(count($ordersns)<1)
        {
            return false;
        }
        $now_time = time();
        OrderRefund::whereIn('ordersn',$ordersns)->update(['opt_time'=>$now_time]);
        Order::whereIn('ordersn',$ordersns)->update(['status'=>self::ORDER_STATUS_OF_REFUND_COMPLETED]);
        OrderItem::whereIn('ordersn',$ordersns)->update(['status'=>self::ORDER_STATUS_OF_REFUND_COMPLETED]);      
         
        $fundflows = Fundflow::whereIn('record_no',$ordersns)->where('code_type',self::REFUND_CODE_TYPE_OF_CUSTOM)->get(['record_no','ticket_no']);
        $fundflowsArr = $fundflows->toArray();
        self::modifFundflowCompleted($ordersns);
        //改为键 避免重复
        $fund_flows = Utils::column_to_key('record_no',$fundflowsArr);
        $ticketnos = array_column($fundflowsArr, "ticket_no");
        OrderTicket::whereIn('ticketno',$ticketnos)->update(['status'=>self::TICKET_STATUS_OF_REFUND_COMPLETED]);
        foreach($fund_flows as $ordersn => $flow)
        {
            $ticketno = $flow['ticket_no'];
            OrderTicketTrends::create([
            'ordersn'=>$ordersn,
            "ticketno"=>$ticketno,
            "add_time"=>$now_time,
            "status"=>self::ORDER_STATUS_OF_REFUND_COMPLETED,
            "remark"=>$remark,
            ]);
        }
        return true;
    }
    
    
   

    /**
     * 
     */
    public static function modifFundflowCompleted($ordersns,$pay_type = null)
    {
        $fundflow = Fundflow::whereIn('record_no',$ordersns);
        if(!empty($pay_type))
        {
            $fundflow->where('pay_type',$pay_type);
        }
        $fundflow->update(['refund_state'=>self::FUNDFLOW_REFUND_STATE_OF_COMPLETED]);
    }
    
    /**
     * 减少销量
     * @param array $ordersns
     */
    public static function modiSoldNumMinus($ordersns)
    {
        if(count($ordersns) < 1)
        {
            return false;
        }
        $order_items = OrderItem::whereIn('ordersn',$ordersns)->get(['salonid','itemid']);
        if(empty($order_items))
        {
            return false;
        }
        $order_items_arr = $order_items->toArray();
        foreach ($order_items_arr as $item)
        {
            SalonItem::where('salonid',$item['salonid'])->where('itemid',$item['itemid'])->decrement('sold',1);
        }
        return true;
    }

    /**
     * 检查允许的值
     * @param array $items
     * @param array $allowValues
     * @param string|int $column_name
     * @return bool
     */
    public static function checkArrayColumns($items,$allowValues,$column_name)
    {
        $items = array_column($items, $column_name);
        foreach ($items as $item)
        {
            if(!in_array($item,$allowValues))
            {
                return false;
            }
        }
        return true;
    }
    
    /**
     * @param array $ids
     * @throws ApiException
     * @return array
     */
    private static function checkRefundStatus($ids)
    {        
     
        $count = count($ids);
        if ($count < 1) {
            throw new ApiException( "退款id不能为空", ERROR::PARAMS_LOST);
        }
        $refunds = OrderRefund::whereIn('order_refund_id',$ids)->where('status',self::REFUND_STATUS_OF_NORMAL)->select(['ordersn','retype','rereason'])->get();
        if(empty($refunds))
        {
            throw new ApiException( "找不到相关的退款单信息", ERROR::REFUND_STATE_WRONG);
        }
        $refundArr =  $refunds->toArray();
        unset($refunds);
        if(count($refundArr) !== $count)
        {
            throw new ApiException( "您选的部分订单不存在退款信息", ERROR::REFUND_STATE_WRONG);
        }
        $ordersns = array_column($refundArr, "ordersn");
        
        $order = Order::whereIn("ordersn",$ordersns)->whereIn("status",[self::ORDER_STATUS_OF_UNUSED,self::ORDER_STATUS_OF_APPLIED,8,self::ORDER_STATUS_REFUND_FAILED,self::ORDER_STATUS_OF_IN_REFUND])->selectRaw("count(*) as num")->first();
        if(empty($order))
        {
            throw new ApiException( "找不到相关的订单信息", ERROR::REFUND_STATE_WRONG);
        }
        if(intval($order->num) !== $count)
        {
            throw new ApiException( "关联的订单状态不正确", ERROR::REFUND_STATE_WRONG);
        }
        unset($order);
        return $refundArr;
    }
    
    /**
     * 
     * @param array $fundflows
     * @param array $paymentlogs
     * @param array $refunds
     * @return array 
     */
    private static function getRefundItems($fundflows,$paymentlogs,$refunds)
    {
        $wx_refund_url_of_web = env("WX_REFUND_URL_OF_WEB",null);
        $wx_refund_url_of_sdk = env("WX_REFUND_URL_OF_SDK",null);
        if(empty($wx_refund_url_of_web) || empty($wx_refund_url_of_sdk))
        {
            throw new ApiException("获取配置信息 [WX_REFUND_URL_OF_WEB] 或者 [WX_REFUND_URL_OF_SDK] 出错",ERROR::CONFIG_LOST);
        }   
        $res = [
            'union'=>[],
            'alipay'=>[],
            'wx'=>[],
            'balance'=>[],
            'hongbao'=>[],
            'youhui'=>[],
            'yilian'=>[],
            'specail'=>[],
        ];
        foreach ($fundflows as $flow) {
            $ordersn = $flow['record_no'];
            
            if(!isset($refunds[$ordersn]) || !isset($paymentlogs[$ordersn]))
            {                
                throw new ApiException("退款的关键信息不全",ERROR::REFUND_LOST_PRIMARY_INFO);
            }
            $retype = intval($refunds[$ordersn]['retype']); 
            $pay_type = intval($flow['pay_type']);
            if(!in_array($pay_type, [
                self::REFUND_TO_UNION,
                self::REFUND_TO_ALIPAY,
                self::REFUND_TO_WX,
                self::REFUND_TO_BALANCE,
                self::REFUND_TO_HONGBAO,
                self::REFUND_TO_YOUHUI,
                self::REFUND_TO_YILIAN]))
            {
                continue;
            }
            
            if($retype == self::REFUND_RETYPE_REFUND_TO_BALANCE)
            {
                $pay_type = self::REFUND_TO_BALANCE;
            }
            $user_id = $flow['user_id'];
            $money = $flow['money'];
            $reason = implode(',',Mapping::getRefundRereasonNames(explode(',',$refunds[$ordersn]['rereason'])));        
           
            $device = null;
            $tn = "";
            $batch_no = "";
            $alipay_updated = 0;
            if (isset($paymentlogs[$ordersn])) {
                $tn = $paymentlogs[$ordersn]['tn'];
                $device = $paymentlogs[$ordersn]['device'];
                $batch_no = $paymentlogs[$ordersn]['batch_no'];
                $alipay_updated = intval($paymentlogs[$ordersn]['alipay_updated']);
            }
            switch ($pay_type) {
                case self::REFUND_TO_UNION:
                    if (empty($tn)) 
                    {
                        throw  new ApiException("ordersn '{$ordersn}' can not find tn",ERROR::REFUND_CANT_FIND_TN);                   
                    }               
                    $res['union'][] = [
                        'ordersn' => $ordersn,
                        'user_id' => $user_id,
                        'money' => $money,
                        'tn' => $tn
                    ];
                    $res['specail'][] = $ordersn;
                    break;
                case self::REFUND_TO_ALIPAY:
                    if (empty($tn)) {
                        throw  new ApiException("ordersn '{$ordersn}' can not find tn",ERROR::REFUND_CANT_FIND_TN);
                    }
                    if (! empty($batch_no) && (time() - $alipay_updated) < 7200) {
                        throw  new ApiException("ordersn '{$ordersn}' 已于" . date("Y-m-d H:i:s", $alipay_updated) . "开始退款.请不要请求太频繁",ERROR::UNKNOWN_ERROR);
                    }                
                    $res['alipay'][]= [
                        'tn' => $tn,
                        "money" => $money,
                        "reason" => $reason,
                        "ordersn" => $ordersn
                    ];
                    $res['specail'][] = $ordersn;
                    break;
                case self::REFUND_TO_WX: 
                    if (empty($tn)) {
                        throw  new ApiException("ordersn '{$ordersn}' can not find tn",ERROR::REFUND_CANT_FIND_TN);
                    }
                    $url = $device == 2?$wx_refund_url_of_web:$wx_refund_url_of_sdk;                    
                    $res['wx'][] = [
                        'tn' => $tn,
                        "money" => $money,
                        "ordersn" => $ordersn,
                        'user_id' => $user_id,
                        'url' => $url
                    ];
                    $res['specail'][] = $ordersn;
                    break;
                case self::REFUND_TO_BALANCE: 
                   $res['balance'][] = [
                        "money" => $money,
                        "ordersn" => $ordersn,
                        'user_id' => $user_id,
                    ];
                    break;
                case self::REFUND_TO_HONGBAO: 
                    $res['hongbao'][] = [
                        "money" => $money,
                        "ordersn" => $ordersn,
                        'user_id' => $user_id,
                    ];
                    break;
                case self::REFUND_TO_YOUHUI:
                    $res['youhui'][] = [
                        "money" => $money,
                        "ordersn" => $ordersn,
                        'user_id' => $user_id,
                    ];
                    break;
                case self::REFUND_TO_YILIAN:
                    if (empty($tn)) {
                        throw  new ApiException("ordersn '{$ordersn}' can not find tn",ERROR::REFUND_CANT_FIND_TN);
                    }
                    $fund_ordersn_list[] = $ordersn;
                    $res['yilian'][] = [
                        'tn' => $tn,
                        "amount" => $money,
                        "ordersn" => $ordersn,
                        'user_id' => $user_id
                    ];
                    $res['specail'][] = $ordersn;
                    break;
            }
        }
        return $res;
    }
    
    /**
     * 网银退款
     */
    private static function refundOfUnion()
    {
        $url = env("UNIONPAY_REFUND_URL",null);
        if(empty($url))
        {
            throw new ApiException("获取配置信息 [UNIONPAY_REFUND_URL] 出错",ERROR::CONFIG_LOST);
        }
        $args = func_get_args();
        if(count($args)<1)
        {
            throw new ApiException("必要参数丢失",ERROR::UNKNOWN_ERROR);
        }
        $items = $args[0];
        $ret = [];
        $ret['info'] = '';
        foreach ($items as $item) {
            self::makeSignForPost($item);            
            $ret['info'] .= "订单号：".$item['ordersn'].", 退款：".$item['ordersn'].", 退款方式：银联\n";
            Utils::log("pay", date("Y-m-d H:i:s") ."\t [REQUEST] send data: ".json_encode($item)." \t url : {$url} \n","unionpay");
            $respnd = Utils::HttpPost($url, $item);
            Utils::log("pay", date("Y-m-d H:i:s") ."\t [RESPOND] return:  {$respnd} \n","unionpay");
            $ret['info'] .= $respnd . "\n";
        }
        $ret['info'] = nl2br($ret['info']);
        return $ret;
    }


    /**
     * 微信退款
     */
    protected static function refundOfWx()
    {
       $args = func_get_args();
       if (count($args) < 1) {
          throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
       }
       $items = $args[0];
       $ret = [];
       $ret['info'] = '';     
       $thrift = new ThriftHelperModel('','');
        $wechat_config = self::GetWechatConf(env('APP_ENV'));
        foreach ($items as $item) {
            $ordersn = $item['ordersn'];
            $money = $item['money'];
            $bountysn = '';
            $refundRequestParam = new \cn\choumei\thriftserver\service\stub\gen\WechatRefundRequestParam();
            $refundRequestParam->payMerchantId = $wechat_config['PAY_MERCHANT_ID'];
            $refundRequestParam->payMerchantPassword =  $wechat_config['PAY_MERCHANT_PASSWORD'];
            $refundRequestParam->payRefundUrl =  $wechat_config['PAY_REFUND_URL'];
            $refundRequestParam->payPartnerId =  $wechat_config['PAY_PARTNER_ID'];
            $refundRequestParam->payPartnerKey =  $wechat_config['PAY_PARTNER_KEY'];
            $refundRequestParam->h5AppId =  $wechat_config['H5_APP_ID'];
            $refundRequestParam->h5MerchantId =  $wechat_config['H5_MERCHANT_ID'];
            $refundRequestParam->h5RefundUrl =  $wechat_config['H5_REFUND_URL'];
            $refundRequestParam->h5PartnerKey =  $wechat_config['H5_PARTNER_KEY'];
            $refundRequestParam->orderSn = $ordersn;
            $refundRequestParam->bountySn = $bountysn;
            $refundRequestParam->amount = $money;
            if(isset($item['booking_sn'])){  //如果是定妆单
                $refundRequestParam->beautySn = $ordersn;
                $refundRequestParam->orderSn = '';  
            }
             Utils::log("pay", date("Y-m-d H:i:s") . "\t [REQUEST] send data: item[ " . json_encode($item) . "] config[".json_encode($wechat_config)."]  refundRequestParam[".json_encode($refundRequestParam)."]\n", "wechat");
//            print_r($refundRequestParam);exit;
            $refundResponseThrift = $thrift->request('trade-center', 'wechatRefund', array($refundRequestParam));  

            Utils::log("pay", date("Y-m-d H:i:s") . "\t [RESPOND] Response: ".json_encode($refundResponseThrift)." \n", "wechat");
            if (empty($refundResponseThrift) || !isset($refundResponseThrift['code'])) {
                $ret['info'] .= '调用微信退款服务失败\n';
            } elseif ($refundResponseThrift['code'] == '0') {
                $ret['info'] .= $ordersn.' 微信退款成功\n';               
            } else {
                $ret['info'] .= $ordersn.' 微信退款失败\n'.$refundResponseThrift['code'] . ': ' . (isset($refundResponseThrift['message'])?$refundResponseThrift['message']:'') ."\n";
            }
            unset($refundRequestParam);
        }
        $ret['info'] = nl2br($ret['info']);
        return $ret;
        
//         $args = func_get_args();
//         if (count($args) < 1) {
//             throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
//         }
//         $items = $args[0];
//         $ret = [];
//         $ret['info'] = '';
//         foreach ($items as $item) {
//             $url = $item['url'];
//             unset($item['url']);
//             self::makeSignForPost($item);
//             Utils::log("pay", date("Y-m-d H:i:s") . "\t [REQUEST] send data: " . json_encode($item) . " \t url : {$url} \n", "wechat");
//             $respnd = Utils::HttpPost($url, $item);
//             Utils::log("pay", date("Y-m-d H:i:s") . "\t [RESPOND] return:  {$respnd} \n", "wechat");
//             $ret['info'] .= $item['ordersn']." ".$respnd . "\n";
//         }
//         $ret['info'] = nl2br($ret['info']);
//         return $ret;
    }
    
    /**
     * 易联支付
     */
    protected static function refundOfYilian()
    {      
       $args = func_get_args();
        if (count($args) < 1) {
            throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
        }
        $items = $args[0];
        $ret = [];
        $ret['info'] = '';
        
        $thrift = new ThriftHelperModel('', '');
        
        $payeco_config = self::GetPayEcoConf(env('APP_ENV'));
        
        foreach ($items as $item) {
            $ordersn = $item['ordersn'];
            $extData = "orderSn={$ordersn}";
            $money =    $item['amount'];         
            $refundSn = $ordersn . 'Z' . time();            
            $refundRequestParam = new \cn\choumei\thriftserver\service\stub\gen\PayecoRefundRequestParam();
            $refundRequestParam->paymentSn = $refundSn;
            $refundRequestParam->amount = $money;
            $refundRequestParam->merchantId = $payeco_config['MERCHANT_ID'];
            $refundRequestParam->privateKey = $payeco_config['PRIVATE_KEY'];
            $refundRequestParam->publicKey = $payeco_config['PUBLIC_KEY'];
            $refundRequestParam->payecoUrl = $payeco_config['PAYECO_URL'];
            $refundRequestParam->extData = $extData;
            if(isset($item['booking_sn'])){
                $extData = "beautySn={$ordersn}";
                $refundRequestParam->extData=$extData;
            }
//            print_r($refundRequestParam);exit;
            $refundResponseThrift = $thrift->request('trade-center', 'payecoRefund', array(
                $refundRequestParam
            ));
            if (empty($refundResponseThrift) || !isset($refundResponseThrift['retCode'])) {
                $ret['info'] .= '调用易联退款服务失败\n';
            } elseif ($refundResponseThrift['retCode'] == '0000') {
                $ret['info'] .= $ordersn.' 易联退款成功\n';               
            } else {
                $ret['info'] .= $ordersn.' 易联退款失败\n'.$refundResponseThrift['retCode'] . ': '. (isset($refundResponseThrift['retMsg'])?$refundResponseThrift['retMsg']:'') ."\n";
            }
            unset($refundRequestParam);
        }
        $ret['info'] = nl2br($ret['info']);
        
        return $ret;
//         $url = env("YILIAN_REFUND_NOTIFY_URL",null);
//         if(empty($url))
//         {
//             throw new ApiException("获取配置信息 [YILIAN_REFUND_NOTIFY_URL] 出错",ERROR::CONFIG_LOST);
//         }
//         $args = func_get_args();
//         if (count($args) < 1) {
//             throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
//         }
//         $items = $args[0];
//         $ret = [];
//         $ret['info'] = "";
//         foreach ($items as $item) {
    
//             $argc = array();
//             $argc['body'] = $item;
//             $argc['to'] = 'refund';
//             $argc['type'] = 'Payeco';
    
//             $argStr = json_encode($argc);
//             $param['code'] = $argStr;
    
//             Utils::log("pay", date("Y-m-d H:i:s") . "\t [REQUEST] send data: " . json_encode($param) . " \t url : {$url} \n", "yilian");
//             $respnd = Utils::HttpPost($url, $param);
//             Utils::log("pay", date("Y-m-d H:i:s") . "\t [RESPOND] return:  {$respnd} \n", "yilian");    
//             $resDecode = json_decode($respnd, true);
//             if (isset($resDecode['result']) && $resDecode['result'] == 1) {
//                 $ret['info'] .= $item['ordersn'] . " 退款成功  退款方式 易联\n";
//             } else {
//                 $ret['info'] .= $item['ordersn'] . " 退款失败   退款方式 易联\n";
//             }
//         }
//         $ret['info'] = nl2br($ret['info']);
//         return $ret;
    }
    

    /**
     * 支付宝退款
     */
    protected static function refundOfAlipay()
    {
        $args = func_get_args(); 
        if (count($args) < 1) {
            throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
        }
        $items = $args[0];
        if(count($items)<1)
        {
            return [];
        }
        $booking_sns=  array_column($items,'booking_sn');
        if(empty($booking_sns)){
            $url = env("ALIPAY_REFUND_NOTIFY_URL",null);  
        }else{
            $url = env("ALIPAY_BEAUTY_REFUND_CALLBACK_URL",null);  //TODO   URL需要更换
        }
          
        if(empty($url))
        {
            throw new ApiException("获取配置信息 [ALIPAY_REFUND_NOTIFY_URL] 出错",ERROR::CONFIG_LOST);
        }
        
        $batch_no = AlipaySimple::getRandomBatchNo();   
        // 写入退款批次号
        self::UpdateAlipayBatchNo($batch_no, $items);
        
        $ret = [];
        $data = ['notify_url' => $url,'batch_no' => $batch_no,'detail_data' => $items];
        // 支付宝的表单提交 for debug
//        $ret['form_args'] = AlipaySimple::refund($data,AlipaySimple::REFUND_RETURN_TYPE_HTML);
        $ret['form_args'] = AlipaySimple::refund($data,AlipaySimple::REFUND_RETURN_TYPE_ARRAY);     
        return $ret;
    }
    
    /**
     * 余额退款
     */
    private static function refundOfBalance()
    {
        $args = func_get_args();
        if (count($args) < 1) {
            throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
        }
        $items = $args[0];
        $specail_ordersns = isset($args[1])?$args[1]:[];
        $ret = [];
        $ret['info'] = '';
        $all_ok_ordersns = [];
        foreach ($items as $item) {
            $ordersn = $item['ordersn'];
            if(!in_array($ordersn,$specail_ordersns))
            {
                $all_ok_ordersns[] =  $ordersn;
            }
            User::where('user_id',$item['user_id'])->increment("money",$item["money"]);
            $ret['info'] .= "订单号：{$ordersn}, 退款：{$item["money"]}, 退款方式：余额\n";
        }
        // 改为已完成状态
        $all_ordersns = array_column($items, "ordersn");
        self::modifFundflowCompleted($all_ordersns,self::REFUND_TO_BALANCE);
        self::modifOrderStatusRefundCompleted($all_ok_ordersns, "余额退款完成");
        // 销量减一
        self::modiSoldNumMinus($all_ordersns);
        $ret['info'] = nl2br($ret['info']);
        return $ret;
    }
    
    /**
     * 红包退款
     */
    private static function refundOfHongbao()
    {
        $args = func_get_args();
        if (count($args) < 1) {
            throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
        }
        $items = $args[0];
        $specail_ordersns = isset($args[1])?$args[1]:[];
        $ret = [];
        $ret['info'] = '';
        $all_ok_ordersns = [];
        foreach ($items as $item) {
            $ordersn = $item['ordersn'];
            if(!in_array($ordersn,$specail_ordersns))
            {
                $all_ok_ordersns[] =  $ordersn;
            }
            User::where('user_id',$item['user_id'])->increment("packetmoney",$item["money"]);
            $ret['info'] .= "订单号：{$ordersn}, 退款：{$item["money"]}, 退款方式：红包\n";
        }
        // 改为已完成状态
        $all_ordersns = array_column($items, "ordersn");
        self::modifFundflowCompleted($all_ordersns,self::REFUND_TO_HONGBAO);
        self::modifOrderStatusRefundCompleted($all_ok_ordersns, "红包退款完成");
        // 销量减一
        self::modiSoldNumMinus($all_ordersns);
        $ret['info'] = nl2br($ret['info']);
        return $ret;
    }
    
    /**
     * 优惠码
     */
    private static function refundOfYouhui()
    {
        $args = func_get_args();
        if (count($args) < 1) {
            throw new ApiException("必要参数丢失", ERROR::UNKNOWN_ERROR);
        }
        $items = $args[0];
        $specail_ordersns = isset($args[1])?$args[1]:[];
        $ret = [];
        $ret['info'] = '';
        $all_ok_ordersns = [];
        foreach ($items as $item) {
            $ordersn = $item['ordersn'];
            if(!in_array($ordersn,$specail_ordersns))
            {
                $all_ok_ordersns[] =  $ordersn;
            }
            User::where('user_id',$item['user_id'])->increment("couponmoney",$item["money"]);
            $ret['info'] .= "订单号：{$ordersn}, 退款：{$item["money"]}, 退款方式：优惠码\n";
        }
        // 改为已完成状态
        $all_ordersns = array_column($items, "ordersn");
        self::modifFundflowCompleted($all_ordersns,self::REFUND_TO_YOUHUI);
        self::modifOrderStatusRefundCompleted($all_ok_ordersns, "优惠码退款完成");      
        self::modiSoldNumMinus($all_ordersns);
        $ret['info'] = nl2br($ret['info']);
        return $ret;
    }
    
    /**
     * 为远程调用数据生成token
     * @param array $data
     */
    private static function makeSignForPost(&$data)
    {
        $data['sign'] = self::encryptionSign($data['ordersn'], $data['user_id'], $data['money'], $data['tn']);
    }
   
    /**
     * 加密key
     * @param unknown $ordersn
     * @param unknown $user_id
     * @param unknown $money
     * @param unknown $tn
     * @return string
     */
    private static function encryptionSign($ordersn, $user_id, $money, $tn)
	{
		return md5(md5($ordersn.$user_id.$money).$tn.'choumei.cn');	
	}
	
    /**
     * 写入支付宝的退款批次号
     * @param unknown $batch_no
     * @param unknown $items
     */
    private static function UpdateAlipayBatchNo($batch_no,$items)
    {
        foreach ($items as $item)
        {
            $tn = $item['tn'];
            $ordersn = $item['ordersn'];
            PaymentLog::where('tn',$tn)->where('ordersn',$ordersn)->update(
            ['batch_no'=>$batch_no,'alipay_updated'=>time()]
            );           
        }
    }

    /**
     * 获取微信的配置
     * 
     * @param string $env            
     * @return array
     */
    public static function GetWechatConf($env = 'prod')
    {
        $configs = [
            'local' => [
                'PAY_MERCHANT_ID' => '10037612',
                'PAY_MERCHANT_PASSWORD' => 'choumei88371180',
                'PAY_REFUND_URL' => 'https://api.mch.tenpay.com/refundapi/gateway/refund.xml',
                'PAY_PARTNER_ID' => '1224362901',
                'PAY_PARTNER_KEY' => '00d1362509914d5b7e6fcdfe2d2d2904',
                'H5_APP_ID' => 'wxdf5a63a0bcc5daed',
                'H5_MERCHANT_ID' => '1278948601',
                'H5_REFUND_URL' => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
                'H5_PARTNER_KEY' => 'FBZUCT6HVMA729XQDL8WGJN35ERYSP4K'
            ],
            'dev' => [
                'PAY_MERCHANT_ID' => '10037612',
                'PAY_MERCHANT_PASSWORD' => 'choumei88371180',
                'PAY_REFUND_URL' => 'https://api.mch.tenpay.com/refundapi/gateway/refund.xml',
                'PAY_PARTNER_ID' => '1224362901',
                'PAY_PARTNER_KEY' => '00d1362509914d5b7e6fcdfe2d2d2904',
                'H5_APP_ID' => 'wxdf5a63a0bcc5daed',
                'H5_MERCHANT_ID' => '1278948601',
                'H5_REFUND_URL' => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
                'H5_PARTNER_KEY' => 'FBZUCT6HVMA729XQDL8WGJN35ERYSP4K'
            ],
            'test' => [
                'PAY_MERCHANT_ID' => '10037612',
                'PAY_MERCHANT_PASSWORD' => 'choumei88371180',
                'PAY_REFUND_URL' => 'https://api.mch.tenpay.com/refundapi/gateway/refund.xml',
                'PAY_PARTNER_ID' => '1224362901',
                'PAY_PARTNER_KEY' => '00d1362509914d5b7e6fcdfe2d2d2904',
                'H5_APP_ID' => 'wxd4c590c3a2dad288',
                'H5_MERCHANT_ID' => '1243472202',
                'H5_REFUND_URL' => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
                'H5_PARTNER_KEY' => '31D9B01827FF7F850FE94A2FD0D7DE10',
            ],
            'uat' => [
                'PAY_MERCHANT_ID' => '10037612',
                'PAY_MERCHANT_PASSWORD' => 'choumei88371180',
                'PAY_REFUND_URL' => 'https://api.mch.tenpay.com/refundapi/gateway/refund.xml',
                'PAY_PARTNER_ID' => '1224362901',
                'PAY_PARTNER_KEY' => '00d1362509914d5b7e6fcdfe2d2d2904',
                'H5_APP_ID' => 'wxd4c590c3a2dad288',
                'H5_MERCHANT_ID' => '1243472202',
                'H5_REFUND_URL' => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
                'H5_PARTNER_KEY' => '31D9B01827FF7F850FE94A2FD0D7DE10',
            ],
            'prod' => [
                'PAY_MERCHANT_ID' => '10037612',
                'PAY_MERCHANT_PASSWORD' => 'choumei88371180',
                'PAY_REFUND_URL' => 'https://api.mch.tenpay.com/refundapi/gateway/refund.xml',
                'PAY_PARTNER_ID' => '1224362901',
                'PAY_PARTNER_KEY' => '00d1362509914d5b7e6fcdfe2d2d2904',
                'H5_APP_ID' => 'wx6cfe7d87206790b5',
                'H5_MERCHANT_ID' => '10037612',
                'H5_REFUND_URL' => 'https://api.mch.weixin.qq.com/secapi/pay/refund',
                'H5_PARTNER_KEY' => '31D9B01827FF7F850FE94A2FD0D7DE09',
            ]
        ];
        $key = strtolower($env);
        if (isset($configs[$key])) {
            return $configs[$key];
        }
        return $configs['prod'];
    }

    /**
     * 获取易联的配置
     * 
     * @param string $env            
     * @return array
     */
    public static function GetPayEcoConf($env = 'prod')
    {
        $configs = [
            'local' => [
                'MERCHANT_ID' => '502050000158',
                'PRIVATE_KEY' => 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALgBOGF2251xzolP0V4vhyVjr/2Qki8Xnok2vpuv1ls1GzWxp5swpg7FjPA3V2/gkZq5yOA943xgRydHb2iv39rnxX46iL/sxMUVXo6FrbRlTuQs4Grzfe3hTd95UJRpOjq1IkipN3ybP5UoC/a3OJpOfRDIgcD03QV53ddjno8PAgMBAAECgYEAkgXd4XL6vAq59jxSkeUz4hcnbhnR+O9Mj+XTBChZ+028xzKyKTVFQwnBXEz+5bkYs+pmIJbctjKNpP1Ky5BvG6gZLnbtallr+A8eZSWcvqnDJCcP2VJSRF9vAvwjXofoyMIy0Z533Lldy1KX5bxOk6eBxV55HJnOLZofyWC0NBECQQDfxgF2Wxt/dSPwcJvUQgs6zLLhyxggXMBS2EJ6a7SyMwh0jaZvmWGHNhHf1IyKYSNSYjM3thHGfChRDG+MD1tnAkEA0oEJnYFFACi91J8Dj70BD0okwqOjT+ZyjYMxDjNOZk/Zl95Vxs3o036QmspmjmsWYXsijedn6aVAVLjqQ4kOGQJAHz7nr14TXd2+cfFXYPED3mb8x1hzevlYhXja93sYlRVZJeUti0Gwg4/COS3VnfDoXLWHj0zl+IAXpRGGddkjGwJBAM2/Qd6o0wBs0d5X7es4GSkQlw2HU8Bsxdp7OB9hFmf58/v0XHKMH91X/47L9aGOGbn92LBKVc6QrmggtRh9hUECQQCowGcHum4dShGTAXZ/ZGyIWrRpaIjSMRUqKj2bemi8MWHT/WvNSnASsi8CrzEWtGREcfFtUkPZZOwy7VCpF/d+',
                'PUBLIC_KEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCRxin1FRmBtwYfwK6XKVVXP0FIcF4HZptHgHu+UuON3Jh6WPXc9fNLdsw5Hcmz3F5mYWYq1/WSRxislOl0U59cEPaef86PqBUW9SWxwdmYKB1MlAn5O9M1vgczBl/YqHvuRzfkIaPqSRew11bJWTjnpkcD0H+22kCGqxtYKmv7kwIDAQAB',
                'PAYECO_URL' => 'https://testmobile.payeco.com'
            ],
            'dev' => [
                'MERCHANT_ID' => '502050000158',
                'PRIVATE_KEY' => 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALgBOGF2251xzolP0V4vhyVjr/2Qki8Xnok2vpuv1ls1GzWxp5swpg7FjPA3V2/gkZq5yOA943xgRydHb2iv39rnxX46iL/sxMUVXo6FrbRlTuQs4Grzfe3hTd95UJRpOjq1IkipN3ybP5UoC/a3OJpOfRDIgcD03QV53ddjno8PAgMBAAECgYEAkgXd4XL6vAq59jxSkeUz4hcnbhnR+O9Mj+XTBChZ+028xzKyKTVFQwnBXEz+5bkYs+pmIJbctjKNpP1Ky5BvG6gZLnbtallr+A8eZSWcvqnDJCcP2VJSRF9vAvwjXofoyMIy0Z533Lldy1KX5bxOk6eBxV55HJnOLZofyWC0NBECQQDfxgF2Wxt/dSPwcJvUQgs6zLLhyxggXMBS2EJ6a7SyMwh0jaZvmWGHNhHf1IyKYSNSYjM3thHGfChRDG+MD1tnAkEA0oEJnYFFACi91J8Dj70BD0okwqOjT+ZyjYMxDjNOZk/Zl95Vxs3o036QmspmjmsWYXsijedn6aVAVLjqQ4kOGQJAHz7nr14TXd2+cfFXYPED3mb8x1hzevlYhXja93sYlRVZJeUti0Gwg4/COS3VnfDoXLWHj0zl+IAXpRGGddkjGwJBAM2/Qd6o0wBs0d5X7es4GSkQlw2HU8Bsxdp7OB9hFmf58/v0XHKMH91X/47L9aGOGbn92LBKVc6QrmggtRh9hUECQQCowGcHum4dShGTAXZ/ZGyIWrRpaIjSMRUqKj2bemi8MWHT/WvNSnASsi8CrzEWtGREcfFtUkPZZOwy7VCpF/d+',
                'PUBLIC_KEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCRxin1FRmBtwYfwK6XKVVXP0FIcF4HZptHgHu+UuON3Jh6WPXc9fNLdsw5Hcmz3F5mYWYq1/WSRxislOl0U59cEPaef86PqBUW9SWxwdmYKB1MlAn5O9M1vgczBl/YqHvuRzfkIaPqSRew11bJWTjnpkcD0H+22kCGqxtYKmv7kwIDAQAB',
                'PAYECO_URL' => 'https://testmobile.payeco.com'
            ],
            'test' => [
                'MERCHANT_ID' => '502050000158',
                'PRIVATE_KEY' => 'MIICeAIBADANBgkqhkiG9w0BAQEFAASCAmIwggJeAgEAAoGBALgBOGF2251xzolP0V4vhyVjr/2Qki8Xnok2vpuv1ls1GzWxp5swpg7FjPA3V2/gkZq5yOA943xgRydHb2iv39rnxX46iL/sxMUVXo6FrbRlTuQs4Grzfe3hTd95UJRpOjq1IkipN3ybP5UoC/a3OJpOfRDIgcD03QV53ddjno8PAgMBAAECgYEAkgXd4XL6vAq59jxSkeUz4hcnbhnR+O9Mj+XTBChZ+028xzKyKTVFQwnBXEz+5bkYs+pmIJbctjKNpP1Ky5BvG6gZLnbtallr+A8eZSWcvqnDJCcP2VJSRF9vAvwjXofoyMIy0Z533Lldy1KX5bxOk6eBxV55HJnOLZofyWC0NBECQQDfxgF2Wxt/dSPwcJvUQgs6zLLhyxggXMBS2EJ6a7SyMwh0jaZvmWGHNhHf1IyKYSNSYjM3thHGfChRDG+MD1tnAkEA0oEJnYFFACi91J8Dj70BD0okwqOjT+ZyjYMxDjNOZk/Zl95Vxs3o036QmspmjmsWYXsijedn6aVAVLjqQ4kOGQJAHz7nr14TXd2+cfFXYPED3mb8x1hzevlYhXja93sYlRVZJeUti0Gwg4/COS3VnfDoXLWHj0zl+IAXpRGGddkjGwJBAM2/Qd6o0wBs0d5X7es4GSkQlw2HU8Bsxdp7OB9hFmf58/v0XHKMH91X/47L9aGOGbn92LBKVc6QrmggtRh9hUECQQCowGcHum4dShGTAXZ/ZGyIWrRpaIjSMRUqKj2bemi8MWHT/WvNSnASsi8CrzEWtGREcfFtUkPZZOwy7VCpF/d+',
                'PUBLIC_KEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCRxin1FRmBtwYfwK6XKVVXP0FIcF4HZptHgHu+UuON3Jh6WPXc9fNLdsw5Hcmz3F5mYWYq1/WSRxislOl0U59cEPaef86PqBUW9SWxwdmYKB1MlAn5O9M1vgczBl/YqHvuRzfkIaPqSRew11bJWTjnpkcD0H+22kCGqxtYKmv7kwIDAQAB',
                'PAYECO_URL' => 'https://testmobile.payeco.com'
            ],
            'uat' => [
                'MERCHANT_ID' => '502050001806',
                'PRIVATE_KEY' => 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAJFhPKmrd6iFtFbg1CQhcDC9YxaA9nxK2AlYGCfk6Qa3Qq6mWtEMcEopBL+ZYWKujSyf7u7er36rvZZ/WYCehdDgeLNT0a0vtwhG8F/0HIM2HW6Fky7eZ+gxg4paj00uHRFgkAE5fg+dVMWPjwoOKJRq8X9qeehWTM9SqX822KHDAgMBAAECgYB5Vxt20jLxaYd36/ekoHQveiM2KTWR0DY4tlqTEbCddmAlvZuXWDZw74OTF8X8w4v5bxPSM/NuWpHHB0wA2k79HpuGtWXhxfQOq6jMKAuh7uX5Q4phubGXVx3yWRqt3//LkBvyHt5hBEIJuukNSohVNdndouV89gVVWHgsEMz3gQJBAN9lEQPdJSthhf0QKOCASfm0cASxI2ds3AWzQu9DXZGK1pIBtWXwpzmrqY1KBMHfA4h/FJyTQ+aSC8PRcYkv/9MCQQCmmTdD7xs3IeAdsszyg4WyT5vSWvjRqjop4R7RurCkicn2HTcW/87aEiPBL1KrwNWKVbitWbd1ZBxSOHXe9pBRAkANNkj/VYDxQ99MzDveqze00PsfC+rwHvwUSjnXNMC/7top4Hf+A3Ggc4qflJUbcjkfRYTOjdciN9kCR8zTNEeJAkEAiHmlK1KZ0d0/UjTh7ZzOjlbmyDjb8i3n/dy8OYUdJXz25FXkhkPCeSQ5BA23RJnwlKVKZz/CqTj8dmJoNOF5MQJAMC4TSKTdUealTcPVVLlvyHapO3qpjRn/miD9u5+x+6cIMsMQQjY846Bth0XUubes83w6uhVBvT12bOitb653Qw==',
                'PUBLIC_KEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCoymAVb04bvtIrJxczCT/DYYltVlRjBXEBFDYQpjCgSorM/4vnvVXGRb7cIaWpI5SYR6YKrWjvKTJTzD5merQM8hlbKDucxm0DwEj4JbAJvkmDRTUs/MZuYjBrw8wP7Lnr6D6uThqybENRsaJO4G8tv0WMQZ9WLUOknNv0xOzqFQIDAQAB',
                'PAYECO_URL' => 'https://mobile.payeco.com'
            ],
            'prod' => [
                'MERCHANT_ID' => '502050001806',
                'PRIVATE_KEY' => 'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAJFhPKmrd6iFtFbg1CQhcDC9YxaA9nxK2AlYGCfk6Qa3Qq6mWtEMcEopBL+ZYWKujSyf7u7er36rvZZ/WYCehdDgeLNT0a0vtwhG8F/0HIM2HW6Fky7eZ+gxg4paj00uHRFgkAE5fg+dVMWPjwoOKJRq8X9qeehWTM9SqX822KHDAgMBAAECgYB5Vxt20jLxaYd36/ekoHQveiM2KTWR0DY4tlqTEbCddmAlvZuXWDZw74OTF8X8w4v5bxPSM/NuWpHHB0wA2k79HpuGtWXhxfQOq6jMKAuh7uX5Q4phubGXVx3yWRqt3//LkBvyHt5hBEIJuukNSohVNdndouV89gVVWHgsEMz3gQJBAN9lEQPdJSthhf0QKOCASfm0cASxI2ds3AWzQu9DXZGK1pIBtWXwpzmrqY1KBMHfA4h/FJyTQ+aSC8PRcYkv/9MCQQCmmTdD7xs3IeAdsszyg4WyT5vSWvjRqjop4R7RurCkicn2HTcW/87aEiPBL1KrwNWKVbitWbd1ZBxSOHXe9pBRAkANNkj/VYDxQ99MzDveqze00PsfC+rwHvwUSjnXNMC/7top4Hf+A3Ggc4qflJUbcjkfRYTOjdciN9kCR8zTNEeJAkEAiHmlK1KZ0d0/UjTh7ZzOjlbmyDjb8i3n/dy8OYUdJXz25FXkhkPCeSQ5BA23RJnwlKVKZz/CqTj8dmJoNOF5MQJAMC4TSKTdUealTcPVVLlvyHapO3qpjRn/miD9u5+x+6cIMsMQQjY846Bth0XUubes83w6uhVBvT12bOitb653Qw==',
                'PUBLIC_KEY' => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCoymAVb04bvtIrJxczCT/DYYltVlRjBXEBFDYQpjCgSorM/4vnvVXGRb7cIaWpI5SYR6YKrWjvKTJTzD5merQM8hlbKDucxm0DwEj4JbAJvkmDRTUs/MZuYjBrw8wP7Lnr6D6uThqybENRsaJO4G8tv0WMQZ9WLUOknNv0xOzqFQIDAQAB',
                'PAYECO_URL' => 'https://mobile.payeco.com'
            ]
        ];
        $key = strtolower($env);
        if (isset($configs[$key])) {
            return $configs[$key];
        }
        return $configs['prod'];
    }
}

?>