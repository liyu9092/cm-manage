<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use App\ScoreConf;

class ScoreconfController extends Controller {

    /**
     * @api {post} /scoreconf/index 1.评分对应分值配置列表
     * @apiName index
     * @apiGroup scoreconf
     *
     * 
     * @apiSuccess {Number} id id标示.
     * @apiSuccess {String} description 合法情况描述.
     * @apiSuccess {Number} very_satisfied 很满意分数.
     * @apiSuccess {Number} satisfied 满意分数.
     * @apiSuccess {Number} unsatisfied 不满意分数.
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
     * 	                "description": "不带文字不带图片",
     * 	                "verySatisfy": 1,
     * 	                "satisfy": 0,
     * 	                "unsatisfy": 0,
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
        $scoreConf = ScoreConf::all();
        return $this->success($scoreConf);
    }

    /**
     * @api {post} /scoreconf/update 2.更新评分配置分值
     * @apiName update
     * @apiGroup scoreconf
     *
     * @apiParam {Number} id 记录ID.
     * @apiParam {Number} verySatisfy 很满意分值.
     * @apiParam {Number} satisfy 满意分值
     * @apiParam {String} unsatisfy 不满意分值
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
     * 		    "msg": "更新评分分值配置失败"
     * 		}
     */
    public function update() {
        $options = $this->parameters([
            'id' => self::T_INT,
            'verySatisfy' => self::T_INT,
            'satisfy' => self::T_INT,
            'unsatisfy' => self::T_INT,
        ]);
        $scoreConf = ScoreConf::find($options['id']);
        if ($scoreConf) {
            $upStatus = ScoreConf::where(['id' => $options['id']])->update([
                'very_satisfied' => $options['verySatisfy'],
                'satisfied' => $options['satisfy'],
                'unsatisfied' => '-' . $options['unsatisfy'],
            ]);
            if ($upStatus !== false) {
                return $this->success();
            } else {
                throw new ApiException("更新评分分值配置失败,id", ERROR::SCORE_CONF_UPDATE_FAILED);
            }
        } else {
            throw new ApiException("未找到该类型,id", ERROR::SCORE_CONF_FAILED);
        }
    }

}
