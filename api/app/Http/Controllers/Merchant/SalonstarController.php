<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\SalonStarConf;
use App\Salon;
use Illuminate\Support\Facades\DB;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class SalonstarController extends Controller {

    /**
     * @api {post} /salonstar/index 1.店铺星级积分管理列表
     * @apiName index
     * @apiGroup salonstar
     *
     * @apiParam {String} salonname 可选,店铺名称.
     * @apiParam {Number} level 所属星级,全部时为0
     * @apiParam {Number} page 可选,页数.
     * @apiParam {Number} page_size 可选,分页大小.
     *
     * 
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {String} salonname 店铺名称.
     * @apiSuccess {Number} score 店铺累计积分.
     * @apiSuccess {Number} salonid 操作类型.
     * @apiSuccess {Number} level 所属星级.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 
     * 	{
     * 	    "result": 1,
     * 	    "data": {
     * 	        "total": 5,
     * 	        "per_page": 20,
     * 	        "current_page": 1,
     * 	        "last_page": 1,
     * 	        "from": 1,
     * 	        "to": 5,
     * 	        "data": [
     * 	            {
     * 	                "salonname": "嘉美专业烫染",
     * 	                "score": 520,
     * 	                "salonid": 1,
     * 	                "level": 1,
     * 	            }
     * 	        ]
     * 	    }
     * 	}
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "未找到相应的等级level"
     * 		}
     */
    public function index() {
        $options = $this->parameters([
            'salonname' => self::T_STRING,
            'level' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
        ]);
        $salonname = (isset($options['salonname'])) ? $options['salonname'] : '';
        $level = !empty($options['level']) ? $options['level'] : 0;
        $page = isset($options['page']) ? max(intval($options['page']), 1) : 1;
        $size = isset($options['page_size']) ? max(intval($options['page_size']), 1) : 20;
        if ($level) {
            $score = SalonStarConf::getPrevAndNextScore4Level($level);
            if (!$score) {
                throw new ApiException("未找到相应的等级level", ERROR::STAR_CONF_LEVEL_IS_ERROR);
            }
        } else {
            $score = 0;
        }
        $salonList = SalonStarConf::getSalonList($salonname, $score, $page, $size);
        if (!empty($salonList)) {
            foreach ($salonList['data'] as $key => $val) {
                if ($level) {
                    $salonList['data'][$key]['level'] = $level;
                } else {
                    $salonList['data'][$key]['level'] = SalonStarConf::getLevelByScore($val['score']);
                }
            }
        }
        return $this->success($salonList);
    }

    /**
     * @api {post} /salonstar/update 2.增加/减少积分
     * @apiName update
     * @apiGroup salonstar
     *
     * @apiParam {Number} salonid 店铺ID.
     * @apiParam {Number} type 1 增加 2减少(只能为1或2).
     * @apiParam {Number} score 积分数
     * @apiParam {String} msg 可选，原因说明
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
     * 		    "msg": "未找到该店铺信息"
     * 		}
     */
    public function update() {
        $options = $this->parameters([
            'salonid' => self::T_INT,
            'type' => self::T_INT, //1增加 2减少
            'score' => self::T_INT,
            'msg' => self::T_STRING,
        ]);
        if (empty($options['salonid'])) {
            throw new ApiException("参数传递错误，salonid", ERROR::PARAMS_LOST);
        }
        if (!in_array($options['type'], [1, 2])) {
            throw new ApiException("参数传递错误，type", ERROR::PARAMS_LOST);
        }
        if ($options['score'] < 0) {
            throw new ApiException("参数传递错误,score", ERROR::PARAMS_LOST);
        }
        $salonInfo = Salon::find($options['salonid']);
        if (is_null($salonInfo)) {
            throw new ApiException("未找到该店铺信息", ERROR::STAR_CONF_SALON_INFO_IS_ERROR);
        }
        if (($options['type'] == 2) && ($options['score'] > $salonInfo->score)) {
            throw new ApiException("参数传递错误,score", ERROR::PARAMS_LOST);
        }
        if ($options['type'] == 1) {
            $score = $salonInfo->score + $options['score'];
        } else {
            $score = $salonInfo->score - $options['score'];
        }
        //更新店铺score
        if (!empty($this->user->name)) {
            $userName = $this->user->name;
        } else {
            $userName = '';
        }
//        $userName = "liyu";
        $upStatus = Salon::updateScore($salonInfo, $score, $options, $userName);
        if ($upStatus) {
            return $this->success();
        } else {
            throw new ApiException("修改店铺积分失败", ERROR::STAR_CONF_UPDATE_SALON_SCORE_FAILED);
        }
    }

    /**
     * @api {post} /salonstar/show 3.积分详情
     * @apiName show
     * @apiGroup salonstar
     *
     * @apiParam {Number} salonid 店铺ID.
     * @apiParam {String} start_time 可选，积分变化开始时间.
     * @apiParam {String} end_time 可选，积分变化结束时间.
     * @apiParam {Number} page 可选,页数.
     * @apiParam {Number} page_size 可选,分页大小.
     *
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} score 积分变化.
     * @apiSuccess {String} description 积分变化原因.
     * @apiSuccess {Number} create_time 积分变化时间.
     * @apiSuccess {Number} totalScore 累计积分.
     * 
     * @apiSuccessExample Success-Response:
     * 
     * 	{
     * 	    "result": 1,
     * 	    "data": {
     * 	        "total": 5,
     * 	        "per_page": 20,
     * 	        "current_page": 1,
     * 	        "last_page": 1,
     * 	        "from": 1,
     * 	        "to": 5,
     * 	        "data": [
     * 	            {
     * 	                "score": -3,
     * 	                "description": "测试减少1",
     * 	                "create_time": "2015-10-15 18:38:14",
     * 	                "totalScore":12,
     * 	            }
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
    public function show() {
        $options = $this->parameters([
            'start_time' => self::T_STRING,
            'end_time' => self::T_STRING, //1增加 2减少
            'salonid' => self::T_INT,
            'page' => self::T_INT,
            'page_size' => self::T_INT,
        ]);
        $startTime = isset($options['start_time']) ? $options['start_time'] : 0;
        $endTime = isset($options['end_time']) ? $options['end_time'] : 0;
        $page = isset($options['page']) ? max(intval($options['page']), 1) : 1;
        $size = isset($options['page_size']) ? max(intval($options['page_size']), 1) : 20;
        $logList = \App\SalonScoreLog::getLogList($options, $startTime, $endTime, $page, $size);
        return $this->success($logList);
    }

}
