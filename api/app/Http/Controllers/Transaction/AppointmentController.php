<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\TransactionSearchApi;
use App\Mapping;
use App\Appointment;
use App\User;
use App\Hairstylist;
use App\Salon;
use Event;
use DB;
Use PDO;

class AppointmentController extends Controller {

    /**
     * @api {get} /appointment/index 1.约造型师列表
     * @apiName index
     * @apiGroup appointment
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} keywordType 必选,搜索关键词类型，可取0 用户臭美号/1 用户手机号/2 店铺名称/3 造型师手机号.
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     * @apiParam {String} sortKey 可选,排序关键词 "contacted" 是否联系客户/ "add_time"提交时间.
     * @apiParam {String} sortType 可选,排序 DESC倒序 ASC升序.
     * @apiParam {Number} contacted 可选,联系客户，0/未联系  1/已联系.
     * 
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} id 约造型师id.
     * @apiSuccess {String} userMobile 用户手机号.
     * @apiSuccess {String} service_item 美发项目
     * @apiSuccess {String} contacted 联系客户
     * @apiSuccess {String} add_time 提交时间
     * @apiSuccess {String} stylistMobile 造型师手机号
     * @apiSuccess {String} salonName 店铺名称
     *
     * @apiSuccessExample Success-Response:
     *       {
     *           "result": 1,
     *           "token": "",
     *           "data":{
     *           "total": 33,
     *           "per_page": 20,
     *           "current_page": 1,
     *           "last_page": 2,
     *           "next_page_url": "http://localhost:8000/appointment/index/?page=2",
     *           "prev_page_url": null,
     *           "from": 1,
     *           "to": 20,
     *           "data":[
     *                   {
     *                   "id": 114,
     *                   "user_id": 1,
     *                   "stylist_id": 575,
     *                   "service_item": "洗剪吹",
     *                   "add_time": "2015-10-20 18:15:45",
     *                   "appoint_date": "2015-10-21",
     *                   "contacted": 0,
     *                   "userMobile": "15102011866",
     *                   "stylistMobile": "15507586054",
     *                   "salonName": "苏苏美发"
     *                   }...
     *                 ]
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
        $sortable_keys = ['contacted', 'add_time'];
        $sortKey = "add_time";
        $sortType = "DESC";
        if (isset($param['sortKey']) && in_array($param['sortKey'], $sortable_keys)) {
            $sortKey = $param['sortKey'];
            $sortType = $param['sortType'];
            if (strtoupper($sortType) != "DESC") {
                $sortType = "ASC";
            }
        }
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $query = Appointment::getQueryByParam($param);
        $appointments = Appointment::search($query, $page, $size, $sortKey, $sortType);
        return $this->success($appointments);
    }

    /**
     * @api {get} /appointment/show/{id} 2.查看约造型师详情
     * @apiName show
     * @apiGroup appointment
     *
     * @apiSuccess {String} unserName 用户臭美号
     * @apiSuccess {String} userMobile 用户手机号
     * @apiSuccess {String} service_item 美发项目
     * @apiSuccess {String} appoint_date 到店时间
     * @apiSuccess {Number} contacted 联系客户 0 未联系 1已联系
     * @apiSuccess {String} salonName 店铺名称
     * @apiSuccess {String} addr 店铺地址
     * @apiSuccess {String} stylistGrade 造型师等级 1/2资深发型师、3设计总监、4美发大师
     * @apiSuccess {String} stylistName 造型师名称
     * @apiSuccess {String} stylistMobile 造型师手机号
     * @apiSuccess {String} add_time 提交时间
     *
     * @apiSuccessExample Success-Response:
     *       {
     *          "result": 1,
     *          "token": "",
     *          "data":{
     *                  "id": 114,
     *                  "user_id": 1,
     *                  "stylist_id": 575,
     *                  "service_item": "洗剪吹",
     *                  "add_time": "2015-10-20 18:15:45",
     *                  "appoint_date": "2015-10-21",
     *                  "contacted": 0,
     *                  "userMobile": "15102011866",
     *                  "userName": "10000000",
     *                  "stylistMobile": "15507586054",
     *                  "stylistName": "Jack",
     *                  "stylistGrade": 3,
     *                  "salonName": "苏苏美发",
     *                  "addr": "深圳市南山区桃园路西海明珠花园119B"
     *                  }
     *          }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function show($id) {
        $id = intval($id);
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $appointment = Appointment::getAppointmentById($id);
        $user = User::getUserById($appointment["user_id"]);
        if ($user) {
            $appointment['userMobile'] = $user["mobilephone"];
            $appointment['userName'] = $user["username"];
        }
        $hairstylist = Hairstylist::getHairstylistById($appointment["stylist_id"]);

        if ($hairstylist) {
            $appointment['stylistMobile'] = $hairstylist["mobilephone"];
            $appointment['stylistName'] = $hairstylist["stylistName"];
            $appointment['stylistGrade'] = $hairstylist["grade"];
            $salon = Salon::getSalonById($hairstylist["salonId"]);
            if ($salon) {
                $appointment['salonName'] = $salon["salonname"];
                $appointment['addr'] = $salon["addr"];
            }
        }

        $appointment["add_time"] = date("Y-m-d H:i:s", $appointment["add_time"]);
        return $this->success($appointment);
    }

    /**
     * @api {get} /appointment/export 3.约造型师列表导出
     * @apiName export
     * @apiGroup appointment
     *
     * @apiParam {Number} page 可选,页码，默认为1.
     * @apiParam {Number} page_size 可选,默认为20.
     * @apiParam {String} keyword 可选,搜索关键词.
     * @apiParam {Number} keywordType 必选,搜索关键词类型，可取0 用户臭美号/1 用户手机号/2 店铺名称/3 造型师手机号.
     * @apiParam {String} minTime 提交最小时间 YYYY-MM-DD
     * @apiParam {String} maxTime 提交最大时间 YYYY-MM-DD
     * @apiParam {String} sortKey 可选,排序关键词 "contacted" 是否联系客户/ "add_time"提交时间.
     * @apiParam {String} sortType 可选,排序 DESC倒序 ASC升序.
     *
     * @apiErrorExample Error-Response:
     * {
     * "result": 0,
     * "msg": "未授权访问"
     * }
     */
    public function export() {
        $param = $this->param;

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
        $sortable_keys = ['contacted', 'add_time'];
        $sortKey = "add_time";
        $sortType = "DESC";
        if (isset($param['sortKey']) && in_array($param['sortKey'], $sortable_keys)) {
            $sortKey = $param['sortKey'];
            $sortType = $param['sortType'];
            if (strtoupper($sortType) != "DESC") {
                $sortType = "ASC";
            }
        }
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $query = Appointment::getQueryByParam($param);
        $appointments = Appointment::search($query, $page, $size, $sortKey, $sortType);
        $header = [
            '用户手机号',
            '提交时间',
            '服务项目',
            '造型师手机号',
            '店铺名称',
        ];
        $res = Appointment::format_export_data($appointments["data"]);
        if (!empty($res)) {
            Event::fire("appointment.export");
        }
        @ini_set('memory_limit', '256M');
        $this->export_xls("约发型师表" . date("Ymd"), $header, $res);
    }

}
