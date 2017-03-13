<?php namespace App\Http\Controllers;

use App\Feed;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class FeedController extends Controller{
	/**
	 * @api {post} /feed/index 1.反馈列表
	 * @apiName index
	 * @apiGroup Feed
	 *
	 * @apiParam {String} date 反馈日期;
	 * @apiParam {String} keyword 反馈关键词;
	 * @apiParam {String} area 可选,区域,省市区用英文逗号,分隔;
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 * @apiParam {String} sort_key 排序的键,比如:start_at,end_at;
	 * @apiParam {String} sort_type 排序方式,DESC或者ASC;默认DESC
	 *
	 * @apiSuccess {Number} id 反馈ID.
	 * @apiSuccess {String} contact 联系方式.
	 * @apiSuccess {String} content 反馈内容.
	 * @apiSuccess {String} source 来源.
	 * @apiSuccess {String} add_time 反馈时间.
	 *
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "token": "",
	 *	    "data": {
	 *	        "total": 1023,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 52,
	 *	        "from": 1,
	 *	        "to": 20,
	 *	        "data": [
	 *	            {
	 *	                "id": 1066,
	 *	                "contact": "13512345678",
	 *	                "content": "763750",
	 *	                "source": "android",
	 *	                "add_time": "2015-08-17 10:54:20"
	 *	            }
	 *	        ]
	 *	    }
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
		$param = $this->param;
		$query = Feed::getQueryByParam($param);

		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;

		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});

		$fields = array(
			'feed_id as id',
			'contact',
			'content',
			'source',
			'add_time'
		);

		//分页
	    $result = $query->select($fields)->paginate($page_size)->toArray();
	    foreach ($result['data'] as $key=>$feed) {
	    	$feed['source'] = feed::getSource($feed['source']);
	    	$feed['add_time'] = date('Y-m-d H:i:s',intval($feed['add_time']));
	    	$result['data'][$key] = $feed;
	    }
	    unset($result['next_page_url']);
	    unset($result['prev_page_url']);
	    return $this->success($result);
	}


	/**
	 * @api {post} /feed/destroy 2.删除反馈信息
	 * @apiName destroy
	 * @apiGroup Feed
	 *
	 * @apiParam {Array} feed 反馈ID.
	 */
	public function destroy()
	{
		$param = $this->param;
		if(empty($param['feed']))
			throw new ApiException('请指定要删除的内容', ERROR::FEED_EMPTY);
		$result = Feed::whereIn('feed_id',$param['feed'])->delete();
		if($result)
			return $this->success();
		throw new ApiException('删除失败', ERROR::FEED_DELETE_FAILED);
	}


}