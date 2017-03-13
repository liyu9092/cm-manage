<?php

namespace App\Http\Controllers\Stylist;

use App\Stylist;
use App\Http\Controllers\Controller;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use DB;

class StylistController extends Controller {

    /**
     * @api {post} /stylist/index 1.造型师列表
     * @apiName list
     * @apiGroup Stylist
     *
     * @apiParam {String} stylistName 可选,造型师名称.
     * @apiParam {String} mobilephone 可选,手机号.
     * @apiParam {String} sortKey 可选,排序字段.
     * @apiParam {String} sortType 可选,排序方式.
     * @apiParam {Number} page 可选,页数.
     * @apiParam {Number} page_size 可选,分页大小.
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面数.
     * @apiSuccess {Number} last_page 最后页面数.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} stylistId 造型师ID.
     * @apiSuccess {String} stylistName 造型师名称.
     * @apiSuccess {Number} mobilephone 手机号.
     * @apiSuccess {String} sNumber 在职编号.
     * @apiSuccess {Numder} grade 悬赏等级 0没有等级 1美发师 2高级美发师 3造型师 4艺术总监.
     * @apiSuccess {Number} fastGrade 快剪等级 0没有等级 1普通快剪 2总监快剪.
     * @apiSuccess {Number} status 状态:1正常;2:禁用..
     * @apiSuccess {Number} num 作品数.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 
     *   {
     *       "result":1,
     *       "token":"",
     *       "data":
     *           {
     *               "total":1,
     *               "per_page":20,
     *               "current_page":1,
     *               "last_page":1,
     *               "from":1,
     *               "to":1,
     *               "data":[
     *                   {
     *                       "stylistId":1891,
     *                       "stylistName":"\u5434\u5f66\u7956",
     *                       "mobilephone":"13360059872",
     *                       "sNumber":"28",
     *                       "grade":1,
     *                       "fastGrade":1,
     *                       "status":1,
     *                       "num":1
     *                   }
     *               ]
     *           }
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function index() {
        $param = $this->param;
        $query = Stylist::getStylistList($param);
        return $this->success($query);
    }

    /**
     * @api {post} /stylist/destroy/:id  3.删除造型师
     * @apiName destroy
     * @apiGroup  Stylist
     *
     * @apiParam {Number} id 必填,主键.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "msg": "",
     * 	    "data": {
     * 	    }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "删除造型师失败"
     * 		}
     */
    public function destroy($stylistId) {
        $stylist = Stylist::find($stylistId);
        if (!$stylist) {
            throw new ApiException('找不到该造型师', ERROR::MERCHANT_STYLIST_ID_ERROR);
        }
        $task = DB::table('bounty_task')->where(array('hairstylistId' => $stylistId))->whereIn('btStatus', [2, 3])->count();
        if ($task == true) {
            throw new ApiException('你有已接单未完成打赏的悬赏单', ERROR::MERCHANT_STYLIST_NOREWARD_ERROR);
        }
        $data['status'] = 2;
        $query = Stylist::find($stylistId)->update($data);
        if ($query) {
            return $this->success();
        } else {
            throw new ApiException('删除造型师失败', ERROR::MERCHANT_STYLIST_DELETE_ERROR);
        }
    }

    /**
     * @api {post} /stylist/enable/:id  4.启用造型师
     * @apiName enable
     * @apiGroup  Stylist
     *
     * @apiParam {Number} id 必填,主键.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "msg": "",
     * 	    "data": {
     * 	    }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "启用造型师失败"
     * 		}
     */
    public function enable($stylistId) {
        $stylist = Stylist::find($stylistId);
        if (!$stylist) {
            throw new ApiException('找不到该造型师', ERROR::MERCHANT_STYLIST_ID_ERROR);
        }
        $data['status'] = 1;
        $query = Stylist::find($stylistId)->update($data);
        if ($query) {
            return $this->success();
        } else {
            throw new ApiException('启用造型师失败', ERROR::MERCHANT_STYLIST_ENABLE_ERROR);
        }
    }

