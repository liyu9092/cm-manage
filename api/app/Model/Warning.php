<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use PDO;
use URL;
use Log;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use Illuminate\Support\Facades\Redis as Redis;

class Warning extends Model {

    protected $table = 'order';
    protected $primaryKey = 'orderid';
    public $timestamps = false;

    public static function searchOrder($input, $page, $size) {
        //如果搜索条件相同,直接从缓存读取数据
        $key = md5(serialize($input).$page.$size);
        $redis = Redis::connection();
        if($result = $redis->get($key))
            return unserialize($result);


        $query = Self::getQuery();
        // 是否有输入关键字搜索 
        $val = '';
        if (!empty($input["keyword"])) {
            $val = $input ["keyword"];
            $val = addslashes($val);
            $val = str_replace(['_', '%'], ['\_', '\%'], $val);
        }
        $orderNum = $input['orderNum'];
        switch ($input ["keywordType"]) {

            case "0" : // 用户手机号     
                $fields = [DB::raw("COUNT(DISTINCT cm_order.ordersn) as orderNum"), DB::raw("MAX(cm_order.add_time)as maxOrderTime"), "user.mobilephone as userMobile", "order.user_id as userId"];
                $query->select($fields)->join('user', 'user.user_id', '=', 'order.user_id')->groupBy('order.user_id')->having(DB::raw("COUNT(DISTINCT cm_order.ordersn)"), '>=', $orderNum);
                if (!empty($val)) {
                    $query->where('user.mobilephone', 'like', '%' . $val . '%');
                }

                break;
            case "2" : // openId

                $fields = [DB::raw("COUNT(DISTINCT cm_request_log.ORDER_SN) as orderNum"), DB::raw("MAX(cm_order.add_time)as maxOrderTime"), "request_log.OPENID as openId"];
                $query->select($fields)->join('request_log', function($join) {
                        $join->on('request_log.ORDER_SN', '=', 'order.ordersn')->orOn('request_log.ORDER_SN', '=', 'order.shopcartsn');
                    })
                    ->groupBy('request_log.OPENID')->having(DB::raw("COUNT(DISTINCT cm_request_log.ORDER_SN)"), '>=', $orderNum)->whereNotNull("request_log.OPENID");
                if (!empty($val)) {
                    $query->where('request_log.OPENID', '=', $val);
                }

                break;
            case "1" ://设备号
                $fields = [DB::raw("COUNT(DISTINCT cm_request_log.ORDER_SN) as orderNum"), DB::raw("MAX(cm_order.add_time)as maxOrderTime"), "request_log.DEVICE_UUID as device"];
                $query->select($fields)->join('request_log', function($join) {
                    $join->on('request_log.ORDER_SN', '=', 'order.ordersn')->orOn('request_log.ORDER_SN', '=', 'order.shopcartsn');
                })->groupBy('request_log.DEVICE_UUID')->having(DB::raw("COUNT(DISTINCT cm_request_log.ORDER_SN)"), '>=', $orderNum)->whereNotNull("request_log.DEVICE_UUID");
                if (!empty($val)) {
                    $query->where('request_log.DEVICE_UUID', '=', $val);
                }
                break;
            default:
                throw new ApiException('不支持其他类似搜索！', 1);
        }

        //时间范围
        if (!empty($input["minTime"])) {
            if ($input["minTime"] < "2015-11-25") {
                $input["minTime"] = "2015-11-25";
            }
            $minTime = strtotime($input["minTime"]);
            if ($minTime) {
                $query->where('order.add_time', '>=', $minTime);
            }
        }
        if (!empty($input["maxTime"])) {
            $maxTime = strtotime($input["maxTime"]);
            if ($maxTime) {
                $maxTime += 86399;
                $query->where('order.add_time', '<=', $maxTime);
            }
        }

        $offset = ($page-1)*$size;
        $nums = $query->where('order.ispay', '=', 2)
            ->orderBy(DB::raw("MAX(cm_order.add_time)"), "DESC")
            ->take($size)
            ->skip($offset)
            ->get();
        $return['data'] = $nums;
        $return['current_page'] = $page;
        $redis->setex($key,3600*24,serialize($return));
        return $return;
    }

    public static function getOderNumByUserId($userId, $minTime, $maxTime) {
        $query = Self::getQuery();
        //时间范围
        if (!empty($minTime)) {
            $minTime = strtotime($minTime);
            if ($minTime) {
                $query->where('order.add_time', '>=', $minTime);
            }
        }
        if (!empty($maxTime)) {
            $maxTime = strtotime($maxTime);
            if ($maxTime) {
                $maxTime += 86399;
                $query->where('order.add_time', '<=', $maxTime);
            }
        }
        $fields = [DB::raw("COUNT(DISTINCT IF(cm_order.shopcartsn='', cm_order.ordersn, cm_order.shopcartsn)) as payNum"), DB::raw("COUNT(DISTINCT cm_order.ordersn) as orderNum"), "order.user_id as userId"];
        return $query->select($fields)->join('user', 'user.user_id', '=', 'order.user_id')->where('user.user_id', '=', $userId)->where('order.ispay', '=', 2)->first();
    }

