<?php  namespace  App\Http\Controllers\Stylist;

use App\Stylist;
use App\StylistWorks;
use App\FileImage;
use App\Http\Controllers\Controller;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use DB;
use PDO;
class WorksController extends Controller {
    /**
     * @api {post} /works/index/:id 1.造型师的作品列表和所在店的其他
     * @apiName list
     * @apiGroup Works
     *
     * @apiParam {String} stylistId 必填,造型师ID.
     *
     * @apiSuccess {String} stylistId 造型师ID.
     * @apiSuccess {String} stylistName 造型师名称.
     * @apiSuccess {Number} mobilephone 手机号.
     * @apiSuccess {Numder} grade 悬赏等级 0没有等级 1美发师 2高级美发师 3造型师 4艺术总监.
     * @apiSuccess {Number} fastGrade 快剪等级 0没有等级 1普通快剪 2总监快剪.
     * @apiSuccess {Number} uploadNum 上传次数总数.
     * @apiSuccess {Number} num 作品数.
     * @apiSuccess {Number} recId 作品ID.
     * @apiSuccess {Number} stylistId 造型师ID.
     * @apiSuccess {String} commoditiesImg 作品集.
     * @apiSuccess {String} description 作品描述.
     * @apiSuccess {String} thumbImg 老版本作品集缩略图.
     * @apiSuccess {String} works['img'] 新版本作品集合  以（作品集和作品集缩略图）为一个单元.
     * @apiSuccess {String} salon['img'] 造型师头像缩略图
     * 
     * @apiSuccessExample Success-Response:
     * {
     *    "result":1,
     *    "token":"",
     *    "data":
     *    {
     *           "works":
     *           [
     *              {
     *                   "id":1,
     *                   "stylist_id":38,
     *                   "image_ids":"1,2,3,4",
     *                   "status":"ON",
     *                   "add_time":1444820916,
     *                   "description":"\u8fd9\u662f\u4e00\u4e2a\u63cf\u8ff01",
     *                   "img":
     *                   [
     *                           {
     *                               "worksId":1,
     *                               "originImg":"http:\/\/img01.choumei.cn\/1\/785973\/201510081147144427607355578597387596.jpg"
     *                           },
     *                          ......
     *                           {
     *                               "worksId":4,
     *                               "originImg":"http:\/\/img01.choumei.cn\/1\/785973\/201509281548144342652083878597311884.jpg"
     *                           }
     *                   ]
     *               }
     *            ],
     *           "salonSelf":
     *               {
     *                   "stylistId":26,
     *                   "stylistName":"\u4f1a\u64b8\u7684\u5b69\u5b50\u4e0d\u4f24\u8eab",
     *                   "mobilephone":"13545108420",
     *                   "grade":4,
     *                   "fastGrade":2,
     *                   "num":0,
     *                   "stylistImg":"www.douyuTV.com"，
     *                   "img":null,
     *                   "uploadNum":0,
     *                   "salonname":"choumeitest_salon"
     *                },
     *           "salon":
     *           [
     *               {
     *                   "stylistId":25,
     *                   "stylistName":"\u4f1a\u64b8\u7684\u5b69\u5b50\u4e0d\u4f24\u8eab",
     *                   "mobilephone":"13545108420",
     *                   "grade":4,
     *                   "fastGrade":2,
     *                   "num":0,
     *                   "stylistImg":"www.douyuTV.com"，
     *                   "img":null,
     *                   "uploadNum":0,
     *                },
     *               {
     *                   "stylistId":27,
     *                   "stylistName":"\u4f1a\u64b8\u7684\u5b69\u5b50\u4e0d\u4f24\u8eab",
     *                   "mobilephone":"19441001801",
     *                   "grade":0,
     *                   "fastGrade":2
     *                   "num":0,
     *                   "uploadNum":0,
     *               }
     *           ]
     *    }
     *  }
     *
     *
     * @apiErrorExample Error-Response:
     *		{
     *		    "result": 0,
     *		    "msg": "未授权访问"
     *		}
     */
    public function index($stylistId){
        $field=['stylistId','stylistName','stylistImg','mobilephone','grade','fastGrade','salonId','img'];
        $stylist=Stylist::select($field)->where(array('stylistId'=>$stylistId))->first();
        if($stylist===false){
            throw new ApiException('造型师ID出错', ERROR::MERCHANT_STYLIST_ID_ERROR);  
        }
        
        $salonStylist=Stylist::select($field)->where('salonId','=',$stylist['salonId'])->where('stylistId','<>',$stylistId )->get();
        $field2=['salonname']; 
        DB::connection()->setFetchMode(PDO::FETCH_ASSOC);
        $salon=DB::table('salon')->select($field2)->where(array('salonId'=>$stylist['salonId']))->first();
          
        $works=StylistWorks::getQuery()->where('stylist_id',$stylistId)->where('status','ON')->orderBy('add_time', 'desc')->get();
        $query=array();
         foreach ($works as $key2 =>$value) {
              if(!empty($value["image_ids"])){
                  $imageArr = explode(',', $value["image_ids"]);
                  
                  $works[$key2]['img']=[];
                  $imagecount=count($imageArr);
                  for ($i = 0; $i <$imagecount; $i++) {
                     $image =FileImage::getQuery()->where('id',$imageArr[$i])->first();
                     $img['worksId']=$image["id"];
                     $img['originImg']=$image["url"];
                     $works[$key2]['img'][]=$img;
   
                  }
                  
              }
         }
    

        foreach ($salonStylist as $key =>$value) {
            $num=0; 
            $works1= StylistWorks::where('stylist_id','=',$value->stylistId)->where('status','ON')->get();
            foreach ($works1 as $key1 =>$value) {
                if(!empty($value['image_ids'])){
                    $imageArr = explode(',', $value['image_ids']);
                    $num=$num+(count($imageArr));
                }  
             }  
           $salonStylist[$key]->num=$num;
           $salonStylist[$key]->uploadNum=  count(json_decode($works1,true));
         }
         
         
        if ($stylist) {
            $num=0; 
            $works3= StylistWorks::where('stylist_id','=',$stylistId)->where('status','ON')->get();
            foreach ($works3 as $key7 =>$value) {
              if(!empty($value['image_ids'])){
                    $imageArr = explode(',', $value['image_ids']);
                    $num=$num+(count($imageArr));
                }  
             }
           $stylist->num=$num;
           $stylist->uploadNum=StylistWorks::where('stylist_id','=',$stylistId)->where('status','ON')->count();
           $stylist->salonname=$salon["salonname"];
         }
         
        $query['works']=$works;
        $query['salonSelf']=$stylist;
        $query['salon']=$salonStylist;
        
        return $this->success($query);  
    }
    
