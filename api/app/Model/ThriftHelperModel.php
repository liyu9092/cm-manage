<?php
namespace App;

use Thrift\Transport\TSocket;
use Thrift\Transport\TFramedTransport;
use Thrift\Protocol\TMultiplexedProtocol;
use Thrift\Protocol\TBinaryProtocol;

define('EXT_PATH', dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."ext".DIRECTORY_SEPARATOR);
define('THRIFT_SERVICE', EXT_PATH."service".DIRECTORY_SEPARATOR);

require_once EXT_PATH.'Thrift/ClassLoader/ThriftClassLoader.php';
$loader = new \Thrift\ClassLoader\ThriftClassLoader();
$loader->registerNamespace('Thrift', EXT_PATH);
$loader->register();
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/BaseService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/PushTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/PushService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/SellerService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/SellerTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/SearchService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/SearchTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/SmsService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/SmsTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/FileService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/FileTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/CommentService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/CommentTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/UserService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/UserTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/TradeService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/TradeTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/CommonTypes.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/VoucherService.php';
require_once THRIFT_SERVICE.'cn/choumei/thriftserver/service/stub/gen/VoucherTypes.php';

/**
 * thrift 调用
 */

class ThriftHelperModel
{
    
    private $protocol = null;
    private $transport = null;
    private static $serviceProtocol = null;
    
    private $service = null;
    private $method = null;
    
    private static $clients = array(
        'seller-center' => '\cn\choumei\thriftserver\service\stub\gen\SellerServiceClient',
        'push-center' => '\cn\choumei\thriftserver\service\stub\gen\PushServiceClient',
        'search-center' => '\cn\choumei\thriftserver\service\stub\gen\SearchServiceClient',
        'file-center' => '\cn\choumei\thriftserver\service\stub\gen\FileServiceClient',
        'sms-center' => '\cn\choumei\thriftserver\service\stub\gen\SmsServiceClient',
        'comment-center' => '\cn\choumei\thriftserver\service\stub\gen\CommentServiceClient',
        'user-center' => '\cn\choumei\thriftserver\service\stub\gen\UserServiceClient',
        'trade-center' => '\cn\choumei\thriftserver\service\stub\gen\TradeServiceClient',
        'voucher-center' => '\cn\choumei\thriftserver\service\stub\gen\VoucherServiceClient',
    );
    
    private static $serverConf = array(
        /*
        'seller-center' => array('IP' => '192.168.10.49', 'PORT' => 9090),
        'push-center' => array('IP' => '192.168.10.58', 'PORT' => 9090),
        'search-center' => array('IP' => '192.168.10.58', 'PORT' => 9090),
        'file-center' => array('IP' => '192.168.10.58', 'PORT' => 9090),
        'sms-center' => array('IP' => '192.168.10.49', 'PORT' => 9090),
        'comment-center' => array('IP' => '192.168.10.49', 'PORT' => 9090),
        'user-center' => array('IP' => '192.168.10.49', 'PORT' => 9090),
        'trade-center' => array('IP' => '192.168.10.49', 'PORT' => 9090),
        'voucher-center' => array('IP' => '192.168.10.49', 'PORT' => 9090),
         */
    );
    
    public function __construct($service='', $method='') {
        // thrift autoload
        
        
        $this->service = $service;
        $this->method = $method;
        $thrift_server_ip = env('THRIFT_SERVER_IP');
        $thrift_server_port = env('THRIFT_SERVER_PORT');
        self::$serverConf = array(
            'seller-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'push-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'search-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'file-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'sms-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'comment-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'user-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'trade-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
            'voucher-center' => array('IP' => $thrift_server_ip, 'PORT' => $thrift_server_port),
        );
    }
    
    public function setSerivice($service)
    {
        $this->service = $service;
    }
    
    public function setMethod($method)
    {
        $this->method = $method;
    }
    
    private function getProtocol($serviceName)
    {
        if(self::$serviceProtocol != null 
        && array_key_exists($serviceName, self::$serviceProtocol) 
        && self::$serviceProtocol[$serviceName] != null 
        && self::$serviceProtocol[$serviceName] instanceof TMultiplexedProtocol
        )
        {
            self::$serviceProtocol[$serviceName]->getTransport()->isOpen() || self::$serviceProtocol[$serviceName]->getTransport()->open();
            return self::$serviceProtocol[$serviceName];
        }
            
        $conf = self::$serverConf[$serviceName];
        $socket = new TSocket($conf['IP'], $conf['PORT']);
        $socket->setSendTimeout(10000);
        $socket->setRecvTimeout(10000);
        $transport = new TFramedTransport($socket, 494, 494);        
        $transport->open();          
        $protocol = new TMultiplexedProtocol(new TBinaryProtocol($transport), $serviceName);
        self::$serviceProtocol[$serviceName] = $protocol;
        return $protocol;
    }
    
    public function Request($serviceName, $method, $paramArr)
    {
        error_reporting(E_ALL);
        if(empty($serviceName) || empty($method))
            return false;
        
        $clientName = self::$clients[$serviceName];
        if(!class_exists($clientName))
            return false;
        
        if(!method_exists($clientName, $method))
            return false;
        
        try{
            $protocol = $this->getProtocol($serviceName);
            $client = new $clientName($protocol);
            $result = call_user_func_array([$client,$method], $paramArr);
            return $this->handleThriftResult($result);
        } catch (\Exception $ex) {
            $msg = $ex->getTraceAsString();
            Utils::log("ThriftError", date("Y-m-d H:i:s")."\n".$msg."\n");
            return false;
        }
    }
    
    private function handleThriftResult($result)
    {
        if($result == false || is_null($result))
            return false;
        
        $this->convertObj2Array($result);
        if(!array_key_exists('result', $result) || $result['result'] != 'success')
            return false;
        
        return $result['data'];
    }
    
    private function convertObj2Array(&$var)
    {
        if(is_object($var))
            $var = (array)$var;
        if(!is_array($var))
            return;
        foreach($var as &$v)
            $this->convertObj2Array($v);
    }
    
  
 
}