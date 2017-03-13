<?php
/**
 * 财务 付款管理相关
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class PayManage extends Model
{
    /**
     * 状态 待提交
     * @var unknown
     */
    const STATE_OF_TO_SUBMIT = 1;
    
    
    /**
     * 状态 待审批
     * @var unknown
     */
    const STATE_OF_TO_CHECK = 2;
    
    /**
     * 状态 待付款
     * @var unknown
     */
    const STATE_OF_TO_PAY = 3;
        
    /**
     * 状态 已付款
     * @var unknown
     */
    const STATE_OF_PAIED = 4;
    
    /**
     * 付交易代收款
     * @var unknown
     */
    const TYPE_OF_FJY = 1;
    
    
    /**
     * 付业务投资款
     * @var unknown
     */
    const TYPE_OF_FTZ = 2;
    
    /**
     * 创建来源
     * @var unknown
     */
    const FROM_LOCAL = 1;
    
    /**
     * 商家后台
     * @var unknown
     */
    const FROM_SHANGMENG = 2;
    
    protected $table = 'pay_manage';
    
//     public function merchant(){
//         return $this->belongsTo(Merchant::class);
//     }
    
    public function make_user()
    {
        return $this->belongsTo(Manager::class,'make_uid');
    }
    
    public function confirm_user()
    {
        return $this->belongsTo(Manager::class,'confirm_uid');
    }
    
    public function cash_user()
    {
        return $this->belongsTo(Manager::class,'cash_uid');
    }
    
    public function salon()
    {
        return $this->belongsTo(Salon::class);
    }
    
    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }
    
    public function salon_user()
    {
        return $this->belongsTo(SalonUser::class,'salon_uid','salon_user_id');
    }
    
    /**
     * 通过提现生成付款单
     * @param unknown $params
     */
    public static function makeByWithdraw($w_id)
    {
        
        $base = SalonMoneyWithdraw::where('id',$w_id)->first();
      
        if(empty($base))
        {
           
            throw new ApiException("can not find the data by id [{$w_id}]", ERROR::PAY_WITHDRAW_NOT_EXIST);
        }
        $state = intval($base->state);
       
        if($state == SalonMoneyWithdraw::STATE_OF_TO_SUBMIT)
        {
            throw new ApiException("the data of id [{$w_id}] is CHECKED and　REJECTED", ERROR::PAY_WITHDRAW_WRONG_STATE);
        }
        elseif($state == SalonMoneyWithdraw::STATE_OF_TO_PAY || $state == SalonMoneyWithdraw::STATE_OF_PAIED )
        {
            throw new ApiException("the data of id [{$w_id}] have already submit !", ERROR::PAY_WITHDRAW_WRONG_STATE);
        }
        
        $exist_num = self::where('w_id',$w_id)->count();
        if($exist_num >0)
        {
            throw new ApiException("the data of id [{$w_id}]  already exist !", ERROR::PAY_WITHDRAW_WRONG_STATE);
        }
        
        $code = self::makeNewCode(self::TYPE_OF_FJY);
        //$merchant_id = Salon::getMercharId($base->salon_id);
        $record = [
            'type' =>self::TYPE_OF_FJY,
            'code'=>$code,
            'state'=>self::STATE_OF_TO_CHECK,
            'w_id'=>$base->id,           
            'salon_id'=>$base->salon_id,
            'salon_uid'=>$base->uid,
           // 'merchant_id'=>intval($merchant_id),
            'from'=>self::FROM_SHANGMENG,
            'money'=>$base->money,
            'pay_type'=>1,//统一为银行付款
            'created_at'=>$base->created_at,
            'updated_at'=>$base->created_at
        ];
        return self::insertGetId($record);
    }
    
    /**
     * 生成(从预付单的交易代收返还生成)  废弃
     * @param unknown $params
     */
    public static function makeFromPrepayReturn($params)
    {
        if( !isset($params['id']) ||//转付单id
            !isset($params['code']) ||//转付单code
            !isset($params['other_id']) ||//代收单id
            !isset($params['other_code']) ||//代收单code
            !isset($params['salon_id']) || 
           // !isset($params['merchant_id']) ||
            !isset($params['pay_money']) ||//金额
            !isset($params['pay_type']) ||//支付方式
            !isset($params['pay_day']) ||//实际付款日期
            !isset($params['uid']) //确认人
        )
        {
            return false;
        }
        //新建付款单
        $code = self::makeNewCode(self::TYPE_OF_FJY);
        $now_date = date("Y-m-d H:i:s");
        $record = [
            'type' =>self::TYPE_OF_FJY,
            'code'=>$code,
            'state'=>self::STATE_OF_PAIED,
            'r_id'=>$params['other_id'],
            'r_code'=>$params['other_code'],
            'p_id'=>$params['id'],
            'p_code'=>$params['code'],
            'salon_id'=>$params['salon_id'],
            //'merchant_id'=>$params['merchant_id'],
            'money'=>$params['pay_money'],
            'pay_type'=>$params['pay_type'],
            'pay_day'=>$params['pay_day'],
            'make_uid'=>$params['uid'],
            'confirm_uid'=>$params['uid'],
            'cash_uid'=>$params['uid'],
            'confirm_at'=>$params['pay_day'],
            'created_at'=>$now_date,
            'updated_at'=>$now_date
        ];
        $id = self::insertGetId($record);
        return ['id'=>$id,'code'=>$code];
    }

    /**
     * 生成 (从收款单,账扣支付 生成)
     * @param array $params
     * @return ['id'=>id,'code'=>code]
     */
    public static function makeFromReceive($params)
    {  
        
        if( !isset($params['id']) ||//三方id
            !isset($params['code']) ||//三方code
            !isset($params['salon_id']) || 
           // !isset($params['merchant_id']) ||
            !isset($params['money']) ||//金额
            !isset($params['receive_type']) ||//支付方式
            !isset($params['receive_day']) ||//实际付款日期
            !isset($params['cash_uid'])    ||//确认人
            !isset($params['make_uid']) ||//制单人
            !isset($params['make_at']) ||//创建日期
            !isset($params['remark'])//备注
        )
        {
            return false;
        }
        $now_day = date("Y-m-d");
        $now_date = date("Y-m-d");
        
        //新建付款单
        $code = self::makeNewCode(self::TYPE_OF_FJY);
        
        $record = [
            'type' =>self::TYPE_OF_FJY,
            'code'=>$code,
            'state'=>self::STATE_OF_PAIED,
            'r_id'=>$params['id'],
            'r_code'=>$params['code'],
            'salon_id'=>$params['salon_id'],
          //  'merchant_id'=>$params['merchant_id'],
            'money'=>$params['money'],
            'pay_type'=>$params['receive_type'],
            'pay_day'=>$params['receive_day'],
            'make_uid'=>$params['make_uid'],
            'confirm_uid'=>$params['cash_uid'],
            'cash_uid'=>$params['cash_uid'],
            'confirm_at'=>$params['receive_day'],
            'remark'=>$params['remark'],
            'created_at'=>$params['make_at'],
            'updated_at'=>date('Y-m-d H:i:s')
        ];
        $id = self::insertGetId($record);
        
        //新建转付单
        $prepay_params = [ 
            'other_id'=>$id,
            'other_code'=>$code,
            'salon_id'=>$params['salon_id'],
            //'merchant_id'=>$params['merchant_id'],
            'type'=>PrepayBill::TYPE_OF_ALREADYPAY,
            'uid'=>$params['make_uid'],
            'pay_money'=>$params['money'],
            'pay_type'=>$params['receive_type'],
            'pay_day'=>$params['receive_day'],
            'remark'=>$params['remark'],
            'count_at'=>$params['make_at'],
        ];
        $prepay = PrepayBill::makeCompleted($prepay_params);      

        ShopCount::count_bill_by_pay_money($params['salon_id'], NULL,  $params['money'],"预付款返还",$params['make_at']);
        
        //付款单关联转付单关联
        self::where('id',$id)->update(['p_id'=>$prepay['id'],'p_code'=>$prepay['code']]);
        
        return ['id'=>$id,'code'=>$code];
    }
    
    /**
     * 生成新单
     * @param array $attr
     * @return boolean|new id
     */
    public static function make($attr)
    {
        if( !isset($attr['type']) ||
            !isset($attr['salon_id']) ||
           // !isset($attr['merchant_id']) ||
            !isset($attr['money']) ||
            !isset($attr['pay_type']) ||
            !isset($attr['remark']) ||
            !isset($attr['make_uid'])
         )
        {
            return false;
        }
        $record = [
            'type'=>intval($attr['type']),
            'salon_id'=>intval($attr['salon_id']),
          //  'merchant_id'=>intval($attr['merchant_id']),
            'money'=>floatval($attr['money']),
            'pay_type'=>intval($attr['pay_type']),         
            'make_uid'=>intval($attr['make_uid']),
            'remark'=>$attr['remark'],
        ];
        if($attr['type'] == self::TYPE_OF_FTZ )
        {
            if(
            !isset($attr['cycle']) ||
            !isset($attr['cycle_day']) ||
            !isset($attr['cycle_money']))
            {
                return false;
            }
            $record_plus = [
                'cycle'=>intval($attr['cycle']),
                'cycle_day'=>intval($attr['pay_type']),
                'cycle_money'=>floatval($attr['cycle_money']),];
            $record =array_merge($record,$record_plus);
        }
        $record['code'] = self::makeNewCode($record['type']);     
        $record['state'] = self::STATE_OF_TO_CHECK;
        $record['created_at'] = $record['updated_at'] =date("Y-m-d H:i:s");
        $id = self::insertGetId($record);
        return ['id'=>$id,'code'=>$record['code']];
    }
    
    /**
     * 修改
     * @param unknown $id
     * @param unknown $attr
     */
    public static function change($id,$attr)
    {
        if(!isset($attr['make_uid']))
        {
            return false;
        }        
        $record = [
            'make_uid'=>intval($attr['make_uid']),
        ];
        if(isset($attr['salon_id']))
        {
            $record['salon_id'] =intval($attr['salon_id']);
        }

        if(isset($attr['money']))
        {
            $record['money'] =floatval($attr['money']);
        }
        if(isset($attr['pay_type']))
        {
            $record['pay_type'] =intval($attr['pay_type']);
        } 
        if(isset($attr['cycle']))
        {
            $record['cycle'] =intval($attr['cycle']);
        }
        if(isset($attr['cycle_day']))
        {
            $record['cycle_day'] =intval($attr['cycle_day']);
        }
        if(isset($attr['cycle_money']))
        {
            $record['cycle_money'] =floatval($attr['cycle_money']);
        }
        if(isset($attr['remark']))
        {
            $record['remark'] =$attr['remark'];
        }
        $record['updated_at'] =date("Y-m-d H:i:s");
        $item = self::where('id',$id)->first(['state','code']);     
        if($item->state  != self::STATE_OF_TO_SUBMIT && $item->state  != self::STATE_OF_TO_CHECK && $item->from != self::FROM_LOCAL)
        {
            return false;
        }        
        $record['state'] = PayManage::STATE_OF_TO_CHECK;
        
        self::where('id',$id)->update($record);
        return ['id'=>$id,'code'=>$item->code];
    }
    
    /**
     * 删除
     * @param unknown $id
     */
    public static function destroy($id)
    {
        $item = self::where('id',$id)->first(['state','type','code']);
        if($item->state  != self::STATE_OF_TO_SUBMIT && $item->state  != self::STATE_OF_TO_CHECK && $item->type  != self::TYPE_OF_FTZ && $item->from != self::FROM_LOCAL)
        {
            return false;
        }
        self::where('id',$id)->delete();
        return ['id'=>$id,'code'=>$item->code];
    }
    
    /**
     * 审核
     * @param unknown $id
     * @param unknown $opera
     * @param unknown $uid
     */
    public static function check($ids,$opera,$uid)
    {
        if(is_numeric($ids))
        {
            $ids = [$ids];
        }
        if(!is_array($ids))
        {
            return false;
        }
        
        $items = self::whereIn('id',$ids)->where('state',self::STATE_OF_TO_CHECK)->get(['from','w_id'])->toArray();
        if(count($ids) !== count($items))
        {
            return false;
        }
        $state = self::STATE_OF_TO_SUBMIT;
        if($opera)
        {
            $state = self::STATE_OF_TO_PAY;
        }
        self::whereIn('id',$ids)->where('state',self::STATE_OF_TO_CHECK)->update(
            [
                'state'=>$state,
                'confirm_uid' => intval($uid),  
                'confirm_at' => date("Y-m-d"),
            ]
        );        
        $useful_wids = array_column($items, 'w_id');      
        SalonMoneyWithdraw::whereIn('id',$useful_wids)->update(['state'=>$state,'updated_at'=>date("Y-m-d H:i:s")]);
        return true;
    }
    
    /**
     * 确认
     * @param unknown $id
     * @param unknown $opera
     * @param unknown $uid
     */
    public static function confirm($ids,$opera,$uid)
    {
       
        if(is_numeric($ids))
        {
            $ids = [$ids];
        }
        if(!is_array($ids))
        {
            return false;
        }
        
        $items = self::whereIn('id',$ids)->where('state',self::STATE_OF_TO_PAY)->get()->toArray();
        if(count($ids) !== count($items))
        {
            return false;
        }
        $now_time = time();
        $now_day = date("Y-m-d",$now_time);
        $now_date = date("Y-m-d H:i:s",$now_time);
        $state = self::STATE_OF_TO_CHECK;
        if($opera)
        {
            $state = self::STATE_OF_PAIED;
        }
        self::whereIn('id',$ids)->where('state',self::STATE_OF_TO_PAY)->update(
            [
                'state'=>$state,
                'cash_uid' => intval($uid),
                'pay_day' => $now_day,                
            ]
        );
        
        $w_ids = array_column($items, 'w_id');
        SalonMoneyWithdraw::whereIn('id',$w_ids)->update([
            'state'=>$state,
            'updated_at'=>$now_date
        ]);
        
        //如果是确认付款
        if($opera)
        {
            foreach($items as $item)
            {                
                if($item['type'] == self::TYPE_OF_FJY)
                {
                    $money = floatval($item['money']);
                    $remark = "预付款返还";
                    $type = PrepayBill::TYPE_OF_RETURN;
                    if($money >= 0)
                    {
                        $type = PrepayBill::TYPE_OF_ALREADYPAY;
                        $remark = "预付保证金";
                    }
                    //生成转付单
                    $record = [
                        'other_id'=>$item['id'],
                        'other_code'=>$item['code'],
                        'salon_id'=>$item['salon_id'],
                       // 'merchant_id'=>$item['merchant_id'],
                        'type'=>$type,
                        'uid'=>$uid,
                        'pay_money'=>$item['money'],
                        'pay_type'=>$item['pay_type'],
                        'pay_day'=>$now_day,
                        'remark'=>$item['remark'],
                    ];
                    $res = PrepayBill::makeCompleted($record);
                    self::where('id',$item['id'])->update(['p_id'=>$res['id'],'p_code'=>$res['code']]);
                    //结算
                    ShopCount::count_bill_by_pay_money($item['salon_id'], NULL,$money,$remark,time());
                }
                elseif($item['type'] == self::TYPE_OF_FTZ)
                {                   
                    ShopCount::count_bill_by_invest_money($item['salon_id'], NULL, $item['money']);
                }
            }
        }
        return true;
    }

    public static function makeNewCode($type)
    {
        $prefix = "";
        if ($type == self::TYPE_OF_FTZ)
        {
            $prefix = "FTZ-";
        }
        else
        {
            $prefix = "FJY-";//默认付交易
        }
        
        $prefix .=date("ymdHis");
        
        $class = __CLASS__;
        $obj = new $class;
         
        $count = $obj->where("code","like",$prefix."%")->count();
        $now_num = intval($count) + 1;
        return $prefix.str_pad(strval($now_num), 3,"0",STR_PAD_LEFT);
    }
    
    public function isFillable($key)
    {
        return true;
    }
    
    
    public static function search($options)
    {
        $salon_fields = [
            'salonid',
            'salonname',
            'sn',
        ];
        $base_fields = [
            'pay_manage.id as id',
            'pay_manage.code as code',
            'pay_manage.type as type',
            'pay_manage.salon_id as salon_id',
            'pay_manage.salon_uid as salon_uid',
            'pay_manage.from as from',
            'pay_manage.remark as remark',
            'merchant.id as merchant_id',
            'pay_manage.make_uid as make_uid',
            'pay_manage.confirm_uid as confirm_uid',
            'pay_manage.cash_uid as cash_uid',
            'pay_manage.money as money',
            'pay_manage.pay_type as pay_type',
            'pay_manage.pay_day as pay_day',
            'pay_manage.created_at as created_at',
            'pay_manage.confirm_at as confirm_at',
            'pay_manage.state as state',
        ];
        $order_by_fields = [
            'id',
            'code',
            'type',
            'money',
            'pay_type',
            'pay_day',
            'created_at',
            'state',
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
//             if ($key == 1) {
//                 $base->join("salon",function($join) use($keyword){
//                     $join->on('salon.salonid','=','pay_manage.salon_id')->where('salon.salonname','like',$keyword);
//                 });
//                 // $base->whereRaw("salon_id in (SELECT `salonid` FROM `cm_salon` WHERE `salonname` LIKE '{$keyword}')");
//             } elseif ($key == 2) {
//                 //                 $base->join("merchant",function($join) use($keyword){
//                 //                     $join->on('merchant.id','=','pay_manage.merchant_id')->where('merchant.merchant','like',$keyword);
//                 //                 });
//                 $base->whereRaw("merchant_id in (SELECT `id` FROM `cm_merchant` WHERE `name` LIKE '{$keyword}')");
//             } elseif ($key == 3) {
//                 $base->join("salon",function($join) use($keyword){
//                     $join->on('salon.salonid','=','pay_manage.salon_id')->where('salon.sn','like',$keyword);
//                 });
//                 //$base->whereRaw("salon_id in (SELECT `salonid` FROM `cm_salon` WHERE `sn` LIKE '{$keyword}')");
//             }
        }
        
        $base = self::join('salon',function($join) use($key,$keyword){
           $join->on('salon.salonid','=','pay_manage.salon_id');
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
       
        $base->select($base_fields);
        
        // 付款单类型
        if (isset($options['type']) && !empty($options['type'])) {
            $base->where('pay_manage.type', intval($options['type']) );
        }
        
        // 付款方式类型
        if (isset($options['pay_type']) && !empty($options['pay_type'])) {
            $base->where('pay_manage.pay_type', intval($options['pay_type']) );
        }
        
        // 状态
        if (isset($options['state']) && !empty($options['state'])) {
            $base->where('pay_manage.state', intval($options['state']) );
        }
        
        $base->with([
            'salon' => function ($q) use($salon_fields)
            {
                $q->get($salon_fields);
            }
        ]);
        
        $base->with([
            'make_user' => function ($q)
            {
                $q->get(['id','name']);
            }
        ]);
        
        $base->with([
            'salon_user' => function ($q)
            {
                $q->get(['salon_user_id','username']);
            }
        ]);
    
        // 按时间搜索
        if (isset($options['pay_time_min']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_min']))) {
            $base->where('pay_manage.pay_day', ">=", trim($options['pay_time_min']));
        }
        if (isset($options['pay_time_max']) && preg_match("/^\d{4}\-\d{2}\-\d{2}$/", trim($options['pay_time_max']))) {
            $base->where('pay_manage.pay_day', "<=", trim($options['pay_time_max']))->where('pay_day','>','1970-01-01');
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
        return $base->orderBy($order, $order_by);
    }
    
    public static function detail($id)
    {
        $query = self::where('pay_manage.id',intval($id));
        $query->join('salon',function($join){
            $join->on('salon.salonid','=','pay_manage.salon_id');
        })->join('merchant',function($join){
            $join->on('merchant.id','=','salon.merchantId');
        });
        $fields = [
            'pay_manage.id as id',
            'pay_manage.w_id as w_id',
            'pay_manage.code as code',
            'pay_manage.type as type',
            'pay_manage.r_id as r_id',
            'pay_manage.r_code as r_code',
            'pay_manage.p_id as p_id',
            'pay_manage.p_code as p_code',
            'pay_manage.salon_id as salon_id',
            'merchant.id as merchant_id',
            'pay_manage.money as money',
            'pay_manage.pay_type as pay_type',
           // 'pay_manage.require_day as require_day',
            'pay_manage.pay_day as pay_day',
            'pay_manage.cycle as cycle',
            'pay_manage.cycle_day as cycle_day',
            'pay_manage.cycle_money as cycle_money',
            'pay_manage.make_uid as make_uid',
            'pay_manage.cash_uid as cash_uid',
            'pay_manage.state as state',
            'pay_manage.salon_uid as salon_uid',
            'pay_manage.from as from',
            'pay_manage.created_at as created_at',
            'pay_manage.confirm_uid as confirm_uid',
            'pay_manage.confirm_at as confirm_at',
            'pay_manage.updated_at as updated_at',
            'pay_manage.remark as remark',
        ];
        $query->with([
            'make_user' => function ($q)
            {
                $q->get(['id','name']);
            }
        ])->with([
            'confirm_user' => function ($q)
            {
                $q->get(['id','name']);
            }
        ])->with([
            'cash_user' => function ($q)
            {
                $q->get(['id','name']);
            }
        ])->with([
            'salon_user' => function ($q)
            {
                $q->get(['salon_user_id','username']);
            }
        ])->with([
            'salon' => function ($q)
            {
                $q->get(['salonid','salonname','sn']);
            }
        ])->with([
            'merchant' => function ($q)
            {
                $q->get(['id','name']);
            }
        ]);
        $obj = $query->first($fields);
        if(empty($obj))
        {
            return [];
        }
        return $obj->toArray();
    }

}
