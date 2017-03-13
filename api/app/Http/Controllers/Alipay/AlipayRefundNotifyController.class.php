<?php

namespace App\Http\Controllers\Alipay;
use App\Http\Controllers\Controller;
Use App\AlipaySimple;
use App\BountyTask;
Use App\Utils;
class AlipayRefundNotifyController extends Controller{

	/**
	 * 支付宝退款的回调 赏金单
	 */
	function callback_alipay()
	{
	    // 记录回调传入的值 
        $param=$this->param;   
	    Utils::log('pay',date("Y-m-d H:i:s") . "\t " . json_encode($param,JSON_UNESCAPED_UNICODE)."\t\n", "alipay_callback_bounty");
	
	    //以下为debug的写法
//	    $ret = AlipaySimple::callback(array(D("Bounty"),"alipayCallback"),[],true);
	
	    //以下为正式的写法
	    $ret = AlipaySimple::callback(function($args){
            return BountyTask::alipayCallback($args);
        },[]);
	
	    if($ret)
	    {
	        echo "success";
	    }
	    else
	    {
	        echo "fail";
	    }
	    die();
	}	
}
?>