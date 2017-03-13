<?php
namespace App;

require_once (dirname(__FILE__)."/../../ext/Alipay/lib/alipay_notify.class.php");
require_once (dirname(__FILE__)."/../../ext/Alipay/lib/alipay_submit.class.php");

class AlipaySimple
{    
    /**
     * 退款返回格式 html(form 表单)
     *
     * @var INT
     */
    CONST REFUND_RETURN_TYPE_HTML = 1;
    
    CONST REFUND_RETURN_TYPE_ARRAY = 2;
    
    protected static $config = null;
    
    /**
     * 支付宝退款
     *
     * @param array $options
     *            ['batch_no'=>"ZXXXX批次号","detail_data"=>[ ["tn"=>"原付款支付宝交易号1","money"=>"退款总金额1","reason"=>"退款理由1 (256字节以内)"],["tn"=>"原付款支付宝交易号2","money"=>"退款总金额2","reason"=>"退款理由2(256字节以内)"],... ] , "notify_url"=>"退款回调 url"]
     * @param int $ret_type
     *            需要的返回参数
     * @return mixed 根据$ret_type 的值返回 html的form表单 或者array的参数数值
     */
    public static function refund($options, $ret_type = self::REFUND_RETURN_TYPE_HTML)
    {
        if (empty($options['batch_no']) || empty($options['detail_data']) || count($options['detail_data']) < 1) {
            throw new \Exception("wrong refund args !");
        }
        $input_config = [];
        $details = [];
        foreach ($options['detail_data'] as $detail) {
            $details[] = $detail['tn'] . "^" . $detail['money'] . "^" . $detail['reason'];
        }
        $options['detail_data'] = implode("#", $details);
        $options['batch_num'] = strval(count($details));
        $params = self::getDefualtRefundParams();
        $params = array_merge($params, $options);
    
        $alipay_submit = new \AlipaySubmit(self::getConfig());
        $res = null;
        if ($ret_type == self::REFUND_RETURN_TYPE_ARRAY) {
            $res = $alipay_submit->buildRequestPara($params);
        } else {
            $res = $alipay_submit->buildRequestForm($params, "post", "确认退款");
        }
        if($ret_type == self::REFUND_RETURN_TYPE_ARRAY)
        {
            
            Utils::log('pay',json_encode($res,JSON_UNESCAPED_UNICODE)."\n", "alipay_refund_form_params");           
        }
        else
        {
            Utils::log('pay',$res."\n", "alipay_refund_form");
        }
        return $res;
    }
    
    /**
     * 支付宝的回调 (除了此函数外的 调用回调 不允许打印任何字符 以免支付宝的重复通知)
     *
     * @param array $option
     * @param
     *            array 一组回调函数参数是 用于成功后用户处理自己的业务
     *            @echo 打印 "success" 支付宝客户端的需求
     * @return bool
     */
    public static function callback($callback = null, $callback_args = [] ,$is_debug = false)
    {
        $verify_result = false;
        if(!$is_debug)
        {
            $config = self::getConfig();
            // 计算得出通知验证结果
            $alipayNotify = new \AlipayNotify($config);
             
            $verify_result = $alipayNotify->verifyNotify();
        }
         
        if ($is_debug || $verify_result) { // 验证成功
    
            if (! empty($callback)) {
    
                $batch_no = $_POST['batch_no'];
    
                // 批量退款数据中转账成功的笔数
    
                $success_num = intval($_POST['success_num']);
    
                // 批量退款数据中的详细信息
                if($is_debug)
                {
                    $result_details = $_POST['result_details'];
                    if(empty($result_details))
                    {
                        $result_details = $_GET['result_details'];
                    }
                }
                else
                {
                    $result_details = $_POST['result_details'];
                }
    
                $details = self::formatCallbackData($result_details);
                $details['batch_no'] = $batch_no;
                $details['success_num'] = $success_num;
                array_unshift($callback_args, $details);
                try {
                    call_user_func_array($callback, $callback_args);
                } catch (\Exception $e) {
                    Utils::log('pay',date("Y-m-d H:i:s")."\t".$e->getMessage()."\n", "alipay_error");
                }
            }
        } else {
            Utils::log('pay',date("Y-m-d H:i:s")."\t"."verify field! \t the data: ".json_encode(['GET' => $_GET, "POST" => $_POST],JSON_UNESCAPED_UNICODE)."\n", "alipay_error");
            return false;
        }
        return true;
    }
    
    /**
     * 获取随机批次号
     *
     * @param number $length
     *            位数在[14 , 32] 之间
     *            return number
     */
    public static function getRandomBatchNo($length = 14)
    {
        $time_arr = explode(" ", microtime());
        $seconds = intval($time_arr[1]);
        $micro_second = round(floatval($time_arr[0]) * 1000);
        $batch_str = date("YmdHis", $seconds) . $micro_second;
        $current_length = strlen($batch_str);
        if ($current_length > $length) {
            $batch_str = substr($batch_str, 0, $length);
        } 
        elseif ($current_length < $length) {
                $batch_str = str_pad($batch_str, $length - $current_length, "0", STR_PAD_RIGHT);
            }
        return $batch_str;
    }
    
