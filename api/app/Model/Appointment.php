<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Log;
Use PDO;
Use URL;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class Appointment extends Model {

    protected $table = 'stylist_appointment';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static function getQueryByParam($input) {
        $query = Self::getQuery();
        // 是否有输入关键字搜索 
        if (!empty($input["keyword"])) {
            $val = $input ["keyword"];
            $val = addslashes($val);
            $val = str_replace(['_', '%'], ['\_', '\%'], $val);
            switch ($input ["keywordType"]) {

                case "0" : // 用户臭美号					
                    $query->whereIn('user_id', function($query)use ($val) {
                        $query->select('user_id')
                            ->from("user")
                            ->where('username', 'like', '%' . $val . '%');
                    });
                    break;
                case "1" : // 用户手机号
                    $query->whereIn('user_id', function($query)use ($val) {
                        $query->select('user_id')
                            ->from("user")
                            ->where('mobilephone', 'like', '%' . $val . '%');
                    });
                    break;
                case "2" ://商铺名称
                    $query->whereIn('stylist_id', function($query)use ($val) {
                        $query->select('stylistId')->from("hairstylist")->whereIn('salonId', function($query)use ($val) {
                            $query->select('salonid')
                                ->from("salon")
                                ->where('salonname', 'like', '%' . $val . '%');
                        });
                    });
                    break;
                case "3" : // 造型师手机号			    
                    $query->whereIn('stylist_id', function($query)use ($val) {
                        $query->select('stylistId')
                            ->from("hairstylist")
                            ->where('mobilephone', 'like', '%' . $val . '%');
                    });
                    break;
                default:
                    throw new ApiException('预约造型师无此类别关键词！', 1);
            }
        }
        //提交时间
        if (!empty($input["minTime"])) {
            $minTime = strtotime($input["minTime"]);
            if ($minTime) {
                $query->where('add_time', '>=', $minTime);
            }
        }
        if (!empty($input["maxTime"])) {
            $maxTime = strtotime($input["maxTime"]);
            if ($maxTime) {
                $maxTime += 86399;
                $query->where('add_time', '<=', $maxTime);
            }
        }
        if (isset($input["contacted"])) {
                $query->where('contacted', '=', $input["contacted"]);
        }
        return $query;
    }

    /**
     * 赏金单列表搜索显示数据
     * @param query $query        	
     * @param int $page        	
     * @param int $size        	
     * @param string $order_by        	
     */
    public static function search($query, $page, $size, $sortKey, $sortType) {

        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });

        $appointments = $query->orderBy($sortKey, $sortType)->paginate($size)->toArray();
        foreach ($appointments["data"] as $key => $value) {
            $user = User::getUserById($value["user_id"]);
            if ($user) {
                $appointments["data"][$key]['userMobile'] = $user["mobilephone"];
            }
            $hairstylist = Hairstylist::getHairstylistById($value["stylist_id"]);

            if ($hairstylist) {
                $appointments["data"][$key]['stylistMobile'] = $hairstylist["mobilephone"];
                $salon = Salon::getSalonById($hairstylist["salonId"]);
                if ($salon) {
                    $appointments["data"][$key]['salonName'] = $salon["salonname"];
                }
            }

            $appointments["data"][$key]["add_time"] = date("Y-m-d H:i:s", $value["add_time"]);
            if ($value["contacted"] == 0) {
                $appointments["data"][$key]["contacted"] = "未联系";
            } elseif ($value["contacted"] == 1) {
                $appointments["data"][$key]["contacted"] = "已联系";
            }
        }
        return $appointments;
    }

    public static function getAppointmentById($id) {
        return self::getQuery()->where("id", "=", $id)->first();
    }

    public static function format_export_data($datas) {
        $res = [];
        foreach ($datas as $data) {
            $userMobile = isset($data['userMobile']) ? ' '.$data['userMobile'] : '';
            $addTime = isset($data['add_time']) ? $data['add_time'] : '';
            $service_item = isset($data['service_item']) ? $data['service_item'] : '';
            $stylistMobile = isset($data['stylistMobile']) ? ' '.$data['stylistMobile'] : '';
            $salonName = isset($data['salonName']) ? $data['salonName'] : '';
            $res[] = [
                $userMobile,
                $addTime,
                $service_item,
                $stylistMobile,
                $salonName,
            ];
        }
        return $res;
    }

}
