<?php
    
    
namespace App\Http\Controllers\VoucherTicket;

use App\Http\Controllers\Controller;
use DB;
use Excel;
use Event;
use App\Voucher;
use Service\NetDesCrypt;
use App\Exceptions\ApiException;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ERROR;

class TicketController extends Controller {
    private static  $DES_KEY = "authorlsptime20141225\0\0\0";
    /**
	 * @api {get} /voucher/list 1.卷列表
	 * @apiName list
	 * @apiGroup voucher
	 *
	 * @apiParam {Number} keywordType 可选,关键词类型. 1.活动编号 2. 活动名称 3.现金劵编号 4.用户手机号  5.使用店铺 6. 分享手机号 7.兑换密码
	 * @apiParam {String} keyword 可选,关键词.
	 * @apiParam {int} status 可选,劵状态.
	 * @apiParam {String} startTime 可选,使用时间 起始日期Y-m-d H:i:s.
	 * @apiParam {String} endTime 可选,使用时间 结束日期Y-m-d H:i:s.
	 * @apiParam {Number} page 可选,页数. 默认为1第一页
	 * @apiParam {Number} pageSize 可选,分页大小. 默认为20
	 *
	 * @apiSuccess {Number} total 总数据量.
	 * @apiSuccess {Number} per_page 分页大小.
	 * @apiSuccess {Number} current_page 当前页面.
	 * @apiSuccess {Number} last_page 当前页面.
	 * @apiSuccess {Number} from 起始数据.
	 * @apiSuccess {Number} to 结束数据.
	 * @apiSuccess {Number} vId 代金券id
	 * @apiSuccess {Number} vSn 代金券编号
	 * @apiSuccess {Number} vcSn 活动编号
	 * @apiSuccess {Number} vcTitle 活动标题.
	 * @apiSuccess {String} vOrderSn 关联订单号.
	 * @apiSuccess {String} vUseMoney 代金券金额.
	 * @apiSuccess {String} vUseTime 使用时间
	 * @apiSuccess {Number} vMobilephone 用户手机号
	 * @apiSuccess {String} vSalonName 店铺名.
	 * @apiSuccess {String} vSalonName 店铺名.
	 * @apiSuccess {Number} vStatus 状态: 1未使用 2已使用 3待激活 4活动关闭 5已失效
	 * @apiSuccess {Number} REDEEM_CODE 兑换码
	 * 
	 *
	 *
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": {
	 *	        "total": 51,
	 *	        "per_page": "1",
	 *	        "current_page": 1,
	 *	        "last_page": 51,
	 *	        "from": 1,
	 *	        "to": 1,
	 *	        "data": [
	 *	            {
     *                  "vId": "1",
     *                  "vSn": "CM37811677985",
     *                  "vcTitle": "^__^首次注册可以获取代金券",
     *                  "vOrderSn": "",
     *                  "vUseMoney": 10,
     *                  "vUseStart": 1437580800,
     *                  "vUseEnd": 1437753599,
     *                  "vMobilephone": "18212345608",
     *                  "vSalonName": "",
     *                  "vStatus": 1
     *               },
	 *              ......
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
    public function ticketList(){
        $post = $this->param;
        $keyword = isset( $post['keyword'] ) ? $post['keyword'] : '';
        $status = isset($post['status']) ? $post['status'] : '';
        $startTime = isset( $post['startTime'] ) ? strtotime($post['startTime']) : '';
        $endTime = isset( $post['endTime'] ) ? strtotime($post['endTime']) : '';
        $page = isset($post['page'])?$post['page']:1;
		$pageSize = isset($param['pageSize'])?$param['pageSize']:20;
        if( isset($post['page_size']) && !empty($post['page_size']))
            $pageSize = $post['page_size'];
        $keywordType = isset($post['keywordType']) ? $post['keywordType'] : '';
        $obj = Voucher::select(['vId','vSn','vcSn','vcTitle','vOrderSn','vUseMoney','vUseTime','vMobilephone','vSalonName','vStatus','REDEEM_CODE']);
        if($keyword && !empty($keywordType)){
            $selectType = array('','vcSn','vcTitle','vSn','vMobilephone','vSalonName','','REDEEM_CODE'); // 目前缺少分享手机号
//            分享手机号查询
            if( $keywordType == 6 ){
                $obj = DB::table('laisee')->select(['voucher.vId','voucher.vSn','voucher.vcSn','voucher.vcTitle'
                        ,'voucher.vOrderSn','voucher.vUseMoney','voucher.vUseTime','voucher.vMobilephone','voucher.vSalonName','voucher.vStatus','voucher.REDEEM_CODE'])
                        ->leftjoin('voucher','laisee.vsn','=','voucher.vSn')
                        ->leftjoin('salon_itemcomment','laisee.item_comment_id','=','salon_itemcomment.itemcommentid')
                        ->leftjoin('user','salon_itemcomment.user_id','=','user.user_id')
                        ->where('user.mobilephone','=',$keyword);
            }elseif( in_array($keywordType,[1,2,3,4,5]) )
                $obj = $obj->where( $selectType[ $keywordType ] , '=' , $keyword );
            elseif( $keywordType == 7 ){
                $des = new \Service\NetDesCrypt;
                $des->setKey( self::$DES_KEY );
                $encrypt = $des->encrypt( $keyword );
                $obj = $obj->whereRaw('REDEEM_CODE="'.$keyword.'" or REDEEM_CODE="'.$encrypt.'"');
            }
        }

        if($status){
			if ($status == 1) $obj = $obj->where( 'vStatus','=',1 )->where('vUseEnd','>',time());
			elseif ($status == 2) $obj = $obj->where( 'vStatus','=',2 );
			elseif ($status == 3) $obj = $obj->whereRaw('vStatus=5 or ('.time().' > vUseEnd and vStatus not in (2,4))');
			elseif( $status == 4) $obj = $obj->whereRaw(' vStatus=10 and REDEEM_CODE!=""');
        }else $obj->where('vStatus','<>',10);

        if($startTime && empty($endTime)) $obj = $obj->where('vUseTime','>=',$startTime);
        if($endTime && empty($startTime)) $obj = $obj->where('vUseTime','<=',$endTime);
        if($startTime && $endTime) $obj = $obj->whereBetween('vUseTime',[$startTime,$endTime]);
        //手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
        $list = $obj->orderBy('vId','DESC')->paginate($pageSize)->toArray();
        foreach($list['data'] as $key => $val ){
            $list['data'][$key]['vUseTime'] = empty($list['data'][$key]['vUseTime']) ? '' : date('Y-m-d H:i:s',$val['vUseTime']);
            if( $val['vStatus'] == 1 ){
                $list['data'][$key]['vSalonName'] = '';
            }
        }
        return $this->success( $list );
    }
    /**
	 * @api {get} /voucher/exportTicketList 4.导出卷列表
	 * @apiName exportTicketList
	 * @apiGroup voucher
	 *
	 * @apiParam {Number} keywordType 可选,关键词类型. 1.活动编号 2. 活动名称 3.现金劵编号 4.用户手机号  5.使用店铺 6. 分享手机号 7.兑换密码
	 * @apiParam {String} keyword 可选,关键词.
	 * @apiParam {int} status 可选,劵状态.
	 * @apiParam {String} startTime 可选,使用时间 起始日期Y-m-d H:i:s.
	 * @apiParam {String} endTime 可选,使用时间 结束日期Y-m-d H:i:s.
	 * @apiParam {Number} page 可选,页数. 默认为1第一页
	 * @apiParam {Number} pageSize 可选,分页大小. 默认为20
	 *
	 * 
	 *
	 *
	 * @apiSuccessExample Success-Response:
	 *	{
	 *	    "result": 1,
	 *	    "data": ""
	 *	}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "未授权访问"
	 *		}
	 */
    public function exportTicketList(){
        $post = $this->param;
        $keyword = isset( $post['keyword'] ) ? $post['keyword'] : '';
        $status = isset($post['status']) ? $post['status'] : '';
        $startTime = isset( $post['startTime'] ) ? strtotime($post['startTime']) : '';
        $endTime = isset( $post['endTime'] ) ? strtotime($post['endTime']) : '';
        $page = isset($post['page'])?$post['page']:1;
		$pageSize = isset($post['pageSize'])?$post['pageSize']:20;
        if( isset($post['page_size']) && !empty($post['page_size']))
            $pageSize = $post['page_size'];
        $keywordType = isset($post['keywordType']) ? $post['keywordType'] : '';
        $obj = Voucher::select(['vId','vSn','vcSn','vcTitle','vOrderSn','vUseMoney','vUseTime','vMobilephone','vSalonName','vStatus','REDEEM_CODE','vUseEnd']);
        if($keyword && !empty($keywordType)){
            $selectType = array('','vcSn','vcTitle','vSn','vMobilephone','vSalonName','','REDEEM_CODE');
//            分享手机号查询
            if( $keywordType == 6 ){
                $obj = DB::table('laisee')->select(['voucher.vId','voucher.vSn','voucher.vcSn','voucher.vcTitle'
                        ,'voucher.vOrderSn','voucher.vUseMoney','voucher.vUseTime','voucher.vMobilephone','voucher.vSalonName','voucher.vStatus','voucher.REDEEM_CODE'])
                        ->leftjoin('voucher','laisee.vsn','=','voucher.vSn')
                        ->leftjoin('salon_itemcomment','laisee.item_comment_id','=','salon_itemcomment.itemcommentid')
                        ->leftjoin('user','salon_itemcomment.user_id','=','user.user_id')
                        ->where('user.mobilephone','like',"%$keyword%");
            }elseif( in_array($keywordType,[1,2,3,4,5]) )
                $obj = $obj->where( $selectType[ $keywordType ] , 'like' , "%$keyword%" );
            elseif( $keywordType == 7 ){
                $des = new \Service\NetDesCrypt;
                $des->setKey( self::$DES_KEY );
                $encrypt = $des->encrypt( $keyword );
                $obj = $obj->whereRaw('REDEEM_CODE like "%'.$keyword.'%" or REDEEM_CODE like "%'.$encrypt.'%"');
            }
        }

        if($status){
			if ($status == 1) $obj = $obj->where( 'vStatus','=',1 )->where('vUseEnd','>',time());
			elseif ($status == 2) $obj = $obj->where( 'vStatus','=',2 );
			elseif ($status == 3) $obj = $obj->whereRaw('vStatus=5 or ('.time().' > vUseEnd and vStatus not in (2,4))');
			elseif( $status == 4) $obj = $obj->whereRaw('vStatus=10 and REDEEM_CODE!=""');
        }else $obj->where('vStatus','<>',10);

        if($startTime && empty($endTime)) $obj = $obj->where('vUseTime','>=',$startTime);
        if($endTime && empty($startTime)) $obj = $obj->where('vUseTime','<=',$endTime);
        if($startTime && $endTime) $obj = $obj->whereBetween('vUseTime',[$startTime,$endTime]);
        
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        $list = $obj->orderBy('vId','DESC')->paginate($pageSize)->toArray();
        $list = $list['data'];
        $tempData = [];
        $i = 0;
        $t = ['','未使用','已使用','待激活','活动关闭','已失效'];
        foreach($list as $key => $val ){
            $tempData[$key][] = $val['vSn'];
            $tempData[$key][] = $val['REDEEM_CODE'];
            $tempData[$key][] = $val['vcSn'];
            $tempData[$key][] = $val['vcTitle'];
            $tempData[$key][] = $val['vOrderSn'];
            $tempData[$key][] = $val['vUseMoney'];
            $tempData[$key][] = empty($list['data'][$key]['vUseTime']) ? '' : date('Y-m-d H:i:s',$val['vUseTime']);
            $tempData[$key][] = $val['vMobilephone'];
            if( $val['vStatus'] == 1 ){
                $tempData[$key][]['vSalonName'] = ' ';
            }else{
                $tempData[$key][] = $val['vSalonName'];
            }
            
            if( $val['vStatus'] != 2 && !empty($val['vUseEnd']) && $val['vUseEnd'] < time() ){
                $tempData[$key][] = $t[ 5 ];
            }else{
                if( !empty( $val['REDEEM_CODE'] ) && $val['vStatus'] == 10 )
                    $tempData[$key][] = '未兑换';
                elseif( in_array($val['vStatus'],[1,2,3,4,5]) )
                    $tempData[$key][] = $t[ $val['vStatus'] ];
            }
        }
//        unset( $list );
        $title = '现金卷查询列表' .date('Ymd');
        Event::fire('voucher.exportTicketList','导出劵列表');
        //导出excel	   
        $header = ['券编号','兑换密码','活动编号','活动名称','订单号','券金额','使用时间','用户手机号','使用店铺','现金券状态'];
        Excel::create($title, function($excel) use($tempData,$header){
            $excel->sheet('Sheet1', function($sheet) use($tempData,$header){
                $sheet->fromArray($tempData, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
                $sheet->prependRow(1, $header);//添加表头
            });
        })->export('xls');
        exit;
    }
    /***
	 * @api {get} /voucher/invalidStatus/:id 2.作废卷
	 * @apiName invalidStatus
	 * @apiGroup voucher
	 *
	 * @apiParam {Number} id 代金劵id.
	 *
	 * 
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": {
	 *		    }
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
	 *		    "result": 0,
	 *		    "msg": "更新失败重新尝试"
	 *		}
	 ***/
    public function invalidStatus($id){
        $res = Voucher::where( ["vId"=>$id,'vStatus'=>1] )->update(['vStatus'=>5]);
        Event::fire('voucher.invalidStatus','作废劵id: '.$id);
        return $this->success();
    }
     /***
	 * @api {get} /voucher/info/:id 3.获取卷详情
	 * @apiName info
	 * @apiGroup voucher
	 *
	 *
      * @apiParam {Number} id 代金劵id.
      * 
      * 
      * 
	 * 
	 * @apiSuccess {Number} vcId 活动id.
	 * @apiSuccess {String} vcSn 活动编号
	 * @apiSuccess {String} vSn 代金券编号
	 * @apiSuccess {String} vAddTime 获取时间
	 * @apiSuccess {String} vMobilephone 用户手机号
	 * @apiSuccess {Number} sharePhone 分享手机号
	 * @apiSuccess {String} vUseMoney 代金券金额
	 * @apiSuccess {String} vUseTime 使用时间
	 * @apiSuccess {String} vUseStart 可使用时间 起始(0 表示不限制)
	 * @apiSuccess {String} vUseEnd 可使用时间 结束(0 表示不限制)
	 * @apiSuccess {String} vOrderSn 关联订单号
	 * @apiSuccess {String} vSalonName 店铺名
	 * @apiSuccess {String} vItemName 项目名
	 * @apiSuccess {String} vcTitle 活动标题
	 * @apiSuccess {String} REDEEM_CODE 兑换码
	 * @apiSuccess {String} isPay 支付
	 * @apiSuccess {String} getText 获取条件
	 * @apiSuccess {String} useLimitText 使用限制
	 * @apiSuccess {String} vStatus 劵状态 状态: 1未使用 2已使用 3待激活 4活动关闭 5已失效
	 * 
	 * @apiSuccessExample Success-Response:
	 *		{
	 *		    "result": 1,
	 *		    "data": [{
     *               "typeid": 1,
     *               "typename": "洗剪吹"
	 *		    },
     *          ...
     *          ]
	 *		}
	 *
	 *
	 * @apiErrorExample Error-Response:
	 *		{
     *          "result": 1,
     *          "token": "",
     *           "data": {
     *               "vcId": 8,
     *               "vcSn": "cm999888",
     *               "vSn": "CM45093010561",
     *               "vAddTime": 1445093010,
     *               "vMobilephone": "15820487222",
     *               "sharePhone": "15820487223",
     *               "vUseMoney": 50,
     *                "vUseTime": 0,
     *                "vUseStart": 0,
     *               "vUseEnd": 0,
     *               "vOrderSn": "",
     *               "vSalonName": "",
     *               "vItemName": "",
     *               "vcTitle": "test-0001",
     *               "REDEEM_CODE": "",
     *               "isPay": "",
     *               "getText": "全平台用户(店铺码用户;洗吹;烫染;限时特价 ;)",
     *                "useLimitText": ""
     *           }
     *     }
	 ***/
    public function info( $id ){
        $allItemType = $this->_getItemType();
        $voucherInfo = Voucher::select(['vcId','vcSn','vSn','vAddTime','vMobilephone','vUseMoney','vUseTime','vUseStart','vUseEnd','vOrderSn','vSalonName','vItemName','vcTitle','REDEEM_CODE','vStatus'])
                ->where('vId','=',$id)
                ->first()
                ->toArray();
        // 如果有关联订单 需要找到支付状态
        if( empty($voucherInfo) )
            throw new ApiException('获取信息失败', ERROR::RECEIVABLES_ERROR);
        if( !empty( $voucherInfo['vOrderSn'] ) ){
            $isPay = \App\Order::select(['ispay'])->where('ordersn','=',$voucherInfo['vOrderSn'])->first()->toArray();
            if( empty( $isPay ) ) throw new ApiException('获取信息失败', ERROR::RECEIVABLES_ERROR);
            $voucherInfo['isPay'] = $isPay['ispay'] == 1 ?  '未支付' :  '已支付';
        }else $voucherInfo['isPay'] = '';
        $voucherConfInfo = \App\VoucherConf::select(['useItemTypes','useLimitTypes','useNeedMoney','getTypes','getItemTypes','getCodeType','getCode','getNeedMoney'])
                ->where('vcId','=',$voucherInfo['vcId'])
                ->first()
                ->toArray();
        // 加上失效判断
        if( $voucherInfo['vStatus'] != 2 && !empty($voucherInfo['vUseEnd']) && $voucherInfo['vUseEnd']<time() )
            $voucherInfo['vStatus'] = 5;
        // 查找获取条件
        $getText = ['','新注册;','首次消费;','指定手机号;','指定集团码用户;','指定活动码用户;','指定店铺码用户;','指定消费项目;','全平台用户;','H5用户;'];
        $voucherInfo['getText'] = '';
        if( $voucherConfInfo['getTypes'] == 1 || $voucherConfInfo['getTypes'] == 2 )
            $voucherInfo['getText'] .= $getText[  $voucherConfInfo['getTypes']  ];
        elseif( $voucherConfInfo['getTypes'] == 3 )
            $voucherInfo['getText'] = $getText[  3  ]; 
        elseif( $voucherConfInfo['getTypes'] == 4 ){
            if( !empty($voucherConfInfo['getNeedMoney']) ) $voucherInfo['getText'].= "项目消费满". $voucherConfInfo['getNeedMoney'] ."元获取;";
            $voucherInfo['getText'] .= '全平台用户('; 
            $codeText = ['','店铺码用户;','集团码用户;','活动码用户;'];
            if( !empty($voucherConfInfo['getCodeType']) )   $voucherInfo['getText'] .= $codeText[ $voucherConfInfo['getCodeType'] ];
            if( !empty( $voucherConfInfo['getItemTypes'] ) ){
                $getTypes = explode(',',rtrim(ltrim($voucherConfInfo['getItemTypes'],','),','));
                $tempItemArr = [];
                foreach($allItemType as $val){
                    $tempItemArr[ $val['typeid'] ] = $val;
                }
                foreach( $getTypes as $val){
                    $voucherInfo['getText'] .= $tempItemArr[ $val ]['typename'] . ";";
                }
            }
            $voucherInfo['getText'] .= ")";
        }elseif( $voucherConfInfo['getTypes'] == 5 ) $voucherInfo['getText'] = 'H5用户;'; 
        
        $voucherInfo['useLimitText'] = '';
        // 查找限制条件
        if( !empty( $voucherConfInfo['useLimitTypes'] ) && $voucherConfInfo['useLimitTypes'] == 2 ) $voucherInfo['useLimitText'] .= '限制首单;';
        if( !empty( $voucherConfInfo['useItemTypes'] ) ){
            $getTypes = explode(',',rtrim(ltrim($voucherConfInfo['useItemTypes'],','),','));
            $tempItemArr = [];
            foreach($allItemType as $val){
                $tempItemArr[ $val['typeid'] ] = $val;
            }
            if( !empty($getTypes) )
                $voucherInfo['useLimitText'] .= "指定";
            foreach( $getTypes as $val){
                $voucherInfo['useLimitText'] .= $tempItemArr[ $val ]['typename'] . ";";
            }
        }
        if( !empty( $voucherConfInfo['useNeedMoney'] ) ) $voucherInfo['useLimitText'] .= "项目消费满". $voucherConfInfo['useNeedMoney'] ."元使用;";
        if( empty($voucherInfo['vUseTime']) )  $voucherInfo['vUseTime'] = '';  
        $voucherInfo['sharePhone'] = '';
        $vType = \App\VoucherConf::select(['vType'])->where(['vcSn'=>$voucherInfo['vcSn']])->first();
        if( !empty($vType) ){
            $vType = $vType->toArray();
            $vType = $vType['vType'];
            if( $vType == 2 ){
                $commentId = \App\Laisee::select(['item_comment_id'])->where(['vsn'=>$voucherInfo['vSn']])->first();
                if( !empty($commentId) ){
                   $commentId = $commentId->toArray();
                   $commentId = $commentId['item_comment_id'];
                   $userId = \App\SalonItemComment::select(['user_id'])->where(['itemcommentid'=>$commentId])->first()->toArray();
                   $userId = $userId['user_id'];
                   $phone = \App\User::select(['mobilephone'])->where(['user_id'=>$userId])->first()->toArray();
                   $voucherInfo['sharePhone'] = $phone['mobilephone'];
                }
            }
        }
        if( $voucherInfo['vStatus'] == 1 ){
            $voucherInfo['vSalonName'] = '';
            $voucherInfo['vItemName'] = '';
        }
        return $this->success( $voucherInfo );
	}
    // 获取分类
    private function _getItemType(){
        // 这里用于 代金劵和配置中会和前端约定 增加一个项目特价类型为typeid为101
        $itemType = \App\SalonItemType::select(['typeid','typename'])
                ->where('status','=',1)
                ->orderBy('sortIt','DESC')
                ->get()
                ->toArray();
        array_unshift( $itemType , array('typeid'=>101,'typename'=>'限时特价 ') );
        return $itemType;
    }
}