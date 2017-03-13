<?php

namespace App\Http\Controllers\SystemConfig;

use App\Http\Controllers\Controller;
use Event;
use DB;
Use PDO;
use App\Warning;
use App\RequestLog;
use App\Blacklist;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class WarningController extends Controller {

    /**
     * @api {post} /warning/phoneIndex 1.预警查询手机列表
     * @apiName phoneIndex
     * @apiGroup warning
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} keywordType 必选,搜索关键词类型，可取0 用户手机号/1 设备号/3 微信OpenId.
     * @apiParam {Number} orderNum 购买单量最小值
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} userId 用户id.
     * @apiSuccess {String} userMobile 用户手机号.
     * @apiSuccess {Number} loginNum 登录次数
     * @apiSuccess {Number} payNum 支付次数
     * @apiSuccess {Number} orderNum 购买单量
     * @apiSuccess {Number} blacklistStatus 黑名单状态
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          "result": 1,
     *          "token": "",
     *          "data":{
     *          "total": 17,
     *          "per_page": 20,
     *          "current_page": 1,
     *           "last_page": 1,
     *          "next_page_url": null,
     *          "prev_page_url": null,
     *          "from": 1,
     *          "to": 17,
     *          "data":[
     *              {
     *              "payNum": 32,
     *              "orderNum": 124,
     *              "maxOrderTime": 1447828338,
     *              "device": "KTU84P",,
     *              "userId": 720005,
     *              "loginNum": 4,
     *              "blacklistStatus": 0
     *              }..]
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function phoneIndex() {
        $param = $this->param;
        $param["keywordType"] = 0;
        $nums = $this->getIndex($param);
        return $this->success($nums);
    }

    /**
     * @api {post} /warning/deviceIndex 2.预警查询设备号列表
     * @apiName deviceIndex
     * @apiGroup warning
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} orderNum 购买单量最小值
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} device 设备号.
     * @apiSuccess {Number} loginNum 登录次数
     * @apiSuccess {Number} payNum 支付次数
     * @apiSuccess {Number} orderNum 购买单量
     * @apiSuccess {Number} blacklistStatus 黑名单状态
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          "result": 1,
     *          "token": "",
     *          "data":{
     *          "total": 17,
     *          "per_page": 20,
     *          "current_page": 1,
     *           "last_page": 1,
     *          "next_page_url": null,
     *          "prev_page_url": null,
     *          "from": 1,
     *          "to": 17,
     *          "data":[
     *              {
     *              "payNum": 32,
     *              "orderNum": 124,
     *              "maxOrderTime": 1447828338,
     *              "device": "KTU84P",
     *              "userId": 720005,
     *              "loginNum": 4,
     *              "blacklistStatus": 0
     *              }..]
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function deviceIndex() {
        $param = $this->param;
        $param["keywordType"] = 1;
        $nums = $this->getIndex($param);
        return $this->success($nums);
    }

    /**
     * @api {post} /warning/openidIndex 3.预警查询openid列表
     * @apiName openidIndex
     * @apiGroup warning
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} orderNum 购买单量最小值
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} openId 微信openId.
     * @apiSuccess {Number} loginNum 登录次数
     * @apiSuccess {Number} payNum 支付次数
     * @apiSuccess {Number} orderNum 购买单量
     * @apiSuccess {Number} blacklistStatus 黑名单状态
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          "result": 1,
     *          "token": "",
     *          "data":{
     *          "total": 17,
     *          "per_page": 20,
     *          "current_page": 1,
     *           "last_page": 1,
     *          "next_page_url": null,
     *          "prev_page_url": null,
     *          "from": 1,
     *          "to": 17,
     *          "data":[
     *              {
     *              "payNum": 32,
     *              "orderNum": 124,
     *              "maxOrderTime": 1447828338,
     *              "openid": "",
     *              "loginNum": 4,
     *              "blacklistStatus": 0
     *              }..]
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function openidIndex() {
        $param = $this->param;
        $param["keywordType"] = 2;
        $nums = $this->getIndex($param);
        return $this->success($nums);
    }

    public function getIndex($param) {

        if (!isset($param['orderNum']) || $param['orderNum'] < 6) {
            $param['orderNum'] = 6;
        } else {
            $param['orderNum'] = $param['orderNum'];
        }
        if (!isset($param['keywordType'])) {
            throw new ApiException('缺少类型！', ERROR::Warning_KeywordType_Notfound);
        }
        if (isset($param['page']) && !empty($param['page'])) {
            $page = $param['page'];
        } else {
            $page = 1;
        }
        if (isset($param['page_size']) && !empty($param['page_size'])) {
            $size = $param['page_size'];
        } else {
            $size = 20;
        }
        $minTime=null;
        $maxTime=null;
        if (!empty($param["minTime"])) {
            $minTime = $param["minTime"];
        }
        if (!empty($param["maxTime"])) {
            $maxTime = $param["maxTime"];
        }
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $nums = Warning::searchOrder($param, $page, $size);
        switch ($param ["keywordType"]) {
            case "0" : // 用户手机号

                foreach ($nums["data"] as $key => $num) {

                    $nums["data"][$key]["loginNum"] = RequestLog::getLoginNumbyUserId($num["userId"]);
                    $nums["data"][$key]["blacklistStatus"] = Blacklist::getStatusbyUserMobile($num["userMobile"]);
                    if ($num["orderNum"] >= $param['orderNum']) {
                        $orderNums=Warning::getOderNumByUserId($num["userId"], $minTime, $maxTime);
                        $nums["data"][$key]["payNum"]=$orderNums["payNum"];
                        $nums["data"][$key]["orderNum"]=$orderNums["orderNum"];
                    }
                }
                break;
            case "1" : // 设备号
                foreach ($nums["data"] as $key => $num) {
                    $nums["data"][$key]["loginNum"] = RequestLog::getLoginNumbyDevice($num["device"]);
                    $nums["data"][$key]["blacklistStatus"] = Blacklist::getStatusbyUserDevice($num["device"]);
                    if ($num["orderNum"] >= $param['orderNum']) {
                        $orderNums=Warning::getOderNumByDevice($num["device"], $minTime, $maxTime);
                        $nums["data"][$key]["payNum"]=$orderNums["payNum"];
                        $nums["data"][$key]["orderNum"]=$orderNums["orderNum"];
                    }
                }
                break;
            case "2" ://openId
                foreach ($nums["data"] as $key => $num) {
                    $nums["data"][$key]["loginNum"] = RequestLog::getLoginNumbyOpenId($num["openId"]);
                    $nums["data"][$key]["blacklistStatus"] = Blacklist::getStatusbyOpenId($num["openId"]);
                    if ($num["orderNum"] >= $param['orderNum']) {
                        $orderNums=Warning::getOderNumByOpenId($num["openId"], $minTime, $maxTime);
                        $nums["data"][$key]["payNum"]=$orderNums["payNum"];
                        $nums["data"][$key]["orderNum"]=$orderNums["orderNum"];
                    }
                }
                break;
        }
        return $nums;
    }

    /**
     * @api {post} /warning/phoneExport 4.预警查询手机列表导出
     * @apiName phoneExport
     * @apiGroup warning
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} keywordType 必选,搜索关键词类型，可取0 用户手机号/1 设备号/3 微信OpenId.
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     *
     * @apiErrorExample Error-Response:
     * {
     * "result": 0,
     * "msg": "未授权访问"
     * }
     */
    public function phoneExport() {
        $param = $this->param;
        $param["keywordType"] = 0;
        $this->export($param);
    }

    /**
     * @api {post} /warning/deviceExport 5.预警查询设备号列表导出
     * @apiName deviceExport
     * @apiGroup warning
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} keywordType 必选,搜索关键词类型，可取0 用户手机号/1 设备号/3 微信OpenId.
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     *
     * @apiErrorExample Error-Response:
     * {
     * "result": 0,
     * "msg": "未授权访问"
     * }
     */
    public function deviceExport() {
        $param = $this->param;
        $param["keywordType"] = 1;
        $this->export($param);
    }

    /**
     * @api {post} /warning/openidExport 6.预警查询openid列表导出
     * @apiName openidExport
     * @apiGroup warning
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} keywordType 必选,搜索关键词类型，可取0 用户手机号/1 设备号/3 微信OpenId.
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     *
     * @apiErrorExample Error-Response:
     * {
     * "result": 0,
     * "msg": "未授权访问"
     * }
     */
    public function openidExport() {
        $param = $this->param;
        $param["keywordType"] = 2;
        $this->export($param);
    }

    public function export($param) {

        $nums = $this->getIndex($param);
        $keywordName = "";

        switch ($param ["keywordType"]) {
            case "0" : // 用户手机号				
                $keywordName = "手机号";
                break;
            case "1" : // 设备号
                $keywordName = "设备号";
                break;
            case "2" ://openid
                $keywordName = "微信OpenId";
                break;
            default:
                throw new ApiException('黑名单无此类别！', ERROR::Warning_KeywordType_Notfound);
        }

        $header = [
            '序号',
            $keywordName,
            '合计登录次数',
            '合计支付次数',
            '合计购买单量',
        ];
        $res = Warning::format_export_data($nums["data"], $param['keywordType']);
        if (!empty($res)) {
            Event::fire("warning.export");
        }
        @ini_set('memory_limit', '256M');
        $this->export_xls("黑名单列表" . date("Ymd"), $header, $res);
    }

    /**
     * @api {post} /warning/phoneBlock 7.移入黑名单
     * @apiName phoneBlock
     * @apiGroup  warning
     * @apiParam {Number} mobilephone 必选
     * 
     *  
     * @apiSuccess {String} msg 移入信息
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          result": 1,
     *          "token": "",
     *          "data":{
     *          "msg": "成功移入黑名单！"
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    result": 0,
     *          "code": 1,
     *          "msg": "移入黑名单失败",
     *          "token": ""
     * 		}
     */
    public function phoneBlock() {
        $param = $this->param;
        $param["keywordType"] = 0;
        $result = $this->block($param);
        return $this->success($result);
    }

    /**
     * @api {post} /warning/deviceBlock 8.移入黑名单
     * @apiName deviceBlock
     * @apiGroup  warning
     *
     * @apiParam {Number} device_uuid	必选
     * 
     *  
     * @apiSuccess {String} msg 移入信息
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          result": 1,
     *          "token": "",
     *          "data":{
     *          "msg": "成功移入黑名单！"
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    result": 0,
     *          "code": 1,
     *          "msg": "移入黑名单失败",
     *          "token": ""
     * 		}
     */
    public function deviceBlock() {
        $param = $this->param;
        $param["keywordType"] = 1;
        $result = $this->block($param);
        return $this->success($result);
    }

    /**
     * @api {post} /warning/openidBlock 9.移入黑名单
     * @apiName openidBlock
     * @apiGroup  warning
     * 
     * @apiParam {Number} openid 必选
     * 
     *  
     * @apiSuccess {String} msg 移入信息
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          result": 1,
     *          "token": "",
     *          "data":{
     *          "msg": "成功移入黑名单！"
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    result": 0,
     *          "code": 1,
     *          "msg": "移入黑名单失败",
     *          "token": ""
     * 		}
     */
    public function openidBlock() {
        $param = $this->param;
        $param["keywordType"] = 2;
        $result = $this->block($param);
        return $this->success($result);
    }

    function block($param) {
        if (!isset($param['keywordType'])) {
            throw new ApiException('缺少类型！', ERROR::Warning_KeywordType_Notfound);
        }
        $date = date('Y-m-d H:i:s');
        $blacklistStatus = 0;
        switch ($param ["keywordType"]) {
            case "0" : // 用户手机号
                if (empty($param["mobilephone"])) {
                    throw new ApiException('找不到手机号！', ERROR::Blacklist_Exist);
                }
                $blacklistStatus = Blacklist::getStatusbyUserMobile($param["mobilephone"]);
                if ($blacklistStatus) {
                    throw new ApiException('已在黑名单内！', ERROR::Blacklist_Exist);
                }

                $result = Blacklist::insert(array('mobilephone' => $param["mobilephone"], "created_at" => $date, "updated_at" => $date));

                break;
            case "1" : // 设备号
                if (empty($param["device_uuid"])) {
                    throw new ApiException('找不到设备号！', ERROR::Blacklist_Exist);
                }
                $blacklistStatus = Blacklist::getStatusbyUserDevice($param["device_uuid"]);
                if ($blacklistStatus) {
                    throw new ApiException('已在黑名单内！', ERROR::Blacklist_Exist);
                }

                $result = Blacklist::insert(array('device_uuid' => $param["device_uuid"], "created_at" => $date, "updated_at" => $date));

                break;
            case "2" ://openId
                if (empty($param["openid"])) {
                    throw new ApiException('找不到openid！', ERROR::Blacklist_Exist);
                }
                $blacklistStatus = Blacklist::getStatusbyOpenId($param["openid"]);
                if ($blacklistStatus) {
                    throw new ApiException('已在黑名单内！', ERROR::Blacklist_Exist);
                }
                $result = Blacklist::insert(array('openid' => $param["openid"], "created_at" => $date, "updated_at" => $date));

                break;
        }

        if ($result) {
            $res['msg'] = "成功移入黑名单！";
            return $res;
        }
        throw new ApiException('移入黑名单失败！', ERROR::Blacklist_Block_FAILED);
    }

}
