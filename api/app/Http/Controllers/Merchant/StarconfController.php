<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\SalonStarConf;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use Illuminate\Support\Facades\DB;

class StarconfController extends Controller {

    /**
     * @api {post} /starconf/index 1.星级对应积分配置列表
     * @apiName index
     * @apiGroup starconf
     *
     * 
     * @apiSuccess {String} id 记录ID.
     * @apiSuccess {Number} level 等级.
     * @apiSuccess {Number} score 起始积分.
     * @apiSuccess {Number} add_time 添加时间.
     * @apiSuccess {Number} update_time 更新时间.
     * @apiSuccess {Number} salonCount 店铺数量.
     * @apiSuccess {Number} status 星级店铺开启（Y）/关闭（N）.
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 
     * 	{
     * 	    "result": 1,
     * 	    "data": {
     * 	        "data": [
     * 	            {
     * 	                "id": 1,
     * 	                "level": 1,
     * 	                "score": 0,
     * 	                "add_time": 0,
     * 	                "update_time": 0,
     * 	                "salonCount": 992,
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
    public function index() {
        $levelList = SalonStarConf::getSalonLevelList();
        return $this->success($levelList);
    }

    /**
     * @api {post} /starconf/update 2.更新起始积分分值
     * @apiName update
     * @apiGroup starconf
     *
     * @apiParam {Number} id 记录ID.
     * @apiParam {Number} score 分值.
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
     * 		    "msg": "更新星级积分失败"
     * 		}
     */
    public function update() {
        $param = $this->param;
        $id = isset($param['id']) ? $param['id'] : 0;
        $score = isset($param['score']) ? $param['score'] : 0;
        if (!$id) {
            throw new ApiException("缺失参数id", ERROR::PARAMS_LOST);
        }
        if ($score < 0) {
            throw new ApiException("请输入有效积分数值", ERROR::STAR_CONF_SCORE_IS_ERROR);
        }
        $level = SalonStarConf::find($id);
        if ($level) {
            $updateRes = SalonStarConf::updateConf($param);
            if ($updateRes) {
                if ($updateRes == 1) {
                    throw new ApiException("请输入有效积分数值", ERROR::STAR_CONF_SCORE_IS_ERROR);
                } elseif ($updateRes == 2) {
                    throw new ApiException("更新星级积分失败", ERROR::STAR_CONF_UPDATE_IS_ERROR);
                }
            } else {
                return $this->success();
            }
        } else {
            throw new ApiException("未找到相应的星级积分等级", ERROR::STAR_CONF_LEVEL_IS_ERROR);
        }
    }

    /**
     * @api {post} /starconf/online 3.开始/暂停  店铺星级
     * @apiName online
     * @apiGroup starconf
     *
     * @apiParam {String} status 开启（Y）/暂停(N).
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
     * 		    "msg": "操作失败"
     * 		}
     */
    public function online() {
        $options = $this->parameters([
            'status' => self::T_STRING,
        ]);
        if (!in_array($options['status'],['Y', 'N'])) {
            throw new ApiException("操作失败", ERROR::STAR_CONF_SCORE_IS_ERROR);
        }
        $res = DB::table('business_switch')->where(['business_name' => 'salon_star'])->update(['status' => $options['status'], 'update_time' => date('Y-m-d H:i:s')]);
        if ($res !== false) {
            return $this->success();
        } else {
            throw new ApiException("操作失败", ERROR::STAR_CONF_SCORE_IS_ERROR);
        }
    }

}