    public static function getOderNumByOpenId($openId, $minTime, $maxTime) {
        $query = Self::getQuery();
        $query1=Self::getQuery();
        //时间范围
        if (!empty($minTime)) {
            $minTime = strtotime($minTime);
            if ($minTime) {
                $query->where('order.add_time', '>=', $minTime);
                $query1->where('order.add_time', '>=', $minTime);
            }
        }
        if (!empty($maxTime)) {
            $maxTime = strtotime($maxTime);
            if ($maxTime) {
                $maxTime += 86399;
                $query->where('order.add_time', '<=', $maxTime);
                $query1->where('order.add_time', '<=', $maxTime);
            }
        }
        
        $fields = [DB::raw("COUNT(DISTINCT IF(cm_order.shopcartsn='', cm_order.ordersn, cm_order.shopcartsn)) as payNum"), DB::raw("COUNT(DISTINCT cm_order.ordersn) as orderNum"), "request_log.OPENID as openId"];
        $orderNum2 = $query1->select($fields)->join('request_log', 'request_log.ORDER_SN', '=', 'order.shopcartsn')->where('request_log.OPENID', '=', $openId)->where('order.ispay', '=', 2)->first();
        $orderNum1 = $query->select($fields)->join('request_log', 'request_log.ORDER_SN', '=', 'order.ordersn')->where('request_log.OPENID', '=', $openId)->where('order.ispay', '=', 2)->first();
        
        $orderNum['payNum'] = $orderNum1['payNum'] + $orderNum2['payNum'];
        $orderNum['orderNum'] = $orderNum1['orderNum'] + $orderNum2['orderNum'];
        $orderNum['openId'] = $openId;
        return $orderNum;
    }

    public static function getOderNumByDevice($device, $minTime, $maxTime) {
        $query = Self::getQuery();
        $query1=Self::getQuery();
        //时间范围
        if (!empty($minTime)) {
            $minTime = strtotime($minTime);
            if ($minTime) {
                $query->where('order.add_time', '>=', $minTime);
                $query1->where('order.add_time', '>=', $minTime);
            }
        }
        if (!empty($maxTime)) {
            $maxTime = strtotime($maxTime);
            if ($maxTime) {
                $maxTime += 86399;
                $query->where('order.add_time', '<=', $maxTime);
                $query1->where('order.add_time', '<=', $maxTime);
            }
        }
        $fields = [DB::raw("COUNT(DISTINCT IF(cm_order.shopcartsn='', cm_order.ordersn, cm_order.shopcartsn)) as payNum"), DB::raw("COUNT(DISTINCT cm_order.ordersn) as orderNum"), "request_log.DEVICE_UUID as device"];
        $orderNum1 = $query->select($fields)->join('request_log', 'request_log.ORDER_SN', '=', 'order.ordersn')->where('request_log.DEVICE_UUID', '=', $device)->where('order.ispay', '=', 2)->first();
        $orderNum2 = $query1->select($fields)->join('request_log', 'request_log.ORDER_SN', '=', 'order.shopcartsn')->where('request_log.DEVICE_UUID', '=', $device)->where('order.ispay', '=', 2)->first();
        $orderNum['payNum'] = $orderNum1['payNum'] + $orderNum2['payNum'];
        $orderNum['orderNum'] = $orderNum1['orderNum'] + $orderNum2['orderNum'];
        $orderNum['device'] = $device;
        return $orderNum;
    }

    public static function format_export_data($datas, $keywordType) {
        $res = [];
        Log::info("waring data is", $datas);
        foreach ($datas as $key => $data) {
            switch ($keywordType) {
                case "0" : // 用户手机号             
                    $keyword = isset($data['userMobile']) ? ' ' . $data['userMobile'] : '';
                    break;
                case "1" : // 设备号
                    $keyword = isset($data['device']) ? ' ' . $data['device'] : '';
                    break;
                case "2" ://openid
                    $keyword = isset($data['openId']) ? ' ' . $data['openId'] : '';
                    break;
                default:
                    throw new ApiException('预警查询无此类别！', 1);
            }

            $loginNum = !empty($data['loginNum']) ? $data['loginNum'] : '0';
            $payNum = !empty($data['payNum']) ? $data['payNum'] : '0';
            $orderNum = !empty($data['orderNum']) ? $data['orderNum'] : '0';
            $res[] = [
                $key + 1,
                $keyword,
                $loginNum,
                $payNum,
                $orderNum,
            ];
        }
        return $res;
    }

}