    public static function getDefualtRefundParams()
    {
        $config  = [
            "service" => "refund_fastpay_by_platform_pwd", // 三方接口名称
            "partner" => "2088701753684258", // 合作者身份ID
            "_input_charset" => "utf-8", // 参数编码字符集
            "notify_url" => "", // 回调的url 200字符以内
            "seller_email" => "zfb@choumei.cn", // 卖家支付宝账号 如果卖家Id 已填,则此字 段可为空.
            "seller_user_id" => "", // 卖家用户 ID 卖家支付宝账号对应的支付宝唯一用户号.以 2088 开头的纯 16 位数字.登录时,seller_email 和seller_user_id 两者必填一个.如果两者都填,以seller_user_id 为准.
            "refund_date" => date("Y-m-d H:i:s"), // 退款请求的当前时间
            "batch_no" => "", // 退款批次号
            "batch_num" => "", // 总笔数
            "detail_data" => ""
        ] // 单笔数据集
        ;
        
        //测试环境账号独立
        if(strtolower(env("APP_ENV","")) == "test")
        {
            $config['partner'] = "2088911446736234";
            $config['seller_email'] = "zfb2@choumei.cn";
        }
        
        return $config;
    }
    
    /**
     * 获取配置
     *
     * @param array $config
     *            要替换默认参数的项
     */
    public static function getConfig()
    {
        if (is_null(self::$config)) {
            $alipay_base_path = self::getAlipayPemPath();
    
            $alipay_config['partner'] = '2088701753684258';
    
            // 商户的私钥（后缀是.pem）文件相对路径
            $alipay_config['private_key_path'] = $alipay_base_path . 'rsa_private_key.pem';
    
            // 支付宝公钥（后缀是.pem）文件相对路径
            $alipay_config['ali_public_key_path'] = $alipay_base_path . 'alipay_public_key.pem';
    
            // ↑↑↑↑↑↑↑↑↑↑请在这里配置您的基本信息↑↑↑↑↑↑↑↑↑↑↑↑↑↑↑
    
            // 签名方式 不需修改
            $alipay_config['sign_type'] = strtoupper('MD5');
    
            // 字符编码格式 目前支持 gbk 或 utf-8
            $alipay_config['input_charset'] = strtolower('utf-8');
    
            // ca证书路径地址，用于curl中ssl校验
            // 请保证cacert.pem文件在当前文件夹目录中
            $alipay_config['cacert'] = $alipay_base_path . 'cacert.pem';
    
            // 安全检验码，以数字和字母组成的32位字符
            $alipay_config['key'] = '6jwcsuim3w6i6k3r4smv5g0xu2cwvt2s';
    
            // 访问模式,根据自己的服务器是否支持ssl访问，若支持请选择https；若不支持请选择http
            $alipay_config['transport'] = 'http';
            //测试环境账号独立
            if(strtolower(env("APP_ENV","")) == "test")
            {
                $alipay_config['partner'] = "2088911446736234";
                $alipay_config['private_key_path'] = $alipay_base_path . 'rsa_private_key.test.pem';
                $alipay_config['key'] = 's4gziv0g4ryj1my1as9djjomhp5fz3ia';
            }
            self::$config = $alipay_config;
        }
        return self::$config;
    }
    
    /**
     * 设置配置
     *
     * @param unknown $name
     * @param string $value
     */
    public static function setConfig($name, $value = NULL)
    {
        if (is_array($name)) {
            self::$config = array_merge(self::$config, $name);
        }
        if (is_string($name)) {
            self::$config[$name] = $value;
        }
    }
    
    /**
     * 支付宝文件的路径
     *
     * @return string
     */
    public static function getAlipayPemPath()
    {
        return dirname(__FILE__)."/../../ext/Alipay/pem/";
    }
    
    /**
     * 格式化支付宝回调时传入的数据
     *
     * @param string $data
     *            like 2010031906272929^80^SUCCESS$jax_chuanhang@alipay.com^2088101003147483^0.01^SUCCESS
     */
    public static function formatCallbackData($data)
    {
        $details = [];
        // 退款成功的数据
        $details['success'] = [];
        // 退款失败的数据
        $details['failed'] = [];
    
        $data_array = explode("#", $data);
        foreach ($data_array as $item) {
            $item_details = explode("^", $item);
            $count = count($item_details);
            if ($count >= 3) {
                if (substr($item_details[2], 0, 7) === "SUCCESS") {
                    $details['success'][] = [
                        'tn' => $item_details[0],
                        'money' => $item_details[1]
                    ];
                }
            }
        }
        return $details;
    }
}
