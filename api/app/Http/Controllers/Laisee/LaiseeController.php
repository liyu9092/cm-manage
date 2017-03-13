<?php

namespace App\Http\Controllers\Laisee;

use App\Http\Controllers\Controller;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\LaiseeConfig;
use App\VoucherConf;
use App\Voucher;
use Illuminate\Support\Facades\DB;
use App\Laisee;
use Excel;

class LaiseeController extends Controller {

    protected $addFields = array(
        "laisee_name",
        "lc_remark",
        "effective",
        "total_money",
//        "amount_warning",
        "voucher",
        "share_icon",
        "share_title",
        "share_desc",
        "bonus_bg_img",
    );
    protected $voucherFields = ["vUseItemTypes", "vUseMoney", "vNumber", "vDay", "vUseNeedMoney"];

    /**
     * @api {post} /laisee/index 1.红包活动列表
     * @apiName index
     * @apiGroup laisee
     * 
     * @apiParam {String} laisee_name 可选，活动名称.
     * @apiParam {Number} start_time 可选,创建时间YYYY-MM-DD
     * @apiParam {Number} end_time 可选，结束时间YYYY-MM-DD
     *  @apiParam {Number} page 可选,页数.
     * @apiParam {Number} page_size 可选,分页大小.
     *
     * 
     * @apiSuccess {String} laisee_name 红包名称.
     * @apiSuccess {Number} voucherNum 现金券数.
     * @apiSuccess {Number} receiveNum 已领取数.
     * @apiSuccess {Number} usedNum 已使用数.
     * @apiSuccess {Number} giftNum 礼包领取数.
     * @apiSuccess {String} create_time 创建时间.
     * @apiSuccess {String} start_time 上线时间.
     * @apiSuccess {String} status 活动状态 (N已结束 Y进行中 S已关闭 F下线).
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 
     * 	{
     * 	    "result": 1,
     * 	    "data": [
     *      {
     *          "id": 2,
     *          "laisee_name": "范德萨的范德萨",
     *          "create_time": "2015-10-19 12:12:48",
     *          "start_time": "2012-10-19",
     *          "status": "Y",
     *          "vcsns": "cm299012,cm310470",
     *          "gift_vcsn": "",
     *          "over_time": 172800,
     *          "voucherNum": "6",
     *          "receiveNum": 3,
     *          "usedNum": 0,
     *          "giftNum": 0
     *      }
     * 	        ]
     * 	    }
     * 	}
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
        $laiseeName = isset($param['laisee_name']) ? $param['laisee_name'] : '';
        $startTime = isset($param['start_time']) ? $param['start_time'] : '';
        $endTime = isset($param['end_time']) ? $param['end_time'] : '';
        $page = isset($param['page']) ? max(intval($param['page']), 1) : 1;
        $size = isset($param['page_size']) ? max(intval($param['page_size']), 1) : 20;
        $laiseeList = LaiseeConfig::getLaiseeList($laiseeName, $startTime, $endTime, $page, $size);
        $data = [];
        //判断在线的活动是否过期
//        LaiseeConfig::laiseeConfigAble();
        foreach ($laiseeList['data'] as $key => &$val) {
            $vcsnWhere = $val['vcsns'] . "," . $val['gift_vcsn'];
            $vcsnWhere = array_filter(array_unique(explode(",", $vcsnWhere)));
//            $val['voucherNum']
            $val['voucherNum'] = VoucherConf::whereIn('vcSn', $vcsnWhere)->sum('useTotalNum'); //现金券总数
//            $val['receiveNum']
            $val['receiveNum'] = Laisee::where('laisee_config_id', $val['id'])->whereNotNull('mobilephone')->count(); //已领取数  TODO Laisee::where('id', $val['id']) ID需要改
//            $val['usedNum'] 
            $val['usedNum'] = Voucher::whereIn('vcSn', $vcsnWhere)->where('vStatus', 2)->count();  //已使用数
//            $val['giftNum'] 
            $val['giftNum'] = !empty($val['gift_vcsn']) ? Laisee::where('id', $val['id'])->whereIn('vcsn', explode(",", $val['gift_vcsn']))->whereNotNull('mobilephone')->count() : 0;  //礼包领取数

            if ($val['status'] == 'Y') {
                $data = $val;
                array_splice($laiseeList['data'], $key, 1);
            }
        }
        !empty($data) && array_unshift($laiseeList['data'], $data);
        return $this->success($laiseeList);
    }

    private function format($val) {
        
    }

    /**
     * @api {post} /laisee/export 2.导出活动
     * @apiName export
     * @apiGroup laisee
     *
     * @apiParam {String} laisee_name 可选，活动名称.
     * @apiParam {Number} start_time 可选,创建时间YYYY-MM-DD
     * @apiParam {Number} end_time 可选，结束时间YYYY-MM-DD
     *
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     * 	    "result": 1,
     * 	    "data": null
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function export() {
        $param = $this->param;
        $laiseeName = isset($param['laisee_name']) ? $param['laisee_name'] : '';
        $startTime = isset($param['start_time']) ? $param['start_time'] : '';
        $endTime = isset($param['end_time']) ? $param['end_time'] : '';
        $page = isset($options['page']) ? max(intval($options['page']), 1) : 1;
        $size = isset($options['page_size']) ? max(intval($options['page_size']), 1) : 20;
        $laiseeList = LaiseeConfig::getLaiseeList($laiseeName, $startTime, $endTime, $page, $size);
        $data = [];
        foreach ($laiseeList['data'] as $key => $val) {
//            \Illuminate\Support\Facades\DB::enableQueryLog();
//            $data[$key]['id'] = $val['id'];
            $data[$key]['laisee_name'] = $val['laisee_name'];
            $vcsnWhere = $val['vcsns'] . "," . $val['gift_vcsn'];
            $vcsnWhere = array_filter(array_unique(explode(",", $vcsnWhere)));
            $data[$key]['voucherNum'] = (string) VoucherConf::whereIn('vcSn', $vcsnWhere)->sum('useTotalNum'); //现金券总数
            $data[$key]['receiveNum'] = (string) Laisee::where('laisee_config_id', $val['id'])->whereNotNull('mobilephone')->count(); //已领取数  TODO Laisee::where('id', $val['id']) ID需要改
            $data[$key]['usedNum'] = (string) Voucher::whereIn('vcSn', $vcsnWhere)->where('vStatus', 2)->count();  //已使用数
            $giftNum = !empty($val['gift_vcsn']) ? Laisee::where('id', $val['id'])->whereIn('vcsn', explode(",", $val['gift_vcsn']))->whereNotNull('mobilephone')->count() : 0;  //礼包领取数
            $data[$key]['giftNum'] = (string) $giftNum;
            $data[$key]['create_time'] = $val['create_time'];
            $data[$key]['start_time'] = $val['start_time'];
            if ($val['status'] == 'Y') {
                $data[$key]['status'] = "进行中";
            } elseif ($val['status'] == 'N') {
                $data[$key]['status'] = "已结束";
            } elseif ($val['status'] == 'S') {
                $data[$key]['status'] = "已关闭";
            } else {
                $data[$key]['status'] = "下线";
            }
        }
        //导出excel	   
        $title = '红包活动列表' . date('Ymd');
        $header = ['红包名称', '现金券数', '已领取数', '已使用数', '礼包领取数', '创建时间', '上线时间', '活动状态'];
        Excel::create($title, function($excel) use($data, $header) {
            $excel->sheet('Sheet1', function($sheet) use($data, $header) {
                $sheet->fromArray($data, null, 'A1', false, false); //第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header); //添加表头
            });
        })->export('xls');
    }

    /**
     * @api {post} /laisee/create 3.新增红包活动
     * @apiName create
     * @apiGroup laisee
     *
     * @apiParam {String} laisee_name 红包名称.
     * @apiParam {String} lc_remark 活动简介.
     * @apiParam {Number} effective  有效天数.
     * @apiParam {Number} total_money 活动领取金额上限.
     * @apiParam {Number} amount_warning 红包领取金额预警(只需要数字).
     * @apiParam {String} warning_phone 可选，预警提醒的手机号码用,号隔开.
     * @apiParam {String} sms_on_gained 可选，获取代金券时下发的短信内容（模板）.
     * 
     * @apiParam {String} voucher   json数组串{vUseItemTypes:现金券类型;  vUseMoney:现金券金额; vNumber:现金券数量;  few_day:有效时间; vUseNeedMoney:满多少可用 }
     * @apiParam {String} gift  json数组串{vUseItemTypes:礼包现金券类型;  vUseMoney:礼包现金券金额; vNumber:礼包现金券数量;  few_day:礼包有效时间; vUseNeedMoney:礼包满多少可用 }
     * 
     * @apiParam {String} share_icon 分享ICON.
     * @apiParam {String} share_title 分享标题.
     * @apiParam {String} share_desc 分享摘要.
     * @apiParam {String} bonus_bg_img 红包页面背景图片.
     * 
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": null
     * 	    }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "红包活动创建失败"
     * 		}
     */
    public function create() {
        return $this->dosave($this->param);
    }

