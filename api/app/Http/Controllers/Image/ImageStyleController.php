<?php namespace App\Http\Controllers\Image;

use App\Http\Controllers\Controller;
use App\ImageStyle;
use Log;
use Config;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

/**
 *
 * @author zhengjiangang
 */
class ImageStyleController extends Controller{
    /**
	 * @api {post} /ImageStyle/index 1.图片列表
	 * 
	 * @apiName index
	 * @apiGroup Image
	 *
	 * @apiParam {Number} style 可选,风格.
	 * @apiParam {Number} length 可选,长度.
	 * @apiParam {Number} curl 可选,卷度.
	 * @apiParam {Number} color 可选,颜色.
	 * @apiParam {Number} page 可选,页数.
	 * @apiParam {Number} page_size 可选,分页大小.
	 * 
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} id  主键.
	 * @apiSuccess {Number} style 风格.
	 * @apiSuccess {Number} length 长度.
	 * @apiSuccess {Number} curl 卷度.
	 * @apiSuccess {Number} color 颜色.
	 * @apiSuccess {String} img 图片路径
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 * 
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "total": 5,
	 *	        "per_page": 20,
	 *	        "current_page": 1,
	 *	        "last_page": 1,
	 *	        "from": 1,
	 *	        "to": 5,
	 *	        "data": [
	 *	            { 
	 *                      "id":3,
	 *                      "style":1,
	 *                      "length":1,
	 *                      "curl":1,
	 *                      "color":1,
	 *                      "img":"{}"
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
           $query =ImageStyle::getAllImage($param);
           foreach($query['data'] as &$item)
           {
               $img = json_decode($item->img);
               if(json_last_error() || empty($img))
                   continue;
               $item->img = $img->thumb;
               $item->thumb = $img->thumb;
               $item->original = $img->original;
               unset($item->img);
           }
           return $this->success($query);
     }
     
     
    /**
	 * @api {post} /ImageStyle/create 2.添加风格
	 * @apiName create
	 * @apiGroup Image
	 * @apiParam {Number} style 必填,风格.
	 * @apiParam {Number} length 必填,长度.
	 * @apiParam {Number} curl 必填,卷度.
	 * @apiParam {Number} color 必填,颜色.
	 * @apiParam {String} original 必填,原图路径.
     * @apiParam {String} thumb 必填,缩略图路径.
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "msg": "",
	 *	    "data": {
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "图片风格插入失败"
	 *		}
	 */
    public function create()
    {
            
          $param = $this->param; 
         // Log::info('ImageStyle create param is: ', $param);
          if(empty($param['style']) || empty($param['length']) || empty($param['curl']) || empty($param['color']) || empty($param['original']) || empty($param['thumb']))
          {
             throw new ApiException('参数不齐', ERROR::PARAMETER_ERROR);
          }
          $data=[];
          $data['style']=$param['style'];
          $data['length']=$param['length'];
          $data['curl']=$param['curl'];
          $data['color']=$param['color'];
          $img = array('original' => $param['original'], 'thumb' => $param['thumb']);
          $data['img']= json_encode($img);
          $data['status']=1;
          $result=ImageStyle::create($data);
          if($result){
			return $this->success();
          }else{
			throw new ApiException('图片风格插入失败', ERROR::STYLE_CREATE_FAILED);
          }
     }


    /**
	 * @api {post} /ImageStyle/destroy/:id 3.停用风格
	 * @apiName destroy
	 * @apiGroup  Image
	 *
	 * @apiParam {Number} id 必填,主键.
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "msg": "",
	 *	    "data": {
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "图片风格删除失败"
	 *		}
	 */
    public function destroy($id)
    {  
		$image = ImageStyle::find($id);
		if(!$image){
			throw new ApiException('未知图片', ERROR::STYLE_NOT_FOUND);
                }
                $data=[];
		$data['status']=2;
                $result = $image->update($data);
		if($result){
			return $this->success();
                }else {
                  throw new ApiException('图片删除失败', ERROR::STYLE_DELETE_FAILED); 
                }
		
     }
     
    /**
	 * @api {post} /ImageStyle/update/:id 4.更新风格
	 * @apiName update
	 * @apiGroup  Image
	 *
	 * @apiParam {Number} id 必填,主键.
	 * @apiParam {Number} style 必填,风格.
	 * @apiParam {Number} length 必填,长度.
	 * @apiParam {Number} curl 必填,卷度.
	 * @apiParam {Number} color 必填,颜色. 
     * @apiParam {String} img 必填,图片路径.
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "msg": "",
	 *	    "data": {
	 *	    }
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "图片风格更新失败"
	 *		}
	 */
      
    public function update($id)
    { 
        $param = $this->param; 
		
        if(empty($id) || empty($param['style']) || empty($param['length']) || empty($param['curl']) || empty($param['color']) || empty($param['original']) || empty($param['thumb']))
        {
           throw new ApiException('参数不齐', ERROR::PARAMETER_ERROR);
        }
        $fields = ['id', 'style', 'length','curl','color','img','status'];
        $image=ImageStyle::select($fields)->find($id);
    	if(!$image && $image['status']!=1){
            throw new ApiException('未知图片', ERROR::STYLE_NOT_FOUND);
        }
        $data=[];
        $data['style']=$param['style'];
        $data['length']=$param['length'];
        $data['curl']=$param['curl'];
        $data['color']=$param['color'];
        $img = array('original' => $param['original'], 'thumb' => $param['thumb']);
        $data['img']=  json_encode($img); 
        $result=$image->update($data);
         if($result){
              return $this->success();
        }else{
              throw new ApiException('图片风格更新失败', ERROR::STYLE_UPDATE_FAILED);
        }
     }
     
    /**
	 * @api {post} /ImageStyle/show/:id 5.查找一张图片
	 * @apiName show
	 * @apiGroup Image
	 *
	 * @apiParam {Number} ID 必填，主键.
	 *
     * @apiSuccess {Number} id  主键.
	 * @apiSuccess {Number} style 风格.
	 * @apiSuccess {Number} length 长度.
	 * @apiSuccess {Number} curl 卷度.
	 * @apiSuccess {Number} color 颜色.
	 * @apiSuccess {String} img 图片路径
     * 
     * @apiSuccessExample Success-Response:
     *   {   
     *      "result":1,
     *      "token":"",
     *      "data":{
     *                     "id":2,
     *                     "style":1,
     *                     "length":1,
     *                     "curl":1,
     *                     "color":1,
     *                     "img":"1"
     *              }
     *    }
     */
    public function show($id)
    {
    	 $fields = ['id', 'style', 'length','curl','color','img'];  
         $image=ImageStyle::select($fields)->find($id);
         $imgData = json_decode($image['img'], true);
         $image['img'] = $imgData['original'];
         $image->original = $imgData['original'];
         $image->thumb = $imgData['thumb'];
         return $this->success($image);
     }
       
       
}