    /**
     * @api {post} /works/del_list/:id  2.删除作品集合
     * @apiName del_list
     * @apiGroup  Works
     *
     * @apiParam {Number} recId 必填,作品id.
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
     *		    "msg": "删除作品集合失败"
     *		}
     */
    public function  del_list($recId){
        $works=StylistWorks::where('id',$recId)->where('status','ON')->count();
        if($works==FALSE){
             throw new ApiException('作品ID出错', ERROR::MERCHANT_WORKS_ID_ERROR);
        }
        $data['status']="OFF";
        $query=  StylistWorks::where(array('id'=>$recId))->update($data);
        if($query){
            return $this->success();
        }else{
            throw new ApiException('删除作品失败', ERROR::MERCHANT_WORKS_DELETE_ERROR);
        }
    }
    
    
    /**
     * @api {post} /works/del/:id  3.删除单个作品
     * @apiName del
     * @apiGroup  Works
     *
     * @apiParam {Number} recId 必填,作品id.
     * @apiParam {String} img  必填,[woeksId]作品集合.eg:  "1,2,3"  作品ID集合，以逗号隔开
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
     *		    "msg": "删除作品失败"
     *		}
     */
    public function  del($recId){
        $param=$this->param;
        $works=  StylistWorks::where(array('id'=>$recId))->where('status','ON')->count();
        if($works==FALSE){
             throw new ApiException('作品ID出错', ERROR::MERCHANT_WORKS_ID_ERROR);
        }
        $data=array();
        if(!empty($param['img'])){
            $data['image_ids']=$param['img'];
            $query=  StylistWorks::where(array('id'=>$recId))->update($data);
        }else{
            $data2['status']="OFF";
            $query=  StylistWorks::where(array('id'=>$recId))->update($data2);
        }
        if($query){
                return $this->success();
        }else{
                 throw new ApiException('删除单个作品失败', ERROR::MERCHANT_WORKS_DELETE_ERROR);
        }
    }
    