    /**
     * @api {post} /laisee/update 4.修改红包活动
     * @apiName update
     * @apiGroup laisee
     *
     * @apiParam {Number} id 活动id（必须）.
     * @apiParam {String} laisee_name 红包名称.
     * @apiParam {String} lc_remark 活动简介.
     * @apiParam {Number} effective  有效天数.
     * @apiParam {Number} total_money 活动领取金额上限.
     * @apiParam {Number} amount_warning 红包领取金额预警(只需要数字).
     * @apiParam {String} warning_phone 可选，预警提醒的手机号码用,号隔开.
     * @apiParam {String} sms_on_gained 获取代金券时下发的短信内容（模板）.
     * 
     * @apiParam {String} voucher   json数组串{ vVcId: 现金券配置信息id; vUseItemTypes:现金券类型;  vUseMoney:现金券金额; vNumber:现金券数量;  few_day:有效时间; vUseNeedMoney:满多少可用 }
     * @apiParam {String} gift  json数组串{ vVcId: 礼包信息id; vUseItemTypes:礼包现金券类型;  vUseMoney:礼包现金券金额; vNumber:礼包现金券数量;  few_day:礼包有效时间; vUseNeedMoney:礼包满多少可用 }
     * 
     * @apiParam {String} delVcId 删除的现金券配置信息id，多个以逗号隔开.
     * @apiParam {String} delGiftVcId 删除的礼包配置信息id，多个以逗号隔开.
     * 
     * @apiParam {String} share_icon 分享ICON.
     * @apiParam {String} share_title 分享标题.
     * @apiParam {String} share_desc 分享摘要.
     * @apiParam {String} bonus_bg_img 红包页面背景图片.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	    {
     * 	        "result": 1,
     * 	        "data": null
     * 	    }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "红包活动创建失败"
     * 		}
     */
    public function update() {
        return $this->dosave($this->param);
    }

