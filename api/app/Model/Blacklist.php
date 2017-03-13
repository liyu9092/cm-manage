<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
Use PDO;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use Illuminate\Support\Facades\Redis as Redis;

class Blacklist extends Model {

    protected $table = 'blacklist';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static function getQueryByParam($input) {
        $query = Self::getQuery();
        //
        if (isset($input['keywordType']))
            switch ($input ["keywordType"]) {
                case "0" : // 用户手机号				
                    $query->whereNotNull('mobilephone')->where('mobilephone','<>','');
                    break;
                case "1" : // 设备号
                    $query->whereNotNull('device_uuid')->where('device_uuid','<>','');
                    break;
                case "2" ://openid
                    $query->whereNotNull('openid')->where('openid','<>','');
                    break;
                default:
                    throw new ApiException('黑名单无此类别！', 1);
            }
        // 是否有输入关键字搜索 
        if (!empty($input["keyword"])) {
            $val = $input ["keyword"];
            $val = addslashes($val);
            $val = str_replace(['_', '%'], ['\_', '\%'], $val);
            switch ($input ["keywordType"]) {

                case "0" : // 用户手机号				
                    $query->where('mobilephone', 'like', '%' . $val . '%');
                    break;
                case "1" : // 设备号
                    $query->where('device_uuid', 'like', '%' . $val . '%');
                    break;
                case "2" ://openid
                    $query->where('openid', 'like', '%' . $val . '%');
                    break;
                default:
                    throw new ApiException('黑名单无此类别关键词！', 1);
            }
        }
        //提交时间

        if (!empty($input["minTime"])) {
            $query = $query->where('created_at', '>=', $input['minTime']);
        }
        if (!empty($input["maxTime"])) {
            $query = $query->where('created_at', '<=', $input['maxTime'] . ' 24');
        }
        return $query;
    }

    /**
     * 黑名单列表搜索
     * @param type $query
     * @param type $page
     * @param type $size
     * @param type $sortKey
     * @param type $sortType
     * @return string
     */
    public static function search($query, $page, $size) {

        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });

        $blacklists = $query->orderBy("created_at", "DESC")->paginate($size)->toArray();

        return $blacklists;
    }

    public static function format_export_data($datas, $keywordType) {
        $res = [];
        foreach ($datas as $key => $data) {
            switch ($keywordType) {
                case "0" : // 用户手机号				
                    $keyword = isset($data['mobilephone']) ? ' ' . $data['mobilephone'] : '';
                    break;
                case "1" : // 设备号
                    $keyword = isset($data['device_uuid']) ? ' ' . $data['device_uuid'] : '';
                    break;
                case "2" ://openid
                    $keyword = isset($data['openid']) ? ' ' . $data['openid'] : '';
                    break;
                default:
                    throw new ApiException('黑名单无此类别！', 1);
            }
            $addTime = isset($data['created_at']) ? ' ' . $data['created_at'] : '';
            $note = isset($data['note']) ? $data['note'] : '';
            $res[] = [
                $key+1,
                $keyword,
                $addTime,
                $note,
            ];
        }
        return $res;
    }
    
    public static function getName(){
		$redis = Redis::connection();
		$key = 'blacklist-'.date('ymd');
		if($redis->get($key)==FALSE)
			$redis->setex($key,3600*24,0);
		$name = $redis->incr($key);
		$name = str_pad($name, 3,'0',STR_PAD_LEFT);
		return 'blacklist'.$name;
	}
    
    public static function getStatusbyUserMobile($userMobile){
		$result = Self::getQuery()->where("mobilephone",$userMobile)->get();
        if($result)
        {
            return 1;
        }
        else
            return 0;
	}
    
    public static function getStatusbyUserDevice($device){
		$result = Self::getQuery()->where("device_uuid",$device)->get();
        if($result)
        {
            return 1;
        }
        else
            return 0;
	}
    
    public static function getStatusbyOpenId($openId){
		$result = Self::getQuery()->where("openid",$openId)->get();
        if($result)
        {
            return 1;
        }
        else
            return 0;
	}
    

}
