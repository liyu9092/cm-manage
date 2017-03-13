<?php

namespace App\Http\Controllers\SystemConfig;

use App\Http\Controllers\Controller;
use App\Blacklist;
use Event;
use Excel;
use DB;
Use PDO;
use Request;
use Storage;
use File;
use Fileentry;
use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Redis as Redis;
use App\Exceptions\ERROR;
use Log;

class BlacklistController extends Controller {

    /**
     * @api {post} /blacklist/phoneIndex 1.黑名单手机列表
     * @apiName phoneIndex
     * @apiGroup blacklist
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} minTime 进入黑名单最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 进入黑名单最大时间 YYYY-MM-DD
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} id 黑名单的id.
     * @apiSuccess {String} mobilephone 手机号.
     * @apiSuccess {String} device_uuid 设备号
     * @apiSuccess {String} openid 微信openid
     * @apiSuccess {String} created_at 进入黑名单时间
     * @apiSuccess {String} note 备注
     *
     * @apiSuccessExample Success-Response:
     * {
     *       result": 1,
     *      "token": "",
     *      "data":{
     *      "total": 6,
     *       "per_page": 20,
     *      "current_page": 1,
     *      "last_page": 1,
     *      "next_page_url": null,
     *      "prev_page_url": null,
     *      "from": 1,
     *      "to": 6,
     *      "data":[
     *      {
     *      "id": 4,
     *      "mobilephone": "13856961253",
     *      "device_uuid": "",
     *      "openid": "",
     *      "created_at": "2015-11-18 17:22:14",
     *      "updated_at": "0000-00-00 00:00:00",
     *      "note": ""
     *      },...
     *                 ]
     * }
     *
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
        $blacklists = $this->getIndex($param);
        return $this->success($blacklists);
    }

    /**
     * @api {post} /blacklist/deviceIndex 2.黑名单设备号列表
     * @apiName deviceIndex
     * @apiGroup blacklist
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} minTime 进入黑名单最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 进入黑名单最大时间 YYYY-MM-DD
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} id 黑名单的id.
     * @apiSuccess {String} mobilephone 手机号.
     * @apiSuccess {String} device_uuid 设备号
     * @apiSuccess {String} openid 微信openid
     * @apiSuccess {String} create_at 进入黑名单时间
     * @apiSuccess {String} note 备注
     *
     * @apiSuccessExample Success-Response:
     * {
     *       result": 1,
     *      "token": "",
     *      "data":{
     *      "total": 6,
     *       "per_page": 20,
     *      "current_page": 1,
     *      "last_page": 1,
     *      "next_page_url": null,
     *      "prev_page_url": null,
     *      "from": 1,
     *      "to": 6,
     *      "data":[
     *      {
     *      "id": 4,
     *      "mobilephone": "13856961253",
     *      "device_uuid": "",
     *      "openid": "",
     *      "created_at": "2015-11-18 17:22:14",
     *      "updated_at": "0000-00-00 00:00:00",
     *      "note": ""
     *      },...
     *                 ]
     * }
     *
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
        $blacklists = $this->getIndex($param);
        return $this->success($blacklists);
    }

    /**
     * @api {post} /blacklist/openidIndex 3.黑名单openid列表
     * @apiName openidIndex
     * @apiGroup blacklist
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} minTime 进入黑名单最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 进入黑名单最大时间 YYYY-MM-DD
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} id 黑名单的id.
     * @apiSuccess {String} mobilephone 手机号.
     * @apiSuccess {String} device_uuid 设备号
     * @apiSuccess {String} openid 微信openid
     * @apiSuccess {String} create_at 进入黑名单时间
     * @apiSuccess {String} note 备注
     *
     * @apiSuccessExample Success-Response:
     * {
     *       result": 1,
     *      "token": "",
     *      "data":{
     *      "total": 6,
     *       "per_page": 20,
     *      "current_page": 1,
     *      "last_page": 1,
     *      "next_page_url": null,
     *      "prev_page_url": null,
     *      "from": 1,
     *      "to": 6,
     *      "data":[
     *      {
     *      "id": 4,
     *      "mobilephone": "13856961253",
     *      "device_uuid": "",
     *      "openid": "",
     *      "created_at": "2015-11-18 17:22:14",
     *      "updated_at": "0000-00-00 00:00:00",
     *      "note": ""
     *      },...
     *                 ]
     * }
     *
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
        $blacklists = $this->getIndex($param);
        return $this->success($blacklists);
    }

    public function getIndex($param) {

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
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $query = Blacklist::getQueryByParam($param);
        return Blacklist::search($query, $page, $size);
    }

    /**
     * @api {post} /blacklist/phoneExport 4.黑名单手机导出
     * @apiName phoneExport
     * @apiGroup blacklist
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} minTime 进入黑名单最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 进入黑名单最大时间 YYYY-MM-DD
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
     * @api {post} /blacklist/deviceExport 5.黑名单设备号导出
     * @apiName deviceExport
     * @apiGroup blacklist
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} minTime 进入黑名单最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 进入黑名单最大时间 YYYY-MM-DD
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
     * @api {post} /blacklist/openidExport 6.黑名单openid导出
     * @apiName openidExport
     * @apiGroup blacklist
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {String} minTime 进入黑名单最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 进入黑名单最大时间 YYYY-MM-DD
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

//        if (isset($param['page']) && !empty($param['page'])) {
//            $page = $param['page'];
//        } else {
//            $page = 1;
//        }
//        if (isset($param['page_size']) && !empty($param['page_size'])) {
//            $size = $param['page_size'];
//        } else {
//            $size = 20;
//        }
//        if ($param['keywordType'] == '') {
//            throw new ApiException('请设置关键词类型！', ERROR::Blacklist_KeywordType_Notfound);
//        }
//        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
//        $query = Blacklist::getQueryByParam($param);
        $blacklists = $this->getIndex($param);
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
                throw new ApiException('黑名单无此类别！', ERROR::Blacklist_KeywordType_Notfound);
        }

        $header = [
            '序号',
            $keywordName,
            '进入黑名单时间',
            '备注',
        ];
        $res = Blacklist::format_export_data($blacklists["data"], $param['keywordType']);
        if (!empty($res)) {
            Event::fire("blacklist.export");
        }
        @ini_set('memory_limit', '256M');
        $this->export_xls("黑名单列表" . date("Ymd"), $header, $res);
    }

    /**
     * @api {post} /blacklist/phoneUpload 7.上传手机黑名单
     * @apiName phoneUpload
     * @apiGroup blacklist
     *
     * @apiParam {File} blacklist 必填,excel文件.
     *
     * @apiSuccess {String} userInfo 用户手机号
     * @apiSuccess {String} add_time 进入黑名单时间
     * @apiSuccess {String} note 备注
     * @apiSuccess {Number} blacklistStatus 黑名单状态 0 不存在黑名单/ 1 已存在黑名单
     * @apiSuccess {Number} isMobilephone 检测手机号 0 手机号码不符合规则/ 1 手机号码不符合规则
     * @apiSuccess {String} redisKey 数据缓存key
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": 
     *              {"redisKey":"9d728e9d887bc882eb232778227329b6",
     *              "data":[
     *                      {"device_uuid":" ",
     *                      "blacklistStatus":1,
     *                       "add_time":" 2015-11-19 17:22:14",
     *                      "updated_at":" 2015-11-19 17:22:14",
     *                      "note":null
     *                      }...
     *              }
     * 
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function phoneUpload() {
        $param = $this->param;
        $param["keywordType"] = 0;
        $result = $this->upload($param);
        return $this->success($result);
    }

    /**
     * @api {post} /blacklist/deviceUpload 8.上传设备号黑名单
     * @apiName deviceUpload
     * @apiGroup blacklist
     *
     * @apiParam {File} blacklist 必填,excel文件.
     *
     * @apiSuccess {String} userInfo 用户设备号
     * @apiSuccess {String} add_time 进入黑名单时间
     * @apiSuccess {String} note 备注
     * @apiSuccess {Number} blacklistStatus 黑名单状态 0 不存在黑名单/ 1 已存在黑名单
     * @apiSuccess {String} redisKey 数据缓存key
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": 
     *              {"redisKey":"9d728e9d887bc882eb232778227329b6",
     *              "data":[
     *                      {"device_uuid":" ",
     *                      "blacklistStatus":1,
     *                       "add_time":" 2015-11-19 17:22:14",
     *                      "updated_at":" 2015-11-19 17:22:14",
     *                      "note":null
     *                      }...
     *              }
     * 
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function deviceUpload() {
        $param = $this->param;
        $param["keywordType"] = 1;
        $result = $this->upload($param);
        return $this->success($result);
    }

    /**
     * @api {post} /blacklist/openidUpload 9.上传黑名单
     * @apiName openidUpload
     * @apiGroup blacklist
     *
     * @apiParam {File} blacklist 必填,excel文件.
     *
     * @apiSuccess {String} userInfo 用户openid
     * @apiSuccess {String} add_time 进入黑名单时间
     * @apiSuccess {String} note 备注
     * @apiSuccess {Number} blacklistStatus 黑名单状态 0 不存在黑名单/ 1 已存在黑名单
     * @apiSuccess {String} redisKey 数据缓存key
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": 
     *              {"redisKey":"9d728e9d887bc882eb232778227329b6",
     *              "data":[
     *                      {"device_uuid":" ",
     *                      "blacklistStatus":1,
     *                       "add_time":" 2015-11-19 17:22:14",
     *                      "updated_at":" 2015-11-19 17:22:14",
     *                      "note":null
     *                      }...
     *              }
     * 
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function openidUpload() {
        $param = $this->param;
        $param["keywordType"] = 2;
        $result = $this->upload($param);
        return $this->success($result);
    }

    public function upload($param) {
        if (!isset($param['keywordType'])) {
            throw new ApiException('请设置关键词类型！', ERROR::Blacklist_KeywordType_Notfound);
        }
        $file = Request::file('blacklist');
        if (!$file)
            throw new ApiException('请上传文件', ERROR::FILE_EMPTY);
        $extension = $file->getClientOriginalExtension();
        if (!in_array($extension, ['xls', 'xlsx']))
            throw new ApiException('请上传xls或者xlsx文件', ERROR::FILE_FORMAT_ERROR);

        $data = [];
        $redisKey = 'blacklist';
        $available = 1;
        Excel::load($file->getPathname(), function($reader)use($param, &$data, &$redisKey, &$available) {
            $reader = $reader->getSheet(0);
            $array = $reader->toArray();
            array_shift($array);
            $n = -1;
            foreach ($array as $key => $value) {
                if (empty($value[1]))
                    continue;
                $n = $n + 1;
                switch ($param['keywordType']) {
                    case "0" : // 用户手机号				
                        $data[$n]['userInfo'] = $value[1];
                        if (preg_match("/^1[3458]{1}\d{9}$/", $value[1])) {
                            $data[$n]['isMobilephone'] = 1;
                        } else {
                            $data[$n]['isMobilephone'] = 0;
                        }

                        $data[$n]["blacklistStatus"] = Blacklist::getStatusbyUserMobile($value[1]);
                        if ($data[$n]['isMobilephone'] == 0) {
                            $available = 0;
                        }

                        break;
                    case "1" : // 设备号
                        $data[$n]['userInfo'] = $value[1];
                        $data[$n]["blacklistStatus"] = Blacklist::getStatusbyUserDevice($value[1]);
//                        if ($data[$n]["blacklistStatus"] == 1) {
//                            $available = 0;
//                        }
                        break;
                    case "2" ://openid
                        $data[$n]['userInfo'] = $value[1];
                        $data[$n]["blacklistStatus"] = Blacklist::getStatusbyOpenId($value[1]);
//                        if ($data[$n]["blacklistStatus"] == 1) {
//                            $available = 0;
//                        }
                        break;
                    default:
                        throw new ApiException('黑名单无此类别！', ERROR::Blacklist_KeywordType_Notfound);
                }
                $data[$n]['note'] = $value[2];
                $redisKey = $redisKey . $value[1];
            }
        }, 'GBK');
        $result["data"] = $data;
        $redisKey = md5($redisKey);
        $redis = Redis::connection();
        if ($available) {
            $redis->setex($redisKey, 3600 * 24, serialize($data));
            $result["redisKey"] = $redisKey;
        } else {
            $redis->setex($redisKey, 3600 * 24, 0);
            $result["redisKey"] = null;
        }
//        $name = Blacklist::getName();
//        $folder = date('Y/m/d') . '/';
//        $src = $folder . $name . '.' . $extension;
//        Storage::disk('local')->put($src, File::get($file));              
        Log::info("blacklist upload result is", $result);
        return $result;
    }

    /**
     * @api {post} /blacklist/phoneSubmit 10.提交手机黑名单
     * @apiName phoneSubmit
     * @apiGroup blacklist
     *
     * @apiParam {File} blacklist 必填,excel文件.
     * @apiParam {String} redisKey 必选，缓存数据key
     *
     * @apiSuccess {String} msg 提交信息
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": [
     *              "msg":"黑名单提交成功!"
     *              ]
     *              
     * 	    }
     * 
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function phoneSubmit() {
        $param = $this->param;
        $param["keywordType"] = 0;
        $result = $this->submit($param);
        return $this->success($result);
    }

    /**
     * @api {post} /blacklist/deviceSubmit 11.提交设备号黑名单
     * @apiName deviceSubmit
     * @apiGroup blacklist
     *
     * @apiParam {File} blacklist 必填,excel文件.
     * @apiParam {String} redisKey 必选，缓存数据key
     *
     * @apiSuccess {String} msg 提交信息
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": [
     *              "msg":"黑名单提交成功!"
     *              ]
     *              
     * 	    }
     * 
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function deviceSubmit() {
        $param = $this->param;
        $param["keywordType"] = 1;
        $result = $this->submit($param);
        return $this->success($result);
    }

    /**
     * @api {post} /blacklist/openidSubmit 12.提交openid黑名单
     * @apiName openidSubmit
     * @apiGroup blacklist
     *
     * @apiParam {File} blacklist 必填,excel文件.
     * @apiParam {String} redisKey 必选，缓存数据key
     *
     * @apiSuccess {String} msg 提交信息
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": [
     *              "msg":"黑名单提交成功!"
     *              ]
     *              
     * 	    }
     * 
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function openidSubmit() {
        $param = $this->param;
        $param["keywordType"] = 2;
        $result = $this->submit($param);
        return $this->success($result);
    }

    public function submit($param) {
        if (empty($param['redisKey'])) {
            throw new ApiException('请提供数据key！', ERROR::Blacklist_RedisKey_Notfound);
        }
        $redis = Redis::connection();
        $data = $redis->get($param['redisKey']);
        if (!$data) {
            throw new ApiException('黑名单提交失败!', ERROR::Blacklist_UPLOAD_FAILED);
        }
        $data = unserialize($data);
        $date = date('Y-m-d H:i:s');
        $resultMsg="黑名单导入成功！";
        foreach ($data as $key => $value) {
            $insertDatas=[];
            $insertDatas['updated_at'] = $date;
            $insertDatas["created_at"] = $date;
            if(isset($value["note"]))
            {
                $insertDatas["note"] = $value["note"];
            }
            switch ($param['keywordType']) {
                case "0" : // 用户手机号				
                    $insertDatas["mobilephone"] = $value["userInfo"];
                    if (Blacklist::getStatusbyUserMobile($value["userInfo"])) {
                        
                        $result = Blacklist::where("mobilephone", '=', $value["userInfo"])->update($insertDatas);
                    } else {
                        
                        $result = Blacklist::insert($insertDatas);
                    }
                    if (!$result) {
                        $resultMsg='黑名单部分提交失败!';
                    }
                    break;
                case "1" : // 设备号
                    $insertDatas["device_uuid"] = $value["userInfo"];
                    if (Blacklist::getStatusbyUserDevice($value["userInfo"])) {
                        $result = Blacklist::where("device_uuid", '=', $value["userInfo"])->update($insertDatas);
                    } else {
                        $result = Blacklist::insert($insertDatas);
                    }
                    if (!$result) {
                        $resultMsg='黑名单部分提交失败!';
                    }
                    
                    break;
                case "2" ://openid
                    $insertDatas["openid"] = $value["userInfo"];
                    if (Blacklist::getStatusbyOpenId($value["userInfo"])==1) {
                        $result = Blacklist::where("openid", '=', $value["userInfo"])->update($insertDatas);
                    } else {
                        $result = Blacklist::insert($insertDatas);
                    }
                    if (!$result) {
                        $resultMsg='黑名单部分提交失败!';
                    }
                    
                    break;
                default:
                    throw new ApiException('黑名单无此类别！', ERROR::Blacklist_KeywordType_Notfound);
            }
        }
        
        return $data["msg"] = $resultMsg;
    }

    /**
     * @api {get} /blacklist/phoneRemove/{id} 13 .移出手机黑名单
     * @apiName phoneRemove
     * @apiGroup  blacklist
     *
     * @apiParam {Number} id 必选,黑名单的Id.	   
     *  
     * @apiSuccess {String} msg 移除信息
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          result": 1,
     *          "token": "",
     *          "data":{
     *          "msg": "成功移出黑名单！"
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    result": 0,
     *          "code": 1,
     *          "msg": "移出黑名单失败",
     *          "token": ""
     * 		}
     */
    public function phoneRemove($id) {
        $result = $this->remove($id);
        return $this->success($result);
    }

