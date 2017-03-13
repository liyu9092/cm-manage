<?php
/**
 * 商铺结算相关的功能
 */
namespace App;

use App\ShopCount;
use App\InsteadReceive;
use App\PrepayBill;
use Illuminate\Pagination\AbstractPaginator;
use App\Commission;
use Log;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class ShopCountApi
{
    
    CONST TOKEN_KEY = "CHOUmei";
    
    /////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////    业务逻辑相关         /////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////
    /**
     * 统计已消费的信息
     */
    public static function countAlreadyCost()
    {
        
    }
    
    public static function importAllOrder()
    {
        $total = Order::where('status',4)->count();
        $offset = 0;
        $size = 5000;
        $now_time = time();        
        do{
            $base_order_infos = Order::where('status',4)->where('use_time','<',$now_time)->select('ordersn','salonid','priceall','use_time')->skip($offset)->take($size)->get()->toArray();
            $rows_num = count($base_order_infos);
            $offset += $size;
            $salon_ids = array_column($base_order_infos, "salonid");
            //$marchant_info = self::getSalonMerchantBaseInfo($salon_ids);            
            foreach($base_order_infos as $key => $order)
            {
                $now_num = $key + $offset + 1;
                $ordersn = $order['ordersn'];
                $salon_id = $order['salonid'];            
                $money = floatval($order['priceall']);
                $time = $order['use_time'];                
                echo "run at {$now_num} / {$total}  count ordersn [{$ordersn}] ...\n";
               // $merchant_id = $marchant_info[$salon_id];
       
                
                $ds_code =InsteadReceive::getNewCode();
                
                ShopCountDetail::create([
                    'code' => $ordersn,
                    'type' => 1,
                    'money' => $money,
                    'salon_id' => $salon_id,
                    //'merchant_id' => $merchant_id,
                    'created_at' => date("Y-m-d H:i:s", $time)
                ]);
                
                $ir = InsteadReceive::where('salon_id',$salon_id)->where('day',date("Y-m-d", $time))->first();
                if(empty($ir))
                {
                    InsteadReceive::create([
                    'code' => $ds_code,
                    'salon_id' => $salon_id,
                   // 'merchant_id' => $merchant_id,
                    'type' => InsteadReceive::TYPE_OF_ORDER,
                    'money' => $money,
                    'day' => date("Y-m-d", $time),
                    'created_at' => date("Y-m-d H:i:s")
                    ]);
                }
                else
                {
                    $now_money = intval($ir->money) + $money;
                    InsteadReceive::where('id',$ir->id)->update(['money'=> $now_money]);
                }
                unset($ir);
                $shop_counts = ShopCount::count_bill_by_receive_money($salon_id, NULL, $money);             
            }
            unset($base_order_infos);
        }
        while($rows_num >= $size);            
    }
    

    
    /**
     * 订单佣金结算
     * @param array $options 订单号
     */
    public static function commissionOrder($options){
        $orders = Order::whereIn("ordersn",$options)
        ->where('order.status',4)
        ->leftJoin('salon_info','salon_info.salonid','=','order.salonid')
        ->leftJoin('salon','salon.salonid','=','order.salonid')
        ->select('order.orderid','order.ordersn','order.salonid','order.priceall','order.use_time','salon.merchantId','salon.salonGrade','salon_info.commissionRate')
        ->get();

        $insert = [];
        $model = new CommissionLog;
        
        foreach ($orders as $key => $order) {

            if($exist = $model->where('ordersn',$order->ordersn)->first())
                continue;
            $rate = floatval($order->commissionRate);
            $amount = floatval($order->priceall);
            $commission = $rate*$amount/100;
            $commission = round($commission,2);
            $data['ordersn'] = $order->ordersn;
            $data['type'] = 1;//订单1,赏金单2
            $data['salonid'] = $order->salonid;
            $data['amount'] = $commission;
            $data['grade'] = $order->salonGrade;
            $data['rate'] = $rate;
            $date = date('Y-m-d H:i:s',$order->use_time);
            $data['updated_at'] = $date;
            $data['created_at'] = $date;

            $insert[] = $data;

            $commissionModel = \App\Commission::where('salonid','=',$order->salonid)->where('date','=',date('Y-m-d',$order->use_time))->first();
            if($commissionModel){
                $commissionModel->update(['amount'=>$commission+$commissionModel->amount]);
            }
            else{
                $commissionModel = new \App\Commission;
                $commissionModel->sn = $commissionModel::getSn();
                $commissionModel->salonid = $order->salonid;
                $commissionModel->amount = $commission;
                $commissionModel->date = $date;
                $now = date('Y-m-d H:i:s');
                $data['updated_at'] = $now;
                $data['created_at'] = $now;
                $commissionModel->save();
            }

            ShopCount::count_bill_by_commission_money($order->salonid,$order->merchantId,$commission,'佣金率'.$rate.'%',date('Y-m-d H:i:s',$order->use_time));
        }
        $model->insert($insert);
    }
    

    /**
     * 赏金单佣金结算
     * @param array $options 订单号
     */
    public static function commissionBounty($options){
        $orders = BountyTask::whereIn("btSn",$options)
        ->where('bounty_task.status',4)
        ->leftJoin('salon','salon.salonid','=','bounty_task.salonId')
        ->leftJoin('salon_info','salon_info.salonid','=','bounty_task.salonId')
        ->select('bounty_task.btId','bounty_task.btSn','bounty_task.salonId','bounty_task.money','bounty_task.endTime','salon.merchantId','salon.salonGrade')
        ->get();

        $insert = [];
        $model = new CommissionLog;
        foreach ($orders as $key => $order) {

            if($exist = $model->where('ordersn',$order->ordersn)->first())
                continue;
            $rate = floatval($order->salonInfo->commissionRate);
            $amount = floatval($order->money);
            $commission = $rate*$amount/100;
            $commission = round($commission,2);
            $data['ordersn'] = $order->btSn;
            $data['type'] = 2;//订单1,赏金单2
            $data['salonid'] = $order->salonId;
            $data['sn'] = $model->getSn();
            $data['amount'] = $commission;
            $data['grade'] = $order->salonGrade;
            $data['rate'] = $rate;
            $date = date('Y-m-d H:i:s',$order->use_time);
            $data['updated_at'] = $date;
            $data['created_at'] = $date;
            $insert[] = $data;

            $commissionModel = \App\Commission::where('salonid','=',$order->salonid)->where('date','=',date('Y-m-d',$order->use_time))->first();
            if($commissionModel){
                $commissionModel->update(['amount'=>$commission+$commissionModel->amount]);
            }
            else{
                $commissionModel = new \App\Commission;
                $commissionModel->sn = $commissionModel::getSn();
                $commissionModel->salonid = $order->salonid;
                $commissionModel->amount = $amount;
                $commissionModel->date = $date;
                $now = date('Y-m-d H:i:s');
                $data['updated_at'] = $now;
                $data['created_at'] = $now;
                $commissionModel->save();
            }
            
            ShopCount::count_bill_by_commission_money($order->salonid,$order->merchantId,$commission,'赏金单佣金',date('Y-m-d H:i:s',$order->endTime));
        }
        $model->insert($insert);
    }

    /**
     * 已消费的订单结算
     * @param array $options 订单号
     */
    public static function countOrder($options)
    {
        $order_sns = $options;
        $input_count = count($order_sns);
        $base_order_infos = Order::whereIn("ordersn",$order_sns)->where('status',4)->select('ordersn','salonid','priceall','use_time')->get()->toArray();
       
        $select_count = count($base_order_infos);
        //状态检查
        if($input_count != $select_count)
        {
            throw new ApiException("some ordersn in [".implode(",", $order_sns)."] is wrong status",ERROR::ORDER_STATUS_WRONG);
        }
        
        $salon_ids = array_column($base_order_infos, "salonid");
        
     //   $marchant_info = self::getSalonMerchantBaseInfo($salon_ids);
       
        $res = ['success'=>[],'type'=>1,'already'=>[]];
        foreach($base_order_infos as $order)
        {
            $ordersn = $order['ordersn'];
            $salon_id = $order['salonid'];
            $type = 1;
            $money = floatval($order['priceall']);
            $time = $order['use_time'];           
           // $merchant_id = $marchant_info[$salon_id];           
            $ret = ShopCount::ShopCountOrder($ordersn,$salon_id,NULL,$money,$time,$type);   
            if($ret == 1)
            {
                $res['success'][] = $ordersn;
            }
            else if($ret == 2)
            {
               $res['already'][] = $ordersn;;
            }
        }
        return $res;
    }

    /**
     * 结算赏金单
     * @param array $options
     */
    public static function countBounty($options)
    {
        $order_sns = $options;
        $input_count = count($order_sns);
        $base_order_infos = BountyTask::whereIn("btSn",$order_sns)->where('btStatus',4)->select('btSn','salonId','money','endTime')->get()->toArray();
        
        $select_count = count($base_order_infos);
        //状态检查
        if($input_count != $select_count)
        {
            throw new ApiException("some ordersn in [".implode(",", $order_sns)."] is wrong status",ERROR::BOUNTY_STATUS_WRONG);
        }
        
        $salon_ids = array_column($base_order_infos, "salonId");
        
       // $marchant_info = self::getSalonMerchantBaseInfo($salon_ids);
         
        $res = ['success'=>[],'type'=>2,'already'=>[]];
        foreach($base_order_infos as $order)
        {
            $ordersn = $order['btSn'];
            $salon_id = $order['salonId'];
            $money = floatval($order['money']);
            $time = $order['endTime'];
            $type = 2;
           // $merchant_id = $marchant_info[$salon_id];
            
            $ret = ShopCount::ShopCountOrder($ordersn,$salon_id,NULL,$money,$time,$type);
            if($ret == 1)
            {
                $res['success'][] = $ordersn;
            }
            else if($ret == 2)
            {
                $res['already'][] = $ordersn;;
            }
        }
        return $res;
    }
    
    /**
     * 收到用户的钱
     * @param array $options
     */
    public static function receiveMoney($options)
    {
        
    }
    


    /**
     * 生成一个预览状态的预付单 已经不用了
     */
    public static function makePreviewPrepay($options)
    {
//         if(isset($options['merchant_id'])
//             && isset($options['salon_id'])
//             && isset($options['type'])
//             && isset($options['uid'])
//             && isset($options['pay_money'])
//             && isset($options['cost_money'])
//             && isset($options['day']))
//         {
//             $code = PrepayBill::getNewCode($options['type']);
//             $options['code'] = $code;
//             $id = PrepayBill::insertGetId($options);
//             return $id;
//         }
//         return false;
    }
    
    /**
     * 生成一个预付单
     */
    public static function makePrepay($options)
    {
        if(
            //isset($options['merchant_id'])
            isset($options['type'])
            && isset($options['salon_id'])
            && isset($options['uid'])
            && isset($options['pay_money'])
            && isset($options['pay_type'])
            && isset($options['day']))
        {
            $now_date = date("Y-m-d H:i:s");
            $now_day = date("Y-m-d");
            $pay_code = PayManage::makeNewCode(PayManage::TYPE_OF_FJY);
            $pay_id = PayManage::insertGetId([
                'code' => $pay_code,
                'type' => PayManage::TYPE_OF_FJY,
                'salon_id' => $options['salon_id'],
              //  'merchant_id' => $options['merchant_id'],
                'money' => $options['pay_money'],
                'pay_type' => $options['pay_type'],
                'require_day'=>$options['day'],
                'make_uid'=>$options['uid'],
                'created_at'=>$now_date,
                'updated_at'=>$now_date,
                'state'=>PayManage::STATE_OF_TO_CHECK,
            ]);
            
            $code = PrepayBill::getNewCode($options['type']);
            $options['other_id'] = $pay_id;
            $options['code'] = $code;
            $options['other_code'] = $pay_code;
            $options['state'] = PrepayBill::STATE_OF_TO_CHECK;
            $options['created_at'] = $options['updated_at'] = date("Y-m-d H:i:s");
            $id = PrepayBill::insertGetId($options);
            PayManage::where("id",$pay_id)->update(['p_id'=>$id,'p_code'=>$code]);
            return ['id'=>$id,'code'=>$code];
        }
        return false;
    }

    /**
     * 更新一个预付单
     */
    public static function updatePrepay($id, $options)
    {
        $now_date = date("Y-m-d H:i:s");
        $ret = true;
        $prepay = PrepayBill::where('id', $id)->first();
        if (empty($prepay)) {
            return false;
        }
        
        if( $prepay->state != PrepayBill::STATE_OF_TO_SUBMIT && $prepay->state != PrepayBill::STATE_OF_TO_CHECK)
        {
            return false;
        }        
        $options['updated_at'] = $now_date;
        $options['state'] = PrepayBill::STATE_OF_TO_CHECK;
        
        //更新转付单
        $ret = PrepayBill::where('id', $id)->update($options);
        
        if(empty($prepay->other_id))
        {
            return $ret;
        }
        
        //更新付款单
        $pay_record = ['updated_at'=>$now_date,'state'=>PayManage::STATE_OF_TO_CHECK, ];
        if(isset($options['salon_id']))
        {
            $pay_record['salon_id'] = $options['salon_id'];
        }

        if(isset($options['pay_money']))
        {
            $pay_record['money'] = $options['pay_money'];
        }
        if(isset($options['pay_type']))
        {
            $pay_record['pay_type'] = $options['pay_type'];
        }
        if(isset($options['day']))
        {
            $pay_record['require_day'] = $options['day'];
        }
        PayManage::where('id',$prepay->other_id)->update($pay_record);
        
        return $ret;
    }   

    /**
     * 更新一个预付单
     */
    public static function deletePrepay($id)
    {
        $prepay = PrepayBill::where('id', $id)->first();
        if (empty($prepay)) {
            return false;
        }
    
        if( $prepay->state != PrepayBill::STATE_OF_TO_SUBMIT && $prepay->state != PrepayBill::STATE_OF_TO_CHECK)
        {
            return false;
        }
        $pay_id = $prepay->other_id;
        //删除转付单
        PrepayBill::where('id',$id)->delete();
        //删除付款单
        PayManage::where('id',$pay_id)->delete();
       
        return ['id'=>$id,'code'=>$prepay->code];
    }
    
    public static function getSalonMerchantBaseInfo($salon_ids)
    {
        $salon_infos = Salon::whereIn('salonid',$salon_ids)->get(['salonid','salonname','shopType','merchantId'])->toArray();
        $res = [];
        foreach ($salon_infos as $salon)
        {
           $id = $salon['salonid'];
           $res[$id] = $salon['merchantId'];          
        }       
        return $res;
    }
    
    
    public static function makeToken(&$params)
    {
        asort($params);
        $url = http_build_query($params);
        $params['token'] =  md5(md5($url).self::TOKEN_KEY);
    }
    
    public static function checkToken($params)
    {
        if(isset($params['token']))
        {
            $token = $params['token'];
            unset($params['token']);
            self::makeToken($params);
            return $params['token'] === $token;
        }
        return false;
    }    
    
    /////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////    数据查询相关         /////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * 搜索预付款信息
     * @param array $options
     */
    public static function searchPrepay($options)
    {
        $prepay = self::getPrepayCondition($options);
        //页数
        $page = isset($options['page'])?max(intval($options['page']),1):1;
        $size = isset($options['page_size'])?max(intval($options['page_size']),1):20;
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
  
        $res =  $prepay->paginate($size)->toArray();  
        unset($res['next_page_url']);
        unset($res['prev_page_url']);
        return $res;
    }
    
    public static function getPrepayCondition($options)
    {
        $salon_fields = [
            'salonid',
            'sn',
            'salonname'
        ];
        $merchant_fields = [
            'id',
            'name'
        ];
        $user_fields = [
            'id',
            'name'
        ];
        $prepay_fields = [
            'prepay_bill.id as id',
            'prepay_bill.created_at as created_at',
            'merchant.id as merchant_id',
            'prepay_bill.salon_id as salon_id',
            'prepay_bill.code as code',
            'prepay_bill.type as type',
            'prepay_bill.uid as uid',
            'prepay_bill.pay_money as pay_money',
            'prepay_bill.pay_type as pay_type',
            'prepay_bill.remark as remark',
            //'cost_money',
            'prepay_bill.day as day',
            'prepay_bill.pay_day as pay_day',
            'prepay_bill.state as state',
        ];
        $order_by_fields = [
            'id',
            'created_at',
            'code',
            'type',
            'pay_money',
            'pay_type',
            'day'
        ];
        
        $prepay = PrepayBill::where('prepay_bill.state', '<>', PrepayBill::STATE_OF_PREVIEW);
        
        // 关键字搜索
        $key = 0;
        $keyword = NULL;
        if (isset($options['key']) && ! empty($options['key']) && isset($options['keyword']) && ! empty($options['keyword'])) {
            $key = intval($options['key']);
            $keyword = '%' . str_replace([
                "%",
                "_"
            ], [
                "\\%",
                "\\_"
            ], $options['keyword']) . "%";
        }
        
        
        $prepay->join('salon',function($join) use($key,$keyword){
            $join->on('salon.salonid','=','prepay_bill.salon_id');
            if(!empty($keyword))
            {
                if($key == 1)
                {
                    $join->where('salon.salonname','like',$keyword);
                }
                elseif($key == 3)
                {
                    $join->where('salon.sn','like',$keyword);
                }
            };
        })->join('merchant',function($join) use($key,$keyword){
            $join->on('merchant.id','=','salon.merchantId');
            if(!empty($keyword))
            {
                if($key == 2)
                {
                    $join->where('merchant.name','like',$keyword);
                }
            };
        });        

        $prepay->select($prepay_fields);
        
        $prepay->with([
            'user' => function ($q) use($user_fields)
            {
                $q->get($user_fields);
            }
        ]);
        
        $prepay->with([
            'salon' => function ($q) use($salon_fields)
            {
                $q->get($salon_fields);
            }
        ]);
        
        $prepay->with([
            'merchant' => function ($q) use($merchant_fields)
            {
                $q->get($merchant_fields);
            }
        ]);
        
        // 按时间搜索
        if (isset($options['pay_time_min']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_min']))) {
            $prepay->where('prepay_bill.pay_day', ">=", trim($options['pay_time_min']));
        }
        if (isset($options['pay_time_max']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_max']))) {
            $prepay->where('prepay_bill.pay_day', "<=", trim($options['pay_time_max']));
        }
        
        // 排序
        if (isset($options['sort_key']) && in_array($options['sort_key'], $order_by_fields)) {
            $order = $options['sort_key'];
        } else {
            $order = "created_at";
        }
        
        if (isset($options['sort_type']) && strtoupper($options['sort_type']) == "ASC") {
            $order_by = "ASC";
        } else {
            $order_by = "DESC";
        }        
        return $prepay->orderBy($order, $order_by);
    }
    
    /**
     * 搜索代收单信息
     * @param array $option
     */
    public static function searchInsteadReceive($options)
    {
        $instead_receive = self::getInsteadReceiveCondition($options);
        // 页数
        $page = isset($options['page']) ? max(intval($options['page']), 1) : 1;
        $size = isset($options['page_size']) ? max(intval($options['page_size']), 1) : 20;
        AbstractPaginator::currentPageResolver(function () use($page)
        {
            return $page;
        });
        
        $res = $instead_receive->paginate($size)->toArray();
        unset($res['next_page_url']);
        unset($res['prev_page_url']);
        return $res;
    }
    
    public static function getInsteadReceiveCondition($options)
    {
        $salon_fields = [
            'salonid',
            'sn',
            'salonname'
        ];
        $merchant_fields = [
            'id',
            'name'
        ];
        $instead_receive_fields = [
            'instead_receive.id as id',
            'instead_receive.created_at as created_at',
            'merchant.id as merchant_id',
            'instead_receive.salon_id as salon_id',
            'instead_receive.code as code',
            'instead_receive.type as type',
            'instead_receive.money as money',
            'instead_receive.day as day'
        ];
        $order_by_fields = [
            'id',
            'created_at',
            'code',
            'type',
            'money',
            'day'
        ];
        
        // 关键字搜索
        $key = 0;
        $keyword = NULL;
        if (isset($options['key']) && ! empty($options['key']) && isset($options['keyword']) && ! empty($options['keyword'])) {
            $key = intval($options['key']);
            $keyword = "%" . str_replace([
                "%",
                "_"
            ], [
                "\\%",
                "\\_"
            ], $options['keyword']) . "%";
        }
        
        $instead_receive = InsteadReceive::join('salon',function($join) use($key,$keyword){
            $join->on('salon.salonid','=','instead_receive.salon_id');
            if(!empty($keyword))
            {
                if($key == 1)
                {
                    $join->where('salon.salonname','like',$keyword);
                }
                elseif($key == 3)
                {
                    $join->where('salon.sn','like',$keyword);
                }
            };
        })->join('merchant',function($join) use($key,$keyword){
            $join->on('merchant.id','=','salon.merchantId');
            if(!empty($keyword))
            {
                if($key == 2)
                {
                    $join->where('merchant.name','like',$keyword);
                }
            };
        });

        $instead_receive->select($instead_receive_fields);
        
        $instead_receive->with([
            'salon' => function ($q) use($salon_fields)
            {
                $q->get($salon_fields);
            }
        ]);
        
        $instead_receive->with([
            'merchant' => function ($q) use($merchant_fields)
            {
                $q->get($merchant_fields);
            }
        ]);
        
        // 按时间搜索
        if (isset($options['pay_time_min']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_min']))) {
            $instead_receive->where('instead_receive.day', ">=", trim($options['pay_time_min']));
        }
        if (isset($options['pay_time_max']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_max']))) {
            $instead_receive->where('instead_receive.day', "<=", trim($options['pay_time_max']));
        }
        
        // 排序
        if (isset($options['sort_key']) && in_array($options['sort_key'], $order_by_fields)) {
            $order = $options['sort_key'];
        } else {
            $order = "created_at";
        }
        
        if (isset($options['sort_type']) && strtoupper($options['sort_type']) == "ASC") {
            $order_by = "ASC";
        } else {
            $order_by = "DESC";
        }
        return $instead_receive->orderBy($order, $order_by);
    }
    
    
    /**
     * 搜索商铺往来结算信息
     * @param array $option
     */
    public static function searchShopCount($options)
    {
        $shopcount = self::getShopCountCondition($options);
        
        // 页数
        $page = isset($options['page']) ? max(intval($options['page']), 1) : 1;
        $size = isset($options['page_size']) ? max(intval($options['page_size']), 1) : 20;
        AbstractPaginator::currentPageResolver(function () use($page)
        {
            return $page;
        });        
        $res = $shopcount->paginate($size)->toArray();
        unset($res['next_page_url']);
        unset($res['prev_page_url']);  
        return $res;
    }
    
    public static function getShopCountCondition($options)
    {
        $salon_fields = [
            'salonid',
            'salonname',
            'sn',
            'shopType'
        ];
        $merchant_fields = [
            'id',
            'name'
        ];
        $shop_count_fields = [
            'shop_count.id as id',
            'shop_count.created_at as created_at',
            'merchant.id as merchant_id',
            'shop_count.salon_id as salon_id',
            'shop_count.pay_money as pay_money',
            'shop_count.cost_money as cost_money',
            'shop_count.commission_money as commission_money',
            'shop_count.commission_return_money as commission_return_money',
            'shop_count.spend_money as spend_money',
            //'balance_money',
            'shop_count.invest_money as invest_money',
            'shop_count.invest_return_money as invest_return_money',
            //'invest_balance_money',
            'shop_count.borrow_money as borrow_money',
            'shop_count.borrow_return_money as borrow_return_money',
            'shop_count.borrow_balance_money as borrow_balance_money'
        ];
        $order_by_fields = [
            'id',
            'created_at',
            'salon_name',
            'salon_type',
            'pay_money',
            'cost_money',
            'spend_money',
            'balance_money',
            'invest_money',
            'invest_return_money',
            'invest_balance_money',
            'borrow_money',
            'borrow_return_money',
            'borrow_balance_money'
        ];

        $shop_count = ShopCount::selectRaw('(`cm_shop_count`.`pay_money` - `cm_shop_count`.`spend_money` + `cm_shop_count`.`commission_money` - `cm_shop_count`.`commission_return_money`) as `balance_money`')->selectRaw('(`cm_shop_count`.`invest_money` - `cm_shop_count`.`invest_return_money`) as `invest_balance_money`');
        
        // 关键字搜索
        $key = 0;
        $keyword = NULL;
        if (isset($options['key']) && ! empty($options['key']) && isset($options['keyword']) && ! empty($options['keyword'])) {
            $key = intval($options['key']);
            $keyword = "%" . str_replace([
                "%",
                "_"
            ], [
                "\\%",
                "\\_"
            ], $options['keyword']) . "%";
        }
        
        $shop_count->join('salon',function($join) use($key,$keyword){
            $join->on('salon.salonid','=','shop_count.salon_id');
            if(!empty($keyword))
            {
                if($key == 1)
                {
                    $join->where('salon.salonname','like',$keyword);
                }
                elseif($key == 3)
                {
                    $join->where('salon.sn','like',$keyword);
                }
            };
        })->join('merchant',function($join) use($key,$keyword){
            $join->on('merchant.id','=','salon.merchantId');
            if(!empty($keyword))
            {
                if($key == 2)
                {
                    $join->where('merchant.name','like',$keyword);
                }
            };
        });
        
        $shop_count->addSelect($shop_count_fields);
        
        // 按时间搜索
        if (isset($options['pay_time_min']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_min']))) {
            $shop_count->where('shop_count.day', ">=", trim($options['pay_time_min']));
        }
        if (isset($options['pay_time_max']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_max']))) {
            $shop_count->where('shop_count.day', "<=", trim($options['pay_time_max']));
        }
        
        $shop_count->with([
            'salon' => function ($q) use($salon_fields)
            {
                $q->get($salon_fields);
            }
        ])->with([
            'merchant' => function ($q) use($merchant_fields)
            {
                $q->get($merchant_fields);
            }
        ]);
        
        // 排序
        if (isset($options['sort_key']) && in_array($options['sort_key'], $order_by_fields)) {
            $order = $options['sort_key'];
        } else {
            $order = "created_at";
        }
        
        if (isset($options['sort_type']) && strtoupper($options['sort_type']) == "ASC") {
            $order_by = "ASC";
        } else {
            $order_by = "DESC";
        }
        // 值全为0的不要
        $shop_count->whereRaw(" NOT (`cm_shop_count`.`pay_money` = 0 and `cm_shop_count`.`spend_money` = 0 and `cm_shop_count`.`invest_money` = 0 and `cm_shop_count`.`invest_return_money` = 0 and `cm_shop_count`.`borrow_money`=0 and `cm_shop_count`.`borrow_return_money` = 0 and `cm_shop_count`.`borrow_balance_money`  = 0 )");
        
        return $shop_count->orderBy($order, $order_by);
    }

    
    /**
     * 预付款详情信息
     * @param int $id
     */
    public static function prepayDetail($id)
    {
        $salon_fields = ['salonid','salonname','sn'];
        $merchant_fields = ['id','name'];
        $user_fields = ['id','name'];
        $prepay_fields = [
            'prepay_bill.id as id',
            'prepay_bill.created_at as created_at',
            'merchant.id as merchant_id',
            'prepay_bill.salon_id as salon_id',
            'prepay_bill.code as code',
            'prepay_bill.type as type',
            'prepay_bill.uid as uid',
            'prepay_bill.pay_money as pay_money',
            'prepay_bill.pay_type as pay_type',
            'prepay_bill.day as day',
            'prepay_bill.pay_day as pay_day',
            'prepay_bill.state as state',
            'prepay_bill.remark as remark',            
        ];
        
        $prepay = PrepayBill::where('prepay_bill.id',$id);
        $prepay->join('salon',function($join){
            $join->on('salon.salonid','=','prepay_bill.salon_id');            
        })->join('merchant',function($join){
            $join->on('merchant.id','=','salon.merchantId');         
        });
        
        $prepay->with([
            'user' => function ($q) use($user_fields)
            {
                $q->get($user_fields);
            }
        ]);
        
        $prepay->with([
            'salon' => function ($q) use($salon_fields)
            {
              $q->get($salon_fields);
            }
        ]);
        
        $prepay->with([
            'merchant' => function ($q) use($merchant_fields)
            {
              $q->get($merchant_fields);
            }
        ]);
        
        return $prepay->first($prepay_fields);
    }
    
    /**
     * 代收单详情信息
     * @param int $id
     */
    public static function insteadReceiveDetail($id)
    {
        $salon_fields = ['salonid','salonname','sn'];
        $merchant_fields = ['id','name'];
        $instead_receive_fields = [
            'instead_receive.id as id',
            'instead_receive.created_at as created_at',
            'merchant.id as merchant_id',
            'instead_receive.salon_id as salon_id',
            'instead_receive.code as code',
            'instead_receive.type as type',
            'instead_receive.money as money',            
            'instead_receive.day as day',            
        ];
        
        $instead_receive = InsteadReceive::where('instead_receive.id',$id);
        $instead_receive->join('salon',function($join){
            $join->on('salon.salonid','=','instead_receive.salon_id');
        })->join('merchant',function($join){
            $join->on('merchant.id','=','salon.merchantId');
        });
        $instead_receive->with([
            'salon' => function ($q) use($salon_fields)
            {
                $q->get($salon_fields);
            }
        ]);
        
        $instead_receive->with([
            'merchant' => function ($q) use($merchant_fields)
            {
                $q->get($merchant_fields);
            }
        ]);
        return $instead_receive->first($instead_receive_fields)->toArray();
    }
    
}