    /**
     * @api {post} /stylist/disabled/:id  5.禁用造型师
     * @apiName disabled
     * @apiGroup  Stylist
     *
     * @apiParam {Number} id 必填,主键.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "msg": "",
     * 	    "data": {
     * 	    }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "禁用造型师失败"
     * 		}
     */
    public function disabled($stylistId) {
        $stylist = Stylist::find($stylistId);
        if (!$stylist) {
            throw new ApiException('找不到该造型师', ERROR::MERCHANT_STYLIST_ID_ERROR);
        }
        $data['status'] = 3;
        $query = Stylist::find($stylistId)->update($data);
        if ($query) {
            return $this->success();
        } else {
            throw new ApiException('禁用造型师失败', ERROR::MERCHANT_STYLIST_DESABLED_ERROR);
        }
    }

    /**
     * @api {post} /stylist/show/:id 6.查看造型师
     * @apiName show
     * @apiGroup Stylist
     *
     * @apiParam {Number} id 必填,主键.
     *
     * @apiSuccess {Number} stylistId 造型师ID.
     * @apiSuccess {Number} salonId 店铺编号.
     * @apiSuccess {String} stylistName 造型师名称.
     * @apiSuccess {String} stylistImg 造型师图像.
     * @apiSuccess {String} job 职位.
     * @apiSuccess {Number} mobilephone 手机号.
     * @apiSuccess {Number} addTime 添加时间.
     * @apiSuccess {Number} likeNum 喜欢总数.
     * @apiSuccess {String} signature 造型师签名.
     * @apiSuccess {Number} status 状态:1正常;2:禁用..
     * @apiSuccess {Number} sex 性别 0保密 1男 2女.
     * @apiSuccess {String} wechat 微信.
     * @apiSuccess {String} qq QQ.
     * @apiSuccess {String} email email.
     * @apiSuccess {Number} birthday 出生日期.
     * @apiSuccess {String} IDcard 身份证.
     * @apiSuccess {String} sNumber 在职编号.
     * @apiSuccess {Numder} workYears 工作年限.
     * @apiSuccess {Numder} grade 悬赏等级 0没有等级 1美发师 2高级美发师 3造型师 4艺术总监.
     * @apiSuccess {String} workExp 工作经验.
     * @apiSuccess {String} educateExp 教育经验.
     * @apiSuccess {String} description 自我描述.
     * @apiSuccess {Number} gradeType 悬赏等级.
     * @apiSuccess {Number} osType 造型师使用的设备类型.
     * @apiSuccess {Number} fastGrade 快剪等级 0没有等级 1普通快剪 2总监快剪.
     * @apiSuccess {String} drivingLicense 驾驶证.
     * @apiSuccess {String} passport 护照.
     * @apiSuccess {String} officerCert 军官证.
     * @apiSuccess {String} salonname 店铺名称.
     * @apiSuccess {String} name 所属商户.
     * @apiSuccess {String} stylistImgCom 造型师头像的缩略图. 
     * @apiSuccess {String} img 造型师图像和造型师头像的缩略图的集合. 
     * @apiSuccess {String} reward 1 是你有已接单未完成打赏的悬赏单  2 是没有订单和未完成打赏的单.
     * 
     * @apiSuccessExample Success-Response:
     * 
     *   {
     *       "result":1,
     *       "token":"",
     *       "data":
     *           {
     *               "stylistId":1039,
     *               "salonId":29,
     *               "stylistName":"test-z",
     *               "stylistImg":"http:\/\/dev-sm.choumei.me\/Uploads\/menlist\/2015-07-08\/559ccc7e2ec39.png",
     *               "job":"dashge",
     *               "mobilephone":"12266332222",
     *               "addTime":1436339446,
     *               "likeNum":0,
     *               "signature":"asfasfasfasdf\r\nasdfasfdadsfasdf",
     *               "status":1,
     *               "sex":1,
     *               "wechat":"12266332222",
     *               "qq":"12266332222",
     *               "email":"12266332222@12.com",
     *               "birthday":633801600,
     *               "IDcard":"12266332222",
     *               "sNumber":"1226",
     *               "workYears":5,
     *               "grade":0,
     *               "workExp":"{"wsTime1":"","weTime1":"","wname1":"","wjob1":"","waddress1":"","wsTime2":"","weTime2":"","wname2":"","wjob2":"","waddress2":"","wsTime3":"","weTime3":"","wname3":"","wjob3":"","waddress3":"","wsTime4":"","weTime4":"","wname4":"","wjob4":"","waddress4":"","wsTime5":"","weTime5":"","wname5":"","wjob5":"","waddress5":""}",
     *               "educateExp":"{"sTime1":"","eTime1":"","name1":"","sTime2":"","eTime2":"","name2":"","sTime3":"","eTime3":"","name3":"","sTime4":"","eTime4":"","name4":"","sTime5":"","eTime5":"","name5":""}",
     *               "description":"asdfasdfasdf",
     *               "gradeType":0,
     *               "osType":0,
     *               "fastGrade":1,
     *               "drivingLicense":"",
     *               "passport":"",
     *               "officerCert":""，
     *               "salonname":"HERE\u2032S",
     *               "name":"\u6df1\u5733\u5e02\u98a2\u4e1d\u5f62\u8c61\u8bbe\u8ba1\u7ba1\u7406\u6709\u9650\u516c\u53f8"
     *          }
     *    }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function show($stylistId) {
        $stylist = Stylist::find($stylistId);
        if (!$stylist) {
            throw new ApiException('找不到该造型师', ERROR::MERCHANT_STYLIST_ID_ERROR);
        }
        $task = DB::table('bounty_task')->where(array('hairstylistId' => $stylistId))->whereIn('btStatus', [2, 3])->count();
        if ($task == true) {
            $stylist->reward = 1;
        } else {
            $stylist->reward = 2;
        }
        $field = ['salonname', 'merchantId'];
        $salon = DB::table('salon')->select($field)->where(array("salonid" => $stylist->salonId))->first();
        if ($salon === false) {
            throw new ApiException('店铺不存在', ERROR::MERCHANT_NOTNAME_ERROR);
        }
        $field2 = ['name'];
        $merchant = DB::table('merchant')->select($field2)->where(array("id" => $salon->merchantId))->first();
        if ($merchant === false) {
            throw new ApiException('没有所属商户', ERROR::MERCHANT_NOT_MERCHANT_ERROR);
        }
        $stylist->salonname = $salon->salonname;
        $stylist->name = $merchant->name;
        return $this->success($stylist);
    }

    /**
     * @api {post} /stylist/update/:id 7.修改造型师
     * @apiName update
     * @apiGroup Stylist
     *
     * @apiParam {Number} id 必填,造型师ID、主键.
     * @apiParam {String} stylistName 可选,造型师名称.
     * @apiParam {String} stylistImg 必填,造型师图像.
     * @apiParam {String} img  可选, 造型师图像和造型师头像的缩略图的集合. 
     * @apiParam {Number} mobilephone 必填,手机号.
     * @apiParam {String} signature 必填,个性签名.
     * @apiParam {Number} checkbox 必填,修改所属店铺 ：1为选中，其他为没选中.
     * @apiParam {Number} sex 必填,性别.
     * @apiParam {String} wechat 可选,微信.
     * @apiParam {String} qq 可选,QQ.
     * @apiParam {String} email 可选,email.
     * @apiParam {Number} birthday 必填,出生日期.
     * @apiParam {String} IDcard 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,身份证.
     * @apiParam {String} sNumber 必填,在职编号.
     * @apiParam {Numder} workYears 必填,工作年限.
     * @apiParam {String} job 必填,门店职位.
     * @apiParam {Numder} grade 可选,悬赏等级 0没有等级 1美发师 2高级美发师 3造型师 4艺术总监.
     * @apiParam {String} workExp 可选,工作经验.
     * @apiParam {String} educateExp 可选,教育经历.
     * @apiParam {String} description 可选,自我描述.
     * @apiParam {Number} fastGrade 可选,快剪等级 0没有等级 1普通快剪 2总监快剪.
     * @apiParam {String} drivingLicense 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,驾驶证.
     * @apiParam {String} passport 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,护照.
     * @apiParam {String} officerCert 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,军官证.
     * @apiParam {String} salonname 必填,店铺名称.
     *
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "msg": "",
     * 	    "data": {
     * 	    }
     * 	}
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function update($stylistId) {
        $param = $this->param;
        $field = ['salonid', 'merchantId'];
        $stylist = Stylist::find($stylistId);
        if (!$stylist) {
            throw new ApiException('找不到该造型师', ERROR::MERCHANT_STYLIST_ID_ERROR);
        }
        if (!isset($param['stylistImg']) || !isset($param['stylistName']) || empty($param['sex']) || !isset($param['mobilephone']) || !isset($param['job']) || empty($param['birthday']) || empty($param['sNumber']) || empty($param['workYears']) || empty($param['signature'])) {
            throw new ApiException('参数不齐', ERROR::MERCHANT_ERROR);
        }
        if (!isset($param['IDcard']) && !isset($param['drivingLicense']) && !isset($param['passport']) && !isset($param['officerCert'])) {
            throw new ApiException('参数不齐', ERROR::MERCHANT_ERROR);
        }
        $salon = DB::table('salon')->select($field)->where(array("salonname" => $param['salonname']))->first();
        if ($salon == FALSE) {
            throw new ApiException('店铺名称不存在', ERROR::MERCHANT_NOTNAME_ERROR);
        }
        $param['salonid'] = $salon->salonid;

        if ($stylist['mobilephone'] != $param['mobilephone']) {
            $stylistCount = Stylist::where(array('mobilephone' => $param['mobilephone']))->whereIn('status', [1, 3])->count();
            if ($stylistCount) {
                throw new ApiException('手机号码重复', ERROR::MERCHANT_MOBILEPHONE_ERROR);
            }
        }
        $query = Stylist::updateStylist($stylistId, $param);
        if ($query) {
            return $this->success();
        } else {
            throw new ApiException('修改造型师失败', ERROR::MERCHANT_STYLIST_UPDATE_ERROR);
        }
    }

    /**
     * @api {post} /stylist/create/:id 8.创建造型师
     * @apiName create
     * @apiGroup Stylist
     *
     * @apiParam {Number} id 必填,salonId.
     * @apiParam {String} stylistName 必填,造型师名称.
     * @apiParam {String} stylistImg 必填,造型师图像.
     * @apiParam {String} img  必填,造型师图像和造型师头像的缩略图的集合. 
     * @apiParam {Number} mobilephone 必填,手机号.
     * @apiParam {String} signature 必填,个性签名.
     * @apiParam {Number} sex 必填,性别.
     * @apiParam {String} wechat 可选,微信.
     * @apiParam {String} qq 可选,QQ.
     * @apiParam {String} email 可选,email.
     * @apiParam {Number} birthday 必填,出生日期.
     * @apiParam {String} IDcard 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,身份证.
     * @apiParam {String} sNumber 必填,在职编号.
     * @apiParam {Numder} workYears 必填,工作年限.
     * @apiParam {String} job 必填,门店职位.
     * @apiParam {Numder} grade 可选,悬赏等级 0没有等级 1美发师 2高级美发师 3造型师 4艺术总监.
     * @apiParam {String} workExp 可选,工作经验.
     * @apiParam {String} educateExp 可选,教育经历.
     * @apiParam {String} description 可选,自我描述.
     * @apiParam {Number} fastGrade 可选,快剪等级 0没有等级 1普通快剪 2总监快剪.
     * @apiParam {String} drivingLicense 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,驾驶证.
     * @apiParam {String} passport 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,护照.
     * @apiParam {String} officerCert 选择IDcard、drivingLicense、officerCert、passport四个中必填一个,军官证.
     *
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "msg": "",
     * 	    "data": {
     * 	    }
     * 	}
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function create($salonid) {
        $param = $this->param;
        $salon = DB::table('salon')->where(array('salonid' => $salonid))->first();
        if (!$salon) {
            throw new ApiException('找不到该店铺', ERROR::MERCHANT_ID_IS_ERROR);
        }
        if (!isset($param['stylistImg']) || empty($param['img']) || !isset($param['stylistName']) || empty($param['sex']) || !isset($param['mobilephone']) || !isset($param['job']) || empty($param['birthday']) || empty($param['sNumber']) || empty($param['workYears']) || empty($param['signature'])) {
            throw new ApiException('参数不齐', ERROR::MERCHANT_ERROR);
        }
        if (!isset($param['IDcard']) && !isset($param['drivingLicense']) && !isset($param['passport']) && !isset($param['officerCert'])) {
            throw new ApiException('参数不齐', ERROR::MERCHANT_ERROR);
        }
        $stylistCount = Stylist::where(array('mobilephone' => $param['mobilephone']))->whereIn('status', [1, 3])->count();
        if ($stylistCount) {
            throw new ApiException('手机号码重复', ERROR::MERCHANT_MOBILEPHONE_ERROR);
        }
        $query = Stylist::createStylist($salonid, $param);
        if ($query) {
            return $this->success();
        } else {
            throw new ApiException('创建造型师失败', ERROR::MERCHANT_STYLIST_CREATE_ERROR);
        }
    }

}
