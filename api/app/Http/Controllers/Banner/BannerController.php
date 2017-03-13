<?php

namespace App\Http\Controllers\Banner;

use App\Http\Controllers\Controller;
use Illuminate\Pagination\AbstractPaginator;
use App\Banner;
use App\Exceptions\ERROR;
use App\Exceptions\ApiException;
use Log;
use Event;

class BannerController extends Controller {

    /**
     * @api {post} /banner/index 1.banner列表  
     * @apiName index
     * @apiGroup  Banner
     *
     * @apiParam {Number} type 必填,''banner类型  ‘1’‘ 代表主页banner；’2‘ 代表定妆banner；’3‘ 代表迁体banner；’4‘ 代表水光针banner； 
     * 
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * @apiSuccess {Number} banner_id  主键.    
     * @apiSuccess {Number} type  ''banner类型  ‘1’‘ 代表主页banner；’2‘ 代表定妆banner；’3‘ 代表迁体banner；’4‘ 代表水光针banner；  
     * @apiSuccess {String} name 'banner名称',(即项目名称)
     * @apiSuccess {String} image bnnaer图片.
     * @apiSuccess {String} price 特价.
     * @apiSuccess {String} bargainPrice 酬宾价.
     * @apiSuccess {String} priceOri 市场价.
     * @apiSuccess {String} introduce 描述和介绍.
     * @apiSuccess {String} salonName  salon店的名称
     * @apiSuccess {Number} behavior  链接到哪里  0无跳转;1H5； 2app内部',
     * @apiSuccess {Json}    url  'banner链接地址',  (behavior为’1‘或‘3’ 类型为String ,behavior为'2'类型为json {"type":"SPM","itemId":1}且type只有四种类型：(SPM - 半永久,FFA - 快时尚'),itemId:主键  ,(salon-美发店铺主页)salonId:主键  ,（artificers-专家主页)artificerId：主键
     * 
     * 
     * @apiSuccessExample Success-Response:
     * {
     *       "result": 1,
     *       "token": "",
     *       "data":
     *              {
     *                   "total": 48,
     *                   "per_page": 20,
     *                   "current_page": 1,
     *                   "last_page": 3,
     *                   "from": 1,
     *                   "to": 20,
     *                   "data":[
     *                         price:111,
     *                         bargainPrice:1111,
     *                         priceOri:11111,
     *                         introduce:wwwwwwwwww,
     *                         list：[{
     *                                   "banner_id": 1,
     *                                   "type": 1,
     *                                   "name": "无痛水光针",
     *                                   "image": "http://img01.choumei.cn/1/7/2015102714041445925884600748229.jpg",
     *                                   "behavior": 1,
     *                                   "url": "http://img01.choumei.cn/1/7/2015102714041445925884600748229.jpg",
     *                                   "created_at": 1448871460,
     *                                   "updated_at": 1448871460
     *                               },
     *                               {
     *                                   "banner_id": 60,
     *                                   "type": 1,
     *                                   "name": "无痛水光针",
     *                                   "image": "http://img01.choumei.cn/1/7/2015102714041445925884600748229.jpg",
     *                                   "behavior": 2,
     *                                   "url": {"type":"SPM","itemId":1},
     *                                   "created_at": 1448871460,
     *                                   "updated_at": 1448871460
     *                               },
     *                               .......
     *                             ]
     *                          ]
     *              }
     * }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     * 		    "result": 0,
     * 		    "msg": "查询banner列表失败"
     * 		}
     */
    public function index() {
        $param = $this->param;
        if (empty($param['type'])) {
            throw new ApiException('参数不齐', ERROR::BEAUTY_ITEM_ERROR);
        }
        $page = isset($param['page']) ? max($param['page'], 1) : 1;
        $page_size = isset($param['page_size']) ? $param['page_size'] : 20;
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        $query = Banner::where('type', '=', $param['type'])->orderBy('sort', 'asc')->orderBy('created_at', 'asc')->paginate($page_size)->toArray();
        unset($query['next_page_url']);
        unset($query['prev_page_url']);
        foreach ($query['data'] as $key =>$value) {
            if(isset($value['url'])){
                $temp = json_decode($value['url'],true);
                if(isset($temp['salonId'])){
                    $query['data'][$key]['salonId'] = $temp['salonId'];
                }
                if(isset($temp['type'])){
                    switch($temp['type']){
                        case "booking" : 
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"1"}'; break;
                        case "beauty-center":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"2"}'; break;
                        case "book-pop-artificer":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"3"}'; break;
                        case "book-artificer":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"4"}'; break;
                        case "artificers":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"5"}'; break;
                        case "salon-appraise":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"6"}'; break;
                        case "salon-nearby":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"7"}'; break;
                        case "salon-grade":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"8"}'; break;
                        case "appoint-stylist":
                            $query['data'][$key]['url']='{"type":"artificers","itemId":"9"}'; break;
                    }
                }    
            }        
        }
        $result=[];
        if(!empty($query['data'])){
            $result['price'] =$value['price'];
            $result['bargainPrice'] =$value['bargainPrice'];
            $result['priceOri'] =$value['priceOri'];
            $result['introduce'] =$value['introduce'];  
            $result['list']=$query['data'];
        }
        if ($query) {
            return $this->success($result);
        } else {
            throw new ApiException('查询banner失败', ERROR::BEAUTY_BANNER_SELECT_ERROR);
        }
    }

    /**
     * @api {post} /banner/create 2.banner的添加
     * @apiName create
     * @apiGroup  Banner
     *
     * @apiParam {Number} type 必填,  ''banner类型  ‘1’‘ 代表主页banner；’2‘ 代表定妆banner；’3‘ 代表迁体banner；’4‘ 代表水光针banner；  
     * @apiParam {String} name 必填,题目.
     * @apiParam {String} image 必填,bnnaer图片的路径.
     * @apiParam {String} salonName 可选, salon店的名称
     * @apiParam {Number} behavior 必填, 链接到哪里  0无跳转; 1H5； 2app内部',
     * @apiParam {Json}    url  'banner链接地址',  (behavior为’1‘或‘3’ 类型为String ,behavior为'2'类型为json {"type":"SPM","itemId":1}且type只有四种类型：SPM - 半永久,FFA - 快时尚',salons-美发店铺主页,artificers-专家主页,同上 )
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
     * 		    "msg": "创建banner失败"
     * 		}
     */
    public function create() {
        $param = $this->param;
        if (empty($param['type']) || !isset($param['name']) || !isset($param['image']) || !isset($param['behavior'])) {
            throw new ApiException('参数不齐', ERROR::BEAUTY_ITEM_ERROR);
        }
        if ($param['behavior'] == 1 || $param['behavior'] == 2) {
            if (empty($param['url'])) {
                throw new ApiException('参数不齐', ERROR::BEAUTY_ITEM_ERROR);
            }
        }
        $field = ['price','priceOri','introduce'];
        $banner = Banner::select($field)->where('type', '=', $param['type'])->first();
        if($banner == true){
            $date['price']=$banner['price'];
            $date['priceOri']=$banner['priceOri'];
            $date['introduce']=$banner['introduce'];
        }
        $date['type']=$param['type'];
        $date['sort']=10;
        $date['name']=$param['name'];
        $date['image']=$param['image'];
        $date['behavior']=$param['behavior'];
        if(!empty($param['salonId'])){
            $temp=json_decode($param['url']);
            $temp->salonId=$param['salonId'];
            $param['url']=  json_encode($temp);
        }
        if (!empty($param['url'])) {
            $temp2 = json_decode($param['url'],true);
            if(isset($temp2['type'])&&$temp2['type'] == "artificers"){
                switch($temp2['itemId']){
                    case 1 : 
                        $date['url']='{"type":"booking"}'; break;
                    case 2:
                        $date['url']='{"type":"beauty-center"}'; break;
                    case 3:
                        $date['url']='{"type":"book-pop-artificer"}'; break;
                    case 4:
                        $date['url']='{"type":"book-artificer"}'; break;
                    case 5:
                        $date['url']='{"type":"artificers"}'; break;
                    case 6:
                        $date['url']='{"type":"salon-appraise"}'; break;
                    case 7:
                        $date['url']='{"type":"salon-nearby"}'; break;
                    case 8:
                        $date['url']='{"type":"salon-grade"}'; break;
                    case 9:
                        $date['url']='{"type":"appoint-stylist"}'; break;
                }        
            } else {
                $date['url']=$param['url'];
            }
  
        }
        if (!empty($param['salonName'])) {
            $date['salonName']=$param['salonName'];
        }
        $date['created_at'] = time();
        $date['updated_at'] = time();
        $id = Banner::insertGetId($date);
        if ($id) {
            Event::fire('banner.create','主键:'.$id);
            return $this->success();
        } else {
            throw new ApiException('创建主页banner失败', ERROR::BEAUTY_BANNER_CREATE_ERROR);
        }
    }
    


    /**
     * @api {post} /banner/edit/:id 3.banner的修改 
     * @apiName edit
     * @apiGroup  Banner
     *
     * @apiParam {Number} id 必填,主键.
     * @apiParam {String} name 必填,题目.
     * @apiParam {String} salonName 可选, salon店的名称
     * @apiParam {String} image 必填,bnnaer图片的路径.
     * @apiParam {Number} behavior 必填, 链接到哪里  0无跳转; 1H5； 2app内部',(单选按钮),
     * @apiParam {Json}    url  'banner链接地址',  (behavior为’1‘或‘3’ 类型为String ,behavior为'2'类型为json {"type":"SPM","itemId":1}且type只有四种类型：SPM - 半永久,FFA - 快时尚',salons-美发店铺主页,artificers-专家主页,同上 )
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
     * 		    "msg": "修改banner失败"
     * 		}
     */
    public function edit($id) {
        $param = $this->param;
        $banner = Banner::find($id);
        if ($banner == FALSE) {
            throw new ApiException('找不到这样的banner，id有误', ERROR::BEAUTY_BANNER_NOT_ID);
        }       
        if (!isset($param['name']) || !isset($param['image']) || !isset($param['behavior'])) {
            throw new ApiException('参数不齐', ERROR::BEAUTY_ITEM_ERROR);
        }          
        if ($param['behavior'] == 1 || $param['behavior'] == 2) {
            if (empty($param['url'])) {
                throw new ApiException('参数不齐', ERROR::BEAUTY_ITEM_ERROR);
            }
        }
        if(!empty($param['salonId'])){
            $temp=json_decode($param['url']);
            $temp->salonId=$param['salonId'];
            $param['url']=  json_encode($temp);
        }
        if(!array_key_exists('salonName',$param)){
            $param['salonName']="";
        }
        $data['type']=$param['type'];
        $data['image']=$param['image'];
        $data['salonName']=$param['salonName'];
        $data['name']=$param['name'];
        if (isset($param['behavior'])) {
            $data['behavior']=$param['behavior'];
            if ($param['behavior'] == 0) {
                $data['url'] = "";
            }
        }
        $data['updated_at']=time();        
       if (!empty($param['url'])) {
            $temp2 = json_decode($param['url'], true);
            if(isset($temp2['type'])&&$temp2['type'] == "artificers"){
                  switch($temp2['itemId']){
                    case 1 : 
                        $data['url']='{"type":"booking"}'; break;
                    case 2:
                        $data['url']='{"type":"beauty-center"}'; break;
                    case 3:
                        $data['url']='{"type":"book-pop-artificer"}'; break;
                    case 4:
                        $data['url']='{"type":"book-artificer"}'; break;
                    case 5:
                        $data['url']='{"type":"artificers"}'; break;
                    case 6:
                        $data['url']='{"type":"salon-appraise"}'; break;
                    case 7:
                        $data['url']='{"type":"salon-nearby"}'; break;
                    case 8:
                        $data['url']='{"type":"salon-grade"}'; break;
                    case 9:
                        $data['url']='{"type":"appoint-stylist"}'; break;
                }        
            }  else {
                $data['url']=$param['url'];
            }
        }
        $query = Banner::where('banner_id',$id)->update($data);
        if ($query) {
            Log::info("param is ",$data);
          //  Event::fire('banner.edit','主键:'.$id);
            return $this->success();
        } else {
            throw new ApiException('修改banner失败', ERROR::BEAUTY_BANNER_UPDATE_ERROR);
        }
    }

    /**
     * @api {post} /banner/destroy/:id 4.banner的删除
     * @apiName destroy
     * @apiGroup  Banner
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
     * 		    "msg": "删除banner失败"
     * 		}
     */
    public function destroy($id) {
        $banner = Banner::find($id);
        if ($banner == FALSE) {
            throw new ApiException('找不到这样的banner，id有误', ERROR::BEAUTY_BANNER_NOT_ID);
        }
        $query = Banner::destroy($id);
        if ($query) {
            Event::fire('banner.destroy','主键:'.$id);
            return $this->success();
        } else {
            throw new ApiException('删除banner失败', ERROR::BEAUTY_BANNER_DELETE_ERROR);
        }
    }

    /**
     * @api {post} /banner/sort 5.banner的排序
     * @apiName sort
     * @apiGroup  Banner
     *
     * @apiParam {Json} sort 必填,顺序  [{"id":11,"sort":1},{"id":5,"sort":2}...].  排序,是sort的升序(id 是表的主键)
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
     * 		    "msg": "修改失败"
     * 		}
     */
    public function sort() {
        $param = $this->param;
        $date[] = array();
        $banner[] = array();
        $date = json_decode($param['sort']);
        for ($i = 0; $i < count($date); $i++) {
            $banner = $date[$i];
            $query1 = Banner::where('banner_id', '=', $banner->id)->first();
            if ($query1 == FALSE) {
                throw new ApiException('集合中有id不存在', ERROR::BEAUTY_BANNER_NOT_ID);
            }
        }
        for ($i = 0; $i < count($date); $i++) {
            $banner = $date[$i];
            $date2['updated_at'] = time();
            $date2['sort'] = $banner->sort;
            $query = Banner::where('banner_id', '=', $banner->id)->update($date2);
            if ($query == FALSE) {
                throw new ApiException('修改失败', ERROR::BEAUTY_BANNER_UPDATE_ERROR);
            }
        }
        return $this->success();
    }
    
    /**
     * @api {post} /banner/createOrSave 6.描述和介绍增加和修改
     * @apiName createOrSave
     * @apiGroup  Banner
     *
     * @apiParam {Number} type 必填,''banner类型  ‘1’‘ 代表主页banner；’2‘ 代表定妆banner；’3‘ 代表迁体banner；’4‘ 代表水光针banner； 
     * @apiParam {String} price 必填,  臭APP爆品价； 
     * @apiParam {String} priceOri 必填, 国内美容机构市场价； 
     * @apiParam {Text} introduce 必填,  'banner项目的介绍和描述'； 
     * @apiParam {Text} bargainPrice 必填, 酬宾价； 
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
     * 		    "msg": "删除banner失败"
     * 		}
     */
    public function  createOrSave(){
        $param = $this->param;
        $data = [];
        if (empty($param['type']) || !isset($param['introduce'])) {
            throw new ApiException('参数不齐', ERROR::BEAUTY_ITEM_ERROR);
        }
        $banner=Banner::where('type',$param['type'])->count();
        if($banner == FALSE){
             throw new ApiException('这个banner类不存在，请添加后再来！', ERROR::BEAUTY_BANNER_NOT_ID);
        }
        if($param['type'] == 3 || $param['type'] == 4 ){
            if (!isset($param['price']) || !isset($param['priceOri'])|| !isset($param['bargainPrice'])) {
                throw new ApiException('参数不齐', ERROR::BEAUTY_ITEM_ERROR);
            }else{
                $data['price']=$param['price'];
                $data['priceOri']=$param['priceOri'];
                $data['bargainPrice']=$param['bargainPrice'];
            }
        }

        $data['introduce']=$param['introduce'];
        $query = Banner::where('type',$param['type'])->update($data);
        
        if ($query) {
            Log::info("param is ",$data);
            return $this->success();
        } else {
            throw new ApiException('banner的描述和介绍失败!', ERROR::BEAUTY_BANNER_UPDATE_ERROR);
        }
        
    }
}
