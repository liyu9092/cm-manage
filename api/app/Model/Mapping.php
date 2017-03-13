<?php
/**
 * 数据库字段 平台常用 数字对应的意思
 * 配置规范 XXXXXNames
 * 调用规范 getXXXXXName (字符串) getXXXXXNames (数组)
 */
namespace App;

class Mapping
{   
    //demo Mapping::getPayTypeName(1)  //return '银行存款'
    //demo Mapping::getPayTypeName(4)  //return ''
    //demo Mapping::getPayTypeName(4,[],'未知')  //return '未知'
    //demo Mapping::getPayTypeName(2,[2=>'易联支付'],'未知')  //return '易联支付'
    //demo Mapping::getPayTypeNames([1,2])  //return ['银行存款','账扣支付']
    
    /**
     * 
     * @return multitype:string
     */
    public static function PayTypeNames()
    {
        return [
            1=>'银行存款',
            2=>'账扣支付',
            3=>'现金',
            4=>'支付宝',
            5=>'财付通',
            6=>'其他',
        ];
    }
    
    public static function ShopTypeNames()
    {
        return [
            1 => '预付款店',
            2 => '投资店',
            3 => '金字塔店',
        ];
    }
    
    public static function PayManageStateNames()
    {
        return [
            PayManage::STATE_OF_TO_SUBMIT => '待提交',
            PayManage::STATE_OF_TO_CHECK => '待审批',
            PayManage::STATE_OF_TO_PAY => '待付款',
            PayManage::STATE_OF_PAIED => '已付款',
        ];
    }
    
    public static function PrepayStateNames()
    {
        return [
            PrepayBill::STATE_OF_PREVIEW => '预览',
            PrepayBill::STATE_OF_TO_SUBMIT => '待提交',
            PrepayBill::STATE_OF_TO_CHECK => '待审批',
            PrepayBill::STATE_OF_TO_PAY => '待付款',
            PrepayBill::STATE_OF_COMPLETED => '已付款',
        ];
    }
    
    public static function RefundRereasonNames()
    {
        return [
            0=>'去过了,不太满意',
            1=>'朋友/网上评价不好',
            2=>'买多了/买错了',
            3=>'计划有变,没时间去',
            4=>'后悔了,不想要了',
            5=>'其他',
        ];
    }
    
    public static function BeautyRefundRereasonNames()
    {
        return [
            0=>'买错了／买多了',
            1=>'朋友／网上评价不好',
            2=>'担心伤害健康',
            3=>'太贵了',
            4=>'去过了不太满意',
        ];
    }
    
    public static function FundflowPayTypeNames()
    {
        return [
            1=>'网银',
            2=>'支付宝',
            3=>'微信',
            4=>'余额',
            5=>'红包',
            6=>'优惠券',
            7=>'积分',
            8=>'邀请码兑换',
            9=>'现金券',
            10=>'易联',
        ];
    }    
    
    public static function OrderStatusNames()
    {
        return [
            2=>'未使用',
            3=>'使用部分',
            4=>'使用完成',
            5=>'作废',
            6=>'申请退款',
            7=>'退款完成',
            9=>'退款失败 ',
            10=>'退款中',
        ];
    }
    
    public static function OrderIsPayNames()
    {
        return [
            1=>'未付款',
            2=>'已付款',
        ];
    }
    
    public static function OrderRefundRetypeNames()
    {
        return [
            1=>'原路返还',
            2=>'退回余额',
        ];
    }
    
    public static function BookingOrderStatusNames()
    {
        return [
            'NEW'=>'未支付',
            'PYD'=>'已支付',
            'CSD'=>'已完成',
            'RFN'=>'退款中',
            'RFD'=>'已退款',
            'RFD-OFL'=>'线下已退款',
            'RFE'=>'退款失败',
        ];
    }
    
    public static function BookingCashPayTypeNames()
    {
        return [
            1=>'微信',
            2=>'支付宝',
            3=>'POS机',
            4=>'现金',
            5=>'微信+现金',
            6=>'支付宝+现金',
            7=>'POS机+现金',
        ];
    }
    
    public static function BookingSalonRefundBacktoNames()
    {
        return [
            1=>'微信',
            2=>'支付宝',
            3=>'银联',
            4=>'现金',
        ];
    }
    
    public static function __callStatic($method,$args=[])
    {
        $prefix = substr($method, 0,3);
        if($prefix == "get")
        {
            $length = strlen($method);
            $can_call = false;
            $get_as_single = false;
            $subfix = substr($method,$length-4);
             
            if($subfix == "Name")
            {
                $can_call = true;
                $get_as_single = true;
                $source_method_name = substr($method, 3)."s";
            }
    
            $subfix = substr($method,$length-5);
            if($subfix == "Names")
            {
                $can_call = true;
                $get_as_single = false;
                $source_method_name = substr($method, 3);
            }
            if($can_call && is_callable([self::class, $source_method_name]))
            {
    
                $sources = call_user_func_array([self::class, $source_method_name],[]);
                $default = '';
                $key = null;
                if(isset($args[0]))
                {
                    $key = $args[0];
                }
                if(isset($args[1]))
                {
                    $sources = self::merge_strict($sources,$args[1]);
                }
                if(isset($args[2]))
                {
                    $default = $args[2];
                }
                if(!empty($key))
                {
                    if($get_as_single)
                    {
                        return call_user_func_array([self::class,'getMappingName'], [$key,$sources,$default]);
                    }
                    else
                    {
                        return call_user_func_array([self::class,'getMappingNames'], [$key,$sources,$default]);
                    }
                }
            }
        }
    }
    
    private static function getMappingName($key,$resources,$default="")
    {
        if(isset($resources[$key]))
        {
            return $resources[$key];
        }
        return $default;
    }

    private static function getMappingNames($keys,$resources,$default="")
    {
        $res = [];
        foreach ($keys as $key)
        {
            $res[] = isset($resources[$key])?$resources[$key]:$default;
        }
        return $res;
    }

    private static function merge_strict($source,$replace)
    {
        foreach($replace as $key => $val)
        {
            $source[$key] = $val;
        }
        return $source;
    }
}