    /**
     * @api {get} /blacklist/deviceRemove/{id} 14 .移出设备号黑名单
     * @apiName deviceRemove
     * @apiGroup  blacklist
     *
     * @apiParam {Number} id 必选,黑名单的Id.	   
     *  
     * @apiSuccess {String} msg 移除信息
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          result": 1,
     *          "token": "",
     *          "data":{
     *          "msg": "成功移出黑名单！"
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    result": 0,
     *          "code": 1,
     *          "msg": "移出黑名单失败",
     *          "token": ""
     * 		}
     */
    public function deviceRemove($id) {
        $result = $this->remove($id);
        return $this->success($result);
    }

    /**
     * @api {get} /blacklist/openidRemove/{id} 15 .移出openid黑名单
     * @apiName openidRemove
     * @apiGroup  blacklist
     *
     * @apiParam {Number} id 必选,黑名单的Id.	   
     *  
     * @apiSuccess {String} msg 移除信息
     *
     * @apiSuccessExample Success-Response:
     *      {
     *          result": 1,
     *          "token": "",
     *          "data":{
     *          "msg": "成功移出黑名单！"
     *      }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    result": 0,
     *          "code": 1,
     *          "msg": "移出黑名单失败",
     *          "token": ""
     * 		}
     */
    public function openidRemove($id) {
        $result = $this->remove($id);
        return $this->success($result);
    }

    public function remove($id) {
        $id = intval($id);
        if (empty($id)) {
            throw new ApiException('找不到id！', ERROR::Blacklist_Id_Notfound);
        }
        $result = Blacklist::where('id', $id)->delete();
        if ($result) {
            $res['msg'] = "成功移出黑名单！";
            return $res;
        }
        throw new ApiException('移出黑名单失败', ERROR::Blacklist_Remove_FAILED);
    }

}