    /**
     * @api {post} /works/update/:id  4.修改作品集合
     * @apiName update
     * @apiGroup  Works
     *
     * @apiParam {Number} recId 必填,作品id.
     * @apiParam {String} img 必填,[woekId]作品集合.eg:  "1,2,3"  作品ID集合，以逗号隔开
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
     *		    "msg": "修改作品失败"
     *		}
     */
    public function  update($recId){
        $param=$this->param;
        $works=  StylistWorks::where(array('id'=>$recId))->where('status','ON')->count();
        if($works==FALSE){
             throw new ApiException('作品ID出错', ERROR::MERCHANT_WORKS_ID_ERROR);
        }
        $data=array();
        if(empty($param['img'])){
             throw new ApiException('参数错误', ERROR::MERCHANT_ERROR);
        }
        $data['image_ids']=$param['img'];
        $query=  StylistWorks::where(array('id'=>$recId))->update($data);
        if($query){
                return $this->success();
        }else{
                 throw new ApiException('修改作品失败', ERROR::MERCHANT_WORKS_SAVE_ERROR);
        }
    }
    
    /**
     * @api {post} /works/create 5.新增作品集合
     * @apiName create
     * @apiGroup  Works
     *
     * @apiParam {Number} stylistId 必填,造型师ID.
     * @apiParam {String} description 必填,作品描述.
     * @apiParam {String} img  必填,[originImg]作品集合. eg:"www,ee,qq,bb"    作品原图路径集合，以逗号隔开
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
     *		    "msg": "创建作品失败"
     *		}
     */
    public function  create(){
        $param=$this->param;
        if(empty($param['img'])||empty($param['stylistId'])){
             throw new ApiException('创建作品的参数不齐', ERROR::MERCHANT_ERROR);
        }
      
         $imageArr =json_decode($param['img'],true);
         $fileIds = array();
         $imagecount=count($imageArr);
         for ($i = 0; $i < $imagecount; $i++) {
             $data2=array();
             $imageUrl = $imageArr[$i]; 
             $urlParsed = parse_url($imageArr[$i]);
             $imageUrl = $urlParsed['scheme'].'://'.$urlParsed['host'].$urlParsed['path'];
             if ($urlParsed['path']){
                $data2['file_name']=$urlParsed['path'];
             }
             $aa=getimagesize($imageUrl);
             $data2['width']=$aa["0"];////获取图片的宽 
             $data2['height']=$aa["1"];///获取图片的高
             $data2['url']=$imageUrl; 
             $fileImage=FileImage::create($data2);
             $fileIds[] = $fileImage->id;

         }
        $data['image_ids']= implode(',', $fileIds);
        $data['stylist_id']=$param['stylistId'];
        $data['add_time']= time();
        
        if(isset($param['description'])||$param['description']){
             $data['description']=$param['description'];
        }
        $query=  StylistWorks::create($data);
        if($query){
             return $this->success();
        }else{
             throw new ApiException('创建作品失败', ERROR::MERCHANT_WORKS_CREATE_ERROR);
        }
    }  
}