    private function dosave($param) {
        //基本信息
        $data['laisee_name'] = isset($param['laisee_name']) ? $param['laisee_name'] : '';
        $data['lc_remark'] = isset($param['lc_remark']) ? $param['lc_remark'] : '';
        $data['effective'] = isset($param['effective']) ? $param['effective'] : '';
        $data['total_money'] = isset($param['total_money']) ? $param['total_money'] : '';
        $data['amount_warning'] = isset($param['amount_warning']) ? $param['amount_warning'] : 0;
        $data['warning_phone'] = isset($param['warning_phone']) ? $param['warning_phone'] : '';
        $data['sms_on_gained'] = isset($param['sms_on_gained']) ? $param['sms_on_gained'] : '';
        //红包现金券配置
        $data['voucher'] = isset($param['voucher']) ? $param['voucher'] : '';
//        $data['vUseItemTypes'] = isset($param['vUseItemTypes']) ? $param['vUseItemTypes'] : 1;
//        $data['vUseMoney'] = isset($param['vUseMoney']) ? $param['vUseMoney'] : 0;
//        $data['vNumber'] = isset($param['vNumber']) ? $param['vNumber'] : 0;
//        $data['vDay'] = isset($param['vDay']) ? $param['vDay'] : 0;
//        $data['vUseNeedMoney'] = isset($param['vUseNeedMoney']) ? $param['vUseNeedMoney'] : 0;
        //礼包配置
        $data['gift'] = isset($param['gift']) ? $param['gift'] : '';
//        $data['gUseItemTypes'] = isset($param['gUseItemTypes']) ? $param['gUseItemTypes'] : 1;
//        $data['gUseMoney'] = isset($param['gUseMoney']) ? $param['gUseMoney'] : 0;
//        $data['gNumber'] = isset($param['gNumber']) ? $param['gNumber'] : 0;
//        $data['gDay'] = isset($param['gDay']) ? $param['gDay'] : 0;
//        $data['gUseNeedMoney'] = isset($param['gUseNeedMoney']) ? $param['gUseNeedMoney'] : 0;
        //H5页面配置
        $data['share_icon'] = isset($param['share_icon']) ? $param['share_icon'] : '';
        $data['share_title'] = isset($param['share_title']) ? $param['share_title'] : '';
        $data['share_desc'] = isset($param['share_desc']) ? $param['share_desc'] : '';
        $data['bonus_bg_img'] = isset($param['bonus_bg_img']) ? $param['bonus_bg_img'] : '';
        $data['id'] = isset($param['id']) ? $param['id'] : 0;
        //修改时需传递  现金券和礼包配置信息id
//        $data['vVcId'] = isset($param['vVcId']) ? $param['vVcId'] : 0;
//        $data['gVcId'] = isset($param['gVcId']) ? $param['gVcId'] : 0;
        // 需要删除的 现金券和礼包配置信息id
        $data['delVcId'] = isset($param['delVcId']) ? $param['delVcId'] : 0;
        $data['delGiftVcId'] = isset($param['delGiftVcId']) ? $param['delGiftVcId'] : 0;


        $retMissing = "";

        foreach ($this->addFields as $val) {
            if (!$data[$val]) {
                $retMissing .= $val . "-";
            }
        }
        if ($data['voucher']) {
            $data['voucher'] = json_decode($data['voucher'], true);
        }
        if ($retMissing) {
            throw new ApiException("缺失参数" . $retMissing, ERROR::PARAMS_LOST);
        }

        //检测参数
        if ($data['amount_warning']) {
            if (empty($data['warning_phone'])) {
                throw new ApiException("请填写预警手机号" . $retMissing, ERROR::PARAMS_LOST);
            }
        }

        if ($data['gift']) {
            $data['gift'] = json_decode($data['gift'], true);
        } else {
            $data['gift'] = [];
        }
        if ($data['id']) {
            foreach ($data['voucher'] as $vc) {
                if (!isset($vc['vVcId'])) {
                    throw new ApiException("缺失参数  vVcId", ERROR::PARAMS_LOST);
                }
            }
            $where["id"] = $data["id"];
            $laiseeConfig = LaiseeConfig::find($data['id']);
            if ($laiseeConfig) {
//                if ($laiseeConfig->status != 'Y') {
//                    throw new ApiException("已关闭或已结束的活动无法再编辑", ERROR::PARAMS_LOST);
//                }
                // 检测预警值 是否有修改
                if ($laiseeConfig->amount_warning != $data['amount_warning'] && $data['amount_warning'] > 0) {
                    $data['send_warning_sms'] = 'Y';
                } else {
                    $data['send_warning_sms'] = 'N';
                }
            } else {
                throw new ApiException("活动不存在", ERROR::PARAMS_LOST);
            }
        } else {
            $where = '';
        }
        $id = LaiseeConfig::doAdd($where, $data);
        if ($id) {
            return $this->success();
        } else {
            throw new ApiException("添加失败", ERROR::UNKNOWN_ERROR);
        }
    }

