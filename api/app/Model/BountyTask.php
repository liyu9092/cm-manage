<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Log;
Use PDO;
Use URL;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class BountyTask extends Model {

    protected $table = 'bounty_task';
    protected $primaryKey = 'btId';
    public $timestamps = false;

    /**
     * 订单类型中的赏金单类型
     * @var unknown
     */
    CONST BOUNTY_TYPE = 2;

    /**
     * 赏金单申请退款
     * @var unknown
     */
    CONST STATUS_APPLY_REFUND = 5;

    /**
     * 赏金单退款中
     * @var unknown
     */
    CONST STATUS_IN_REFUND = 6;

    /**
     * 赏金单 退款完成
     * @var unknown
     */
    CONST STATUS_REFUND_COMPLETED = 7;

    /**
     * 赏金单 申请退款被拒绝
     * @var unknown
     */
    CONST STATUS_APPLY_FAILED = 8;

    /**
     * 赏金单 退款失败
     * @var unknown
     */
    CONST STATUS_REFUND_FAILED = 9;

    /**
     * 退款方式 银联
     * @var unknown
     */
    CONST PAYTYPE_UNION_PAY = 1;

    /**
     * 退款方式 支付宝
     * @var unknown
     */
    CONST PAYTYPE_ALIPAY = 2;

    /**
     * 退款方式 微信
     * @var unknown
     */
    CONST PAYTYPE_WECHAT = 3;

    /**
     * 退款方式 易联
     * @var unknown
     */
    CONST PAYTYPE_VOUCHER = 6;

    /**
     * 退款方式 易联
     * @var unknown
     */
    CONST PAYTYPE_YILIAN = 10;

    /**
     * 退款超时设置
     * @var unknown
     */
    CONST TIME_OUT = 30;

    /**
     * 根据用户输入获取搜索条件
     * 
     * @param array $input        	
     */
    public static function getQueryByParam($input) {
        $query = Self::getQuery();
        // 是否有输入关键字搜索 
        if (!empty($input["keyword"])) {
            $val = $input ["keyword"];
            $val = addslashes($val);
            $val = str_replace(['_', '%'], ['\_', '\%'], $val);
            switch ($input ["keywordType"]) {
                case "0" : // 订单号				    
                    $query->where('btSn', 'like', '%' . $val . '%');
                    break;
                case "1" : // 用户名					
                    $query->whereIn('userId', function($query)use ($val) {
                        $query->select('user_id')
                            ->from("user")
                            ->where('username', 'like', '%' . $val . '%');
                    });
                    break;
                case "2" : // 用户手机号
                    $query->whereIn('userId', function($query)use ($val) {
                        $query->select('user_id')
                            ->from("user")
                            ->where('mobilephone', 'like', '%' . $val . '%');
                    });
                    break;
                case "3" ://商铺名称
                    $query->whereIn('salonId', function($query)use ($val) {
                        $query->select('salonid')
                            ->from("salon")
                            ->where('salonname', 'like', '%' . $val . '%');
                    });
                    break;
                default:
                    throw new ApiException('赏金单搜索无此类别关键词！', ERROR::BOUNTY_SEARCH_KEYWORD_WRONG);
            }
        }
        if (!empty($input["payType"])) {
            $payType = intval($input["payType"]);
            if ($payType > 10) {
                throw new ApiException('赏金单搜索暂不支持该支付方式搜索！', ERROR::BOUNTY_SEARCH_PAYTYPE_WRONG);
            }
            switch ($payType) {
                case self::PAYTYPE_ALIPAY :
                case self::PAYTYPE_YILIAN:
                case self::PAYTYPE_VOUCHER:
                case self::PAYTYPE_WECHAT:
                    $query->where('payType', '=', $payType);
                    break;
            }
        }

        //付款状态
        if (!empty($input["isPay"])) {
            if ($input["isPay"] > 2) {
                throw new ApiException('赏金单搜索付款状态不正确！', ERROR::BOUNTY_SEARCH_ISPAY_WRONG);
            }
            $isPay = intval($input["isPay"]);
            $query->where('isPay', '=', $isPay);
        }

        //赏金单状态
        if (!empty($input["btStatus"])) {
            $btStatus = intval($input["btStatus"]);
            if ($btStatus > 5 && $btStatus != 9) {
                throw new ApiException('赏金单搜索暂不支持该赏金单状态搜索！', ERROR::BOUNTY_SEARCH_BTSTATUS_WRONG);
            }
            if ($btStatus == 5) { //5为不满意状态
                $query->where('satisfyType', '=', 2);
            } else if ($btStatus == 4) { //已打赏时  不包括不满意的状态		    
                $query->where('btStatus', '=', $btStatus)->where('satisfyType', '=', 0)->orWhere('satisfyType', '=', 1);
            } else {
                $query->where('btStatus', '=', $btStatus);
            }
        }

//        //是否满意
//		if (!empty($input["satisfyType"])) {
//            if($input["satisfyType"]>2)
//            {
//                return $this->error("赏金单满意类型不正确！");
//            }
//		    $satisfyType = intval($input["satisfyType"]);
////		    $condition [] = "`satisfyType` = {$satisfyType}";
//            $query->where('satisfyType','=',$satisfyType);
//            
//		}
        //退款状态
        if (!empty($input["isRefund"])) {
            $isRefund = intval($input["isRefund"]);
            if (!empty($isRefund) && $isRefund == 2) {
                $query->where('refundStatus', '>=', 5);
                if (!empty($input["refundStatus"])) {
                    if ($input["refundStatus"] > 9 || $input["refundStatus"] < 5) {
                        throw new ApiException('赏金单查询退款状态不正确！', ERROR::BOUNTY_SEARCH_REFUNDSTATUS_WRONG);
                    }
//                    $refundStatus = intval($input["refundStatus"]);
                    $refundStatus = explode(',', $input["refundStatus"]);
                    $query->whereIn('refundStatus', $refundStatus);
                }
            }
        }


        //下单时间
        if (!empty($input["minAddTime"])) {
            $minTime = strtotime($input["minAddTime"]);
            if ($minTime) {
                $query->where('addTime', '>=', $minTime);
            }
        }
        if (!empty($input["maxAddTime"])) {
            $maxTime = strtotime($input["maxAddTime"]);
            if ($maxTime) {
                $maxTime += 86399;
                $query->where('addTime', '<=', $maxTime);
            }
        }

        //退款时间
        if (!empty($input["minEndTime"])) {
            $minTime = strtotime($input["minEndTime"]);
            if ($minTime) {
                $query->where('endTime', '>=', $minTime);
            }
        }
        if (!empty($input["maxEndTime"])) {
            $maxTime = strtotime($input["maxEndTime"]);
            if ($maxTime) {
                $maxTime += 86399;
                $query->where('endTime', '<=', $maxTime);
            }
        }
        return $query;
    }

    public static function getCount($query) {
        $count = $query->count();
        return $count;
    }

    /**
     * 赏金单列表搜索显示数据
     * @param query $query        	
     * @param int $page        	
     * @param int $size        	
     * @param string $order_by        	
     */
    public static function search($query, $page, $size, $sortKey, $sortType) {

        $offset = ($page - 1) * $size;
        if ($offset < 0) {
            $offset = 0;
        }
        //赏金单
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        //导出查询
        if ($size < 0) {
            $bountys = $query->orderBy($sortKey, $sortType)->get();
        }
        //列表查询
        else {
            $bountys = $query->orderBy($sortKey, $sortType)->skip($offset)->take($size)->get();
        }
        //相关的用户信息
        $uids = Utils::get_column_array("userId", $bountys);
        $uids = array_unique($uids);
        $users = [];
        if (count($uids) > 0) {
            $users = User::getUsersByIds($uids);
        }

        //造型师信息
        $hairstylistIds = Utils::get_column_array("hairstylistId", $bountys);
        $hairstylistIds = array_map("intval", $hairstylistIds);
        $hairstylistIds = array_unique($hairstylistIds);
        $hairstylists = [];
        if (count($hairstylistIds) > 0) {
            $hairstylists = Hairstylist::getHairstylistsByIds($hairstylistIds);
        }

        //店铺信息
        $salon_ids = Utils::get_column_array("salonId", $bountys);
        $salon_ids = array_map("intval", $salon_ids);
        $salon_ids = array_unique($salon_ids);
        $salons = [];
        if (count($salon_ids) > 0) {
            $salons = Salon::getsalonsByIds($salon_ids);
        }

        //支付流水
        $bountySn = Utils::get_column_array("btSn", $bountys);
        $bountySn = array_unique($bountySn);
        $flows = [];
        if (count($bountySn) > 0) {
            $flows = PaymentLog::getPaymentLogsBySns($bountySn);
        }

        $items = self::compositeAll($bountys, $users, $salons, $flows, $hairstylists);
        return $items;
    }

    /**
     * 组合显示信息
     * @param unknown $bountys
     * @param unknown $users
     */
    public static function compositeAll($bountys, $users, $salons, $flows, $hairstylists) {
        $users = Utils::column_to_key("user_id", $users);
        $flows = Utils::column_to_key("ordersn", $flows);
        $salons = Utils::column_to_key("salonid", $salons);
        $hairstylists = Utils::column_to_key("stylistId", $hairstylists);
        foreach ($bountys as &$bounty) {

            $bounty['userName'] = "";
            $bounty['userMobile'] = "";
            $bounty['hairStylistMobile'] = "";
            $bounty['tn'] = "";
            $bounty['salonName'] = "";

            $id = $bounty['btId'];
            $uid = $bounty['userId'];
            $bounty_sn = $bounty['btSn'];
            $salon_id = $bounty['salonId'];
            $hairstylistId = $bounty['hairstylistId'];
//			$url = U("/Bounty/detail/no/{$bounty_sn}");           
//			$refund_url = U("/Bounty/refund_detail/no/{$bounty_sn}");
//            $url = URL::action('Bounty\BountyController@detail');
//            $refund_url = URL::action('Bounty\BountyController@refundDetail');
//            $bounty["operations"] = "<a href=\"{$url}\" target=\"_blank\">查看</a>";
//            $bounty["refund_operations"] = "<a href=\"{$refund_url}\" target=\"_blank\">查看</a>";
            $status = intval($bounty['refundStatus']);

            $tmp = self::compositeSingle($bounty);
            $bounty = array_merge($bounty, $tmp);
            if (isset($users[$uid])) {
                $bounty['userName'] = $users[$uid]['username'];
                $bounty['userMobile'] = $users[$uid]['mobilephone'];
            }

            if (isset($salons[$salon_id])) {
                $bounty['salonName'] = $salons[$salon_id]['salonname'];
            }

            if (isset($flows[$bounty_sn])) {
                $bounty['tn'] = $flows[$bounty_sn]['tn'];
            }

            if (isset($hairstylists[$hairstylistId])) {
                $bounty['hairStylistMobile'] = $hairstylists[$hairstylistId]['mobilephone'];
            }

//            if ($status == self::STATUS_APPLY_REFUND) {
//                $bounty["refund_operations"] .= "&nbsp;&nbsp;<a href=\"javascript:;\" class=\"accept_refund\" refund_id=\"{$id}\">通过</a>";
//                $bounty["refund_operations"] .= "&nbsp;&nbsp;<a href=\"javascript:;\" class=\"reject_refund\" refund_id=\"{$id}\">拒绝</a>";
//            }
//
//            if ($status == self::STATUS_IN_REFUND) {
//                $bounty["refund_operations"] .= "&nbsp;&nbsp;<a href=\"javascript:;\" class=\"accept_refund\" refund_id=\"{$id}\">重新退款</a>";
//            }
        }
        return $bountys;
    }

    /**
     * 组合显示信息 (单条)
     * @param unknown $bountys
     */
    public static function compositeSingle($task) {
        $item = [];
        $item['payType'] = self::getPayName($task['payType']);
        $item['isPay'] = self::getIsPayStr($task['isPay']);
        $status = self::getBtStatusName($task['btStatus']);
        $item['btStatus'] = self::getBtStatusStr($task['btStatus'], $task['satisfyType']);
        $item['selectType'] = self::getSelectTypeName($task['selectType']);
        $satisfy = self::getSatisfyTypeStr($task['satisfyType']);
        $item['refundStatus'] = $task['refundStatus'];

        if ($task['btStatus'] == 4 && $task['satisfyType'] == 2) {
            $item['btStatus'] = "不打赏";
        }
        if (!empty($task['addTime'])) {
            $item['addTime'] = date("Y-m-d H:i:s", intval($task['addTime']));
        } else {
            $item['addTime'] = "";
        }
        if (!empty($task['payTime'])) {
            $item['payTime'] = date("Y-m-d H:i:s", intval($task['payTime']));
        } else {
            $item['payTime'] = "";
        }

        if (!empty($task['selectTime'])) {
            $item['selectTime'] = date("Y-m-d H:i:s", intval($task['selectTime']));
        } else {
            $item['selectTime'] = "";
        }
        if (!empty($task['serviceTime'])) {
            $item['serviceTime'] = date("Y-m-d H:i:s", intval($task['serviceTime']));
        } else {
            $item['serviceTime'] = "";
        }
        if (!empty($task['endTime'])) {
            $item['endTime'] = date("Y-m-d H:i:s", intval($task['endTime']));
        } else {
            $item['endTime'] = "";
        }
        return $item;
    }

    /**
     * 计算总金额
     * @param unknown $bountys
     */
    public static function getAmount($bountys) {
        $amount = 0;
        foreach ($bountys as $bounty) {
            $amount += floatval($bounty['money']);
        }
        return $amount;
    }

    /**
     * 获取付款方式信息
     * @param int $pay_id
     */
    public static function getPayName($pay_id) {
        $pay_id = intval($pay_id);
        $name = "";
        switch ($pay_id) {
            case self::PAYTYPE_ALIPAY:
                $name = "支付宝";
                break;
            case self::PAYTYPE_YILIAN:
                $name = "易联";
                break;
            case self::PAYTYPE_WECHAT:
                $name = "微信";
                break;
            case self::PAYTYPE_VOUCHER:
                $name = "优惠券";
                break;
        }
        return $name;
    }

    public static function getSelectTypeName($selectType) {
        $selectType = intval($selectType);
        $name = "";
        switch ($selectType) {
            case 1:
                $name = "自己选";
                break;
            case 2:
                $name = "臭美代选";
                break;
            case 3:
                $name = "为我服务过的";
                break;
        }
        return $name;
    }

    public static function getSatisfyTypeStr($satisfyType) {
        $satisfyType = intval($satisfyType);
        $name = "";
        switch ($satisfyType) {
            case 1:
                $name = "满意";
                break;
            case 2:
                $name = "不满意";
                break;
        }
        return $name;
    }

    public static function getIsPayStr($isPay) {
        $isPay = intval($isPay);
        $name = "";
        switch ($isPay) {
            case 1:
                $name = "未支付";
                break;
            case 2:
                $name = "已支付";
                break;
        }
        return $name;
    }

    public static function getBtStatusStr($status, $satisfyType) {
        $status = intval($status);
        $name = "";
        switch ($status) {
            case 1:
                $name = "待抢单";
                break;
            case 2:
                $name = "待服务";
                break;
            case 3:
                $name = "已服务";
                break;
            case 4:
                $name = "已打赏";
                break;
            case 9:
                $name = "取消";
                break;
        }
        return $name;
    }

    public static function getBtStatusName($status) {
        $status = intval($status);
        $name = "";
        switch ($status) {
            case 1:
                $name = "未完成";
                break;
            case 2:
                $name = "未完成";
                break;
            case 3:
                $name = "已完成";
                break;
            case 4:
                $name = "已完成";
                break;
            case 9:
                $name = "取消";
                break;
        }
        return $name;
    }

    public static function getHairstyGradeName($grade) {
        $grade = intval($grade);
        $name = "";
        switch ($grade) {
            case 1:
                $name = "高级设计师";
                break;
            case 2:
                $name = "资深设计师";
                break;
            case 3:
                $name = "设计总监";
                break;
            case 4:
                $name = "美发大师";
                break;
        }
        return $name;
    }

    public static function getSalonBountyTypeName($bountyType) {
        $bountyType = intval($bountyType);
        $name = "";
        switch ($bountyType) {
            case 1:
                $name = "店铺类型A";
                break;
            case 2:
                $name = "店铺类型B";
                break;
            case 3:
                $name = "店铺类型C";
                break;
            case 4:
                $name = "店铺类型D";
                break;
        }
        return $name;
    }

    /**
     * 获取对应状态信息
     * @param unknown $status
     * @return string
     */
    public static function getStatusName($status) {
        $status = intval($status);
        $name = "";
        switch ($status) {
            case self::STATUS_APPLY_REFUND:
                $name = "申请退款";
                break;
            case self::STATUS_IN_REFUND:
                $name = "退款中";
                break;
            case self::STATUS_REFUND_COMPLETED:
                $name = "已退款";
                break;
            case self::STATUS_APPLY_FAILED:
                $name = "拒绝退款";
                break;
            case self::STATUS_REFUND_FAILED:
                $name = "失败";
                break;
        }
        return $name;
    }

    /**
     * 赏金单详情显示数据
     * 
     * @param unknown $id        	
     */
    public static function detail($id) {

        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $task = self:: getBountyTaskBySn($id);
        if (!$task) {
            return null;
        }
        $payment = PaymentLog::getBountyPaymentLogBySn($id, self::BOUNTY_TYPE);

        $uid = $task['userId'];
        $salon_id = $task['salonId']; //店铺
        $hairstylist_id = $task['hairstylistId']; //发型师
        $district_id = $task['district']; //区域
        $task['district_id'] = $district_id;
        $zone = $task['zone']; //区域
        $user = [];
        $salon = [];
        $hairsty = [];
        $district = [];
        $salon_area = [];
        if (!empty($uid)) {
            $user = User::getUserById($uid);
        }
        if (!empty($salon_id)) {
            $salon = Salon::getSalonById($salon_id);
        }
        if (!empty($hairstylist_id)) {
            $hairsty = Hairstylist::getHairstylistById($hairstylist_id);
        }

        if (!empty($district_id)) {
            $district = Town::getTownById($district_id);
        } else if ($district_id == 0) {
            $district['tname'] = "全城全区";
        }

        //商圈
        if (!empty($zone)) {
            $salon_area = SalonArea::getSalonAreaById($zone);
        }
        if (!empty($salon['bountyType'])) {
            $salon['bountyType'] = self::getSalonBountyTypeName($salon['bountyType']);
        }
        if (!empty($hairsty['grade'])) {
            $hairsty['grade'] = self::getHairstyGradeName($hairsty['grade']);
        }


        $task_tmp = self::compositeSingle($task);

        $task = array_merge($task, $task_tmp);
        $task['user'] = $user;
        $task['salon'] = $salon;
        $task['hairsty'] = $hairsty;
        $task['district'] = $district;
        $task['salon_area'] = $salon_area;

        $detail['btId'] = $task['btId'];
        $detail['btSn'] = $task['btSn'];

        $detail['needsStr'] = $task['needsStr'];
        $detail['remark'] = $task['remark'];
        $detail['selectType'] = $task['selectType'];
        $detail['payType'] = $task['payType'];
        $detail['money'] = $task['money'];
        $detail['isPay'] = $task['isPay'];
        $detail['addTime'] = $task['addTime'];
        $detail['payTime'] = $task['payTime'];
        if ($task['btStatus'] == "取消") {
            $detail['cancelTime'] = $task['endTime'];
        } else {
            $detail['endTime'] = $task['endTime'];
        }
        $detail['btStatus'] = $task['btStatus'];

        if (!empty($user)) {
            $detail['userName'] = $user['username'];
            $detail['userMobile'] = $user['mobilephone'];
        }
        if (!empty($payment)) {
            $detail['tn'] = $payment['tn'];
        }
        if (!empty($salon)) {
            $detail['salonName'] = $salon['salonname'];
            $detail['bountyType'] = $salon['bountyType'];
        }

        $detail['district'] = $district['tname'];

        if (!empty($hairsty)) {
            $detail['grade'] = $hairsty['grade'];
            $detail['stylistName'] = $hairsty['stylistName'];
            $detail['hairStylistMobile'] = $hairsty['mobilephone'];
        }
        return $detail;
    }

    public static function refundDetail($id) {
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $task = self:: getBountyTaskBySn($id);
        if (!$task) {
            return null;
        }
        $payment = PaymentLog::getBountyPaymentLogBySn($id, self::BOUNTY_TYPE);
        $uid = $task['userId'];
        $salon_id = $task['salonId']; //店铺

        $user = [];
        $salon = [];

        if (!empty($uid)) {
            $user = User::getUserById($uid);
        }
        if (!empty($salon_id)) {
            $salon = Salon::getSalonById($salon_id);
        }

        $task_tmp = self::compositeSingle($task);
        $task = array_merge($task, $task_tmp);
        $task['refund_status'] = self::getStatusName($task['refundStatus']);

        $refundDetail['btID'] = $task['btId'];
        $refundDetail['btSn'] = $task['btSn'];
        $refundDetail['btStatus'] = $task['btStatus'];
        $refundDetail['endTime'] = $task['endTime'];
        $refundDetail['payType'] = $task['payType'];
        $refundDetail['money'] = $task['money'];
        $refundDetail['refundStatus'] = $task['refundStatus'];

        if (!empty($payment)) {
            $refundDetail['tn'] = $payment['tn'];
        }

        if (!empty($user)) {
            $refundDetail['userName'] = $user['username'];
            $refundDetail['userMobile'] = $user['mobilephone'];
        }
        $refundDetail['refundType'] = "原路返还";
        return $refundDetail;
    }

    public static function getBountyTaskBySn($btSn) {
        $bountyTask = Self::getQuery()->where("btSn", "=", $btSn)->get();
        if (empty($bountyTask)) {
            return [];
        } else {
            return $bountyTask[0];
        }
    }

    public static function getBountyTaskByIds($ids) {
        return $bountyTasks = Self::getQuery()->whereIn("btId", $ids)->get();
    }

    public static function getRefundBountyTaskByIds($btIds) {
        return $bountyTask = Self::getQuery()->whereIn("btId", $btIds)->where("refundStatus", "<>", 0)->get();
    }

    public static function updateRefundStatus($btIds, $refundStatus) {
        return Self::getQuery()->whereIn("btId", $btIds)->update(["refundStatus" => $refundStatus]);
    }

    public static function updateRefundStatusBySn($btSns, $refundStatus) {
        return Self::getQuery()->whereIn("btSn", $btSns)->update(["refundStatus" => $refundStatus]);
    }

    public static function updateRejectStatus($ids, $refundStatus, $reason) {
        return Self::getQuery()->whereIn("btId", $ids)->where('refundStatus', '=', self::STATUS_APPLY_REFUND)->update(['refundStatus' => $refundStatus, 'cause' => $reason]);
    }

    /**
     * 检查需要退款单状态
     * @param array $refunds
     * @return boolean
     */
    public static function checkRefundStatus($refunds, $allow_status = [self::STATUS_APPLY_REFUND]) {
        $statuses = Utils::get_column_array("refundStatus", $refunds);
        $statuses = array_unique($statuses);
        $statuses = array_values($statuses);
        $allow_status = array_map("intval", $allow_status);
        foreach ($statuses as $status) {
            if (!in_array($status, $allow_status)) {
                return false;
            }
        }
        return true;
    }

    public static function accept($ids, &$output) {
        $output['err_info'] = "";
        $output['info'] = "";
        if (is_numeric($ids)) {
            $ids = [$ids];
        }
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
//        $condition[] = "`btId` IN (" . implode(",", $ids) . ")";
//        $condition[] = "`refundStatus` <> 0";
//        $refunds = M("bounty_task")->get(NULL, [], $condition);
        $refunds = BountyTask::getRefundBountyTaskByIds($ids);
//        var_dump($refunds);
        if (count($refunds) < 1) {
            $output['err_info'] .= "退款单已不存在";
            return false;
        }
//
        if (!self::checkRefundStatus($refunds, [self::STATUS_APPLY_REFUND, self::STATUS_IN_REFUND, self::STATUS_APPLY_FAILED])) { //检查状态  允许再次申请
            $output['err_info'] .= "退款单状态不正确";
            return false;
        }
//
        $alipay_items = []; //支付宝的退款项
        $wechat_items = []; //微信的退款项
        $union_items = []; //银联的退款项
        $yilian_items = []; //易联的退款项
        //支付流水
        $bountySn = Utils::get_column_array("btSn", $refunds);
        $bountySn = array_unique($bountySn);
        $flows = [];
        if (count($bountySn) > 0) {
//            $flows_condition = ["`ordersn` IN ('" . implode("','", $bountySn) . "')", "`logtype` = " . self::BOUNTY_TYPE]; //2为赏金单
//            $flows = M("payment_log")->get("", ['ordersn', 'tn'], $flows_condition);
            $flows = PaymentLog::getBountyPaymentLogBySns($bountySn, self::BOUNTY_TYPE);
        }
        $flows_index = Utils::column_to_key("ordersn", $flows);
//
        foreach ($refunds as $refund) {
            $bounty_sn = $refund['btSn'];
            $userId = $refund['userId'];
            $payid = intval($refund['payType']);
            $money = floatval($refund['money']);
            $tn = "";
            if (!empty($flows_index[$bounty_sn])) {
                $tn = $flows_index[$bounty_sn]['tn'];
            } else {
                $output['err_info'] .= "{$bounty_sn} 退款失败!\n";
                return false;
            }
            switch ($payid) {
                case self::PAYTYPE_ALIPAY://支付宝	              
                    $alipay_items[] = ['tn' => $tn, "money" => $money, "reason" => "协商退款"];
                    break;
                case self::PAYTYPE_WECHAT://微信	
                    $wechat_items[] = ['bountySn' => $bounty_sn, 'userId' => $userId, 'money' => $money, 'tn' => $tn];
                    break;
                case self::PAYTYPE_UNION_PAY://银联	                 
                    //#@todo         
                    //$union_items[] = [];
                    $output['err_info'] .= "{$bounty_sn} 暂不支持银联退款!\n";
                    break;
                case self::PAYTYPE_YILIAN://易联
                    $yilian_items[] = ['tn' => $tn, "money" => $money, "bountySn" => $bounty_sn, 'user_id' => $userId];
                    break;
                default:
                    $output['err_info'] .= "{$bounty_sn} 不支持该支付方式的退款 \n";
            }
        }
//
        //将订单标记为 退款中
        self::updateRefundStatus($ids, self::STATUS_IN_REFUND);
        //微信的退款处理
        if (count($wechat_items) > 0) {
            $thrift = new ThriftHelperModel('', '');
            $wechat_config = TransactionWriteApi::GetWechatConf(env('APP_ENV'));
            foreach ($wechat_items as $item) {
                $ordersn = '';
                $money = $item['money'];
                $bountysn = $item['bountySn'];
                $refundRequestParam = new \cn\choumei\thriftserver\service\stub\gen\WechatRefundRequestParam();
                $refundRequestParam->payMerchantId = $wechat_config['PAY_MERCHANT_ID'];
                $refundRequestParam->payMerchantPassword = $wechat_config['PAY_MERCHANT_PASSWORD'];
                $refundRequestParam->payRefundUrl = $wechat_config['PAY_REFUND_URL'];
                $refundRequestParam->payPartnerId = $wechat_config['PAY_PARTNER_ID'];
                $refundRequestParam->payPartnerKey = $wechat_config['PAY_PARTNER_KEY'];
                $refundRequestParam->h5AppId = $wechat_config['H5_APP_ID'];
                $refundRequestParam->h5MerchantId = $wechat_config['H5_MERCHANT_ID'];
                $refundRequestParam->h5RefundUrl = $wechat_config['H5_REFUND_URL'];
                $refundRequestParam->h5PartnerKey = $wechat_config['H5_PARTNER_KEY'];
                $refundRequestParam->orderSn = $ordersn;
                $refundRequestParam->bountySn = $bountysn;
                $refundRequestParam->amount = $money;

                Utils::log("pay", date("Y-m-d H:i:s") . "\t [REQUEST] send data: " . json_encode($item) . "\n", "wechat");
                $refundResponseThrift = $thrift->request('trade-center', 'wechatRefund', array($refundRequestParam));
                Utils::log("pay", date("Y-m-d H:i:s") . "\t [RESPOND] Response: " . json_encode($refundResponseThrift) . " \n", "wechat");

                if (empty($refundResponseThrift) || !isset($refundResponseThrift['code'])) {
                    $output['info'] .= '调用微信退款服务失败\n';
                } elseif ($refundResponseThrift['code'] == '0') {
                    $output['info'] .= $bountysn . ' 微信退款成功\n';
                } else {
                    $output['info'] .= $bountysn . ' 微信退款失败\n' . $refundResponseThrift['code'] . ': ' . isset($refundResponseThrift['message']) ? $refundResponseThrift['message'] : '' . "\n";
                }
                unset($refundRequestParam);
                $output['info'] = nl2br($output['info']);
            }
        }
//        if (count($wechat_items) > 0) {
//            $wx_url = env("WXREFUND_URL");
//            foreach ($wechat_items as $item) {
//                $res_str = self::curlRefund($item['bountySn'], $item['userId'], $item['money'], $item['tn'], $wx_url);
//                Utils::log('pay', date("Y-m-d H:i:s") . "\t" . $res_str . "\n", "wx_refund_return");
//                if (strpos($res_str, "OK") !== false) {
//                    $output['info'] .= $item['bountySn'] . " 退款成功\n";
//                } else {
//                    $output['info'] .= $item['bountySn'] . " 退款失败\n";
//                }
//            }
//        }
        //支付宝的退款处理
        if (count($alipay_items) > 0) {
            $notify_url = self::getAlipayNotifyUrl();
            $batch_no = AlipaySimple::getRandomBatchNo();
            //支付宝的表单提交
            $output['alipay_form_args'] = AlipaySimple::refund(['notify_url' => $notify_url, 'batch_no' => $batch_no, 'detail_data' => $alipay_items], 2);
        }


        //易联退款处理
        if (count($yilian_items) > 0) {
            $thrift = new ThriftHelperModel('', '');
            $payeco_config = TransactionWriteApi::GetPayEcoConf(env('APP_ENV'));
            foreach ($yilian_items as $item) {
                $money = $item['money'];
                $bountysn = $item['bountySn'];
                $extData = "bountySn={$bountysn}";
                $refundSn = $bountysn . 'Z' . time();
                $refundRequestParam = new \cn\choumei\thriftserver\service\stub\gen\PayecoRefundRequestParam();
                $refundRequestParam->paymentSn = $refundSn;
                $refundRequestParam->amount = $money;
                $refundRequestParam->merchantId = $payeco_config['MERCHANT_ID'];
                $refundRequestParam->privateKey = $payeco_config['PRIVATE_KEY'];
                $refundRequestParam->publicKey = $payeco_config['PUBLIC_KEY'];
                $refundRequestParam->payecoUrl = $payeco_config['PAYECO_URL'];
                $refundRequestParam->extData = $extData;
                $refundResponseThrift = $thrift->request('trade-center', 'payecoRefund', array(
                    $refundRequestParam
                ));

                if (empty($refundResponseThrift) || !isset($refundResponseThrift['retCode'])) {
                    $output['info'] .= '调用易联退款服务失败\n';
                } elseif ($refundResponseThrift['retCode'] == '0000') {
                    $output['info'] .= $bountysn . ' 易联退款成功\n';
                } else {
                    $output['info'] .= $bountysn . ' 易联退款失败\n' . $refundResponseThrift['retCode'] . ': ' . isset($refundResponseThrift['retMsg']) ? $refundResponseThrift['retMsg'] : '' . "\n";
                }
                Utils::log('pay', date("Y-m-d H:i:s") . $refundResponseThrift . "\n", "YILIAN_order_refund");
                unset($refundRequestParam);                               
                $output['info'] = nl2br($output['info']);
            }
        }

//        if (count($yilian_items) > 0) {
//            foreach ($yilian_items as $yilian_item) {
//                $data['user_id'] = $yilian_item['user_id'];
//                $data['amount'] = $yilian_item['money'];
//                $data['bountysn'] = $yilian_item['bountySn'];
//                $data['tn'] = $yilian_item['tn'];
//                $notify_url = env("YILIAN_REFUND_NOTIFY_URL");
//
//                $argc = array();
//                $argc['body'] = $data;
//                $argc['to'] = 'refund';
//                $argc['type'] = 'Payeco';
//
//                $argStr = json_encode($argc);
//                $param['code'] = $argStr;
//                $yilian_result = self::curlPostRefund($param, $notify_url);
//                Utils::log('pay', date("Y-m-d H:i:s") . $yilian_result . "\n", "YILIAN_order_refund");
//                $resDecode = json_decode($yilian_result, true);
//                if ($resDecode['result'] == 1) {
//                    $output['info'] .= $yilian_item['bountySn'] . " 退款成功\n";
//                } else {
//                    $output['info'] .= $yilian_item['bountySn'] . " 退款失败\n";
//                }
//            }
//        }

        return true;
    }

    /**
     * 通过curl调用远程的服务器
     * @param string $orsersn
     * @param int $user_id
     * @param double $money
     *
     */
    public static function curlRefund($ordersn, $user_id, $money, $tn, $url) {
        $argc = array();
        $argc['body'] = array(
            'type' => 'Wxpay',
            'bountySn' => $ordersn,
            'userId' => $user_id,
            'money' => $money,
            'transactionNo' => $tn,
            'sign' => self::encryptionSign($ordersn, $user_id, $money, $tn)
        );
        $argc['to'] = 'refund';
        $argc['type'] = 'Payment';
        $argStr = json_encode($argc);
        Log::debug("request parameter:{$argStr}");
        $param['code'] = $argStr;
        return self::httpPost($param, $url);
    }

    /**
     * 发送http POST 请求
     * @param unknown $data
     * @param unknown $url
     * @return mixed
     */
    public static function httpPost($data, $url) {
        $curl = curl_init();
        //请求前的信息记录
        Utils::log('pay', date("Y-m-d H:i:s") . "\t" . json_encode(['url' => $url, 'data' => $data]) . "\n", "REFUND_POST_FLOW");
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::TIME_OUT);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        sleep(1);  //暂停1秒
        return $output;
    }

    /**
     * 赏金单的支付宝回调地址
     */
    public static function getAlipayNotifyUrl() {
        $url = env("ALIPAY_REFUND_CALLBACK_URL");
        if (empty($url)) {
            Utils::log('pay', "please set the config of `ALIPAY_REFUND_CALLBACK_URL` \n", "refund_error");
        }
        return $url;
    }

    //加密key
    public static function encryptionSign($ordersn, $user_id, $money, $tn) {
        return md5(md5($ordersn . $user_id . $money) . $tn . 'choumei.cn');
    }

    /**
     * 通过curl调用远程的服务器
     * @param string $orsersn
     * @param int $user_id
     * @param double $money
     * 
     */
    public static function curlPostRefund($data, $url) {
        //请求前的信息记录
        Utils::log('pay', date("Y-m-d H:i:s") . "\t" . json_encode(['url' => $url, 'data' => $data]) . "\n", "REFUND_POST_FLOW");
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, self::TIME_OUT);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($curl);
        curl_close($curl);
        return $output;
    }

    /**
     * 支付宝退款成功的回调
     */
    public static function alipayCallback() {
        Utils::log('pay', "comming bounty alipayCallback!");
        $args = func_get_args();

        //成功 则改变退款的状态
        if (isset($args[0]) && isset($args[0]['success']) && count($args[0]['success']) > 0) {
            $items = $args[0]['success'];
            $tns = Utils::get_column_array("tn", $items);

            if (count($tns) > 0) {
//	           $condition[] = "`tn` IN ('".implode("','",$tns)."')";
//	           $payments = M("payment_log")->get("",['tn','ordersn'],$condition);
                DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
                $payments = PaymentLog::getPaymentLogsByTns($tns);
                $sns = Utils::get_column_array("ordersn", $payments);
                $payments_index = Utils::column_to_key("ordersn", $payments);
                if (count($sns) > 0) {
//	               $update_condition = "`btSn` IN ('".implode("','",$sns)."') AND `refundStatus` IN(".self::STATUS_APPLY_FAILED.",".self::STATUS_APPLY_REFUND.",".self::STATUS_IN_REFUND.")";
//                   //将赏金单标记为    已退款update
//	               M('bounty_task')->where($update_condition)->save(['refundStatus'=>self::STATUS_REFUND_COMPLETED]);
                    self::updateRefundStatusBySn($sns, self::STATUS_REFUND_COMPLETED);
                }
            }
        }
    }

    /**
     * 拒绝赏金单退款
     * @param  array|num $ids        	
     * @param array $options        	
     */
    public static function reject($ids, &$output, $reason) {
        $output['err_info'] = "";
        $output['info'] = "";
        if (is_numeric($ids)) {
            $ids = [$ids];
        }
        $count = count($ids);
        if ($count < 1) {
            $output['err_info'] = "退款单号不能为空";
            return false;
        }

        if (empty($reason)) {
            $output['err_info'] = "拒绝原因不能为空";
        }

//	    $condition[]= "`btId` IN (".implode(",",$ids).")";
//	    $refunds = M("bounty_task")->get(NULL,[],$condition);
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $refunds = self::getBountyTaskByIds($ids);

        if (!self::checkRefundStatus($refunds)) {
            $output['error_info'] = "退款单状态不正确";
            return false;
        }

        $bountySn = Utils::get_column_array("btSn", $refunds);
        $bountySn = array_unique($bountySn);

        //将订单标记为 拒绝退款   
//	    M("bounty_task")->where("`btId` IN (".implode(",",$ids).") AND `refundStatus` = ".self::STATUS_APPLY_REFUND)->save(
//	    [
//	    'refundStatus'=>self::STATUS_APPLY_FAILED ,
//	    'cause'=>$reason,
//	    ]
//	    );	updateRejectStatus
        self::updateRejectStatus($ids, self::STATUS_APPLY_FAILED, $reason);

        $output['info'] .="执行成功\n";
        return true;
    }

    protected static function format_exportBounty_data($datas) {
        $res = [];
        foreach ($datas as $data) {
            $btSn = isset($data['btSn']) ? ' ' . $data['btSn'] : '';
            $tn = isset($data['tn']) ? ' ' . $data['tn'] : '';
            $payType = isset($data['payType']) ? $data['payType'] : '';
            $money = isset($data['money']) ? $data['money'] : '';
            $addTime = isset($data['addTime']) ? $data['addTime'] : '';
            $hairStylistMobile = isset($data['hairStylistMobile']) ? $data['hairStylistMobile'] : '';
            $userMobile = isset($data['userMobile']) ? $data['userMobile'] : '';
            $salonName = isset($data['salonName']) ? $data['salonName'] : '';
            $isPay = isset($data['isPay']) ? $data['isPay'] : '';
            $res[] = [
                $btSn,
                $tn,
                $payType,
                $money,
                $addTime,
                $hairStylistMobile,
                $userMobile,
                $salonName,
                $isPay,
            ];
        }
        return $res;
    }

    protected static function format_exportRefund_data($datas) {
        $res = [];
        foreach ($datas as $data) {
            $btSn = isset($data['btSn']) ? ' ' . $data['btSn'] : '';
            $payType = isset($data['payType']) ? $data['payType'] : '';
            $money = isset($data['money']) ? $data['money'] : '';
            $endTime = isset($data['endTime']) ? $data['endTime'] : '';
            $userName = isset($data['userName']) ? $data['userName'] : '';
            $userMobile = isset($data['userMobile']) ? $data['userMobile'] : '';
            $salonName = isset($data['salonName']) ? $data['salonName'] : '';
            $refundStatus = isset($data['refundStatus']) ? self::getStatusName($data['refundStatus']) : '';
            $res[] = [
                $btSn,
                $payType,
                $money,
                $endTime,
                $userName,
                $userMobile,
                $salonName,
                $refundStatus,
            ];
        }
        return $res;
    }

}
