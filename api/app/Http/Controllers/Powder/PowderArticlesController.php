<?php

namespace App\Http\Controllers\Powder;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Model\Present;
use App\Model\PresentArticleCode;
use App\User;
use App\RecommendCodeUser;
use App\BookingOrder;
use App\ThriftHelperModel;
use App\BeautyItem;
use App\Push;
use DB;
use App\Model\SeedPool;
use Event;
use Log;
use Excel;

use App\Jobs\PowderArticleTicket;

use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class PowderArticlesController extends Controller
{
    private static $articleStatusName = array(
        '活动正常',
        '停止活动',
        '停止验证',
        '活动过期',
    );
    private static $ticketCodeStatus = array(
        1 =>'已使用',
        2 =>'未使用',
        3 =>'已过期',
    );
    private static $presentTypeName= array(
        1=>'消费赠送',
        2=>'推荐赠送',
        3=>'活动赠送',
    );
    /**
     * @api {post} /powderArticles/addArticles 1.添加活动
     * 
     * @apiName addArticles
     * @apiGroup PowderArticles
     *
     * @apiParam {String} articleName 必填，活动名
     * @apiParam {Number} itemId 必填，项目id
     * @apiParam {Number} nums 必填，活动数量
     * @apiParam {String} startTime 必填，活动开始时间
     * @apiParam {String} endTime 必填，活动结束时间
     * @apiParam {String} expireTime 必填，活动有效时间
     * @apiParam {Number} departmentId 必填, 部门id
     * @apiParam {Number} userId 必填, 负责人id
     * @apiParam {String} detail 可选，内容
     * 
     * @apiSuccess {Array} data
     * 
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     *       "result": 1,
     *       "token": "",
     *       "data": {
     *           "presentId": 3
     *       }
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 添加定妆赠送活动
     */
    public function addArticles()
    {
        $param = $this->param;
        $createrId = $this->user->id;
        if(empty($param['articleName']) || empty($param['itemId']) || empty($param['startTime']) || empty($param['endTime']) || empty($param['expireTime']) || empty($param['departmentId']) || empty($param['userId'])){
            throw new ApiException('必传参数不能为空');
        }
        if(!isset($param['nums'])){
            throw new ApiException('必传参数不能为空');
        }else{
            if($param['nums'] == 0){
                throw new ApiException('赠送数量不能为0');
            }
        }
        if(strlen($param['articleName']) > 60){
            throw new ApiException('活动名称长度限制20字');
        }
        if(strlen($param['nums']) > 8){
            throw new ApiException('活动数量不能超过8位数');
        }
        if(strtotime($param['startTime']) < strtotime(date('Y-m-d'))){
            throw new ApiException('活动开始时间错误');
        }
        if(strtotime($param['startTime']) > strtotime($param['endTime'])){
            throw new ApiException('活动结束时间必须大于开始时间');
        }
        if(strtotime($param['endTime']) > strtotime($param['expireTime'])){
            throw new ApiException('活动有效时间必须大于活动结束时间');
        }
        if(!empty($param['detail']) && strlen(urldecode($param['detail'])) > 3000){
            throw new ApiException('活动简介内容长度超出');
        }
        //通过活动名称查询是否有重复
        $count = Present::where('name','=',$param['articleName'])->count();
        if($count){
            throw new ApiException('活动名称已存在',ERROR::POWDER_ARTICLE_NAME_EXIST);
        }
        //限制发送数量
        if($param['nums'] > 100000){
            throw new ApiException('活动券数量超过10万，无法发券,请重试');
        }
        //查询种子池中活动券是否够用
        $seed = SeedPool::where(array('TYPE' => 'GSN' , 'STATUS' => 'NEW'))->count();
        if($seed < 100000){
            throw new ApiException('线下活动券数量已超标，现在无法添加活动,请联系管理员');
        }       
        $data['name'] = $param['articleName'];
        $data['item_id'] = $param['itemId'];
        $data['quantity'] = $param['nums'];      
        $data['start_at'] = $param['startTime'];
        $data['end_at']  = $param['endTime']." 23:59:59";
        $data['expire_at'] = $param['expireTime']." 23:59:59";
        $data['department_id'] = $param['departmentId'];
        $data['user_id'] = $param['userId'];
        $data['creater_id'] = $createrId;
        $data['detail'] = isset($param['detail']) ? $param['detail'] : '' ;
        $data['created_at'] = time();
        $resId = Present::insertGetId($data);
        //$queries = DB::getQueryLog();
        if($resId){
            $res['presentId'] = $resId;                     
            $this->dispatch(new PowderArticleTicket($resId));
            Event::fire('powder.create','添加赠送活动,活动编号:'.$resId);
            return $this->success($res);
        }else{
            throw new ApiException('活动添加失败',ERROR::POWDER_ARTICLE_ADD_FIELD);
        }
    }
    /**
     * @api {post} /powderArticles/articlesList 2.定妆活动列表
     * 
     * @apiName articlesList
     * @apiGroup PowderArticles
     *
     * @apiParam {String} articleName 选填，活动名
     * @apiParam {Number} departmentId 选填，部门id
     * @apiParam {String} startTime 选填，活动创建开始时间 YY-MM-DD
     * @apiParam {String} endTime 选填，活动创建结束时间 YY-MM-DD
     * 
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * 
     * @apiSuccess {Number} presentId 活动id.
     * @apiSuccess {String} articleName 活动名.
     * @apiSuccess {Number} itemId 项目id.
     * @apiSuccess {String} itemName 项目名.
     * @apiSuccess {Number} quantity 券总数.
     * @apiSuccess {Number} useNum 使用数量.
     * @apiSuccess {Number} notUseNum 未使用数.
     * @apiSuccess {String} startTime 活动开始时间.
     * @apiSuccess {String} endTime 活动结束时间.
     * @apiSuccess {String} expireTime 活动有效时间.
     * @apiSuccess {Number} departmentId 部门id.
     * @apiSuccess {String} departmentName 部门名.
     * @apiSuccess {Number} userId 负责人id.
     * @apiSuccess {Number} createrId 创建人id.
     * @apiSuccess {String} detail 活动详情.
     * @apiSuccess {Number} articleStatus 活动状态 1: 开启  2: 关闭'.
     * @apiSuccess {Number} verifyStatus 验证状态 1: 开启验证 2: 关闭验证.
     * @apiSuccess {Number} articleType 活动类型 1: 线下  2: 线上.
     * @apiSuccess {String} createTime 活动创建时间
     * @apiSuccess {String} articleStatusName 活动状态名
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     *       "token": "",
     *       "data": {
     *           "total": 4,
     *           "per_page": 20,
     *           "current_page": 1,
     *           "last_page": 1,
     *           "from": 1,
     *           "to": 4,
     *           "data": [
     *               {
     *                   "presentId": 4,
     *                   "articleName": "test4",
     *                   "itemId": 1,
     *                   "itemName": "测试000",
     *                   "quantity": 10,
     *                   "useNum": 0,
     *                   "startTime": "2015-11-30 00:00:00",
     *                   "endTime": "2015-11-30 23:59:59",
     *                   "expireTime": "2015-12-03 00:00:00",
     *                   "departmentId": 1,
     *                   "departmentName": "总裁办",
     *                   "userId": 9527,
     *                   "createrId": 9527,
     *                   "detail": "这是一个活动",
     *                   "articleStatus": 1,
     *                   "verifyStatus": 1,
     *                   "articleType": 1,
     *                   "createTime": "2015-11-30",
     *                   "articleStatusName": "活动正常",
     *                   "notUseNum": 10
     *               },
     *               {
     *                   "presentId": 1,
     *                   "articleName": "test1",
     *                   "itemId": 1,
     *                   "itemName": "测试000",
     *                   "quantity": 10,
     *                   "useNum": 0,
     *                   "startTime": "2015-11-30 00:00:00",
     *                   "endTime": "2015-11-30 23:59:59",
     *                   "expireTime": "2015-12-03 00:00:00",
     *                   "departmentId": 1,
     *                   "departmentName": "总裁办",
     *                   "userId": 9527,
     *                   "articleStatus": 1,
     *                   "verifyStatus": 1,
     *                   "articleType": 1,
     *                   "createTime": "1970-01-01",
     *                   "articleStatusName": "活动正常",
     *                   "notUseNum": 10
     *               }
     *           ]
     *       }
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 定妆赠送活动列表
     */
    public function articlesList()
    {
        $param = $this->param;
        $name = isset($param['articleName']) ? trim($param['articleName']) :'';
        $departmentId = isset($param['departmentId']) ? trim($param['departmentId']) :'';
        
        $startTime = isset($param['startTime'])? strtotime($param['startTime']):'';
        $endTime = isset($param['endTime'])? strtotime($param['endTime']." 23:59:59"):'';
        
        $page = isset($param['page'])?max($param['page'],1):1;
        $pageSize = isset($param['page_size'])?$param['page_size']:20;
        
        $articlesList = Present::getArticlesList($name,$departmentId,$startTime,$endTime,$page,$pageSize);
        foreach ($articlesList['data'] as $key => &$val) {
           if($val['articleType'] == 2){
               $val['expireTime'] = "获赠90天内有效";
               if ($val['verifyStatus'] == 2){
                    $val['articleStatusName'] = self::$articleStatusName[2];  //关闭验证
                }elseif ($val['articleStatus'] == 2) {
                    $val['articleStatusName'] = self::$articleStatusName[1];  //停止活动
                }else{
                    $val['articleStatusName'] = self::$articleStatusName[0];  //活动正常
                }
           }else{
               if(time() > strtotime($val['expireTime'])){
                    $val['articleStatusName'] = self::$articleStatusName[3]; //活动已过期
                }elseif ($val['verifyStatus'] == 2){
                    $val['articleStatusName'] = self::$articleStatusName[2];  //关闭验证
                }elseif ($val['articleStatus'] == 2) {
                    $val['articleStatusName'] = self::$articleStatusName[1];  //停止活动
                }else{
                    $val['articleStatusName'] = self::$articleStatusName[0];  //活动正常
                }
                $val['expireTime'] = substr($val['expireTime'], 0,10);
           }
           $val['notUseNum'] = $val['quantity'] - $val['useNum'];
           $val['createTime'] = date('Y-m-d',$val['createTime']);
           $val['startTime'] = substr($val['startTime'], 0,10);
           $val['endTime'] = substr($val['endTime'], 0,10);
        }
        return $this->success($articlesList);
        
    }
    
    /**
     * @api {post} /powderArticles/showArticlesInfo 3.定妆活动详情
     * 
     * @apiName showArticlesInfo
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} presentId 必填，活动id
     * 
     * @apiSuccess {Number} presentId 活动id.
     * @apiSuccess {String} articleName 活动名.
     * @apiSuccess {Number} itemId 项目id.
     * @apiSuccess {String} itemName 项目名.
     * @apiSuccess {Number} quantity 券总数.
     * @apiSuccess {Number} useNum 使用数量.
     * @apiSuccess {Number} notUseNum 未使用数.
     * @apiSuccess {String} startTime 活动开始时间.
     * @apiSuccess {String} endTime 活动结束时间.
     * @apiSuccess {String} expireTime 活动有效时间.
     * @apiSuccess {Number} departmentId 部门id.
     * @apiSuccess {String} departmentName 部门名.
     * @apiSuccess {Number} userId 用户id.
     * @apiSuccess {String} detail 活动详情.
     * @apiSuccess {Number} articleStatus 活动状态 1: 开启  2: 关闭'.
     * @apiSuccess {Number} articleExpireStatus 活动过期状态 1: 没过期  2: 已过期'.
     * @apiSuccess {Number} verifyStatus 验证状态 1: 开启验证 2: 关闭验证.
     * @apiSuccess {Number} articleType 活动类型 1: 线下  2: 线上.
     * @apiSuccess {String} createTime 活动创建时间
     * @apiSuccess {String} managerName 负责人
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     *       "result": 1,
     *       "token": "",
     *       "data": {
     *           "presentId": 5,
     *           "articleName": "test9",
     *           "itemId": 1,
     *           "itemName": "韩式纤体",
     *           "quantity": 10,
     *           "useNum": 0,
     *           "startTime": "2015-12-30 00:00:00",
     *           "endTime": "2015-12-30 23:59:59",
     *           "expireTime": "2016-12-03 00:00:00",
     *           "articleExpireStatus" : 1,
     *           "departmentId": 1,
     *           "departmentName": "总裁办",
     *           "userId": 115,
     *           "detail": "测试",
     *           "articleStatus": 1,
     *           "verifyStatus": 1,
     *           "articleType": 1,
     *           "createTime": "2015-12-01",
     *           "managerName": "唐敏",
     *           "notUseNum": 10
     *       }
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 定妆赠送活动详情
     */
    public function showArticlesInfo()
    {
        $param = $this->param;
        if(empty($param['presentId'])){
            throw new ApiException('必传参数不能为空');    
        }
        $where = array('present_id' => $param['presentId']);
        $articlesInfo = Present::getArticlesInfoByWhere($where);
        if(!empty($articlesInfo)){
            $articlesInfo['notUseNum'] = $articlesInfo['quantity'] - $articlesInfo['useNum'];
            $articlesInfo['createTime'] = date('Y-m-d H:i:s',$articlesInfo['createTime']);       
            $articlesInfo['startTime'] = substr($articlesInfo['startTime'], 0,10);
            $articlesInfo['endTime'] = substr($articlesInfo['endTime'], 0,10);
            $articlesInfo['articleExpireStatus'] = 1;//活动没有过期
            if($articlesInfo['articleType'] == 2){
                $articlesInfo['expireTime'] = '获赠90天内有效';
            }else{
                if(time() > strtotime($articlesInfo['expireTime'])){
                    $articlesInfo['articleExpireStatus'] = 2; //已过期
                }
                $articlesInfo['expireTime'] = substr($articlesInfo['expireTime'], 0,10);
            }
            
        }
        Event::fire('powder.showArticleDetail','定妆活动详情,活动编号:'.$param['presentId']);
        return $this->success($articlesInfo);       
    }
    
    /**
     * @api {post} /powderArticles/switchArticles 4.定妆活动开关
     * 
     * @apiName switchArticles
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} presentId 必填，活动id
     * @apiParam {Number} articleStatus 必填，活动开启或关闭 1 开启  2 关闭
     * 
     * @apiSuccess {Array} data 空.
     *
     * @apiSuccessExample Success-Response:
     * 	{
     *       "result": 1,
     *       "token": "",
     *       "data": []
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 定妆赠送活动开关
     * 1 开启活动  2 关闭活动
     */
    public function switchArticles()
    {
        $param = $this->param;
        if(empty($param['presentId']) || empty($param['articleStatus'])){
            throw new ApiException('必传参数不能为空');    
        }
        if(!in_array($param['articleStatus'],array(1,2))){
            throw new ApiException('活动开关参数错误');
        }
        $where = array('present_id' => intval($param['presentId']));
        $field = array('end_at','article_status as articleStatus');
        $articlesInfo = Present::select($field)->where($where)->first();
        if(!empty($articlesInfo)){
            if(time() > strtotime($articlesInfo['end_at'])){
                throw new ApiException('活动已截止');
            }elseif($articlesInfo['articleStatus'] == $param['articleStatus']){
                if($param['articleStatus'] == 1){
                    throw new ApiException('活动已开启，无需再开启');
                }
                if($param['articleStatus'] == 2){
                    throw new ApiException('活动已关闭，无需再关闭');
                }
            }else {
                //进行更新
                $data = array('article_status' => $param['articleStatus'],'updated_at' => time());
                $res = Present::where($where)->update($data);
                if($res === false){
                    throw new ApiException('更新失败',ERROR::POWDER_ARTICLE_SWITCH_STATUS);
                }else{
                    $switchValue = ($param['articleStatus'] == 1) ? "开启":"关闭";
                    Event::fire('powder.closeArticle','定妆活动'.$switchValue.'活动编号：'.$param['presentId']);
                    return $this->success();
                }
            }
        }else{
            throw new ApiException('活动不存在');
        }
    }
    /**
     * @api {post} /powderArticles/switchVerifyArticles 5.定妆活动验证开关
     * 
     * @apiName switchVerifyArticles
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} presentId 必填，活动id
     * @apiParam {Number} verifyStatus 必填，活动开启或关闭 1 开启  2 关闭
     * 
     * @apiSuccess {Array} data 空.
     *
     * @apiSuccessExample Success-Response:
     * 	{
     *       "result": 1,
     *       "token": "",
     *       "data": []
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 定妆赠送活动验证开关 1 开启 2 关闭
     */
    public function switchVerifyArticles()
    {
        $param = $this->param;
        if(empty($param['presentId']) || empty($param['verifyStatus'])){
            throw new ApiException('必传参数不能为空');    
        }
        if(!in_array($param['verifyStatus'],array(1,2))){
            throw new ApiException('活动开关参数错误');
        }
        $where = array('present_id' => intval($param['presentId']));
        $field = array('expire_at','verify_status as verifyStatus','article_type');
        $articlesInfo = Present::select($field)->where($where)->first();
        if(!empty($articlesInfo)){
            if(time() > strtotime($articlesInfo['expire_at']) && $articlesInfo['article_type'] == 1){
                throw new ApiException('活动已过期');
            }elseif($articlesInfo['verifyStatus'] == $param['verifyStatus']){
                if($param['verifyStatus'] == 1){
                    throw new ApiException('活动验证已开启，无需再开启');
                }
                if($param['verifyStatus'] == 2){
                    throw new ApiException('活动验证已关闭，无需再关闭');
                }
            }else {
                //进行更新
                $data = array('verify_status' => $param['verifyStatus'],'updated_at' => time());
                $res = Present::where($where)->update($data);
                if($res === false){
                    throw new ApiException('更新失败',ERROR::POWDER_ARTICLE_SWITCH_VERIFY_STATUS);
                }else{
                    $switchValue = ($param['verifyStatus'] == 1) ? "开启":"关闭";
                    Event::fire('powder.closeArticleVerify','定妆活动验证'.$switchValue.'活动编号：'.$param['presentId']);
                    return $this->success();
                }
            }
        }else{
            throw new ApiException('活动不存在');
        }    
    }
    /**
     * @api {post} /powderArticles/articlesTicketList 6.兑换券详情
     * 
     * @apiName articlesTicketList
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} presentId 必填，活动id
     * 
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * 
     * @apiSuccess {String} itemName 项目名.
     * @apiSuccess {Number} TicketCode 券号.
     * @apiSuccess {String} startTime 活动开始时间.
     * @apiSuccess {String} endTime 活动结束时间.
     * @apiSuccess {String} ticketStatusName 券状态名.
     * 
     * @apiSuccessExample Success-Response:
     * {
     *       "result": 1,
     *       "token": "",
     *       "data": {
     *           "total": 2,
     *           "per_page": 20,
     *           "current_page": 1,
     *           "last_page": 1,
     *           "from": 1,
     *           "to": 2,
     *           "data": [
     *               {
     *                   "itemName": "韩式无痛水光针（赠送）",
     *                   "TicketCode": "502",
     *                   "startTime": "2015-11-30 00:00:00",
     *                   "endTime": "2015-11-30 23:59:59",
     *                   "ticketStatus": 1,
     *                   "ticketStatusName": "已使用"
     *               },
     *               {
     *                   "itemName": "韩式无痛水光针（赠送）",
     *                   "TicketCode": "502",
     *                   "startTime": "2015-11-30 00:00:00",
     *                   "endTime": "2015-11-30 23:59:59",
     *                   "ticketStatus": 2,
     *                   "ticketStatusName": "未使用"
     *               }
     *           ]
     *       }
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 兑换券详情
     */
    public function articlesTicketList()
    {
        $param = $this->param;
        if(empty($param['presentId'])){
            throw new ApiException('必传参数不能为空');    
        }
        $page = isset($param['page'])?max($param['page'],1):1;
        $pageSize = isset($param['page_size'])?$param['page_size']:20;
        
        $articleTicketInfoRes =  PresentArticleCode::getArticleTicketInfo($param['presentId'],$page,$pageSize);
        foreach($articleTicketInfoRes['data'] as $key => &$val){
            $val['ticketStatusName'] = self::$ticketCodeStatus[$val['ticketStatus']];
            $val['startTime'] = substr($val['startTime'], 0,10);
            $val['endTime'] = substr($val['endTime'], 0,10);
        }
        Event::fire('powder.showArticleTicketInfo','兑换券详情,活动编号：'.$param['presentId']);   
        return $this->success($articleTicketInfoRes);
    }
    /**
     * @api {post} /powderArticles/exportArticlesTicketList 7.导出定妆活动券
     * 
     * @apiName exportArticlesTicketList
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} presentId 必填，活动id
     * 
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
     * 导出券
     */
    public function exportArticlesTicketList(){
        $param = $this->param;
        if(empty($param['presentId'])){
            throw new ApiException('必传参数不能为空');    
        }
        @set_time_limit(0);
        @ini_set('memory_limit', '512M');
        //获取活动名称
        $where = array('present_id'=>$param['presentId']);
        $articleInfo = Present::getArticleInfoByWhere($where);
        $articleAllTicketInfoRes =  PresentArticleCode::getAllArticleTicketInfoForExport($param['presentId']);
        foreach ($articleAllTicketInfoRes as &$val) {
            $val['startTime'] = substr($val['startTime'], 0,10);
            $val['endTime'] = substr($val['endTime'], 0,10);
            $val['ticketStatusName'] = self::$ticketCodeStatus[$val['ticketStatus']]; 
            unset($val['ticketStatus']);
        }
        $header = [
            '赠送项目',
            '赠送券编码',
            '活动起始日',
            '活动截止日',
            '状态',
        ];
        if (!empty($articleAllTicketInfoRes)) {
            Event::fire('powder.exportArticleTicket','导出活动券,活动编号：'.$param['presentId']);
        }
       
        $this->export_xls($articleInfo['name'] . date("Ymd"), $header, $articleAllTicketInfoRes);
    }
    
    /**
     * @api {post} /powderArticles/presentList 8.定妆赠送查询列表
     * 
     * @apiName presentList
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} mobilephone 选填，手机号
     * @apiParam {Number} reservateSn 选填，预约号
     * @apiParam {String} recommendCode 选填，推荐码
     * @apiParam {Number} ticketCode 选填，券号
     * @apiParam {Number} presentType 选填，赠送方式
     * @apiParam {Number} ticketStatus 选填，券使用状态
     * @apiParam {String} startTime 选填，赠送开始时间 YY-MM-DD
     * @apiParam {String} endTime 选填，赠送结束时间 YY-MM-DD
     * 
     * @apiSuccess {Number} total 总数据量.
     * @apiSuccess {Number} per_page 分页大小.
     * @apiSuccess {Number} current_page 当前页面.
     * @apiSuccess {Number} last_page 当前页面.
     * @apiSuccess {Number} from 起始数据.
     * @apiSuccess {Number} to 结束数据.
     * 
     * @apiSuccess {Number} articleCodeId 活动赠送券记录id
     * @apiSuccess {Number} presentId 活动id.
     * @apiSuccess {Number} reservateSn 预约号.
     * @apiSuccess {Number} orderSn 系统内部订单号.
     * @apiSuccess {Number} itemId 项目id.
     * @apiSuccess {String} ticketCode 券号.
     * @apiSuccess {Number} ticketStatus 券状态.
     * @apiSuccess {Number} mobilephone 手机号.
     * @apiSuccess {String} recommendCode 推荐码.
     * @apiSuccess {Number} presentType 赠送方式.
     * @apiSuccess {Number} managerId 操作人id.
     * @apiSuccess {Number} specialistId 专家id.
     * @apiSuccess {Number} assistantId 助理id.
     * @apiSuccess {String} expireTime 有效期.
     * @apiSuccess {String} useTime 使用时间.
     * @apiSuccess {String} recordTime 记录时间.
     * @apiSuccess {String} createdTime 添加时间.
     * @apiSuccess {String} itemName 项目名.
     * @apiSuccess {String} ticketStatusName 券状态名
     * @apiSuccess {String} presentTypeName 赠送类型名
     * 
     * @apiSuccessExample Success-Response:
     * 	{
            "result": 1,
            "token": "",
            "data": {
                "total": 1,
                "per_page": 20,
                "current_page": 1,
                "last_page": 1,
                "from": 1,
                "to": 1,
                "data": [
                    {
                        "articleCodeId": 1,
                        "presentId": 1,
                        "reservateSn": 123456,
                        "orderSn": 99999,
                        "itemId": 1,
                        "ticketCode": "502",
                        "ticketStatus": 1,
                        "mobilephone": 1802669546,
                        "recommendCode": 5020,
                        "presentType": 1,
                        "managerId": 114,
                        "specialistId": 1,
                        "assistantId": 3,
                        "expireTime": "0000-00-00 00:00:00",
                        "useTime": "0000-00-00",
                        "recordTime": "0000-00-00 00:00:00",
                        "createdTime": "0000-00-00",
                        "itemName": "韩式无痛水光针（赠送）",
                        "ticketStatusName": "已使用",
                        "presentTypeName": "消费赠送"
                    }
                ]
            }
        }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 定妆赠送查询
     */
    public function presentList()
    {
        $param = $this->param;
        $mobilephone = isset($param['mobilephone']) ? $param['mobilephone'] :'';
        $reservateSn = isset($param['reservateSn']) ? trim($param['reservateSn']) :'';
        $recommendCode = isset($param['recommendCode']) ? $param['recommendCode'] :'';
        $ticketCode = isset($param['ticketCode']) ? trim($param['ticketCode']) :'';
        $startTime = isset($param['startTime'])? strtotime($param['startTime']):'';
        $endTime = isset($param['endTime'])? strtotime($param['endTime']." 23:59:59"):'';
        
        $presentType = isset($param['presentType']) ? intval($param['presentType']) :'';
        $ticketStatus = isset($param['ticketStatus']) ? intval($param['ticketStatus']) :'';
        
        $page = isset($param['page'])?max($param['page'],1):1;
        $pageSize = isset($param['page_size'])?$param['page_size']:20;
        
        $presentListInfo = PresentArticleCode::getPresentList($mobilephone,$reservateSn,$recommendCode,$ticketCode,$startTime,$endTime,$presentType,$ticketStatus,$page,$pageSize);
        foreach ($presentListInfo['data'] as $key => &$val) {
            $val['ticketStatusName'] = self::$ticketCodeStatus[$val['ticketStatus']];
            $val['presentTypeName'] = self::$presentTypeName[$val['presentType']];
            //线下活动没有赠送日期
            if($val['reservateSn'] === null){
                $val['createTime'] = '';
            }else{
                $val['createTime'] = date('Y-m-d',$val['createTime']);
            }
            $val['expireTime'] = substr($val['expireTime'],0,10);
        }
        return $this->success($presentListInfo);
        
    }
    /**
     * @api {post} /powderArticles/presentListInfo 9.定妆赠送详情
     * 
     * @apiName presentListInfo
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} articleCodeId 必填，活动赠送券记录id
     * 
     * @apiSuccess {Number} presentId 活动id.
     * @apiSuccess {Number} reservateSn 预约订单号.
     * @apiSuccess {Number} itemId 项目id.
     * @apiSuccess {Number} orderSn 系统内部订单号.
     * @apiSuccess {Number} ticketCode 券号.
     * @apiSuccess {Number} ticketStatus 券使用状态. 1:已使用 2:未使用 3: 已过期
     * @apiSuccess {Number} verifyStatus 验证券状态. 1: 开启验证 2: 关闭验证
     * @apiSuccess {Number} mobilephone 手机号.
     * @apiSuccess {String} createTime 创建时间.
     * @apiSuccess {String} recordTime 记录时间.
     * @apiSuccess {String} expireTime 券有效时间.
     * @apiSuccess {String} useTime 使用时间.
     * @apiSuccess {String} recommendCode 推荐码.
     * @apiSuccess {Number} presentType 赠送类型.
     * @apiSuccess {Number} managerId 记录人id.
     * @apiSuccess {Number} beauticianId 美容师id.
     * @apiSuccess {Number} guideId 美导id.
     * @apiSuccess {String} beauticianName 美容师名称.
     * @apiSuccess {Number} beauticianNumber 美容师在职编号.
     * @apiSuccess {String} guideName 美导名称.
     * @apiSuccess {Number} guideNumber 美导在职编号.
     * @apiSuccess {String} skinConcerns 皮肤状况. 多个使用,隔开。1 腰毛粗大   2皮肤暗沉  3 敏感脆弱   4干燥粗糙  5 细小皱纹   6痤疮痕迹   7皮肤松弛  8其他
     * @apiSuccess {Json} stoste 原液选择. 
     * @apiSuccess {String} remark 备注.
     * @apiSuccess {String} itemName 项目名
     * @apiSuccess {String} articleName 活动名
     * @apiSuccess {String} managerName 负责人
     * @apiSuccess {String} specialistName 专家名
     * @apiSuccess {String} assistantName 助理名
     * @apiSuccess {String} ticketStatusName 券状态名
     * @apiSuccess {String} presentTypeName 赠送类型名
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     *       "result": 1,
     *       "token": "",
     *       "data": {
     *           "articleCodeId": 148252,
     *           "presentId": 61,
     *           "reservateSn": null,
     *           "orderSn": "DZ160105000010",
     *           "itemId": 1,
     *           "ticketCode": "SZ1613693446",
     *           "ticketStatus": 2,
     *           "mobilephone": null,
     *           "recommendCode": null,
     *           "presentType": 3,
     *           "managerId": 0,
     *           "beauticianId": 6,
     *           "guideId": 5,
     *           "skinConcerns": "1,3,5",
     *           "stoste": [{"type":"N5","nums":5},{"type":"N10","nums":5}],
     *           "remark": "I need this",
     *           "expireTime": "2016-12-03",
     *           "useTime": "0000-00-00",
     *           "recordTime": "2015-01-01 00:00",
     *           "createTime": "",
     *           "itemName": "韩式无痛水光针（赠送）",
     *           "articleName": "hhhhh",
     *           "verifyStatus": 1,
     *           "managerName": null,
     *           "beauticianName": "小楠",
     *           "beauticianNumber": "B112",
     *           "guideName": "小木",
     *           "guideNumber": "A1150",
     *           "ticketStatusName": "未使用",
     *           "presentTypeName": "活动赠送"
     *       }
     *  }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
    * 定妆赠送详情
     */
    public function presentListInfo()
    {
        $param = $this->param;
        if(empty($param['articleCodeId'])){
            throw new ApiException('必传参数不能为空');    
        }
        $where = array('article_code_id' => $param['articleCodeId']);
        $presentListInfoDetail = PresentArticleCode::getPresentListInfoByWhere($where);
        if(!empty($presentListInfoDetail)){
            if(!empty($presentListInfoDetail['stoste'])){
                $presentListInfoDetail['stoste'] = json_decode($presentListInfoDetail['stoste']);
            }
            $presentListInfoDetail['ticketStatusName'] = self::$ticketCodeStatus[$presentListInfoDetail['ticketStatus']];
            $presentListInfoDetail['presentTypeName'] = self::$presentTypeName[$presentListInfoDetail['presentType']];
            //线下活动没有赠送日期
            if($presentListInfoDetail['reservateSn'] === null){
                $presentListInfoDetail['createTime'] = '';
            }else{
                $presentListInfoDetail['createTime'] = date('Y-m-d',$presentListInfoDetail['createTime']);
            }
            $presentListInfoDetail['useTime'] = substr($presentListInfoDetail['useTime'],0,10);
            $presentListInfoDetail['recordTime'] = substr($presentListInfoDetail['recordTime'],0,16);
            $presentListInfoDetail['expireTime'] = substr($presentListInfoDetail['expireTime'],0,10);
        }
        Event::fire('powder.showTicketInfo','定妆赠送详情,活动赠送券记录id：'.$param['articleCodeId']);
        return $this->success($presentListInfoDetail);
    }
    /**
     * @api {post} /powderArticles/usePresentTicket 10.消费券
     * 
     * @apiName usePresentTicket
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} articleCodeId 必填，活动赠送券记录id
     * @apiParam {String} skinConcerns 选填，皮肤状况，多个使用,隔开。1 腰毛粗大   2皮肤暗沉  3 敏感脆弱   4干燥粗糙  5 细小皱纹   6痤疮痕迹   7皮肤松弛  8其他
     * @apiParam {array} stoste 选填，原液，array(0=>array('type'=>2,'nums'=>3),1=>array('type'=>2,'nums'=>3))
     * @apiParam {String} remark 选填，备注
     * @apiParam {Number} guideId 选填，美导id
     * @apiParam {Number} beauticianId 选填，美容师id
     * @apiParam {String} recordTime 选填，记录时间 YY-MM-DD
     * 
     * @apiSuccess {Array} data 空值.
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     *       "result": 1,
     *       "token": "",
     *       "data": []
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
     * 定妆赠送消费使用
     */
    public function usePresentTicket(){
        $param = $this->param;
        $managerId = $this->user->id; //记录人id
        if(empty($managerId)){
            throw new ApiException('无法获取此登陆用户id');  
        }
        if(empty($param['articleCodeId'])){
            throw new ApiException('必传参数不能为空');    
        }
        $updateData = array();
        //皮肤
        if(!empty($param['skinConcerns'])){
            $updateData['skin_concerns'] = $param['skinConcerns'];
        }
        //原液
        // [{"type":"N5","nums":5},{"type":"N10","nums":5}];
        if(!empty($param['stoste'])){
            $updateData['stoste'] = json_encode($param['stoste']);
        }
        //备注
        if(!empty($param['remark'])){
            $updateData['remark'] = $param['remark'];
        }
        //美导
        if(!empty($param['guideId'])){
            $updateData['guide_id'] = $param['guideId'];
        }
        //美容师
        if(!empty($param['beauticianId'])){
            $updateData['beautician_id'] = $param['beauticianId'];
        }
        //记录时间
        if(!empty($param['recordTime'])){
            $updateData['record_time'] = $param['recordTime'];
        }        
        //判断券状态和活动验证状态
        $where = array(
           'present_article_code.article_code_id' => $param['articleCodeId']
        );
        $ticketCanUseRes =  PresentArticleCode::getPresentTicketCanUseStatusByWhere($where);
        //券可用
        if($ticketCanUseRes){
            //记录验证信息
            $res = PresentArticleCode::recordVerifyTicketInfo($managerId,$param['articleCodeId'],$updateData);
            if($res){
                Event::fire('powder.useArticleTicket','消费赠送券，活动赠送券记录id：'.$param['articleCodeId']);
                return $this->success();
            }
        }         
    }
    /**
     * @api {post} /powderArticles/editPresentUseInfo 11.编辑定妆赠送消费使用信息
     * 
     * @apiName editPresentUseInfo
     * @apiGroup PowderArticles
     *
     * @apiParam {Number} articleCodeId 必填，活动赠送券记录id
     * @apiParam {String} skinConcerns 选填，皮肤状况，多个使用,隔开。1 腰毛粗大   2皮肤暗沉  3 敏感脆弱   4干燥粗糙  5 细小皱纹   6痤疮痕迹   7皮肤松弛  8其他
     * @apiParam {array} stoste 选填，原液，array(0=>array('type'=>2,'nums'=>3),1=>array('type'=>2,'nums'=>3))
     * @apiParam {String} remark 选填，备注
     * @apiParam {Number} guideId 选填，美导id
     * @apiParam {Number} beauticianId 选填，美容师id
     * @apiParam {String} recordTime 选填，记录时间 YY-MM-DD
     * 
     * @apiSuccess {Array} data 空值.
     * 
     * @apiSuccessExample Success-Response:
     * 	{
     *       "result": 1,
     *       "token": "",
     *       "data": []
     *   }
     *
     *
     * @apiErrorExample Error-Response:
     * 		{
     *               "result": 0,
     *               "code": 0,
     *               "token": "",
     *               "msg" :"必传参数不能为空",
     *           }
     */
    /**
     * 编辑定妆赠送消费使用信息
     */
    public function editPresentUseInfo(){
        $param = $this->param;
        if(empty($param['articleCodeId']) || !isset($param['skinConcerns']) || !isset($param['stoste']) || !isset($param['remark']) || !isset($param['guideId'])|| !isset($param['beauticianId']) || !isset($param['recordTime'])){
            throw new ApiException('必传参数不能为空');    
        }        
        if(!empty($param['stoste']) && !is_array($param['stoste'])){
            throw new ApiException('stoste类型不对，必须为数组');    
        }
        $articleCodeId = $param['articleCodeId'];
        //记录编辑信息
        $data = array();
        //皮肤
        $data['skin_concerns'] = $param['skinConcerns']; 
        //原液
        // [{"type":"N5","nums":5},{"type":"N10","nums":5}];
        if(!empty($param['stoste'])){
            $data['stoste'] = json_encode($param['stoste']);
        }else{
            $data['stoste'] = ""; 
        }   
        //备注
        $data['remark'] = $param['remark'];        
        //美导
        $data['guide_id'] = $param['guideId'];       
        //美容师
        $data['beautician_id'] = $param['beauticianId'];
        //记录时间
        $data['record_time'] = $param['recordTime']; 
        //更新记录
        $res = PresentArticleCode::where('article_code_id','=',$articleCodeId)->update($data);
        if($res === false){
            throw new ApiException('编辑数据失败');    
        }
        Event::fire('powder.editPresentUseInfo','编辑使用记录，活动赠送券记录id：'.$param['articleCodeId']);
        return $this->success();
              
    }
    /**
     * 线上活动增加预约号记录
     * @param type $item_ordersn 定妆项目订单号
     * @param type $user_id 赠送给用户的用户id
     * @return int
     * @throws ApiException
     */
    public static function addReservateSnAfterConsume($item_ordersn,$user_id){
        Log::info("获取时间：".date('Y-m-d H:i:s',time())."--订单号：".$item_ordersn."--用户id:".$user_id);
        if(empty($item_ordersn) || empty($user_id)){
            throw new ApiException('必传参数不能为空');
        }
        //根据订单号获取消费人的order_user_id
        $bookingOrderInfo = BookingOrder::select('USER_ID')->where(array('ORDER_SN' => $item_ordersn))->first();
        if($bookingOrderInfo === null){
            Log::info('无法获取定妆消费用户,定妆消费用户订单号：'.$item_ordersn);
            throw new ApiException('无法获取定妆消费用户'); 
        }else{
            $bookingOrderInfoRes =  $bookingOrderInfo->toArray();
            $item_user_id = $bookingOrderInfoRes['USER_ID'];
        }
        $present_type = 1;  //默认消费赠送
        if($item_user_id != $user_id){
            //如果送给别人，消费类型就是推荐赠送
            $present_type = 2;
        }
        //获取用户手机号
        $userInfo = User::select('mobilephone','os_type')->where(array('user_id' => $user_id))->first();
        if($userInfo === null){
            Log::info('无法获取用户手机号,用户id:'.$user_id);
            throw new ApiException('无法获取用户手机号'); 
        }else{
            $userInfoRes =  $userInfo->toArray();
        }
        $mobilephone = $userInfoRes['mobilephone'];  
        //获取推荐码 和 赠送类型       
        $recommendCodeInfo = RecommendCodeUser::select('recommend_code','type')->where(array('user_id' => $user_id))->whereIn('type',[2, 3])->first();
        if($recommendCodeInfo === null){
            Log::info('用户无推荐码信息,用户id:'.$user_id);
        }else{
            $recommendCodeInfoRes =  $recommendCodeInfo->toArray();
            $recommend_code = $recommendCodeInfoRes['recommend_code'];
        }
        //获取活动信息
        $where = array(
            'article_status' => 1,
            'article_type' => 2,
        );
        $presentInfo = Present::getArticleInfoByWhere($where);
        //活动没有开始，不能赠送
        if(strtotime($presentInfo['start_at']) > time()){
            throw new ApiException('活动还没开始不能赠送');
        }else if(strtotime($presentInfo['end_at']) < time()){
            //截止日期后不能赠送
            throw new ApiException('活动截止后不能赠送');
        }else{
            $data['present_id'] = $presentInfo['present_id'];
            $data['item_id'] = $presentInfo['item_id'];
            $data['user_id'] = $user_id;
            $data['item_ordersn'] = $item_ordersn;
            $data['mobilephone'] = $mobilephone;
            if(isset($recommend_code)){
                $data['recommend_code'] = $recommend_code;
            }          
            $data['present_type'] = $present_type;
            //三个月内有效
            $data['expire_at'] = date("Y-m-d",strtotime("+3 month"))." 23:59:59";
            $data['created_at'] = time();
        }
        //获取内部订单号
        $data['ordersn'] = PresentArticleCode::getOrderSn();
        //获取预约号
        $reservateSnInfo = SeedPool::getReservateSnFromPool();
        $data['reservate_sn'] = $reservateSnInfo['reservateSn'];
        //获取赠送券号
        $articleTicketInfo = SeedPool::getArticleTicketFromPoolForOnline(1,'asc');
        $data['code'] = $articleTicketInfo[0];
        DB::beginTransaction();
        //活动券总数+1
        $resPreIncrement = Present::where($where)
                    ->increment('present.quantity',1);
        if(!$resPreIncrement){
            DB::rollBack();
            //记录错误日志
            Log::info('线上活动赠送券使用后，使用数增加失败');
            throw new ApiException('线上活动赠送券使用后,使用数增加失败');
        }
        //更新预约号
        $updateReservateSnRes = SeedPool::where(array('SEED' => $reservateSnInfo['reservateSn'],'TYPE' => 'TKT'))->update(array('STATUS' => 'USD'));
        if(!$updateReservateSnRes){
            DB::rollBack();
            Log::info("线上更新预约号失败:".$reservateSnInfo['reservateSn']);
            throw new ApiException('更新预约号失败');
        }
        //更新赠送券号
        $updateArticleTicketRes = SeedPool::where(array('SEED' => substr($articleTicketInfo[0],2),'TYPE' => 'GSN'))->update(array('STATUS' => 'USD'));
        if(!$updateArticleTicketRes){
            DB::rollBack();
            Log::info("线上更新赠送券号失败:".substr($articleTicketInfo[0],2));
            throw new ApiException('更新赠送券号失败');
        }
        //插入赠送券表cm_present_article_code
        $insertRes = PresentArticleCode::insertGetId($data);
        if(!$insertRes){
            DB::rollBack();
            Log::info("线上插入赠送券失败:".substr($articleTicketInfo[0],2));
            throw new ApiException('插入赠送券失败');
        }
        DB::commit();
        //发送短信 和 推送消息
        // 1. 获取被赠送人预约名称
        $presentUserInfo = BookingOrder::select('BOOKER_NAME')->where(array('USER_ID' => $user_id))->first();
        if($presentUserInfo === null){
            $bookingName = $mobilephone;
        }else{
            $presentUserInfoRes =  $presentUserInfo->toArray();
            $bookingName = $presentUserInfoRes['BOOKER_NAME'];
        }
        // 2. 获取赠送项目信息
        $beautyItemInfo = BeautyItem::select('name','price')->where(array('item_id' => $presentInfo['item_id']))->first();
        if($beautyItemInfo === null){
            Log::info("无法获取赠送项目相关信息,item_id:".$presentInfo['item_id']."--被赠送人id:".$user_id);
        }else{
            $beautyItemInfoRes = $beautyItemInfo->toArray();
            $beautyItemName = $beautyItemInfoRes['name'];
            $beautyItemPrice = $beautyItemInfoRes['price'];
        }
        //发短信
        if($beautyItemInfo){
            $thrift = new ThriftHelperModel();
            $sms = "亲爱的{$bookingName}，恭喜您获得臭美赠送价值{$beautyItemPrice}元的{$beautyItemName}免费体验1次，预约号为：{$reservateSnInfo['reservateSn']}。无须提前预约，到店提供预约号即可。下载臭美App，http://t.cn/RZXyLPg 了解更多。 退订回复TD";
            $res = $thrift->request('sms-center', 'sendSmsByType', array($mobilephone, $sms, '127.0.0.1', 6));
            $resultMsg = $res == 1 ? '成功' : '失败';
            $msg = '线上活动赠送项目发送信息 ： '.$resultMsg .' , 用户手机号码 ：' . $mobilephone . ' , 预约号：' . $reservateSnInfo['reservateSn'] ;
            Log::info( $msg );
        }
        
        //推送消息
        if( !empty($user_id) ){                       
            $dataPush['RECEIVER_USER_ID'] = $user_id;
            $dataPush['TYPE'] = 'USR';
            if( !empty( $userInfoRes['os_type']) && in_array($userInfoRes['os_type'], array(1,2))){
                $dataPush['OS_TYPE'] = $userInfoRes['os_type'] == 1 ? 'ANDROID':'IOS';
            }                
            $dataPush['TITLE'] = "消费成功，收到一份价值{$beautyItemPrice}元的消费奖励";
            $dataPush['MESSAGE'] = "你获得了臭美赠送的价值{$beautyItemPrice}元的{$beautyItemName}项目免费体验1次，点击查看";
            $dataPush['PRIORITY'] = 1;
            $dataPush['EVENT'] =  '{"event":"presentItem","userId":"'.$user_id.'","articleCodeId":"'.$insertRes.'","msgType":11}';
            $dataPush['STATUS'] = 'NEW';
            $dataPush['CREATE_TIME'] = date('Y-m-d H:i:s');
            $getPushId = Push::insert( $dataPush );
            if(!$getPushId){
                Log::info("推送消息插入失败，articleCodeId：".$insertRes."--用户id：".$user_id);
            }
        }
        return 1;
    }
    private function store_xls_onServer($filename,$header,$datas){
        Excel::create($filename, function($excel) use($datas,$header){
        $excel->setTitle('sheet');
            $excel->sheet('Sheet1', function($sheet) use($datas,$header){

                $sheet->fromArray($datas, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header);//添加表头

            });
        })->store('xls');
    }
    
    private function downloads($name){
        
        $file_dir = storage_path('exports/');
        if (!file_exists($file_dir.$name)){
            header("Content-type: text/html; charset=utf-8");
            echo "File not found!";
            exit; 
        } else {
            $file = fopen($file_dir.$name,"r"); 
            Header("Content-type: application/octet-stream");
            Header("Accept-Ranges: bytes");
            Header("Accept-Length: ".filesize($file_dir . $name));
            Header("Content-Disposition: attachment; filename=".$name);
            echo fread($file, filesize($file_dir.$name));
            fclose($file);
        }
    }
    
}
