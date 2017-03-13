<?php 
namespace App\Http\Controllers\Coupon;

use App\Http\Controllers\Controller;
use DB;
use Excel;
use Event;
use App\Voucher;
use App\Exceptions\ApiException;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ERROR;
use Log;
use App\VoucherConf;
use App\Jobs\Coupon;

class CouponController extends Controller{
    private static  $DES_KEY = "authorlsptime20141225\0\0\0";
	
    /***
	 * @api {post} /coupon/add 1.添加兑换劵活动
	 * @apiName add
	 * @apiGroup Coupon
	 *
	 *@apiParam {String} actName                必填        活动名称
	 *@apiParam {String} actNo                  必填        活动编号
	 *@apiParam {String} actIntro               必填        活动介绍
	 *@apiParam {Number} departmentId           必填        部门id
	 *@apiParam {Number} managerId              必填        部门负责人id
	 *@apiParam {Number} money                  必填        代金券金额
	 *@apiParam {String} getSingleLimit         必填        每个用户获取的劵上限
     *@apiParam {String} useLimitTypes          可选        限制首单使用值为2
     *@apiParam {Number} enoughMoney            可选        满额可用
     *@apiParam {Number} totalNumber            可选        劵总数
     *@apiParam {String} getTimeStart           可选        劵获取开始时间如 2015-10-16 00:00:00
     *@apiParam {String} getTimeEnd             可选        卷获取结束时间   2015-10-16 23:59:59
	 *@apiParam {String} addActLimitStartTime   可选        兑换劵可使用开始时间 2015-10-16 00:00:00
	 *@apiParam {String} addActLimitEndTime     可选        兑换劵可使用结束时间 2015-10-16 23:59:59
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
    public function addConf(){
        $post = $this->param;
        // 必须填写的参数
        $mustKey = array(
            'actName','actIntro','departmentId','managerId',
            'money','getSingleLimit','actNo','totalNumber'
        );
        // 返回前端数组
        $result = array();
        foreach( $mustKey as $val ){
            if( empty($post[ $val ]) ) return $this->error('必填写项未填写' );
        }
        
        if( (empty($post['addActLimitStartTime']) && empty($post['addActLimitStartTime'])) && empty($post['fewDay']) )
            return $this->error('必填写项未填写' );
        
        $data = array();
        $data['vcTitle'] = $post['actName'];
        $data['vcSn'] = $post['actNo'];
        $data['vcRemark'] = $post['actIntro'];
        $exists = \App\VoucherConf::where(['vcSn'=>$data['vcSn']])->count();
        if( $exists ) return $this->error('存在活动编号，请勿重复提交');
        if( !isset($post['totalNumber']) || empty($post['totalNumber'])) return $this->error('兑换劵上限未填写');
        // 定义兑换劵
        $data['useMoney'] = $post['money'];
        $data['getNumMax'] = $post['getSingleLimit'];
        $data['DEPARTMENT_ID'] = $post['departmentId'];
        $data['MANAGER_ID'] = $post['managerId'];
        if( isset($post['totalNumber']) ) $data['useTotalNum'] = $post['totalNumber'];
        if( isset($post['getTimeStart']) ) $data['getStart'] = strtotime($post['getTimeStart'] . " 00:00:00");
        if( isset($post['getTimeEnd']) ) $data['getEnd'] = strtotime($post['getTimeEnd'] . " 23:59:59");
        if( isset($post['limitItemTypes']) && !empty($post['limitItemTypes'][0]) ) $data['useItemTypes'] = ','.join(',',$post['limitItemTypes']).',';
        if( isset($post['useLimitTypes']) && !empty($post['useLimitTypes']) ) $data['useLimitTypes'] = $post['useLimitTypes'][0];
        if( isset($post['enoughMoney']) ) $data['useNeedMoney'] = $post['enoughMoney'];
        if( isset($post['sendSms']) ) $data['SMS_ON_GAINED'] = $post['sendSms'];
        if( isset($post['money'])&&!empty($post['money']) && ($post['money']>999 ||$post['getSingleLimit']<1))
            return $this->error('券金额只能在1~999');
        if( isset($post['useNeedMoney'])&&!empty($post['useNeedMoney']) && $post['useNeedMoney']>999 )
            return $this->error('券总数只能在0~999');
        if( isset($post['totalNumber'])&&!empty($post['totalNumber']) && ($post['totalNumber']>3000 ||$post['totalNumber']<1))
            return $this->error('券总数只能在1~3000');
        if( isset($post['getSingleLimit'])&&!empty($post['getSingleLimit']) && ($post['getSingleLimit']>20 ||$post['getSingleLimit']<1))
            return $this->error('单个用户设置只能在1~20');
        if( isset($post['fewDay'])&&!empty($post['fewDay']) && ($post['fewDay']>999 ||$post['fewDay']<1))
            return $this->error('自定义有效期区间只能在1~999');
        if( isset($post['getTimeStart']) && isset($post['getTimeEnd']) && ( strtotime($post['getTimeStart']) > strtotime($post['getTimeEnd'])) )
            return $this->error('获取开始时间需小于获取结束时间');
        if( isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && ( strtotime($post['addActLimitStartTime']) > strtotime($post['addActLimitEndTime'])) )
            return $this->error('限制开始时间需小于限制结束时间');
        if( isset($post['fewDay'])  && !empty($post['fewDay'])){
            $data['FEW_DAY'] = $post['fewDay'];
            $data['useStart'] = '0';
            $data['useEnd'] = '0';
        }elseif( isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && !empty($post['addActLimitStartTime'])  && !empty($post['addActLimitEndTime']) ){
            $data['useStart'] = strtotime($post['addActLimitStartTime']. " 00:00:00");
            $data['useEnd'] = strtotime($post['addActLimitEndTime']. " 23:59:59");
            $data['FEW_DAY'] = '';
        }
        
        $data['status'] = 2;
        $data['ADD_TIME'] = date('Y-m-d H:i:s');
        $data['IS_REDEEM_CODE'] = 'Y';
        
        $addRes = \App\VoucherConf::insertGetId( $data );

        if( empty($addRes) ) return $this->error('插入数据失败，请稍后再试');
        Event::fire('coupon.add','添加兑换数据: '.$addRes);
        return $this->success();
    }
    
    /***
	 * @api {get} /coupon/list 2.兑换劵配置列表
	 * @apiName list
	 * @apiGroup Coupon
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
	 * @apiSuccess {String} useNum 兑换数
	 * @apiSuccess {String} totalNum 兑换劵可领总数
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
        if( isset($post['page_size']) && !empty($post['page_size']))
            $pageSize = $post['page_size'];
        if( !empty($actEndTime))
            $actEndTime .= ' 23:59:59';
        if( empty($actSelect) && empty($actNumber) && empty($actStatus) && empty($actDepartment) && empty($actStartTime) && empty($actEndTime) ){
            //手动设置页数
            AbstractPaginator::currentPageResolver(function() use ($page) {
                return $page;
            });
            $res = \App\VoucherConf::select(['vcId','vcTitle','vcSn','ADD_TIME as addTime','getStart','getEnd','DEPARTMENT_ID','status','useEnd','useTotalNum as totalNum'])
                    ->where(['vType'=>1,'IS_REDEEM_CODE'=>'Y'])
                    ->orderBy('vcId','desc')
                    ->paginate($pageSize)
                    ->toArray();
            if( empty($res) ) return $this->success();
            
            $res = $this->handlerSearchDataList( $res );
            return $this->success( $res );
        }
        $actType = array('','vcSn','vcTitle');
        $obj = \App\VoucherConf::select(['vcId','vcTitle','vcSn','ADD_TIME as addTime','getStart','getEnd','DEPARTMENT_ID','status','useEnd','useTotalNum as totalNum']);
        $obj = $obj->where(['vType'=>1,'IS_REDEEM_CODE'=>'Y']);
        if( !empty($actSelect) && !empty($actNumber) )
            $obj = $obj->where( $actType[ $actSelect ] , 'like' , "%".$actNumber."%" );
        
        if( !empty($actStatus) ){
            if( $actStatus != 4 )
                $obj = $obj->whereRaw('( status='.$actStatus.' AND ( getEnd=0 OR getEnd>'.time().'))');
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
        $res = $this->handlerSearchDataList( $res , true ,$actStatus );
        return $this->success( $res );
    }
    /***
	 * @api {get} /coupon/actView/:id 3.兑换劵活动概览
	 * @apiName actView
	 * @apiGroup Coupon
	 *
	 * @apiParam {Number} id 平台活动id
     * 
     * 
     * 
	 * @apiSuccess {String} vcTitle 活动名称
	 * @apiSuccess {String} vcSn 活动编号
	 * @apiSuccess {Number} vcRemark 活动简介.
	 * @apiSuccess {String} status 1. 进行中 2. 下线 3.已关闭 4. 已结束
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
	 * @apiSuccess {String} export 0 不能导出 1 可以导出
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
     *               "invalidNum": 3,
     *               "export": 3
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
        $voucherConfInfo = \App\VoucherConf::select(['vcTitle','vcSn','vcRemark','getStart','getEnd','status','DEPARTMENT_ID','MANAGER_ID','useTotalNum','getCodeType','getCode','useMoney'])
                ->where(['vcId'=>$id,'vType'=>1,'IS_REDEEM_CODE'=>'Y'])
                ->first()
                ->toArray();
//        print_r( $voucherConfInfo );exit;
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
        $voucherNum = \App\Voucher::where( ['vcId'=>$id] )->count();
        if( !empty( $voucherConfInfo['useTotalNum'] ) || !empty($voucherNum) ){
            if( !empty($voucherNum) ){
                $voucherConfInfo['totalNum'] = $voucherNum;
                $voucherConfInfo['budget'] = $voucherNum * $voucherConfInfo['useMoney'];
            }else{
                $voucherConfInfo['totalNum'] = $voucherConfInfo['useTotalNum'];
                $voucherConfInfo['budget'] = $voucherConfInfo['useTotalNum'] * $voucherConfInfo['useMoney'];
            }
        }
        
        if( !empty($voucherConfInfo['getEnd']) && time() > $voucherConfInfo['getEnd'] )
            $voucherConfInfo['status'] = 4;
        $voucherConfInfo['companyCode'] = '-';
        $voucherConfInfo['activityCode'] = '-';
        $voucherConfInfo['dividendCode'] = '-';
        if( !empty($voucherConfInfo['getCodeType']) ){
            $temp = ['','dividendCode','companyCode','activityCode'];
            $voucherConfInfo[ $voucherConfInfo['getCodeType'] ] = $voucherConfInfo['getCode'];
        }
        // 劵情况统计情况
        $voucherConfInfo['allNum'] = 0;
        $voucherConfInfo['allMoney'] = 0;
        $voucherConfInfo['useNumed'] = 0;
        $voucherConfInfo['useMoneyed'] = 0;
        $voucherConfInfo['consumeNum'] = 0;
        $voucherConfInfo['consumeMoney'] = 0;
        $voucherConfInfo['invalidNum'] = 0;
        
        $totalNum = \App\Voucher::whereRaw( 'vcId='.$id.' and vStatus<>10 and vStatus<>3 ' )->count();
        if( empty($totalNum) ) return $this->success( $voucherConfInfo );
        
        $allNum = \App\Voucher::whereRaw( 'vcId='.$id.' and vStatus!=3 AND vStatus !=5 ' )->count();
        $voucherConfInfo['allNum'] = $allNum;
        $voucherConfInfo['allMoney'] = $allNum * $voucherConfInfo['useMoney'];
        
        
        $useNumed = \App\Voucher::where( ['vcId'=>$id,'vStatus'=>2] )->count();
        $voucherConfInfo['useNumed'] = $useNumed;
        $voucherConfInfo['useMoneyed'] = $useNumed * $voucherConfInfo['useMoney'];
        
        $invalidNum = \App\Voucher::whereRaw( 'vcId = '.$id.' AND vStatus!=2 AND ( vStatus=5 OR ( vUseEnd !=0 AND vUseEnd<'.time().'))' )->count();
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
        // 查看兑换劵是否已经生成过
        $voucherCount = \App\Voucher::where(['vcId'=>$id])->count();
        $voucherConfInfo['export'] = 0;
        if( !empty($voucherCount) ) $voucherConfInfo['export'] = 1;
        
        unset( $voucherConfInfo['getStart'] );
        unset( $voucherConfInfo['getEnd'] );
        unset( $voucherConfInfo['DEPARTMENT_ID'] );
        unset( $voucherConfInfo['MANAGER_ID'] );
        unset( $voucherConfInfo['useMoney'] );
        unset( $voucherConfInfo['useTotalNum'] );
        unset( $voucherConfInfo['getCodeType'] );
        unset( $voucherConfInfo['getCode'] );
            
        return $this->success( $voucherConfInfo );
    }
    /***
	 * @api {post} /coupon/getInfo/:id 4.读取兑换劵配置
	 * @apiName getInfo
	 * @apiGroup Coupon
	 *
	 * @apiParam {Number} id 平台活动id
     * 
     * 
     * 
	 * @apiSuccess {Number} vcId                活动配置
	 * @apiSuccess {String} actName             活动名称
	 * @apiSuccess {String} actNo               活动编号
	 * @apiSuccess {String} actIntro            活动简介.
	 * @apiSuccess {Number} departmentId        部门.
	 * @apiSuccess {Number} managerId           负责人.
	 * @apiSuccess {Number} money               兑换劵金额
	 * @apiSuccess {Number} totalNumber         代金券可领总数
	 * @apiSuccess {String} limitItemTypes      限制可使用项目类别格式为（,1,2,）
	 * @apiSuccess {Number} useLimitTypes       使用限制类型 2 为限制首单
	 * @apiSuccess {Number} enoughMoney         限制项目需满足金额才可使用
	 * @apiSuccess {String} addActLimitStartTime    可使用时间.起始(0 表示不限制)
	 * @apiSuccess {String} addActLimitEndTime      可使用时间.结束(0 表示不限制)
	 * @apiSuccess {Number} getSingleLimit       个人可获取最大券数
	 * @apiSuccess {String} getTimeStart        可获取时间 起始(0 表示不限制)
	 * @apiSuccess {String} getTimeEnd          可获取时间 结束(0 表示不限制)
	 * @apiSuccess {Number} singleEnoughMoney   获取需满足金额(0表示不限制)
	 * @apiSuccess {String} sendSms             获取代金券时下发的短信内容
	 * @apiSuccess {Number} status              活动状态: 1正常 2暂停 3 关闭 4.已结束
	 * @apiSuccess {Number} fewDay              获取兑换劵后多少天内可用
	 * @apiSuccess {String} limitItemTypes      可使用的项目格式如 ",2,3,"
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
     *                        "getCodeType": 0,
     *                        "status": 1,
     *                       "selectItem": 2
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
            ,'DEPARTMENT_ID as departmentId','MANAGER_ID as managerId','useMoney as money','getCode as code','useLimitTypes'
            ,'useNeedMoney as enoughMoney','useTotalNum as totalNumber' ,'getNeedMoney as singleEnoughMoney','getStart as getTimeStart','getEnd as getTimeEnd'
            ,'useStart as addActLimitStartTime','useEnd as addActLimitEndTime','FEW_DAY as fewDay','getTypes','SMS_ON_GAINED as sendSms','getCodeType','useItemTypes as limitItemTypes','status'])
                ->where(['vcId'=>$id,'vType'=>1,'IS_REDEEM_CODE'=>'Y'])
                ->first()
                ->toArray();
        
        if( !empty($voucherConfInfo['getTimeEnd']) && $voucherConfInfo['getTimeEnd'] <time() )
            $voucherConfInfo['status'] = 4;
        if( !empty($voucherConfInfo['getTimeStart']) )
            $voucherConfInfo['getTimeStart'] = date('Y-m-d H:i:s',$voucherConfInfo['getTimeStart']);
        if( !empty($voucherConfInfo['getTimeEnd']) )
            $voucherConfInfo['getTimeEnd'] = date('Y-m-d H:i:s',$voucherConfInfo['getTimeEnd']);
        if( !empty($voucherConfInfo['addActLimitStartTime']))
            $voucherConfInfo['addActLimitStartTime'] = date('Y-m-d H:i:s',$voucherConfInfo['addActLimitStartTime']);
        if( !empty($voucherConfInfo['addActLimitEndTime']))
            $voucherConfInfo['addActLimitEndTime'] = date('Y-m-d H:i:s',$voucherConfInfo['addActLimitEndTime']);
        if( !empty($voucherConfInfo['limitItemTypes']) )
            $voucherConfInfo['limitItemTypes'] = rtrim(ltrim($voucherConfInfo['limitItemTypes'],','),',');
        return $this->success( $voucherConfInfo );
    }
    /***
	 * @api {get} /coupon/editConf 5.编辑平台兑换劵活动
	 * @apiName editConf
	 * @apiGroup Coupon
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
	 *@apiParam {String} addActLimitStartTime   可选        兑换劵可使用开始时间 2015-10-16 00:00:00
	 *@apiParam {String} addActLimitEndTime     可选        兑换劵可使用结束时间 2015-10-16 23:59:59
     *@apiParam {Number} fewDay                 可选        劵获取多少天内可用 （和上面劵可使用时间必须达到传其一）
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
        if( isset($post['getTimeStart']) ) $data['getStart'] = strtotime($post['getTimeStart'] . " 00:00:00");
        if( isset($post['getTimeEnd']) ) $data['getEnd'] = strtotime($post['getTimeEnd']. " 23:59:59");
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
        
       
        if( isset($post['useNeedMoney'])&&!empty($post['useNeedMoney']) && $post['useNeedMoney']>999 )
            return $this->error('券总数只能在0~999');
        if( isset($post['getSingleLimit'])&&!empty($post['getSingleLimit']) && ($post['getSingleLimit']>20 ||$post['getSingleLimit']<1))
            return $this->error('单个用户设置只能在1~20');
        if( isset($post['fewDay'])&&!empty($post['fewDay']) && ($post['fewDay']>999 ||$post['fewDay']<1))
            return $this->error('自定义有效期区间只能在1~999');
        
        if( isset($post['fewDay'])  && !empty($post['fewDay'])){
            $data['FEW_DAY'] = $post['fewDay'];
            $data['useStart'] = '0';
            $data['useEnd'] = '0';
        }elseif( isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && !empty($post['addActLimitStartTime'])  && !empty($post['addActLimitEndTime']) ){
            $data['useStart'] = strtotime($post['addActLimitStartTime']. " 00:00:00");
            $data['useEnd'] = strtotime($post['addActLimitEndTime']. " 23:59:59");
            $data['FEW_DAY'] = '';
        }
        
        if( isset($post['addActLimitStartTime']) && isset($post['addActLimitEndTime']) && isset($post['fewDay']) && empty($post['addActLimitStartTime']) && empty($post['addActLimitEndTime']) && empty($post['fewDay'])){
            return $this->error('限制时间设置错误');
        }
        if(!isset($post['addActLimitStartTime']) && !isset($post['addActLimitEndTime']) && isset($post['fewDay']) && empty($post['fewDay'])){
            return $this->error('限制时间设置错误');
        }
        $addRes = \App\VoucherConf::where(['vcId'=>$id])->update( $data );
        Event::fire('coupon.editConf','编辑兑换数据id：'.$id);
        return $this->success();
    }
    /***
	 * @api {get} /coupon/offlineConf/{:id} 6.兑换劵平台下线操作
	 * @apiName offlineConf
	 * @apiGroup Coupon
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
        Event::fire('coupon.offlineConf','下线兑换活动id: '.$id);
        return $this->success();
    }
    /***
	 * @api {get} /coupon/closeConf/{:id} 7.兑换劵平台关闭操作
	 * @apiName closeConf
	 * @apiGroup Coupon
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
        Event::fire('coupon.closeConf','关闭兑换活动id: '.$id);
        return $this->success();
    }
    /***
	 * @api {get} /coupon/upConf/{:id} 8.兑换劵平台活动上线操作
	 * @apiName upConf
	 * @apiGroup Coupon
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
        $this->dispatch(new Coupon($vcId));
        Event::fire('coupon.upConf','上线兑换活动id: '.$vcId);
        return $this->success();
    }

    
    /***
	 * @api {get} /coupon/getCoupon/{:id} 9.查看实体券编码和密码
	 * @apiName getCoupon
	 * @apiGroup Coupon
	 *
	 *@apiParam {Number} id                   必填     兑换劵配置id
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
	 *		    "data": [
     *              {
     *                   "vSn": "DH45331379719",
     *                   "REDEEM_CODE": "3a7abe91c69e2a6ab2d49ffad6905846",
     *                   "vUseMoney": 50
     *               },
     *             .....
     *         ],
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "关闭失败，请重新下线"
	 *		}
	 ***/
    public function getCoupon($vcId){
        $coupon = \App\Voucher::select(['vSn','REDEEM_CODE','vUseMoney'])->where(['vcId'=>$vcId])->get();
        return $this->success( $coupon );
    }
    /***
	 * @api {get} /coupon/exportCoupon/{:id} 10.导出查看实体券编码和密码
	 * @apiName exportCoupon
	 * @apiGroup Coupon
	 *
	 *@apiParam {Number} id                   必填     兑换劵配置id
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
    public function exportCoupon($vcId){
        $result = \App\Voucher::select(['vSn','REDEEM_CODE','vUseMoney','vcTitle'])->where(['vcId'=>$vcId])->get()->toArray();
        $desModel = new \Service\NetDesCrypt;
        $desModel->setKey( self::$DES_KEY );
		$title = '兑换劵-'. $result[0]['vcTitle'] .date('Ymd');
        foreach( $result as $key => $val ){
            if( strlen( $val['REDEEM_CODE'] ) !=8 )
                $result[$key]['REDEEM_CODE'] = $desModel->decrypt( $val['REDEEM_CODE'] );
            unset( $result[$key]['vcTitle'] );
        }
        Event::fire('coupon.exportCoupon','导出查看实体券编码和密码活动id: '.$vcId);
        //导出excel	   
		$header = ['兑换券编码','兑换券密码','兑换券金额'];
		Excel::create($title, function($excel) use($result,$header){
		    $excel->sheet('Sheet1', function($sheet) use($result,$header){
			        $sheet->fromArray($result, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
	        		$sheet->prependRow(1, $header);//添加表头
			    });
		})->export('xls');
        exit;
    }
    /***
	 * @api {get} /coupon/exportList 11.导出兑换劵配置列表
	 * @apiName exportList
	 * @apiGroup Coupon
	 *
	 * @apiParam {Number} selectItem 可选 选择的项 1: 活动编号 2.活动名称
	 * @apiParam {String} keyword 可选 对应项查询的字符.
	 * @apiParam {Number} status 可选 活动状态. 1. 进行中 2. 暂停 3.已关闭 4. 已结束
	 * @apiParam {String} department 部门id
	 * @apiParam {String} startTime 活动开始时间
	 * @apiParam {String} endTime 活动结束时间
     * 
     * 
     * 
	 * @apiSuccessExample Success-Response:
	 *		{
     *           "result": 1,
     *           "token": "",
     *           "data": ""
     *          }
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
            //手动设置页数
            AbstractPaginator::currentPageResolver(function() use ($page) {
                return $page;
            });
            $res = \App\VoucherConf::select(['vcId','vcTitle','vcSn','ADD_TIME as addTime','getStart','getEnd','DEPARTMENT_ID','status','useEnd','useTotalNum as totalNum'])
                    ->where(['vType'=>1,'IS_REDEEM_CODE'=>'Y'])
                    ->orderBy('vcId','desc')
                    ->paginate($pageSize)
                    ->toArray();
            if( empty($res) ) return $this->success();
            $tempData = $this->handleList($res['data']);
            unset( $res );
            $title = '兑换劵活动查询列表' .date('Ymd');
            Event::fire('coupon.exportList','导出兑换列表数据');
            //导出excel	   
            $header = ['活动名称','活动编码','券总数','已兑换数','已使用数','创建时间','活动时间','申请部门','活动状态'];
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
        $obj->where(['vType'=>1,'IS_REDEEM_CODE'=>'Y']);
        if( !empty($actSelect) && !empty($actNumber) ) $obj->where( $actType[ $actSelect ] , 'like' , "%".$actNumber."%" );
        
        if( !empty($actStatus) ){
            if( $actStatus != 4 )
                $obj = $obj->whereRaw(' ( status='.$actStatus.' AND ( getEnd=0 OR getEnd>'.time().'))');
            else
                $obj->whereRaw('getEnd !=0 AND getEnd < '.time());
        }
        if( !empty($actStartTime) && !empty($actEndTime))
            $obj->whereRaw(' ((getStart <= "'.strtotime($actStartTime) .'" and getEnd >= "'.strtotime($actStartTime) .'") or (getStart <= "'.strtotime($actEndTime) .'" and getEnd >= "'.strtotime($actEndTime) .'" ))');
        if( !empty( $actDepartment ) )
            $obj->where('DEPARTMENT_ID','=',$actDepartment);
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        $res = $obj->orderBy('vcId','desc')->paginate($pageSize)->toArray();
        if( empty($res) ) return $this->success();
            
        $tempData = $this->handleList($res['data']);
        unset( $res );
        $title = '兑换劵查询列表' .date('Ymd');
        Event::fire('coupon.exportList','导出兑换列表数据');
        //导出excel	   
        $header = ['活动名称','活动编码','券总数','已兑换数','已使用数','创建时间','活动时间','申请部门','活动状态'];
        Excel::create($title, function($excel) use($tempData,$header){
            $excel->sheet('Sheet1', function($sheet) use($tempData,$header){
                $sheet->fromArray($tempData, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header);//添加表头
            });
        })->export('xls');
        exit;
    }
    /***
	 * @api {get} /coupon/getActNum 12.获取活动编码
	 * @apiName getActNum
	 * @apiGroup Coupon
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
        $code = "dh" . $pre  . $end;
        $count = \App\VoucherConf::where( 'vcSn' , '=' , $code )->count();
        if($count)  return $this->getActNum();
        $code = ['actNo'=>$code];
        return $this->success($code);
   }
    // 获取分类
    private function _getItemType(){
        // 这里用于 兑换劵和配置中会和前端约定 增加一个项目特价类型为typeid为101
        $itemType = \App\SalonItemtype::select(['typeid','typename'])
                ->where('status','=',1)
                ->orderBy('sortIt','DESC')
                ->get()
                ->toArray();
        array_unshift( $itemType , array('typeid'=>101,'typename'=>'限时特价 ') );
        return $itemType;
    }
    // 获取兑换劵编号
    private function getVoucherSn( $p = 'CM' ) {
        $pre = substr(time(), 2);
        $end = '';
        for ($i = 0; $i <3; $i++) {
            $end .= rand(0, 9);
        }
        $code = $p . $pre  . $end;
        $count = Voucher::where('vSn','=',$code)->count();
        if ($count) return $this->getVoucherSn();
        return $code;
   }
   // 获取兑换劵状态
   private function getVoucherStatusByActId( $vcId ){
        $result = \App\Voucher::select(['vStatus','vUseEnd'])->where(['vcId'=>$vcId])->get();
        
        if( empty( $result ) )
            return [0,0,0,0];
        $result = $result->toArray();
        if( empty($result) )
            return [0,0,0,0];
        $useEnd = $result[0]['vUseEnd'];
        $totalNum = 0;
        $useNum = 0;
        $invalidNum = 0;
        $duihuanNum = 0;
        foreach( $result as $val ){
            if( $val['vStatus'] != 10 )
                $totalNum++;
            if( $val['vStatus'] == 2 )
                $useNum++;
            if( $val['vStatus'] == 5 )
                $invalidNum++;
            if( $val['vStatus'] != 10 && $val['vStatus'] != 3 && $val['vStatus'] != 5)
                $duihuanNum++;
        }
        if( !empty($invalidNum) )
            return [ $totalNum , $useNum , $invalidNum , $duihuanNum ];
        // 已失效数
        if( empty($useEnd) ||  time()<$useEnd ){
            $invalidNum = 0;
        }else{
            $invalidNum = $totalNum - $useNum;
        }
        return [ $totalNum , $useNum , $invalidNum,$duihuanNum ];
    }
   
    // 加密生成的兑换码
    private function encodeCouponCode(){
        $desModel = new \Service\NetDesCrypt;
        $desModel->setKey( self::$DES_KEY );
        $code = $this->createCouponCode();
        $encodeCode = $desModel->encrypt( $code );
        // 判断当前是否存在
        $exists = \App\Voucher::where( ['REDEEM_CODE'=>$encodeCode] )->count();
        if( !empty($exists) ) $this->encodeCouponCode();
        return $encodeCode;
    }
    // 生成原生的兑换码 $zS true : 生成以数字为先 false ： 生成以字母为先
    private function createCouponCode(){
        $code = '';
        $randRange = array(97,122);
        $otherRanger = array( 0,9 );

        while( strlen($code) < 8 ){
            $rand = rand(0,9);
            $zS = array(1,2,3,5,7);
            if( in_array($rand, $zS)  ){
                $randNum = rand( $randRange[0] , $randRange[1] );
                while( $randNum == 108 || $randNum == 111 ){
                    $randNum = rand( $randRange[0] , $randRange[1] );
                }
                $code .= chr($randNum);
            }else{
                $randNum = rand( $otherRanger[0] , $otherRanger[1] );
                $code .= $randNum;
            }
        }

        // 检查不能全部为数字或字符
        if( preg_match('#^[0-9]{8}$#',$code) || preg_match('#^[a-z]{8}$#',$code) || strlen($code) != 8 )
           return $this->createCouponCode();
        return $code;
    }
    // 处理配置列表返回的数据
   private function handleList( $res ){
        $tempData = [];
        $department = '';
        $i = 0;
        $statusArr = ['','进行中','下线','已关闭'];
        foreach( $res as $key=>$val ){
            $statistics = $this->getVoucherStatusByActId($val['vcId']);
            $actTime = '';
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
            $temp1 = $statistics[1] + $statistics[3];
            $temp2 = $statistics[1];
            $tempData[$key][] = $val['vcTitle'];
            $tempData[$key][] = $val['vcSn'];
            $tempData[$key][] = empty($val['totalNum']) ? '无限制' : $val['totalNum'];
            $tempData[$key][] = empty($temp1) ? '0' : $temp1;
            $tempData[$key][] = empty($temp2) ? '0' : $temp2;
            $tempData[$key][] = $val['addTime'];
            $tempData[$key][] = $actTime;
            $tempData[$key][] = $department;
            if( $val['status'] != 2 && !empty($val['getEnd']) && $val['getEnd']<time() )
                $tempData[$key][] = '已结束';
            else
                $tempData[$key][] = $statusArr[ $val['status'] ];
        }
        return $tempData;
   }
   private function handlerSearchDataList( $res , $searchFlag = false , $actStatus = ''){
       foreach( $res['data'] as $key=>$val ){
            $statistics = $this->getVoucherStatusByActId($val['vcId']);
            $res['data'][$key]['allNum'] = $statistics[1];
            $res['data'][$key]['useNum'] = $statistics[3];
            $res['data'][$key]['actTime'] = '';
            if( empty( $val['getStart'] ) && empty($val['getEnd']) )
                $res['data'][$key]['actTime'] = '无限期活动';
            if( !empty($val['getStart']) && empty($val['getEnd']) )
                $res['data'][$key]['actTime'] = '开始时间：' . date('Y-m-d H:i:s', $val['getStart']);
            if( !empty($val['getEnd']) && empty($val['getStart']) )
                $res['data'][$key]['actTime'] = '结束时间：' . date('Y-m-d H:i:s', $val['getEnd']);
            if( !empty( $val['getStart'] ) && !empty($val['getEnd']) )
                $res['data'][$key]['actTime'] = date('Y-m-d H:i:s', $val['getStart']) . ' - ' . date('Y-m-d H:i:s', $val['getEnd']);
            $res['data'][$key]['department'] = '';
            if( !empty($val['DEPARTMENT_ID']) ){
                $department = \App\Department::select(['title'])->where(['id'=>$val['DEPARTMENT_ID']])->first();
                $res['data'][$key]['department'] = $department['title'];
            }
            if( !empty($val['getEnd']) && time() > $val['getEnd'] && !$searchFlag)
                $res['data'][$key]['status'] = 4;
            if( !empty($val['getEnd']) && time() > $val['getEnd'] && $searchFlag ){
                if( $actStatus == 4 || empty($actStatus))
                    $res['data'][$key]['status'] = 4;
                else 
                    unset( $res['data'][$key] );
            }
            unset( $res['data'][$key]['useEnd'] );
            unset( $res['data'][$key]['getStart'] );
            unset( $res['data'][$key]['getEnd'] );
            unset( $res['data'][$key]['DEPARTMENT_ID'] );
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
