<?php 
namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use DB;
use Excel;
use Event;
use App\Voucher;
use App\ThriftHelperModel;
use App\Exceptions\ApiException;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ERROR;
use Log;
use App\User;

class PlatformController extends Controller{
    private static  $DES_KEY = "authorlsptime20141225\0\0\0";
	
    /***
	 * @api {get} /platform/getItemType 1.获取项目分类名称
	 * @apiName getItemType
	 * @apiGroup Platform
	 *
	 *
	 * 
	 * @apiSuccess {Number} typeid 分类id.
	 * @apiSuccess {String} typename 分类名称
	 * 
	 * @apiSuccessExample Success-Response:
	 *	{
	 *		"result": 1,
	 *		"data": [
     *          {
     *              "typeid": 1,
     *              "typename": "洗剪吹"
     *          },
     *          ...
     *      ]
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function getItemType(){
        return $this->success( $this->_getItemType() );
    }
    /***
	 * @api {post} /platform/add 2.添加平台代金劵活动
	 * @apiName add
	 * @apiGroup Platform
	 *
	 *@apiParam {String} actName                必填        活动名称
	 *@apiParam {String} actNo                  必填        活动编号
	 *@apiParam {String} actIntro               必填        活动介绍
	 *@apiParam {Number} departmentId           必填        部门id
	 *@apiParam {Number} managerId              必填        部门负责人id
	 *@apiParam {Number} money                  必填        代金券金额
	 *@apiParam {Number} getSingleLimit         必填        每个用户获取的劵上限
     *@apiParam {Number} selectItemType         必填        选择用户    1. 新用户   2. 指定用户  3. 全平台用户   4. H5用户
     *@apiParam {Number} selectUseType          可选        选择用户类型    1.新用户 2. 首次消费  3.指定手机号  4.指定集团码用户  5.指定活动码用户 6.指定店铺码用户
     *@apiParam {String} code                   可选        输入码（集团码|活动码|店铺码）     
     *@apiParam {String} getItemTypes           可选        制定消费的项目获取格式如 ",1,3,"
     *@apiParam {String} useLimitTypes          可选        限制首单使用值为2
     *@apiParam {String} phoneList              可选        制定手机号时，手机号码列格式如 "13210553366,18566554455"
     *@apiParam {Number} enoughMoney            可选        满额可用
     *@apiParam {Number} totalNumber            可选        劵总数
     *@apiParam {Number} singleEnoughMoney      可选        项目满额获取
     *@apiParam {String} getTimeStart           可选        劵获取开始时间如 2015-10-16 00:00:00
     *@apiParam {String} getTimeEnd             可选        卷获取结束时间   2015-10-16 23:59:59
	 *@apiParam {String} addActLimitStartTime   可选        代金劵可使用开始时间 2015-10-16 00:00:00
	 *@apiParam {String} addActLimitEndTime     可选        代金劵可使用结束时间 2015-10-16 23:59:59
     *@apiParam {Number} fewDay                 可选        劵获取多少天内可用 （和上面劵可使用时间必须达到传其一）
     *@apiParam {String} limitItemTypes         可选        可使用的项目格式如 ",2,3,"
     *@apiParam {String} sendSms                可选        发送的短信内容
     * 
     * 
     * 
     * 
     * 
     * 
     * 
     * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": "",
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "添加失败"
	 *		}
	 ***/
    public function addVoucherConf(){
        $post = $this->param;
        // 必须填写的参数
        $mustKey = array(
            'actName','actIntro','departmentId','managerId',
            'money','getSingleLimit','actNo','selectItemType'
        );
        // 返回前端数组
        $result = array();
        foreach( $mustKey as $val ){
            if( empty($post[ $val ]) ) return $this->error('必填写项未填写' );
        }
        
        if( $post['selectItemType'] == 2 && $post['selectUseType'] == 3 && empty($post['phoneList']) )
            return $this->error( '选中手机号码,未提交手机号' );
        if( (empty($post['addActLimitStartTime']) && empty($post['addActLimitStartTime'])) && empty($post['fewDay']) )
            return $this->error('必填写项未填写' );
        
        $data = array();
        $data['vcTitle'] = $post['actName'];
        $data['vcSn'] = $post['actNo'];
        $data['vcRemark'] = $post['actIntro'];
        $exists = \App\VoucherConf::where(['vcSn'=>$data['vcSn']])->count();
        if( $exists ) return $this->error('存在活动编号，请勿重复提交');
        if( $post['selectItemType'] == 1 && in_array($post['selectUseType'],[1,2]) )
            $data['getTypes'] = $post['selectUseType'];
        elseif( $post['selectItemType'] == 2 && $post['selectUseType'] == 3 && !empty($post['phoneList']) ){
            $phoneList = explode(',',$post['phoneList']);
            $data['getTypes'] = 3;
        }elseif( $post['selectItemType'] == 2 && in_array($post['selectUseType'],[4,5,6]) && !empty($post['code']) ){
            $tt =  $post['selectUseType'];
            $data['getTypes'] = 0;
            // 检验验证码是否存在
            if( in_array( $tt , array(4,5,6) ) ){
                $tempArr = [4=>'getGroupExists',5=>'getActivityExists',6=>'getDividendExists'];
                $code = $post['code'];
                if( $this->{$tempArr[ $tt ]}( $code ) == 0 ) return $this->error('码不存在！');
                $tempArr1 = [4=>2,5=>3,6=>1];
                $data['getCodeType'] = $tempArr1[ $tt ];
                $data['getCode'] = $code;
                if( isset( $post['getItemTypes'] ) && !empty($post['getItemTypes'][0]))
                    $data['getItemTypes'] =','.join(',', $post['getItemTypes']).',';
            }
        }elseif( $post['selectItemType'] == 3 ){
            $data['getTypes'] = 4;
            if( isset( $post['getItemTypes'] ) && !empty($post['getItemTypes'][0]))
                $data['getItemTypes'] =  ','.join(',', $post['getItemTypes']).',';
        }elseif( $post['selectItemType'] == 4 ){
            $code = $post['code'];
            $data['getTypes'] = 5;
            if(!empty($code)) $data['getCode'] = $code;
        }else return $this->error('选择用户获取错误');
            
        // 定义代金劵
        $data['useMoney'] = $post['money'];
        $data['getNumMax'] = $post['getSingleLimit'];
        $data['DEPARTMENT_ID'] = $post['departmentId'];
        $data['MANAGER_ID'] = $post['managerId'];
        if( isset($post['singleEnoughMoney']) ) $data['getNeedMoney'] = $post['singleEnoughMoney'];
        if( isset($post['totalNumber']) ) $data['useTotalNum'] = $post['totalNumber'];
        if( isset($post['getTimeStart']) ) $data['getStart'] = strtotime($post['getTimeStart']);
        if( isset($post['getTimeEnd']) ) $data['getEnd'] = strtotime($post['getTimeEnd']);
        if( isset($post['limitItemTypes']) && !empty($post['limitItemTypes'][0]) ) $data['useItemTypes'] = ','.join(',',$post['limitItemTypes']).',';
        if( isset($post['useLimitTypes']) && !empty($post['useLimitTypes']) ) $data['useLimitTypes'] = $post['useLimitTypes'][0];
        if( isset($post['enoughMoney']) ) $data['useNeedMoney'] = $post['enoughMoney'];
        if( isset($post['sendSms']) ) $data['SMS_ON_GAINED'] = $post['sendSms'];
        
        if( isset($post['getTimeStart']) && isset($post['getTimeEnd']) && ( strtotime($post['getTimeStart']) > strtotime($post['getTimeEnd'])) )
            return $this->error('获取开始时间需小于获取结束时间');
       
        if( isset($post['money'])&&!empty($post['money']) && ($post['money']>999 ||$post['getSingleLimit']<1))
            return $this->error('券金额只能在1~999');
        if( isset($post['useNeedMoney'])&&!empty($post['useNeedMoney']) && $post['useNeedMoney']>999 )
            return $this->error('券总数只能在0~999');
        if( isset($post['totalNumber'])&&!empty($post['totalNumber']) && ($post['totalNumber']>999999 ||$post['totalNumber']<1))
            return $this->error('券总数只能在1~999999');
        if( isset($post['getSingleLimit'])&&!empty($post['getSingleLimit']) && ($post['getSingleLimit']>20 ||$post['getSingleLimit']<1))
            return $this->error('单个用户设置只能在1~20');
        if( isset($post['fewDay'])&&!empty($post['fewDay']) && ($post['fewDay']>999 ||$post['fewDay']<1))
            return $this->error('自定义有效期区间只能在1~999');
        
        if( isset($post['fewDay'])  && !empty($post['fewDay'])){
            $data['FEW_DAY'] = $post['fewDay'];
            $data['useStart'] = '0';
            $data['useEnd'] = '0';
        }elseif( isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && !empty($post['addActLimitStartTime'])  && !empty($post['addActLimitEndTime']) ){
            $data['useStart'] = strtotime($post['addActLimitStartTime']);
            $data['useEnd'] = strtotime($post['addActLimitEndTime']);
            $data['FEW_DAY'] = '';
        }
        
        $data['status'] = 2;
        $data['ADD_TIME'] = date('Y-m-d H:i:s');
        
        $addRes = \App\VoucherConf::insertGetId( $data );
        if( $data['getTypes'] == '3' ){
            $phoneArr = $phoneList;
            if( isset($data['FEW_DAY']) && !empty( $data['FEW_DAY']) ){
                $useStart = strtotime(date('Y-m-d') . ' 00:00:00');
                $useEnd = (intval( $data['FEW_DAY'] ) * 3600 * 24) + strtotime(date('Y-m-d') . ' 23:59:59');
            }elseif(isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && !empty($post['addActLimitStartTime'])  && !empty($post['addActLimitEndTime'])){
                $useStart = strtotime($post['addActLimitStartTime']);
                $useEnd = strtotime($post['addActLimitEndTime']);
            }
            $voucherData = array(
                'vcId' =>   $addRes,
                'vcSn' =>   $data['vcSn'],
                'vcTitle' => $data['vcTitle'],
                'vcTitle' => $data['vcTitle'],
                'vUseMoney' => $data['useMoney'],
                'vUseItemTypes' => isset($data['useItemTypes'])? $data['useItemTypes'] : '',
                'vUseLimitTypes' => isset($data['useLimitTypes']) ? $data['useLimitTypes'] : '',
                'vUseNeedMoney' => isset($data['useNeedMoney']) ? $data['useNeedMoney'] : '',
                'vUseStart' => isset($useStart)? $useStart :'',
                'vUseEnd' => isset($useEnd) ? $useEnd : '',
                'vAddTime' => time(),
            );
            // 记录已经发放的劵的数量
            $sendNum = 0;
            foreach( $phoneArr as $key => $val ){
                $voucherData['vMobilephone'] = $val;
                $voucherData['vStatus'] = 10;
                $voucherData['vSn'] = Voucher::getNewVoucherSn('CM');
                // 获取的劵的数量如果是多张需要插入多次
                $signerNum = $data['getNumMax']; // 每个用户最多领取的张数
                $limitNum = isset($data['useTotalNum']) ? $data['useTotalNum'] : 0; // 劵的可领的张数
                if( empty( $limitNum ) ){ // 未设置可领的张数即无限制
                    for( $i=0;$i<$signerNum;$i++ ){
                        $voucherData['vSn'] = Voucher::getNewVoucherSn('CM');
                        $addVoucher = \App\Voucher::insertGetId( $voucherData );
                        if(empty($addVoucher)) Log::info( "添加代金劵失败" .print_r($voucherData,true) );
                    }
                }
                // 如果 劵可领总数小于或等于个人可领数的情况
                if( !empty($limitNum) && $limitNum<=$signerNum){
                    for( $i=0;$i<$limitNum;$i++ ){
                        $voucherData['vSn'] =  Voucher::getNewVoucherSn('CM');
                        $addVoucher = \App\Voucher::insertGetId( $voucherData );
                        if(empty($addVoucher)) Log::info( "添加代金劵失败" .print_r($voucherData,true) );
                    }
                }

                // 如果 劵可领总数大于个人可领数的情况
                if( !empty($limitNum) && $limitNum>$signerNum){
                    for( $i=0;$i<$signerNum;$i++ ){
                        if( ($sendNum+1)<= $limitNum ){
                            $voucherData['vSn'] = Voucher::getNewVoucherSn('CM');
                            $addVoucher = \App\Voucher::insertGetId( $voucherData );
                            $sendNum++;
                            if(empty($addVoucher)) Log::info( "添加代金劵失败" .print_r($voucherData,true) );
                        }
                    }
                }
            }
        }
        Event::fire('platform.add','添加平台数据: '.$addRes);
        return $this->success();
    }
    /***
	 * @api {get} /platform/getRequestDepartment 3.获取申请部门分类
	 * @apiName getRequestDepartment
	 * @apiGroup Platform
	 *
	 *
	 * 
	 * @apiSuccess {Number} id 部门id.
	 * @apiSuccess {String} title 部门名称
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": [{
     *                  "id": 1,
     *                  "title": "总裁办"
	 *		    },
     *          ...
     *          ]
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function getRequestDepartment(){
        $departmant = \App\Department::select(['id','title'])->get()->toArray();
        $resurt = [];
        foreach( $departmant as $val ){
            $manager = \App\Manager::select(['id','name','department_id'])->where(['department_id'=>$val['id'],'status'=>1])->get()->toArray();
            if(!empty($manager)) $resurt[] = $val;
        }
        return $this->success( $resurt );
    }
    /***
	 * @api {get} /platform/getDepartmentManager/:id 4.获取部门分类的负责人
	 * @apiName getDepartmentManager
	 * @apiGroup Platform
	 *
	 * @apiParam {Number} id 部门id.
     * 
     * 
	 * 
	 * @apiSuccess {Number} id 部门负责人.
	 * @apiSuccess {String} name 部门负责人.
	 * @apiSuccess {Number} department_id 对应部门id.
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": [{
     *              "id": 117,
     *              "name": "1"
	 *		    },
     *          ...
     *          ]
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function getDepartmentManager($id){
        if($id == 0)
            $manager = \App\Manager::select(['id','name','department_id'])->where(['status'=>1])->get();
        else
            $manager = \App\Manager::select(['id','name','department_id'])->where(['department_id'=>$id,'status'=>1])->get();
        return $this->success( $manager );
    }
    /***
	 * @api {get} /platform/getActNum 5.获取活动编码
	 * @apiName getActNum
	 * @apiGroup Platform
	 *
     * 
     * 
	 * 
	 * @apiSuccess {String} actNo 活动码编号.
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data":  {
     *               "actNo": "cm475526"
     *           }
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function getActNum(){
        //11位  减小重复的几率
        $pre = substr(time(), 7);
        $end = '';
        for ($i = 0; $i <3; $i++) {
            $end .= rand(0, 9);
        }
        $code = "cm" . $pre  . $end;
        $count = \App\VoucherConf::where( 'vcSn' , '=' , $code )->count();
        if($count)  return $this->getActNum();
        $code = ['actNo'=>$code];
        return $this->success($code);
   }
    /***
	 * @api {get} /platform/checkSerial 6.获取 集团码|活动码|推荐码 是否存在
	 * @apiName checkSerial
	 * @apiGroup Platform
	 *
	 * @apiParam {Number} type 验证类型 1： 店铺码 2. 集团码 3. 活动码
	 * @apiParam {String} code 各类码.
     * 
     * 
	 * @apiSuccess {String} exists 1 存在 0 不存在.
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": {
     *              'exists':0
	 *		    }
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function checkSerial(){
        $post = $this->param;
        if( !isset($post['type']) || !isset($post['code']) )
            throw new ApiException('参数错误', ERROR::RECEIVABLES_ERROR);
        $type = $post['type'];
        $code = $post['code'];
        $exists = 0;
        if( $type == 1 ) 
            $exists = $this->getDividendExists( $code );
        if( $type == 2 )
            $exists = $this->getGroupExists( $code ); 
        if( $type == 3 )
            $exists = $this->getActivityExists( $code ); 
        $return = ['exists'=>$exists];
        return $this->success( $return );
    }
    /***
	 * @api {get} /platform/list 7.平台活动配置列表
	 * @apiName list
	 * @apiGroup Platform
	 *
	 * @apiParam {Number} selectItem 可选 选择的项 1: 活动编号 2.活动名称
	 * @apiParam {String} keyword 可选 对应项查询的字符.
	 * @apiParam {Number} status 可选 活动状态. 1. 进行中 2. 暂停 3.已关闭 4. 已结束
	 * @apiParam {String} department 部门id
	 * @apiParam {String} startTime 活动开始时间
	 * @apiParam {String} endTime 活动结束时间
	 * @apiParam {String} page 第几页
	 * @apiParam {String} pageSize 每页请求条数默认为12
     * 
     * 
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {String} vcId 平台配置id
	 * @apiSuccess {String} vcSn 活动编号
	 * @apiSuccess {String} vcTitle 活动名称
	 * @apiSuccess {String} addTime 添加时间
	 * @apiSuccess {String} department 申请部门
	 * @apiSuccess {String} status 1. 进行中 2. 暂停 3.已关闭 4. 已结束
	 * @apiSuccess {String} allNum 发放数
	 * @apiSuccess {String} useNum 使用数
	 * @apiSuccess {String} totalNum 代金劵可领总数
	 * @apiSuccess {String} actTime 活动时间
	 * 
     * 
	 * @apiSuccessExample Success-Response:
	 *		{
     *           "result": 1,
     *           "token": "",
     *           "data": {
     *               "total": 82,
     *               "per_page": 12,
     *               "current_page": 1,
     *               "last_page": 7,
     *               "next_page_url": "http://man.lu/platform/list/?page=2",
     *               "prev_page_url": null,
     *               "from": 1,
     *               "to": 12,
     *               "data": [
     *                   {
     *                       "vcId": 92,
     *                       "vcSn": cm222292,
     *                       "vcTitle": "我的测试哈哈哈",
     *                       "addTime": "0000-00-00 00:00:00",
     *                       "departmentId": 3,
     *                       "status": 2,
     *                       "allNum": 0,
     *                       "useNum": 0,
     *                       "totalNum": 0,
     *                       "actTime": "无限期活动"
     *                   },
     *                  ...
     *              }
     *          }
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function confList(){
        $post = $this->param;
        $actSelect = isset($post['selectItem']) ? $post['selectItem'] : '';
        $actNumber = isset($post['keyword']) ? urldecode($post['keyword']) : '';
        $actStatus = isset($post['status']) ? $post['status'] : '';
        $actDepartment = isset($post['department']) ? $post['department'] : '';
        $actStartTime = isset($post['startTime']) ? $post['startTime'] : '';
        $actEndTime = isset($post['endTime']) ? $post['endTime'] : '';
        $page = isset( $post['page'] ) ? $post['page'] : 1;
        $pageSize = isset( $post['pageSize'] ) ? $post['pageSize'] : 20;
        if( !empty($actEndTime))
            $actEndTime .= ' 23:59:59';
        if( isset($post['page_size']) && !empty($post['page_size']))
            $pageSize = $post['page_size'];
        if( empty($actSelect) && empty($actNumber) && empty($actStatus) && empty($actDepartment) && empty($actStartTime) && empty($actEndTime) ){
            //手动设置页数
            AbstractPaginator::currentPageResolver(function() use ($page) {
                return $page;
            });
            $res = \App\VoucherConf::select(['vcId','vcTitle','vcSn','ADD_TIME as addTime','getStart','getEnd','DEPARTMENT_ID','status','useEnd','useTotalNum as totalNum','getTypes','getNumMax'])
                    ->where(['vType'=>1,'IS_REDEEM_CODE'=>'N'])
                    ->orderBy('vcId','desc')
                    ->paginate($pageSize)
                    ->toArray();
            if( empty($res) ) return $this->success();
            $res = $this->handlerSearchDataList( $res );
            return $this->success( $res );
        }
        $actType = array('','vcSn','vcTitle');
        $obj = \App\VoucherConf::select(['vcId','vcTitle','vcSn','ADD_TIME as addTime','getStart','getEnd','DEPARTMENT_ID','status','useEnd','useTotalNum as totalNum']);
        $obj = $obj->where(['vType'=>1,'IS_REDEEM_CODE'=>'N']);
        if( !empty($actSelect) && !empty($actNumber) )
            $obj = $obj->where( $actType[ $actSelect ] , 'like' , "%".$actNumber."%" );
        
        if( !empty($actStatus) ){
            if( $actStatus != 4 )
                $obj = $obj->whereRaw(' ( status='.$actStatus.' AND ( getEnd=0 OR getEnd>'.time().'))');
            else
                $obj = $obj->whereRaw('getEnd !=0 AND getEnd < '.time());
        }
        if( !empty($actStartTime) && !empty($actEndTime))
            $obj = $obj->whereRaw(' ((getStart <= "'.strtotime($actStartTime) .'" and getEnd >= "'.strtotime($actStartTime) .'") or (getStart <= "'.strtotime($actEndTime) .'" and getEnd >= "'.strtotime($actEndTime) .'" ))');
        if( !empty( $actDepartment ) )
            $obj = $obj->where('DEPARTMENT_ID','=',$actDepartment);
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        $res = $obj->orderBy('vcId','desc')
                    ->paginate($pageSize)
                    ->toArray();
        if( empty($res) ) return $this->success();
            
        $res = $this->handlerSearchDataList( $res ,true, $actStatus );
        return $this->success( $res );
    }
    /***
	 * @api {get} /platform/actView/:id 8.平台活动概览
	 * @apiName actView
	 * @apiGroup Platform
	 *
	 * @apiParam {Number} id 平台活动id
     * 
     * 
     * 
	 * @apiSuccess {String} vcTitle 活动名称
	 * @apiSuccess {String} vcSn 活动编号
	 * @apiSuccess {Number} vcRemark 活动简介.
	 * @apiSuccess {String} status 1. 进行中 2. 暂停 3.已关闭 4. 已结束
	 * @apiSuccess {Number} actTime 活动时间.
	 * @apiSuccess {Number} department 部门.
	 * @apiSuccess {Number} manager 负责人.
	 * @apiSuccess {Number} totalNum 现金券总张数上限.
	 * @apiSuccess {String} budget 现金券预算总金额
	 * @apiSuccess {String} ADD_TIME 添加时间
	 * @apiSuccess {String} department 申请部门
	 * @apiSuccess {String} companyCode 指定集团获取
	 * @apiSuccess {String} activityCode 指定活动获取
	 * @apiSuccess {String} dividendCode 指定店铺获取
	 * @apiSuccess {String} allNum 已发放数
	 * @apiSuccess {String} allMoney 已发放金额
	 * @apiSuccess {String} invalidNum 已失效数
	 * @apiSuccess {String} useNumed 已使用数
	 * @apiSuccess {String} useMoneyed 已使用总金额
	 * @apiSuccess {String} consumeNum 已消费数
	 * @apiSuccess {String} consumeMoney 已消费数金额
	 * @apiSuccess {String} invalidNum 已失效数
	 * 
     * 
	 * @apiSuccessExample Success-Response:
	 *		{
     *           "result": 1,
     *           "token": "",
     *           "data": {
     *               "vcTitle": "指定项目7可以获取",
     *               "vcSn": "cm718745",
     *               "vcRemark": "顶顶顶顶",
     *               "status": 2,
     *               "actTime": "2015-07-08 00:00:00 - 2015-07-30 23:59:59",
     *               "department": "",
     *               "manager": "",
     *               "totalNum": 0,
     *               "budget": 0,
     *               "companyCode": "-",
     *               "activityCode": "-",
     *               "dividendCode": "-",
     *               "allNum": 128,
     *               "allMoney": 1280,
     *               "useNumed": 34,
     *               "useMoneyed": 340,
     *               "consumeNum": 0,
     *               "consumeMoney": 0,
     *               "invalidNum": 3
     *           }
     *       }
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function actView($id){
        $voucherConfInfo = \App\VoucherConf::select(['vcTitle','vcSn','vcRemark','getStart','getEnd','status','DEPARTMENT_ID','MANAGER_ID','useTotalNum','getCodeType','getCode','useMoney','getTypes'])
                ->where(['vcId'=>$id,'IS_REDEEM_CODE'=>'N','vType'=>1])
                ->first()
                ->toArray();
        
        $voucherConfInfo['actTime'] = '';
        if( empty( $voucherConfInfo['getStart'] ) && empty($voucherConfInfo['getEnd']) )
            $voucherConfInfo['actTime'] = '无限期活动';
        if( !empty($voucherConfInfo['getStart']) && empty($voucherConfInfo['getEnd']) )
            $voucherConfInfo['actTime'] = '开始时间：' . date('Y-m-d H:i:s', $voucherConfInfo['getStart']);
        if( !empty($voucherConfInfo['getEnd']) && empty($voucherConfInfo['getStart']) )
            $voucherConfInfo['actTime'] = '结束时间：' . date('Y-m-d H:i:s', $voucherConfInfo['getEnd']);
        if( !empty( $voucherConfInfo['getStart'] ) && !empty($voucherConfInfo['getEnd']) )
            $voucherConfInfo['actTime'] = date('Y-m-d H:i:s', $voucherConfInfo['getStart']) . ' - ' . date('Y-m-d H:i:s', $voucherConfInfo['getEnd']);
        $voucherConfInfo['department'] = '';
        if( !empty($voucherConfInfo['DEPARTMENT_ID']) ){
            $department = \App\Department::select(['title'])->where(['id'=>$voucherConfInfo['DEPARTMENT_ID']])->first();
            $voucherConfInfo['department'] = $department['title'];
        }
        $voucherConfInfo['manager'] = '';
        if( !empty($voucherConfInfo['MANAGER_ID']) ){
            $manager = \App\Manager::select(['name'])->where(['id'=>$voucherConfInfo['MANAGER_ID']])->first();
            $voucherConfInfo['manager'] = $manager['name'];
        }
        $voucherConfInfo['totalNum'] = '无上限';
        $voucherConfInfo['budget'] = ' - ';
        if( !empty( $voucherConfInfo['useTotalNum'] )){
            $voucherConfInfo['totalNum'] = $voucherConfInfo['useTotalNum'];
            $voucherConfInfo['budget'] = $voucherConfInfo['useTotalNum'] * $voucherConfInfo['useMoney'];
        }
        
        if( !empty($voucherConfInfo['getEnd']) && time() > $voucherConfInfo['getEnd'] ) $voucherConfInfo['status'] = 4;
        $voucherConfInfo['companyCode'] = '-';
        $voucherConfInfo['activityCode'] = '-';
        $voucherConfInfo['dividendCode'] = '-';
        if( !empty($voucherConfInfo['getCodeType']) ){
            $temp = ['','dividendCode','companyCode','activityCode'];
            $voucherConfInfo[ $temp[$voucherConfInfo['getCodeType']] ] = $voucherConfInfo['getCode'];
        }
        if( !empty($voucherConfInfo['getTypes']) && $voucherConfInfo['getTypes'] == 5 && !empty($voucherConfInfo['getCode']))
            $voucherConfInfo['activityCode'] = $voucherConfInfo['getCode'];
            
        // 劵情况统计情况
        $voucherConfInfo['allNum'] = 0;
        $voucherConfInfo['allMoney'] = 0;
        $voucherConfInfo['useNumed'] = 0;
        $voucherConfInfo['useMoneyed'] = 0;
        $voucherConfInfo['consumeNum'] = 0;
        $voucherConfInfo['consumeMoney'] = 0;
        $voucherConfInfo['invalidNum'] = 0;
        
        $totalNum = \App\Voucher::whereRaw( 'vcId='.$id )->count();
        if( empty($totalNum) )  return $this->success( $voucherConfInfo );
        
        $voucherConfInfo['allNum'] = $totalNum;
        $voucherConfInfo['allMoney'] = $totalNum * $voucherConfInfo['useMoney'];
        
        $useNumed = \App\Voucher::where( ['vcId'=>$id,'vStatus'=>2] )->count();
        $voucherConfInfo['useNumed'] = $useNumed;
        $voucherConfInfo['useMoneyed'] = $useNumed * $voucherConfInfo['useMoney'];
        
        $invalidNum = \App\Voucher::whereRaw( 'vcId = '.$id.' AND vStatus!=2 AND ( vStatus=5 OR vUseEnd<'.time().')' )->count();
        $voucherConfInfo['invalidNum'] = $invalidNum;
        
        // 查找已消费数
        if( !empty($useNumed) ){
            $order = Voucher::select(['vOrderSn'])->where( ['vcId'=>$id,'vStatus'=>2] )->get()->toArray();
            $consumeNum = 0;
            foreach( $order as $val ){
                $t = \App\Order::where(['ordersn'=>$val['vOrderSn'],'status'=>4])->count();
                if( $t ) $consumeNum++;
            }
            $voucherConfInfo['consumeNum'] = $consumeNum;
            $voucherConfInfo['consumeMoney'] = $consumeNum * $voucherConfInfo['useMoney'];
        }
        
        unset( $voucherConfInfo['getStart'] );
        unset( $voucherConfInfo['getEnd'] );
        unset( $voucherConfInfo['DEPARTMENT_ID'] );
        unset( $voucherConfInfo['MANAGER_ID'] );
        unset( $voucherConfInfo['useMoney'] );
        unset( $voucherConfInfo['useTotalNum'] );
        unset( $voucherConfInfo['getCodeType'] );
        unset( $voucherConfInfo['getCode'] );
        unset( $voucherConfInfo['getTypes'] );
            
        return $this->success( $voucherConfInfo );
        
    }
    /***
	 * @api {get} /platform/getInfo/:id 9.读取平台活动
	 * @apiName getInfo
	 * @apiGroup Platform
	 *
	 * @apiParam {Number} id 平台活动id
     * 
     * 
     * 
	 * @apiSuccess {Number} selectItemType 用户选择栏 1. 新用户 2. 指定用户 3.全平台用户 4.H5用户
	 * @apiSuccess {Number} vcId 活动配置
	 * @apiSuccess {String} actName 活动名称
	 * @apiSuccess {String} actNo 活动编号
	 * @apiSuccess {Number} actIntro 活动简介.
	 * @apiSuccess {Number} departmentId 部门.
	 * @apiSuccess {Number} managerId 负责人.
	 * @apiSuccess {Number} money 代金劵金额
	 * @apiSuccess {Number} totalNumber 代金券可领总数
	 * @apiSuccess {Number} limitItemTypes 限制可使用项目类别格式为（,1,2,）
	 * @apiSuccess {Number} useLimitTypes 使用限制类型 2 为限制首单
	 * @apiSuccess {Number} limitItemTypes 可使用的项目如 ,1,2,
	 * @apiSuccess {Number} enoughMoney 限制项目需满足金额才可使用
	 * @apiSuccess {String} addActLimitStartTime    可使用时间.起始(0 表示不限制)
	 * @apiSuccess {String} addActLimitEndTime      可使用时间.结束(0 表示不限制)
	 * @apiSuccess {Number} getTypes 用户获取条件(为空时表示不限制)1.用户注册，2.首次消费，3.手机号码 4.全平台用户 5.H5用户
	 * @apiSuccess {String} getItemTypes 可获取项目类别(多个用逗号隔开 为空时表示不限制)
	 * @apiSuccess {Number} getCodeType     可获取码类型 (1 店铺码 2集团码 3.活动码)(0 表示不限制)
	 * @apiSuccess {String} code         码
	 * @apiSuccess {Number} getSingleLimit       个人可获取最大券数
	 * @apiSuccess {String} getTimeStart 可获取时间 起始(0 表示不限制)
	 * @apiSuccess {String} getTimeEnd   可获取时间 结束(0 表示不限制)
	 * @apiSuccess {String} singleEnoughMoney 获取需满足金额(0表示不限制)
	 * @apiSuccess {String} sendSms 获取代金券时下发的短信内容
	 * @apiSuccess {Number} status              活动状态: 1正常 2暂停 3 关闭 4.已结束
	 * @apiSuccess {String} fewDay 获取代金劵后多少天内可用
	 * 
     * 
	 * @apiSuccessExample Success-Response:
	 *		{
     *           "result": 1,
     *           "token": "",
     *           "data": {
     *                       "vcId": 1,
     *                       "getSingleLimit": 1,
     *                       "actName": "试哈哈哈",
     *                       "actNo": "cm718745",
     *                       "actIntro": "这是一个简单的介绍",
     *                       "departmentId": 5,
     *                       "managerId": 3,
     *                       "money": 10,
     *                       "code": "",
     *                       "getItemTypes": ",7,",
     *                       "useLimitTypes": "",
     *                       "enoughMoney": 100,
     *                       "totalNumber": 0,
     *                       "singleEnoughMoney": 0,
     *                       "getTimeStart": 1436284800,
     *                       "getTimeEnd": 1438271999,
     *                       "addActLimitStartTime": 1437580800,
     *                       "addActLimitEndTime": 1437753599,
     *                       "fewDay": 12,
     *                       "getTypes": "0",
     *                       "sendSms": "",
     *                        "status": 1,
     *                        "getCodeType": 0,
     *                       "selectItemType": 2
     *           }
     *       }
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function getInfo($id){
        $voucherConfInfo = \App\VoucherConf::select(['vcId','getNumMax as getSingleLimit','vcTitle as actName','vcSn as actNo','vcRemark as actIntro'
            ,'DEPARTMENT_ID as departmentId','MANAGER_ID as managerId','useMoney as money','getCode as code','getItemTypes','useLimitTypes','useItemTypes as limitItemTypes'
            ,'useNeedMoney as enoughMoney','useTotalNum as totalNumber' ,'getNeedMoney as singleEnoughMoney','getStart as getTimeStart','getEnd as getTimeEnd'
            ,'useStart as addActLimitStartTime','useEnd as addActLimitEndTime','FEW_DAY as fewDay','getTypes','SMS_ON_GAINED as sendSms','getCodeType','status'])
                ->where(['vcId'=>$id,'IS_REDEEM_CODE'=>'N','vType'=>1])
                ->first()
                ->toArray();
        if( in_array($voucherConfInfo['getTypes'],[1,2]) )
            $voucherConfInfo['selectItemType'] = 1;
        if( $voucherConfInfo['getTypes'] == 3 ){
            $voucherConfInfo['selectItemType'] = 2;
            $phoneList = \App\Voucher::select(['vMobilephone'])->where(['vcId'=>$id])->get();
            $temp = [];
            foreach( $phoneList as $val ){
                $temp[] = $val['vMobilephone'];
            }
            $voucherConfInfo['phoneList'] = $temp;
        }
        
        if( empty($voucherConfInfo['getTypes']) && ( in_array($voucherConfInfo['getCodeType'],[1,2,3]) || $voucherConfInfo['getItemTypes']) ){
            $voucherConfInfo['selectItemType'] = 2;
        }
        if( $voucherConfInfo['getTypes'] == 4 )
            $voucherConfInfo['selectItemType'] = 3;
        if( $voucherConfInfo['getTypes'] == 5 )
            $voucherConfInfo['selectItemType'] = 4;
        if( !empty($voucherConfInfo['getTimeEnd']) && ($voucherConfInfo['getTimeEnd'] < time()) )
            $voucherConfInfo['status'] = 4;
        if( !empty($voucherConfInfo['getTimeStart']) )
            $voucherConfInfo['getTimeStart'] = date('Y-m-d H:i:s',$voucherConfInfo['getTimeStart']);
        if( !empty($voucherConfInfo['getTimeEnd']) )
            $voucherConfInfo['getTimeEnd'] = date('Y-m-d H:i:s',$voucherConfInfo['getTimeEnd']);
        if( !empty($voucherConfInfo['addActLimitStartTime']))
            $voucherConfInfo['addActLimitStartTime'] = date('Y-m-d H:i:s',$voucherConfInfo['addActLimitStartTime']);
        if( !empty($voucherConfInfo['addActLimitEndTime']))
            $voucherConfInfo['addActLimitEndTime'] = date('Y-m-d H:i:s',$voucherConfInfo['addActLimitEndTime']);
        if( !empty($voucherConfInfo['getItemTypes']) )
            $voucherConfInfo['getItemTypes'] = rtrim(ltrim($voucherConfInfo['getItemTypes'],','),',');
        if( !empty($voucherConfInfo['limitItemTypes']) )
            $voucherConfInfo['limitItemTypes'] = rtrim(ltrim($voucherConfInfo['limitItemTypes'],','),',');
        return $this->success( $voucherConfInfo );
    }
    /***
	 * @api {post} /platform/editConf 10.编辑平台代金劵活动
	 * @apiName editConf
	 * @apiGroup Platform
	 *
	 *@apiParam {String} vcId                   必填        活动配置id
	 *@apiParam {String} actName                可选        活动名称
	 *@apiParam {String} actIntro               可选        活动介绍
	 *@apiParam {Number} departmentId           可选        部门id
	 *@apiParam {Number} managerId              可选        部门负责人id
     *@apiParam {String} useLimitTypes          可选        限制首单使用值为2
     *@apiParam {String} limitItemTypes         可选        可使用的项目格式如 ",2,3,"
     *@apiParam {Number} enoughMoney            可选        满额可用
     *@apiParam {String} getTimeStart           可选        劵获取开始时间如 2015-10-16 00:00:00
     *@apiParam {String} getTimeEnd             可选        卷获取结束时间   2015-10-16 23:59:59
	 *@apiParam {String} addActLimitStartTime   可选        代金劵可使用开始时间 2015-10-16 00:00:00
	 *@apiParam {String} addActLimitEndTime     可选        代金劵可使用结束时间 2015-10-16 23:59:59
     *@apiParam {Number} fewDay                 可选        劵获取多少天内可用 （和上面劵可使用时间必须达到传其一）
     *@apiParam {String} sendSms                可选        发送的短信内容
     *@apiParam {Number} singleEnoughMoney      可选        项目满额获取
     * 
     * 
     * 
     * 
     * 
     * 
     * 
     * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": "",
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "添加失败"
	 *		}
	 ***/
    public function editConf(){
        $post = $this->param;
        if( empty( $post['vcId'] ) )
            throw new ApiException('参数错误', ERROR::RECEIVABLES_ERROR);
        $id = $post['vcId'];
        
        $data = array();
        if( isset( $post['actName'] ) )  $data['vcTitle'] = $post['actName'];
        if( isset( $post['actIntro'] ) ) $data['vcRemark'] = $post['actIntro'];
        if( isset( $post['departmentId'] ) ) $data['DEPARTMENT_ID'] = $post['departmentId'];
        if( isset( $post['managerId'] ) ) $data['MANAGER_ID'] = $post['managerId'];
        if( isset($post['getTimeStart']) ) $data['getStart'] = strtotime($post['getTimeStart']);
        if( isset($post['getTimeEnd']) ) $data['getEnd'] = strtotime($post['getTimeEnd']);
        if( isset($post['fewDay']) ) $data['FEW_DAY'] = $post['fewDay'];
        if( isset($post['addActLimitStartTime']) ) $data['useStart'] = strtotime($post['addActLimitStartTime']);
        if( isset($post['addActLimitEndTime']) ) $data['useEnd'] = strtotime($post['addActLimitEndTime']);
        if( isset($post['limitItemTypes']) ){
            if( !empty($post['limitItemTypes'][0]) )
                $data['useItemTypes'] =  ','.  ltrim(rtrim(join(',',$post['limitItemTypes']),','),',').',' ;
            else
                $data['useItemTypes'] = '';
        } 
        if( isset($post['useLimitTypes']) ){ 
            if( !empty($post['useLimitTypes'][0])){
                $data['useLimitTypes'] = $post['useLimitTypes'][0];
            }else{
                $data['useLimitTypes'] = '';
            }
        }
        if( isset($post['enoughMoney']) ) $data['useNeedMoney'] = $post['enoughMoney'];
        if( isset( $post['getSingleLimit'] ) )  $data['getNumMax'] = $post['getSingleLimit'];
        if( isset($post['sendSms']) ) $data['SMS_ON_GAINED'] = $post['sendSms'];
        if( isset($post['singleEnoughMoney']) ) $data['getNeedMoney'] = $post['singleEnoughMoney'];
        
        if( isset($post['useNeedMoney'])&&!empty($post['useNeedMoney']) && $post['useNeedMoney']>999)
            return $this->error('券总数只能在0~999');
        if( isset($post['totalNumber'])&&!empty($post['totalNumber']) && ($post['totalNumber']>999999 ||$post['totalNumber']<1))
            return $this->error('券总数只能在1~3000');
        if( isset($post['getSingleLimit'])&&!empty($post['getSingleLimit']) && ($post['getSingleLimit']>20 ||$post['getSingleLimit']<1))
            return $this->error('单个用户设置只能在1~20');
        if( isset($post['fewDay'])&&!empty($post['fewDay']) && ($post['fewDay']>999 ||$post['fewDay']<1))
            return $this->error('自定义有效期区间只能在1~999');
        if( isset($post['fewDay'])  && !empty($post['fewDay'])){
            $data['FEW_DAY'] = $post['fewDay'];
            $data['useStart'] = '0';
            $data['useEnd'] = '0';
        }elseif( isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && !empty($post['addActLimitStartTime'])  && !empty($post['addActLimitEndTime']) ){
            $data['useStart'] = strtotime($post['addActLimitStartTime']);
            $data['useEnd'] = strtotime($post['addActLimitEndTime']);
            $data['FEW_DAY'] = '';
        }
        if( isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && isset($post['fewDay']) && empty($post['addActLimitStartTime']) && empty($post['addActLimitEndTime']) && empty($post['fewDay'])){
            return $this->error('限制时间设置错误');
        }
        if(!isset($post['addActLimitStartTime']) && !isset($post['addActLimitEndTime']) && isset($post['fewDay']) && empty($post['fewDay'])){
            return $this->error('限制时间设置错误');
        }
        $addRes = \App\VoucherConf::where(['vcId'=>$id])->update( $data );
        
        Event::fire('platform.editConf','编辑平台数据id：'.$id);
        return $this->success();
    }
    /***
	 * @api {get} /platform/offlineConf/{:id} 11.编辑平台下线操作
	 * @apiName offlineConf
	 * @apiGroup Platform
	 *
	 *@apiParam {Number} id                   必填        活动配置id
     * 
     * 
     * 
     * 
     * 
     * 
     * 
     * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": "",
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "下线失败，请重新下线"
	 *		}
	 ***/
    public function offlineConf( $id ){
        $update = \App\VoucherConf::where(['vcId'=>$id])->update(['status'=>2]);
        Event::fire('platform.offlineConf','下线平台活动id: '.$id);
        return $this->success();
    }
    /***
	 * @api {get} /platform/closeConf/{:id} 12.编辑平台关闭操作
	 * @apiName closeConf
	 * @apiGroup Platform
	 *
	 *@apiParam {Number} id                   必填        活动配置id
     * 
     * 
     * 
     * 
     * 
     * 
     * 
     * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": "",
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "关闭失败，请重新下线"
	 *		}
	 ***/
    public function closeConf( $id ){
        $conf = \App\VoucherConf::where(['vcId'=>$id])->update(['status'=>3]);
        if( $conf )
            \App\Voucher::where(['vcId'=>$id])->whereIn('vStatus',[1,3])->update(['vStatus'=>5]);
        Event::fire('platform.closeConf','关闭平台活动id: '.$id);
        return $this->success();
    }
    /***
	 * @api {get} /platform/upConf/{:id} 13.配置平台活动上线操作
	 * @apiName upConf
	 * @apiGroup Platform
	 *
	 *@apiParam {Number} id                   必填        活动配置id
     * 
     * 
     * 
     * 
     * 
     * 
     * 
     * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": "",
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "关闭失败，请重新下线"
	 *		}
	 ***/
    public function upConf($vcId){
        // 修改配置表中为已上线状态
        \App\VoucherConf::where(['vcId'=>$vcId])->update(['status'=>1]);
        // 修改voucher表中手机是否已经注册
        $phoneList = \App\Voucher::select(['vMobilephone'])->where(['vStatus'=>10,'vcId'=>$vcId])->get()->toArray();
        //判断当前活动是否勾选了消费类项目
        $voucherConf = \App\VoucherConf::select(['getItemTypes','getNeedMoney','getStart','getEnd'])->where(['vcId'=>$vcId])->first()->toArray();
        
        $getItemTypes=$voucherConf['getItemTypes'];
        $getNeedMoney=$voucherConf['getNeedMoney'];
        
        foreach( $phoneList as $val ){
            $voucherData = array();
            $where1 = 'vStatus=10 AND vMobilephone ="'.$val['vMobilephone'].'"';
            $voucherStatus = User::verifyUserPhoneExists($val['vMobilephone']);
            // 统一处理 不管有无注册过
            $nowTime=  time();
            // 其中一个不为空则有消费类型  将状态改为3 待激活   
            if(!empty($getItemTypes)||!empty($getNeedMoney)||(!empty($voucherConf['getStart']) && $nowTime< $voucherConf['getStart'])){  
                $voucherData['vStatus']=3;
            }else{ 
               $voucherData['vStatus'] = empty( $voucherStatus ) ? 3 : 1;
            }
            $voucherData['vUserId'] = empty( $voucherStatus ) ? 0 : $voucherStatus['user_id'];
            \App\Voucher::whereRaw( $where1 )->update( $voucherData );
        }
        $this->verifyPhone( $vcId );
        Event::fire('platform.upConf','上线平台活动id: '.$vcId);
        return $this->success();
    }
    /***
	 * @api {get} /platform/exportList 14.导出平台活动配置列表
	 * @apiName exportList
	 * @apiGroup Platform
	 *
	 * @apiParam {Number} selectItem 可选 选择的项 1: 活动编号 2.活动名称
	 * @apiParam {String} keyword 可选 对应项查询的字符.
	 * @apiParam {Number} status 可选 活动状态. 1. 进行中 2. 暂停 3.已关闭 4. 已结束
	 * @apiParam {String} department 部门id
	 * @apiParam {String} startTime 活动开始时间
	 * @apiParam {String} endTime 活动结束时间
	 * @apiParam {String} page 第几页
	 * @apiParam {String} pageSize 每页请求条数默认为12
     * 
     * 
	 * 
     * 
	 * @apiSuccessExample Success-Response:
	 *		{
     *           "result": 1,
     *           "token": "",
     *           "data": ""
     *      }
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 ***/
    public function exportList(){
        $post = $this->param;
        $actSelect = isset($post['selectItem']) ? $post['selectItem'] : '';
        $actNumber = isset($post['keyword']) ? urldecode($post['keyword']) : '';
        $actStatus = isset($post['status']) ? $post['status'] : '';
        $actDepartment = isset($post['department']) ? $post['department'] : '';
        $actStartTime = isset($post['startTime']) ? $post['startTime'] : '';
        $actEndTime = isset($post['endTime']) ? $post['endTime'] : '';
        $page = isset( $post['page'] ) ? $post['page'] : 1;
        $pageSize = isset( $post['pageSize'] ) ? $post['pageSize'] : 20;
        if( !empty($actEndTime))
            $actEndTime .= ' 23:59:59';
        if( isset($post['page_size']) && !empty($post['page_size']))
            $pageSize = $post['page_size'];
        if( empty($actSelect) && empty($actNumber) && empty($actStatus) && empty($actDepartment) && empty($actStartTime) && empty($actEndTime) ){
            AbstractPaginator::currentPageResolver(function() use ($page) {
                return $page;
            });
            $res = \App\VoucherConf::select(['vcId','vcTitle','vcSn','ADD_TIME as addTime','getStart','getEnd','DEPARTMENT_ID','status','useEnd','useTotalNum as totalNum'])
                    ->where(['vType'=>1,'IS_REDEEM_CODE'=>'N'])
                    ->orderBy('vcId','desc')
                    ->paginate($pageSize)
                    ->toArray();
            if( empty($res) ) return $this->success();
            $tempData = $this->handleList( $res['data'] );
            unset( $res );
            $title = '现金劵活动查询列表' .date('Ymd');
            Event::fire('platform.exportList','导出平台列表数据');
            //导出excel	   
            $header = ['活动名称','活动编码','总数上限','已发放数','已使用数','创建时间','活动时间','申请部门','活动状态'];
            Excel::create($title, function($excel) use($tempData,$header){
                $excel->sheet('Sheet1', function($sheet) use($tempData,$header){
                    $sheet->fromArray($tempData, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
                    $sheet->prependRow(1, $header);//添加表头
                });
            })->export('xls');
            exit;
        }
        $actType = array('','vcSn','vcTitle');
        $obj = \App\VoucherConf::select(['vcId','vcTitle','vcSn','ADD_TIME as addTime','getStart','getEnd','DEPARTMENT_ID','status','useEnd','useTotalNum as totalNum']);
        $obj = $obj->where(['vType'=>1,'IS_REDEEM_CODE'=>'N']);
        if( !empty($actSelect) && !empty($actNumber) )
            $obj = $obj->where( $actType[ $actSelect ] , 'like' , "%".$actNumber."%" );
        
        if( !empty($actStatus) ){
            if( $actStatus != 4 )
                $obj = $obj->whereRaw('( status='.$actStatus.' AND ( getEnd=0 OR getEnd>'.time().'))');
            else
                $obj = $obj->whereRaw('getEnd !=0 AND getEnd < '.time());
        }
        if( !empty($actStartTime) && !empty($actEndTime))
            $obj = $obj->whereRaw(' （(getStart <= "'.strtotime($actStartTime) .'" and getEnd >= "'.strtotime($actStartTime) .'") or (getStart <= "'.strtotime($actEndTime) .'" and getEnd >= "'.strtotime($actEndTime) .'" ))');
        if( !empty( $actDepartment ) )
            $obj = $obj->where('DEPARTMENT_ID','=',$actDepartment);
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        $res = $obj->orderBy('vcId','desc')->paginate($pageSize)->toArray();
        if( empty($res) ) return $this->success();
            
        $tempData = $this->handleList( $res['data'] );
        unset( $res );
        $title = '代金劵查询列表' .date('Ymd');
        //导出excel	   
        Event::fire('platform.exportList','导出平台列表数据');
        $header = ['活动名称','活动编码','总数上限','已发放数','已使用数','创建时间','活动时间','申请部门','活动状态'];
        Excel::create($title, function($excel) use($tempData,$header){
            $excel->sheet('Sheet1', function($sheet) use($tempData,$header){
                $sheet->fromArray($tempData, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header);//添加表头
            });
        })->export('xls');
        exit;
    }
    // 校验集团码
    private function getGroupExists( $code ){
        $count = \App\CompanyCode::where( 'code' ,'=', $code )
                ->where('status','=',1)
                ->count();
        return empty( $count ) ? 0 : 1;
    }
    // 校验推荐码
    private function getDividendExists( $code ){
        $count = \App\Dividend::where( 'status','=','0' )
                ->where('recommend_code','=',$code)
                ->where('activity','=',2)
                ->count();
        return empty( $count ) ? 0 : 1;
    }
    // 校验活动码
    private function getActivityExists( $code ){
        $count = \App\Dividend::where( 'status','=','0' )
                ->where('recommend_code','=',$code)
                ->where('activity','=',1)
                ->count();
        return empty( $count ) ? 0 : 1;
    }
    // 获取分类
    private function _getItemType(){
        // 这里用于 代金劵和配置中会和前端约定 增加一个项目特价类型为typeid为101
        $itemType = \App\SalonItemType::select(['typeid','typename'])
                ->where('status','=',1)
                ->orderBy('sortIt','DESC')
                ->get()
                ->toArray();
//        array_unshift( $itemType , array('typeid'=>101,'typename'=>'限时特价 ') );
        return $itemType;
    }
   // 获取代金劵状态
   private function getVoucherStatusByActId( $vcId ){
        $count = \App\Voucher::where(['vcId'=>$vcId])->count();
        if( !$count )
            return [0,0,0];
        $useEnd = \App\Voucher::select(['vUseEnd'])->where(['vcId'=>$vcId])->first();
        if( empty( $useEnd ) )
            return [0,0,0];
        $useEnd = $useEnd->toArray();
        if( empty( $useEnd ) )
            return [0,0,0];
        $useEnd = $useEnd['vUseEnd'];
        if( $count<1000 ) return $this->getSelectVoucherStatus($vcId,$useEnd);
        else return $this->getCountVoucherStatus($vcId,$useEnd);
            
        
    }
    private function getSelectVoucherStatus($vcId,$useEnd){
        $result = \App\Voucher::select(['vStatus'])->where(['vcId'=>$vcId])->get();
        $result = $result->toArray();
        $totalNum = 0;
        $useNum = 0;
        $invalidNum = 0;
        foreach( $result as $val ){
            if( $val['vStatus'] != 10 )
                $totalNum++;
            if( $val['vStatus'] == 2 )
                $useNum++;
            if( $val['vStatus'] == 5 )
                $invalidNum++;
        }
        if( !empty($invalidNum) )
            return array( $totalNum , $useNum , $invalidNum );
        // 已失效数
        if( empty($useEnd) ||  time()<$useEnd ){
            $invalidNum = 0;
        }else{
            $invalidNum = $totalNum - $useNum;
        }
        return array( $totalNum , $useNum , $invalidNum );
    }

    private function getCountVoucherStatus($vcSn,$useEnd){
        $totalNum = 0;
        $useNum = 0;
        $invalidNum = 0;
        $totalNum = \App\Voucher::where( 'vStatus','<>',10 )->where(['vcId'=>$vcSn])->count();
        $useNum = \App\Voucher::where( 'vStatus','=',2 )->where(['vcId'=>$vcSn])->count();
        $invalidNum = \App\Voucher::where( 'vStatus','=',5 )->where(['vcId'=>$vcSn])->count();
        if( !empty($invalidNum) )
            return array( $totalNum , $useNum , $invalidNum );
        // 已失效数
        if( empty($useEnd) ||  time()<$useEnd ){
            $invalidNum = 0;
        }else{
            $invalidNum = $totalNum - $useNum;
        }
        return array( $totalNum , $useNum , $invalidNum );
    }


    // 校验是否为手机号码添加 且为未激活状态的 如果是 那么发送短信提醒
    private function verifyPhone( $vcId ){
        // 找到用户获取的类型
        $getTypes = \App\VoucherConf::select(['getItemTypes','getNeedMoney','getStart','getEnd','useEnd','SMS_ON_GAINED','useMoney'])
                ->where(['vcId'=>$vcId,'getTypes'=>3,'status'=>1])
                ->first();
        if( empty($getTypes) )
            return false;
        $getTypes = $getTypes->toArray();
        $nowTime = time();
        // 存在开始时间  且 现在的时间小于劵获取开始时间
        $nowItStart = (!empty($getTypes['getStart']) && $nowTime < $getTypes['getStart']);
        // 存在接在结束时间 且 现在的时间大于劵获取的结束时间
        $nowGtEnd = (!empty($getTypes['getEnd']) && $nowTime > $getTypes['getEnd']);
        $getNeedMoney = $getTypes['getNeedMoney'];
        $getItemType = $getTypes['getItemTypes'];
        $sms = $getTypes['SMS_ON_GAINED'];

        if( !empty($getItemType) || !empty($getNeedMoney)  || $nowItStart || $nowGtEnd )
            return false;
        
        // 找到活动对应的手机号码
        $phoneList = \App\Voucher::select(['vMobilephone','vcTitle','vUseMoney','vUseEnd'])->whereRaw('vcId='.$vcId.' and ( vStatus=3 or vStatus=1)')->get()->toArray();

        $userMoney = $phoneList[0]['vUseMoney'];
        $vcTitle = $phoneList[0]['vcTitle'];
        $useEnd = date('Y-m-d H:i:s',$phoneList[0]['vUseEnd']);
        $pushUseEnd = date('Y-m-d',$phoneList[0]['vUseEnd']);
        $osType = array( '','ANDROID','IOS' );
        
        foreach($phoneList as $val){
            $successMsg = date('Y-m-d H:i:s') . " 代金劵发送短信的手机号码成功的有 " . $val['vMobilephone'];
            $errMsg = date('Y-m-d H:i:s') .  "代金劵发送短信的手机号码失败的有" . $val['vMobilephone'];
            if( !empty($sms) ){
                $sms = str_replace(['[useMoney]','[name]','[overtime]'], [$userMoney,$vcTitle,$useEnd], $sms);
//				改用thrift调用发送短信
				$thrift = new ThriftHelperModel();
				$res = $thrift->request('sms-center', 'sendSmsByType', array($val['vMobilephone'], $sms, '127.0.0.1', 3));
				$resultMsg = $res == 1 ? '成功' : '失败';
                $msg = '代金劵发送信息 ： '.$resultMsg .' , 用户手机号码 ：' . $val['vMobilephone'] . ' , 用户信息：' . $sms ;
				Log::info( $msg );
            }
            // 写入到推送表
            $userId = User::verifyUserPhoneExists( $val['vMobilephone'] );
            if( !empty($userId) ){
                $dataPush = array();
                $dataPush['RECEIVER_USER_ID'] = $userId['user_id'];
                $dataPush['TYPE'] = 'USR';
                if( !empty( $osType[ $userId['os_type'] ] ) )
                    $dataPush['OS_TYPE'] = $osType[ $userId['os_type'] ];
                $dataPush['TITLE'] = '您获得了一张代金券';
                $dataPush['MESSAGE'] = '您获得了一张价值￥'. $userMoney .'的代金券，'. $pushUseEnd .'前使用有效，赶快去消费吧(点击查看详情)。';
                $dataPush['PRIORITY'] = 1;
                $dataPush['EVENT'] = '{"event":"voucherList","userId":"'.$userId['user_id'].'","msgType":"6"}';
                $dataPush['STATUS'] = 'NEW';
                $dataPush['CREATE_TIME'] = date('Y-m-d H:i:s');

                \App\Push::insertGetId( $dataPush );
            }
        }
    }
    // 出来列表数据
    private function handleList( $res ){
        $tempData = [];
        $department = '';
//        $i=0;
        $statusArr = ['','进行中','下线','已关闭'];
        foreach( $res as $key=>$val ){
            $statistics = $this->getVoucherStatusByActId($val['vcSn'], $val['useEnd']);
            if( empty( $val['getStart'] ) && empty($val['getEnd']) )
                $actTime = '无限期活动';
            if( !empty($val['getStart']) && empty($val['getEnd']) )
                $actTime = '开始时间：' . date('Y-m-d H:i:s', $val['getStart']);
            if( !empty($val['getEnd']) && empty($val['getStart']) )
                $actTime = '结束时间：' . date('Y-m-d H:i:s', $val['getEnd']);
            if( !empty( $val['getStart'] ) && !empty($val['getEnd']) )
                $actTime = date('Y-m-d H:i:s', $val['getStart']) . ' - ' . date('Y-m-d H:i:s', $val['getEnd']);
            $res['data'][$key]['department'] = '';
            if( !empty($val['DEPARTMENT_ID']) ){
                $department = \App\Department::select(['title'])->where(['id'=>$val['DEPARTMENT_ID']])->first();
                $department = $department['title'];
            }
//            $tempData[$key][] = ++$i;
            
            $tempData[$key][] = $val['vcTitle'];
            $tempData[$key][] = $val['vcSn'];
            $tempData[$key][] = empty($val['totalNum']) ? '无限制' : $val['totalNum'];
            $tempData[$key][] = empty($statistics[0]) ? '0' : $statistics[0];
            $tempData[$key][] = empty($statistics[1]) ? '0' : $statistics[1];
            $tempData[$key][] = $val['addTime'];
            $tempData[$key][] = $actTime;
            $tempData[$key][] = $department;
            if( $val['status'] != 2 &&  !empty($val['getEnd']) && $val['getEnd']<time() )
                $tempData[$key][] = '已结束';
            else
                $tempData[$key][] = $statusArr[ $val['status'] ];
        }
        return $tempData;
    }
    // 处理列表返回的搜索条件数据
    private function handlerSearchDataList( $res , $searchFlag = false, $actStatus='' ){
        foreach( $res['data'] as $key=>$val ){
            $statistics = $this->getVoucherStatusByActId($val['vcId']);
            $res['data'][$key]['allNum'] = $statistics[0];
            $res['data'][$key]['useNum'] = $statistics[1];
            $res['data'][$key]['invalidNum'] = $statistics[2];
            $res['data'][$key]['actTime'] = '';
            if( empty( $val['getStart'] ) && empty($val['getEnd']) )
                $res['data'][$key]['actTime'] = '无限期活动';
            if( !empty($val['getStart']) && empty($val['getEnd'])  && strlen($val['getStart'])>8)
                $res['data'][$key]['actTime'] = '开始时间：' . date('Y-m-d H:i:s', $val['getStart']);

            if( !empty($val['getEnd']) && empty($val['getStart']) && strlen($val['getEnd'])>8)
                $res['data'][$key]['actTime'] = '结束时间：' . date('Y-m-d H:i:s', $val['getEnd']);
            if( !empty( $val['getStart'] ) && !empty($val['getEnd'])  && strlen($val['getEnd'])>8 && strlen($val['getStart'])>8)
                $res['data'][$key]['actTime'] = date('Y-m-d H:i:s', $val['getStart']) . ' - ' . date('Y-m-d H:i:s', $val['getEnd']);
            $res['data'][$key]['department'] = '';
            if( !empty($val['DEPARTMENT_ID']) ){
                $department = \App\Department::select(['title'])->where(['id'=>$val['DEPARTMENT_ID']])->first();
                $res['data'][$key]['department'] = $department['title'];
            }
            if( !empty($val['getEnd']) && time() > $val['getEnd'] && !$searchFlag )
                $res['data'][$key]['status'] = 4;
            if( !empty($val['getEnd']) && time() > $val['getEnd'] && $searchFlag ){
                if( $actStatus == 4 || empty($actStatus) )
                    $res['data'][$key]['status'] = 4;
                else 
                    unset( $res['data'][$key] );
            }
            if( empty($val['totalNum']) )
                $res['data'][$key]['totalNum'] = '无限';
            if(empty($val['totalNum']) && !empty($val['getTypes']) && $val['getTypes']==3 ){
                $tmp = \App\Voucher::where(['vcSn'=>$val['vcSn']])->count();
                $total = $tmp * $val['getNumMax'];
                $res['data'][$key]['totalNum'] = $total;
            }
            unset( $res['data'][$key]['useEnd'] );
            unset( $res['data'][$key]['getStart'] );
            unset( $res['data'][$key]['getEnd'] );
            unset( $res['data'][$key]['DEPARTMENT_ID'] );
            unset( $res['data'][$key]['getTypes'] );
            unset( $res['data'][$key]['getNumMax'] );
        }
        if( $searchFlag ){
            $i = 0;
            $temp = [];
            foreach( $res['data'] as $val ){
                $temp[$i] = $val;
                $i++;
            }
            $res['data'] = $temp;
        }
        return $res;
    }
}