    /**
     * @api {post} /laisee/show/:id 5.活动概况
     * @apiName show
     * @apiGroup laisee
     *
     * @apiParam {Number} id 必填,活动ID.
     *
     * @apiSuccess {Number} id 红包活动id.
     * @apiSuccess {String} laisee_name 红包活动名称.
     * @apiSuccess {String} lc_remark 活动简介.
     * @apiSuccess {String} status 活动状态（N结束 Y进行中）.
     * @apiSuccess {Number} budget_amount 预算总金额.
     * @apiSuccess {Number} over_time 红包有效期.
     * @apiSuccess {Number} receiveNum 已领取数.
     * @apiSuccess {Number} receiveAmount 已领取金额.
     * @apiSuccess {Number} usedNum 已使用数.
     * @apiSuccess {Number} usedAmount 已使用总金额.
     * @apiSuccess {Number} consumeNum 已消费数.
     * @apiSuccess {Number} consumeAmount 已消费数金额.
     * @apiSuccess {Number} failure 已失效数.
     * @apiSuccess {array} voucherConf 现金券配置信息.
     * @apiSuccess {array} voucherGiftConf 礼包配置信息.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * {
     * "result": 1,
     * "token": "",
     * "data": {
     * "id": 13,
     * "laisee_name": "测试添加活动啊啊一",
     * "start_time": "0000-00-00 00:00:00",
     * "end_time": "0000-00-00 00:00:00",
     * "status": "N",
     *  "vcsns": "cm870649",
     * "gift_vcsn": "cm870373",
     *  "over_time": 2,
     *  "total_money": "600",
     *  "used_total_money": "0",
     *  "amount_warning": 0,
     *  "warning_phone": "",
     *  "share_icon": "dddd",
     *  "share_title": "分享",
     *  "share_desc": "收拾收拾",
     *  "bonus_bg_img": "sdffdss",
     *  "lc_remark": "测试添加活动啊啊订单",
     *  "sms_on_gained": "是的发生范德萨发 ",
     *  "create_time": "2015-10-21 17:14:30",
     *  "budget_amount": 2,
     *  "receiveNum": 0,
     *  "receiveAmount": 0,
     *  "usedNum": 0,
     *  "usedAmount": 0,
     *  "failure": 0,
     *  "consumeNum": 0,
     *   "consumeAmount": 0,
     *   "voucherConf": [
     *       {
     *           "vcSn": "cm870649",
     *           "vcId": 156,
     *           "useItemTypes": "1",
     *           "useTotalNum": 1,
     *           "useMoney": 1,
     *           "few_day": 1,
     *           "useNeedMoney": 1
     *         }
     *     ],
     *     "voucherGiftConf": [
     *          {
     *              "vcSn": "cm870373",
     *              "vcId": 157,
     *              "useItemTypes": "1",
     *              "useTotalNum": 1,
     *              "useMoney": 1,
     *              "few_day": 1,
     *              "useNeedMoney": 1
     *          }
     *      ]
     *  }
     * }
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function show($id) {
        $laisee = LaiseeConfig::find($id);
//        print_r($laisee);exit;
        if ($laisee) {
//            DB::enableQueryLog();
            $vcsns = '';
            foreach (explode(",", $laisee->vcsns) as $vcsn) {
                $vcsns.='"' . $vcsn . '"' . ",";
            }
            $voucherAmountRes = DB::select(DB::raw(" SELECT SUM(useTotalNum*useMoney) AS AGGREGATE FROM `cm_voucher_conf` WHERE `vcSn` IN (" . rtrim($vcsns, ',') . ")"));
            $voucherAmount = $voucherAmountRes[0]->AGGREGATE; //现金券总数
            if (!empty($laisee->gift_vcsn)) {
                $gift_vcsn = '';
                foreach (explode(",", $laisee->gift_vcsn) as $gift) {
                    $gift_vcsn .= '"' . $gift . '"' . ",";
                }
                $giftAmountRes = DB::select(DB::raw(" SELECT SUM(useTotalNum*useMoney) AS AGGREGATE FROM `cm_voucher_conf` WHERE `vcSn` IN (" . rtrim($gift_vcsn, ',') . ")"));
                $giftAmount = $giftAmountRes[0]->AGGREGATE;
            } else {
                $giftAmount = 0;
            }
            $laisee->budget_amount = $voucherAmount + $giftAmount;
            $laisee->over_time = $laisee->over_time / 86400;
            $laisee->receiveNum = Laisee::where('laisee_config_id', $laisee->id)->whereNotNull('mobilephone')->count();  //TODO  id=>$laisee->id 需调整
            $laisee->receiveAmount = Laisee::where('laisee_config_id', $laisee->id)->whereNotNull('mobilephone')->sum('value'); //TODO
//             print_r(DB::getQueryLog());
            $usedWhere = array_filter(array_unique(explode(",", $laisee->vcsns . "," . $laisee->gift_vcsn)));
            $laisee->usedNum = Voucher::whereIn('vcSn', $usedWhere)->where('vStatus', 2)->count();
            $laisee->usedAmount = Voucher::whereIn('vcSn', $usedWhere)->where('vStatus', 2)->sum('vUseMoney');
            $laisee->failure = Voucher::whereIn('vcSn', $usedWhere)->whereIn('vStatus', [4, 5])->count();  //已失效
            $voucherConsume = VoucherConf::getVoucherConfConsume($laisee);
            $laisee->consumeNum = $voucherConsume['consumeNum']; //TODO  已消费数
            $laisee->consumeAmount = $voucherConsume['consumeAmount']; //TODO  已消费数金额
            //返回现金券活动相关
            $laisee->voucherConf = VoucherConf::getVoucherConfByVcSns($laisee->vcsns);  // 现金券
            $laisee->voucherGiftConf = VoucherConf::getVoucherConfByVcSns($laisee->gift_vcsn); //礼包
//            print_r(DB::getQueryLog());
        } else {
            throw new ApiException("未找到相关信息", ERROR::UNKNOWN_ERROR);
        }
        return $this->success($laisee);
    }

    /**
     * @api {post} /laisee/online/:id 6.活动上线
     * @apiName online
     * @apiGroup laisee
     *
     * @apiParam {Number} id 必填,活动ID.
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
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function online($id) {
        $rec = LaiseeConfig::where('status', 'Y')->first();
        if (!empty($rec)) {
            throw new ApiException("已经有在线的红包活动", ERROR::UNKNOWN_ERROR);
        }
        $laiseConfig = LaiseeConfig::find($id);
        if (!($laiseConfig->end_time == "0000-00-00 00:00:00" || is_null($laiseConfig->end_time))) {
            throw new ApiException("已结束的活动无法再上线", ERROR::UNKNOWN_ERROR);
        }
        $res = LaiseeConfig::where('id', $id)->update(['status' => 'Y', 'start_time' => date("Y-m-d H:i:s", time())]);
        if ($res !== false) {
            return $this->success();
        } else {
            throw new ApiException("活动上线失败", ERROR::UNKNOWN_ERROR);
        }
    }

    /**
     * @api {post} /laisee/offline/:id 6.活动下线
     * @apiName offline
     * @apiGroup laisee
     *
     * @apiParam {Number} id 必填,活动ID.
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
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function offline($id) {
        $res = LaiseeConfig::where('id', $id)->update(['status' => 'N', 'end_time' => date("Y-m-d H:i:s")]);
        if ($res !== false) {
            Laisee::where('laisee_config_id', $id)->whereNull('vsn')->update(['status' => 'N']);  // 活动下线将未领取红包失效
            return $this->success();
        } else {
            throw new ApiException("活动下线失败", ERROR::UNKNOWN_ERROR);
        }
    }

    /**
     * @api {post} /laisee/close/:id 6.关闭活动
     * @apiName close
     * @apiGroup laisee
     *
     * @apiParam {Number} id 必填,活动ID.
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
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function close($id) {
        $laiseeConfig = LaiseeConfig::where('id', $id)->first();
        if ($laiseeConfig) {
            $res = LaiseeConfig::where('id', $id)->update(['status' => 'S', 'end_time' => date("Y-m-d H:i:s")]);
            if ($res !== false) {
                Laisee::where('laisee_config_id', $id)->update(['status' => 'N']);  // 活动关闭将红包失效
                //活动关闭  则所有 已经下发的代金券也失效
                $vcsns = $laiseeConfig->vcsns;
                if ($laiseeConfig->gift_vcsn) {
                    $vcsns.="," . $laiseeConfig->gift_vcsn;
                }
                Voucher::whereIn("vcSn", explode(",", $vcsns))->update(['vStatus' => 4]);
                return $this->success();
            } else {
                throw new ApiException("活动关闭失败", ERROR::UNKNOWN_ERROR);
            }
        } else {
            throw new ApiException("未找到活动信息", ERROR::UNKNOWN_ERROR);
        }
    }

    /**
     * @api {post} /laisee/itemTypes 7.现金券项目类型
     * @apiName itemTypes
     * @apiGroup laisee
     * 
     * @apiSuccess {Number} typeid 类型Id.
     * @apiSuccess {String} typename 类型名称.
     *
     * 
     * @apiSuccessExample Success-Response:
     *    {
     *   "result": 1,
     *   "token": "",
     *   "data": [
     *       {
     *           "typeid": 1,
     *           "typename": "洗剪吹"
     *       },
     *       {
     *           "typeid": 2,
     *            "typename": "烫发"
     *       },
     *        {
     *            "typeid": 3,
     *           "typename": "染发"
     *        },
     *        {
     *            "typeid": 4,
     *            "typename": "护发"
     *        },
     *        {
     *            "typeid": 5,
     *            "typename": "套餐"
     *        },
     *        {
     *            "typeid": 6,
     *            "typename": "兑换专用"
     *        },
     *        {
     *            "typeid": 7,
     *            "typename": "洗吹"
     *        },
     *        {
     *            "typeid": 8,
     *            "typename": "男士快剪"
     *        },
     *        {
     *            "typeid": 10,
     *            "typename": "其他"
     *        },
     *        {
     *            "typeid": 11,
     *            "typename": "养发"
     *        },
     *        {
     *            "typeid": 12,
     *            "typename": "特价套餐"
     *        },
     *        {
     *            "typeid": 13,
     *            "typename": "发水"
     *        }
     *    ]
     * }
     * 
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未授权访问"
     * 		}
     */
    public function itemTypes() {
        $itemTypes = \App\SalonItemType::select(['typeid', 'typename'])->where('status', 1)->where('typename', '!=', '其他')->get();
        $res = [];
        foreach ($itemTypes as $val) {
            $typeidStr[] = $val->typeid;
            $res[] = [
                'typeid' => (string) $val->typeid,
                'typename' => $val->typename,
            ];
        }
        $res[] = [
            'typeid' => 0,
            'typename' => "无限制券",
        ];
        return $this->success($res);
    }

}
