<?php namespace App\Http\Controllers;

use App\Level;
use DB;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class LevelController extends Controller{
	/**
	 * @api {post} /level/index 1.等级列表
	 * @apiName index
	 * @apiGroup Level
	 *
	 * @apiSuccess {Number} id 等级ID.
	 * @apiSuccess {Number} level 等级.
	 * @apiSuccess {Number} growth 对应成长值.
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": [
	 *	        {
	 *	            "id": 4,
	 *	            "level": 0,
	 *	            "growth": 1
	 *	        }
	 *	    ]
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
	public function index()
	{
		$result = Level::select('id','level','growth')->orderBy('level')->get();
		return $this->success($result);
	}

	/**
	 * @api {post} /level/index 2.更新等级
	 * @apiName update
	 * @apiGroup Level
	 *
	 * @apiParam {Array} level 等级数组,每个元素必须包括等级ID,然后附加更新的字段与值.
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": null
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "没有符合条件数据"
	 *		}
	 */
	public function update()
	{
		$param = $this->param;
		$num = count($param['level']);
		if($num<1)
			throw new ApiException('等级设置为空', ERROR::LEVEL_EMPTY);
		DB::beginTransaction();
		foreach ($param['level'] as $key => $value) {
			$level = Level::find($value['id']);
			if(!$level)
				throw new ApiException('未知等级', ERROR::LEVEL_NOT_FOUND);
			$result = $level->update($value);
			if($result)
				$num--;
		}
		if($num==0){
			DB::commit();
			return $this->success();
		}
		throw new ApiException('等级更新失败', ERROR::LEVEL_UPDATE_FAILED);
	}


}