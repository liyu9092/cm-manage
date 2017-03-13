<?php
namespace App\Http\Controllers\BookCalendar;

use App\Http\Controllers\Controller;
use DB;
use Excel;
use Event;
use App\Exceptions\ApiException;
use Illuminate\Pagination\AbstractPaginator;
use Log;
use App\BookingCalendar;
use App\BookingCalendarLimit;
use App\BookingOrder;
use App\BookingOrderItem;
use App\Manager;
use Illuminate\Support\Facades\Redis as Redis;

class CalendarController extends Controller {
	
	/***
	* @api {post} /calendar/index 1.获取本月概况
	* @apiName index
	* @apiGroup Calendar
	*
	*
	* @apiParam {String} 	searchData          选填        搜索的月份 如 2015-12
	*
	* @apiSuccess {Number} id 					定妆中心id.
	* @apiSuccess {String} bookingDate 			日期.
	* @apiSuccess {Number} quantity 			预约个数（同时也是实际预约个数）
	* @apiSuccess {Number} bookingMorn 			上午预约到店人数
	* @apiSuccess {Number} bookingAfternoon 	下午预约到店人数
	* @apiSuccess {Number} came 				到店人数
	* @apiSuccess {Number} bookingLimit 		预约上限
	* @apiSuccess {Number} nowDay 				0：当天 -1：过去 1：将来
	*
	* @apiSuccessExample Success-Response:
	*	{
	*		"result": 1,
	*		"data": [
	*          {
	*              "id": 1,
	*              "bookingDate": 1,
	*              "quantity": 20,
	*              "bookingMorn": 10,
	*              "bookingAfternoon": 2,
	*              "came": 5,
	*              "bookingLimit": 300,
	*              "nowDay": -1
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
	public function index(){
		$param = $this->param;
		$searchDate = date('Y-m');
		if( isset($param['searchData']) && !empty($param['searchData']) )	$searchDate = $param['searchData'];

		$field ='BEAUTY_ID as id,BOOKING_DATE as bookingDate,SUM(QUANTITY) as quantity,SUM(BOOKING_MORN_COUNT) as bookingMorn,
			SUM(BOOKING_AFTERNOON_COUNT) as bookingAfternoon,SUM(CAME) as came';
		$result = BookingCalendar::select(DB::raw($field))->where('BOOKING_DATE','like','%'.$searchDate.'%')->orderBy('BOOKING_DATE','ASC')->groupBy('BOOKING_DATE')->get()->toArray();

		$result2 = BookingCalendarLimit::select(['BOOKING_DATE as bookingDate','BOOKING_LIMIT as bookingLimit'])->where('BOOKING_DATE','like','%'.$searchDate.'%')->orderBy('BOOKING_DATE','ASC')->get()->toArray();
		// 本月天数
		$nowInterval = idate( 't' , strtotime($searchDate) );
		// 组装有月份的数据
		$tempCalendar = [];
		$iNowDate = strtotime(date('Y-m-d'));
		for($i=1,$j=0,$x=0;$i<=$nowInterval;$i++,$x++){
			$monthTemp = $i<10 ? $searchDate . '-0'.$i : $searchDate.'-'.$i;
			
			if( strtotime($monthTemp) <$iNowDate )
				$returnData[$x]['nowDay'] = -1;
			elseif(strtotime($monthTemp) == $iNowDate )
				$returnData[$x]['nowDay'] = 0;
			else 
				$returnData[$x]['nowDay'] = 1;
			if( isset($tempCalendar[ $monthTemp ]) && !empty( $tempCalendar[ $monthTemp ] ) )
				$returnData[$x]['bookingLimit'] = $tempCalendar[ $monthTemp ];
			else
				$returnData[$x]['bookingLimit'] = 300;
			if( !isset($result[$j]) || (isset( $result[$j]) && $result[$j]['bookingDate'] != $monthTemp) ){
				if( empty($result) ) $id = 1;
				else $id = $result[0]['id'];
				$returnData[$x]['id'] = $id;
				$returnData[$x]['bookingDate'] = $monthTemp;
				$returnData[$x]['quantity'] = 0;
				$returnData[$x]['bookingMorn'] = 0;
				$returnData[$x]['bookingAfternoon'] = 0;
				$returnData[$x]['came'] = 0;
				continue;
			}
			if( isset( $result[$j]) && $result[$j]['bookingDate'] == $monthTemp ){
				$returnData[$x]['id'] = $result[$j]['id'];
				$returnData[$x]['bookingDate'] = $monthTemp;
				$returnData[$x]['quantity'] = $result[$j]['quantity'];
				$returnData[$x]['bookingMorn'] = $result[$j]['bookingMorn'];
				$returnData[$x]['bookingAfternoon'] = $result[$j]['bookingAfternoon'];
				$returnData[$x]['came'] = $result[$j]['came'];
				$j++;
			}
		}
		unset( $result );
		unset( $result2 );
		return $this->success($returnData);
	}
	/***
	* @api {post} /calendar/getDay 2.查看日期订单预约情况
	* @apiName getDay
	* @apiGroup Calendar
	* 
	* @apiParam {Number} day 			必填 日期 如 2015-12-09
	* @apiParam {String} sort_key 		可选 排序字段有 UPDATED_BOOKING_DATE: 预约日期  COME_SHOP: 到店状态  BOOKING_DESC：预约调整
	* @apiParam {Number} sort_type 		可选 排序方式 ASC ：升序  DESC 降序
	* @apiParam {String} page 			第几页
	* @apiParam {String} page_size 		每页请求条数默认为20
	*
	*
	* @apiSuccess {Number} total 总数据量.
	* @apiSuccess {Number} per_page 分页大小.
	* @apiSuccess {Number} current_page 当前页面.
	* @apiSuccess {Number} last_page 当前页面.
	* @apiSuccess {Number} from 起始数据.
	* @apiSuccess {Number} to 结束数据.
	* @apiSuccess {String} orderSn  		订单号
	* @apiSuccess {String} bookerPhone 		手机号
	* @apiSuccess {String} bookerName 		姓名
	* @apiSuccess {String} bookerSex 		性别		F:女 M:男
	* @apiSuccess {String} amount 			预约金额
	* @apiSuccess {String} comeShop 		订单状态		（是否到店）NON - 未 COME -已
	* @apiSuccess {String} bookingDesc 		预约调整 	DEF-未选择，MORNING - 上午，AFTERNOON下午
	* @apiSuccess {String} consumeCallPhone 客服是否打过该电话  	NON - 未拨打 CALL - 已经拨打过
	* @apiSuccess {String} bookingTime 		预约日期 	
	* @apiSuccess {String} itemName 		预约项目
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
	*                       "orderSn": "4929706017687",
	*                       "bookerPhone": "15102011866",
	*                       "bookerName": "礼平6（先生）",
	*                       "bookerSex": "F",
	*                       "amount": "600.00",
	*                       "bookingDesc": "NON",
	*                       "bookingDesc": "DEF",
	*                       "consumeCallPhone": "NON",
	*                       "bookingTime": "2015-12-15",
	*                       "itemName": "韩式眼线（院长）,韩式眉毛（院长）,韩式提拉"
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
	public function getDayIndex(){
		$param = $this->param;
		if( !isset($param['day']) ) return $this->error('未选定那一天');
		if( isset($param['sort_key']) || !empty($param['sort_key'])) $orderField = $param['sort_key'];
		else $orderField = 'CREATE_TIME';
		if( isset($param['sort_type']) || !empty($param['sort_type'])) $orderBy = $param['sort_type'];
		else $orderBy = 'ASC';
		

		$queryDay = $param['day'];
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;
		$field = [
			'ORDER_SN as orderSn','BOOKING_DATE as bookingDate','UPDATED_BOOKING_DATE as updateBookDate',
			'BOOKER_PHONE as bookerPhone','BOOKER_NAME as bookerName','BOOKER_SEX as bookerSex','PAYABLE as amount',
			'BOOKING_DESC as bookingDesc','COME_SHOP as comeShop'
		];
		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
			return $page;
		});
		$result = BookingOrder::select($field)->whereRaw("(( BOOKING_DATE='$queryDay' AND UPDATED_BOOKING_DATE IS NULL) OR UPDATED_BOOKING_DATE='$queryDay') AND  STATUS in ('PYD','CSD') ")->orderBy($orderField,$orderBy)->paginate($page_size)->toArray();
		
		$associateItem = [];
		$temp = [];
		$redis = Redis::connection();
		$keyPre = 'CALENDAR-CALL-'; 
        $key = $keyPre.date('Y-m-d');
		foreach( $result['data'] as $k => $v ){
			$associateItem[] = $v['orderSn'];
			
			if( $v['updateBookDate'] ) $bookingTime = $v['updateBookDate'];
			else $bookingTime = $v['bookingDate'];
			$result['data'][$k]['bookingTime']  = $bookingTime;
			unset( $result['data'][$k]['updateBookDate'] );
			unset( $result['data'][$k]['bookingDate'] );
			
			if( $redis->get( $keyPre . $v['orderSn']  ) === NULL ){
				$result[ 'data' ][$k][ 'consumeCallPhone' ]='NON';
			}else{
				$result[ 'data' ][$k][ 'consumeCallPhone' ]='CALL';
			}
			$temp[ $v['orderSn'] ] = $v['orderSn'];
		}
		// 关联查找信息
		$itemNames = BookingOrderItem::select(['ITEM_NAME','ORDER_SN'])->whereIn('ORDER_SN',$associateItem)->orderBy('ORDER_SN','asc')->get()->toArray();
		$i = 0;
		$prev = '';
		$len = count($itemNames)-2;
		foreach( $itemNames as $k => $v ){
			$prev = $temp[ $v['ORDER_SN'] ];
			if( !isset($result['data'][$i]['itemName']) ) $result['data'][$i]['itemName'] = '';
			if( $temp[ $v['ORDER_SN'] ] == $v['ORDER_SN'] &&  $k<= $len && $prev == $itemNames[$k+1]['ORDER_SN']){
				$result['data'][$i]['itemName'] .= $v['ITEM_NAME'] . '，';
			}else{
				$result['data'][$i]['itemName'] .= $v['ITEM_NAME'];
				$i++;
			}
		}
		return $this->success( $result );
	}
	/***
	* @api {get} /calendar/modifyDay 3. 客服修改预约时间
	* @apiName  modifyDay
	* @apiGroup Calendar
	*
	* @apiParam {String} orderSn                   	必填        订单id
	* @apiParam {String} modifyDate                 必填        修改日期 如 2015-12-29
	* @apiParam {String} modifyDesc                 必填        上午 MORNGIN 下午 AFTERNOON 未选择 DEF
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
	*		    "msg": "未授权访问"
	*		}
	***/
	public function modifyDayIndex(){
		$param = $this->param;
		if(!isset($param['orderSn']) || empty($param['orderSn'])) return $this->error('未传递orderSn参数或值错误');
		if(!isset($param['modifyDate']) || empty($param['modifyDate'])) return $this->error('未传递modifyDate参数或值错误');
		if(!isset($param['modifyDesc']) || empty($param['modifyDesc'])) return $this->error('未传递modifyDesc参数或值错误');
		
		$orderSn = $param['orderSn'];
		$modifyDate = $param['modifyDate'];
		$modifyDesc = $param['modifyDesc'];
		$userId = $this->user->id;

		if( strtotime($modifyDate) < strtotime( date('Y-m-d') )  ) return $this->error('预约日期不能小于当天');
		if( !in_array($modifyDesc,['DEF','MORNING','AFTERNOON'] )) return $this->error('未知的时间类型哦！！！');
			
		$oldBookingDate = BookingOrder::select(['BOOKING_DATE','UPDATED_BOOKING_DATE','BOOKING_DESC'])->where(['ORDER_SN'=>$orderSn])->first();
		if(empty($oldBookingDate))	return $this->error('数据有误，请联系管理员');
		// 需要取出预约的项目id哦:(
		if(!empty($modifyDate)) $itemIds = BookingOrderItem::select(['ITEM_ID','QUANTITY'])->where(['ORDER_SN'=>$orderSn])->get()->toArray();
		
		$oldBookingDate = $oldBookingDate->toArray();
		if( $oldBookingDate['UPDATED_BOOKING_DATE'] ) $oldBookingTime = $oldBookingDate['UPDATED_BOOKING_DATE'];
		else $oldBookingTime = $oldBookingDate['BOOKING_DATE'];
		
		$oldBookingDesc = $oldBookingDate['BOOKING_DESC'];
		
		
		
		// 更新开始
		if( $oldBookingTime == $modifyDate && $oldBookingDesc==$modifyDesc ) return $this->success();
		
		DB::beginTransaction();
		// 1. 修改订单的预约时间
		if( $oldBookingTime == $modifyDate ){
			$result1 = BookingOrder::where(['ORDER_SN'=>$orderSn])->update(['BOOKING_DESC'=>$modifyDesc,'RECORD_TIME'=>date('Y-m-d H:i:s'),'CUSTOMER_SERVICE_ID'=>$userId]);
		}else{
			$result1 = BookingOrder::where(['ORDER_SN'=>$orderSn])->update(['UPDATED_BOOKING_DATE'=>$modifyDate,'BOOKING_DESC'=>$modifyDesc,'RECORD_TIME'=>date('Y-m-d H:i:s'),'CUSTOMER_SERVICE_ID'=>$userId]);
		}
		
		
// 		2. 修改日历数据 按照项目id和时间日期逐一修改统计
		$resultN = true;
		$tempCount = 'tempCount';
		$tempResultStr = 'result';
		foreach( $itemIds as $k => $v ){
			$t = BookingCalendar::where(['ITEM_ID'=>$v['ITEM_ID'],'BOOKING_DATE'=>$oldBookingTime])->first();
			if( empty($t) ) { DB::rollBack();return $this->error('数据错误，请联系管理人员');}
			$tOld = $t->toArray();
			
			$tNew = BookingCalendar::where(['ITEM_ID'=>$v['ITEM_ID'],'BOOKING_DATE'=>$modifyDate])->first();

			$itemQuantity = $v['QUANTITY'];
			// 如果新修改的时间日历不存在的情况
			if( empty($tNew) ){
				$updateCallendar = [];
				$updateCallendar['UPDATE_TIME'] = date('Y-m-d H:i:s');
				if( $v['QUANTITY'] == $tOld['QUANTITY'] ){
					if( $oldBookingDesc != $modifyDesc && $oldBookingTime != $modifyDate ){
						$oldDescField = ['MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
						$updateCallendar['BOOKING_DATE'] = $modifyDate;
						switch( $modifyDesc ){
							case 'DEF':
								$updateDesc = $tOld[ $oldDescField[ $oldBookingDesc ] ] - $itemQuantity;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
								break;
							case 'MORNING':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_AFTERNOON_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_AFTERNOON_COUNT'] = $updateDesc;
								$updateCallendar['BOOKING_MORN_COUNT'] = $tOld['BOOKING_MORN_COUNT']+1;
								break;
							case 'AFTERNOON':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_MORN_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_MORN_COUNT'] = $updateDesc;
								$updateCallendar['BOOKING_AFTERNOON_COUNT'] = $tOld['BOOKING_AFTERNOON_COUNT']+1;
								break;
							default:
								break;
						}
					}
					// 					日期变化 具体时间不变
					if($oldBookingDesc == $modifyDesc && $oldBookingTime != $modifyDate){
						$updateCallendar['BOOKING_DATE'] = $modifyDate;
				
					}
					// 日期不变 具体时间变化
					if( $oldBookingTime == $modifyDate && $oldBookingDesc != $modifyDesc ){
						$oldDescField = ['MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
						switch( $modifyDesc ){
							case 'DEF':
								$updateDesc = $tOld[ $oldDescField[ $oldBookingDesc ]] -$itemQuantity;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
								break;
							case 'MORNING':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_AFTERNOON_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_AFTERNOON_COUNT'] = $updateDesc;
								$updateCallendar['BOOKING_MORN_COUNT'] = $tOld['BOOKING_MORN_COUNT']+$itemQuantity;
								break;
							case 'AFTERNOON':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_MORN_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_MORN_COUNT'] = $updateDesc;
								$updateCallendar['BOOKING_AFTERNOON_COUNT'] = $tOld['BOOKING_AFTERNOON_COUNT']+$itemQuantity;
								break;
							default:
								break;
						}
					}
					$tempResult[ $tempResultStr . ($k+2) ] = BookingCalendar::where(['ITEM_ID'=>$v['ITEM_ID'],'BOOKING_DATE'=>$oldBookingTime])->update($updateCallendar);
				}else{
					$tNewData = $tOld;
					
					$tNewData['BOOKING_DATE'] = $modifyDate;
					$tNewData['QUANTITY'] = $v['QUANTITY'];
					$tNewData['UPDATE_TIME'] = date('Y-m-d H:i:s');
					$tNewData['BOOKING_MORN_COUNT'] = 0;
					$tNewData['BOOKING_AFTERNOON_COUNT'] = 0;
					$tNewData['CAME'] = 0; 
					unset( $tNewData['ID'] );
					$updateCallendar = [];
					$updateCallendar['UPDATE_TIME'] = date('Y-m-d H:i:s');
					$updateCallendar['QUANTITY'] =  $tOld['QUANTITY'] - $v['QUANTITY'];
					
					if( $oldBookingDesc != $modifyDesc && $oldBookingTime != $modifyDate ){
						$oldDescField = ['MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
						switch( $modifyDesc ){
							case 'DEF':
								$updateDesc = $tOld[$oldDescField[ $oldBookingDesc ]] -$itemQuantity;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
								break;
							case 'MORNING':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_AFTERNOON_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_AFTERNOON_COUNT'] = $updateDesc;
								$tNewData['BOOKING_MORN_COUNT'] = $itemQuantity;
								break;
							case 'AFTERNOON':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_MORN_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_MORN_COUNT'] = $updateDesc;
								$tNewData['BOOKING_AFTERNOON_COUNT'] = $itemQuantity;
								break;
							default:
								break;
						}
					}
					// 	日期变化 具体时间不变
					if($oldBookingDesc == $modifyDesc && $oldBookingTime != $modifyDate && $modifyDesc != 'DEF' ){
						$oldDescField = ['MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
						$tNewData[ $oldDescField[ $oldBookingDesc ] ] = $itemQuantity;
						$updateDesc = $tOld[ $oldDescField[$oldBookingDesc] ]-$itemQuantity;
						if( $updateDesc < 0 ) $updateDesc = 0;
						$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
					}
					// 日期不变 具体时间变化
					if( $oldBookingTime == $modifyDate && $oldBookingDesc != $modifyDesc ){
						$oldDescField = ['MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
						switch( $modifyDesc ){
							case 'DEF':
								$updateDesc = $tOld[ $oldDescField[ $oldBookingDesc ]]-$itemQuantity;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
								break;
							case 'MORNING':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_AFTERNOON_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_AFTERNOON_COUNT'] = $updateDesc;
								$tNewData['BOOKING_MORN_COUNT'] = $tempee ;
								break;
							case 'AFTERNOON':
								$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
								$updateDesc = $tOld['BOOKING_MORN_COUNT']-$tempee;
								if( $updateDesc < 0 ) $updateDesc = 0;
								$updateCallendar['BOOKING_MORN_COUNT'] = $updateDesc;
								$tNewData['BOOKING_AFTERNOON_COUNT'] = $itemQuantity;
								break;
							default:
								break;
						}
					}
					$tempResult[ $tempResultStr . ($k+2) ] = BookingCalendar::where(['ITEM_ID'=>$v['ITEM_ID'],'BOOKING_DATE'=>$oldBookingTime])->update($updateCallendar);
					$tempResult[ $tempResultStr . ($k+20) ] = BookingCalendar::insertGetId( $tNewData );
				}
			}else{
				$tNewData = $updateCallendar = [];
				$updateCallendar = [];
				$tNewData['UPDATE_TIME'] = $updateCallendar['UPDATE_TIME'] = date('Y-m-d H:i:s');
				$oldDescField = ['MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
				// 此旧日历统计只含有修改的项目情况时
				if( $oldBookingDesc != $modifyDesc && $oldBookingTime != $modifyDate ){
					$updateCallendar['QUANTITY'] =  $tOld['QUANTITY'] - $itemQuantity;
					$tNewData['QUANTITY'] =  $tNew['QUANTITY'] + $itemQuantity;
					switch( $modifyDesc ){
						case 'DEF':
							$updateDesc = $tOld[$oldDescField[ $oldBookingDesc ]] -$itemQuantity;
							if( $updateDesc < 0 ) $updateDesc = 0;
							$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
							$tNewData[ $oldDescField[ $oldBookingDesc ] ] = $tNew[$oldDescField[ $oldBookingDesc ]] +$itemQuantity;
							break;
						case 'MORNING':
							$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
							$updateDesc = $tOld['BOOKING_AFTERNOON_COUNT']-$tempee;
							if( $updateDesc < 0 ) $updateDesc = 0;
							$updateCallendar['BOOKING_AFTERNOON_COUNT'] =  $updateDesc;
							$tNewData['BOOKING_MORN_COUNT'] = $tNew['BOOKING_MORN_COUNT']+$itemQuantity;
							break;
						case 'AFTERNOON':
							$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
							$updateDesc = $tOld['BOOKING_MORN_COUNT']-$tempee;
							if( $updateDesc < 0 ) $updateDesc = 0;
							$updateCallendar['BOOKING_MORN_COUNT'] = $updateDesc;
							$tNewData['BOOKING_AFTERNOON_COUNT'] = $tNew['BOOKING_AFTERNOON_COUNT']+$itemQuantity;
							break;
						default:
							break;
					}
					
				}
				// 					日期变化 具体时间不变
				if($oldBookingDesc == $modifyDesc && $oldBookingTime != $modifyDate ){
					$updateCallendar['QUANTITY'] =  $tOld['QUANTITY'] - $itemQuantity;
					$tNewData['QUANTITY'] =  $tNew['QUANTITY'] + $itemQuantity;
					if( $modifyDesc != 'DEF' ){
						$updateDesc = $tOld[ $oldDescField[ $oldBookingDesc ] ]-$itemQuantity;
						if( $updateDesc<0 ) $updateDesc = 0;
						$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
						$tNewData[ $oldDescField[ $oldBookingDesc ] ] = $tNew[ $oldDescField[ $oldBookingDesc ] ]+$itemQuantity;
						
					}
				}
				// 日期不变 具体时间变化
				if( $oldBookingTime == $modifyDate && $oldBookingDesc != $modifyDesc ){
					switch( $modifyDesc ){
						case 'DEF':
							$updateDesc = $tOld[ $oldDescField[ $oldBookingDesc ]] -$itemQuantity;
							if( $updateDesc < 0 ) $updateDesc = 0;
							$updateCallendar[ $oldDescField[ $oldBookingDesc ] ] = $updateDesc;
							$tNewData[ $oldDescField[ $oldBookingDesc ] ] = $tNew[ $oldDescField[ $oldBookingDesc ]] +$itemQuantity;
							break;
						case 'MORNING':
							$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
							$updateDesc = $tOld['BOOKING_AFTERNOON_COUNT']-$tempee;
							if( $updateDesc < 0 ) $updateDesc = 0;
							$updateCallendar['BOOKING_AFTERNOON_COUNT'] = $updateDesc;
							$tNewData['BOOKING_MORN_COUNT'] = $tNew['BOOKING_MORN_COUNT']+$itemQuantity;
							break;
						case 'AFTERNOON':
							$tempee = isset( $oldDescField[ $oldBookingDesc ] )?$itemQuantity:0;
							$updateDesc = $tOld['BOOKING_MORN_COUNT'] - $tempee;
							if( $updateDesc < 0 ) $updateDesc = 0;
							$updateCallendar['BOOKING_MORN_COUNT'] = $updateDesc;
							$tNewData['BOOKING_AFTERNOON_COUNT'] = $tNew['BOOKING_AFTERNOON_COUNT']+$itemQuantity;
							break;
						default:
							break;
					}
				}
				$tOldNumber = $tOld['QUANTITY'] - $itemQuantity;
				if( $tOldNumber == 0 && $oldBookingTime != $modifyDate )
					$tempResult[ $tempResultStr . ($k+2) ] = BookingCalendar::where(['ITEM_ID'=>$v['ITEM_ID'],'BOOKING_DATE'=>$oldBookingTime])->delete();
				else
					$tempResult[ $tempResultStr . ($k+2) ] = BookingCalendar::where(['ITEM_ID'=>$v['ITEM_ID'],'BOOKING_DATE'=>$oldBookingTime])->update($updateCallendar);
					
				$tempResult[ $tempResultStr . ($k+20) ] = BookingCalendar::where( ['ITEM_ID'=>$v['ITEM_ID'],'BOOKING_DATE'=>$modifyDate])->update($tNewData);
			}
		}
		foreach( $tempResult as $k => $v ){
			if(empty($v)){ $resultN = false; break 1;}
		}
// 		DB::rollBack();
// 		var_dump($result1,$resultN);exit;
		if( $result1 && $resultN ){
			Event::fire('calendar.modifyDay','修改预约时间');
			DB::commit();
			return $this->success();
		}else{
			DB::rollBack();
			return $this->error('修改失败，请稍后再试');
		}
	}

	/***
	 * @api {get} /calendar/status/{orderSn} 	4. 客服修改拨打电话的状态
	* @apiName  status
	* @apiGroup Calendar
	*
	* @apiParam {String} orderSn                   	必填        订单id
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
	*		    "msg": "小华正在通话中"
	*		}
	***/
	public function modifyDayStatus( $orderSn = '' ){
		$userId = $this->user->id;
		$redis = Redis::connection();
		$keyPre = 'CALENDAR-CALL-';
		$result = BookingOrder::select(['BOOKING_DATE as bookingDate','UPDATED_BOOKING_DATE as updatedBookingDate'])->where(['ORDER_SN'=>$orderSn])->first();
		if(empty($result)) {
			return $this->error('数据错误，请联系管理员');
		}
		if($redis->get($keyPre . $orderSn) === NULL){
			Event::fire('calendar.status','客服id为 '.$userId);
			$redis->setex( $keyPre . $orderSn,120,$userId);
			return $this->success();
		}else{
			$userId = $redis->get($keyPre . $orderSn);
			$name = Manager::select(['name'])->where(['id'=>$userId])->first();
			if( empty($name) ) return $this->error('数据错误了');
			$name = $name['name'];
			return $this->error($name."正在通话中");
		}
	}
	/***
	 * @api {post} /calendar/modifyLimit 	5. 修改预约上限
	* @apiName  modifyLimit
	* @apiGroup Calendar
	*
	* @apiParam {array[Json]} data              必填        外城包裹
	* @apiParam {Number} id 					必填 	定妆中心id
	* @apiParam {String} date                   必填        修改日期 如 2015-12-29
	* @apiParam {String} limit                  必填        修改的数量
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
	*		    "msg": "未授权访问"
	*		}
	***/
	public function setCalendar(){
		$param = $this->param;
		if(empty($param) || !isset($param['data']) || empty($param['data']) )	return $this->error('参数数据错误');
		
		$data = json_decode($param['data'],true);
// 		$i = 0;
		$nowYear = date('Y');
		foreach($data as $k => $v){
			$date = $v['date'];
			$limit = $v['limit'];
			$id = $v['id'];
			
			$calendarSize = BookingCalendar::where(['BOOKING_DATE'=>$date])->sum('QUANTITY');
			if( $limit < $calendarSize ) return $this->error('设置预约'. $date .'数据错误，实际预约量应小于上限');
			if( $limit > 9999 ) return $this->error('设置预约修改范围，0-9999整数');
			$year = idate( 'Y',strtotime($date) );
			if( $nowYear + 10 < $year) return $this->error('设置的年份超过未来的20年哦');
			$exists = BookingCalendarLimit::where(['BOOKING_DATE'=>$date])->count();
			if( $exists ) {
				$u = BookingCalendarLimit::where(['BOOKING_DATE'=>$date])->update(['BOOKING_LIMIT'=>$limit]);
// 				if( $u ) $i+=1;
			}else{
				$insertData = [
					'BEAUTY_ID'=>$id,
					'BOOKING_DATE'=>$date,
					'BOOKING_LIMIT'=>$limit
				];
				$u = BookingCalendarLimit::insertGetId($insertData);
// 				if( $u ) $i+=1;
			}
		}
// 		if( $i == count($data) ){
			Event::fire('calendar.modifyLimit','修改预约上限 ');
			return $this->success();
// 		}
// 		return $this->error('有' .(count($data)-$i).'条数据修改失败哦');
	}
	/***
	 * @api {post} /calendar/limit 		6.获取本月限制设置概况
	* @apiName limit
	* @apiGroup Calendar
	*
	*
	* @apiParam {String} 	searchData          选填        搜索的月份 如 2015-12
	*
	* @apiSuccess {Number} id 					定妆中心id.
	* @apiSuccess {String} bookingDate 			日期.
	* @apiSuccess {Number} quantity 			预约个数（同时也是实际预约个数）
	* @apiSuccess {Number} bookingMorn 			上午预约到店人数
	* @apiSuccess {Number} bookingAfternoon 	下午预约到店人数
	* @apiSuccess {Number} came 				到店人数
	* @apiSuccess {Number} bookingLimit 		预约上限
	* @apiSuccess {Number} nowDay 				0：当天 -1：过去 1：将来
	*
	* @apiSuccessExample Success-Response:
	*	{
	*		"result": 1,
	*		"data": [
	*          {
	*              "id": 1,
	*              "bookingDate": 1,
	*              "quantity": 20,
	*              "bookingMorn": 10,
	*              "bookingAfternoon": 2,
	*              "came": 5,
	*              "bookingLimit": 300,
	*              "nowDay": 0
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
	public function indexLimit(){
		$param = $this->param;
		$searchDate = date('Y-m');
		if( isset($param['searchData']) && !empty($param['searchData']) )	$searchDate = $param['searchData'];
	
		$field ='BEAUTY_ID as id,BOOKING_DATE as bookingDate,SUM(QUANTITY) as quantity,SUM(BOOKING_MORN_COUNT) as bookingMorn,
			SUM(BOOKING_AFTERNOON_COUNT) as bookingAfternoon,SUM(CAME) as came';
		$result = BookingCalendar::select(DB::raw($field))->where('BOOKING_DATE','like','%'.$searchDate.'%')->orderBy('BOOKING_DATE','ASC')->groupBy('BOOKING_DATE')->get()->toArray();
	
		$result2 = BookingCalendarLimit::select(['BOOKING_DATE as bookingDate','BOOKING_LIMIT as bookingLimit'])->where('BOOKING_DATE','like','%'.$searchDate.'%')->orderBy('BOOKING_DATE','ASC')->get()->toArray();
		// 本月天数
		$nowInterval = idate( 't' , strtotime($searchDate) );
		$tempCalendar = [];
		foreach( $result2 as $k=>$v ){
			$tempCalendar[ $v['bookingDate'] ] = $v['bookingLimit'];
		}
		$iNowDate = strtotime(date('Y-m-d'));
		// 组装有月份的数据
		for($i=1,$j=0,$x=0;$i<=$nowInterval;$i++,$x++){
			$monthTemp = $i<10 ? $searchDate . '-0'.$i : $searchDate.'-'.$i;
			if( strtotime($monthTemp) <$iNowDate )
				$returnData[$x]['nowDay'] = -1;
			elseif(strtotime($monthTemp) == $iNowDate )
			$returnData[$x]['nowDay'] = 0;
			else
				$returnData[$x]['nowDay'] = 1;
			if( isset($tempCalendar[ $monthTemp ]) )
				$returnData[$x]['bookingLimit'] = $tempCalendar[ $monthTemp ];
			else
				$returnData[$x]['bookingLimit'] = 300;
			
			if( !isset($result[$j]) || (isset( $result[$j]) && $result[$j]['bookingDate'] != $monthTemp) ){
				if( empty($result) ) $id = 1;
				else $id = $result[0]['id'];
				$returnData[$x]['id'] = $id;
				$returnData[$x]['bookingDate'] = $monthTemp;
				$returnData[$x]['quantity'] = 0;
				$returnData[$x]['bookingMorn'] = 0;
				$returnData[$x]['bookingAfternoon'] = 0;
				$returnData[$x]['came'] = 0;
				continue;
			}
			if( isset( $result[$j]) && $result[$j]['bookingDate'] == $monthTemp ){
				$returnData[$x]['id'] = $result[$j]['id'];
				$returnData[$x]['bookingDate'] = $monthTemp;
				$returnData[$x]['quantity'] = $result[$j]['quantity'];
				$returnData[$x]['bookingMorn'] = $result[$j]['bookingMorn'];
				$returnData[$x]['bookingAfternoon'] = $result[$j]['bookingAfternoon'];
				$returnData[$x]['came'] = $result[$j]['came'];
				$j++;
			}
		}
		unset( $result );
		unset( $result2 );
		return $this->success($returnData);
	}
	/***
	 * @api {get} /calendar/export 	7.导出查看日期订单预约情况
	* @apiName  export
	* @apiGroup Calendar
	*
	* @apiParam {Number} day 			必填 日期 如 2015-12-09
	* @apiParam {String} sort_key 		可选 排序字段有 UPDATED_BOOKING_DATE: 预约日期  COME_SHOP: 到店状态  BOOKING_DESC：预约调整
	* @apiParam {Number} sort_type 		可选 排序方式 ASC ：升序  DESC 降序
	* @apiParam {String} page 			第几页
	* @apiParam {String} page_size 		每页请求条数默认为20
	*
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
	public function exportDayIndex(){
		$param = $this->param;
		if( !isset($param['day']) ) return $this->error('未选定那一天');
		if( isset($param['sort_key']) || !empty($param['sort_key'])) $orderField = $param['sort_key'];
		else $orderField = 'CREATE_TIME';
		if( isset($param['sort_type']) || !empty($param['sort_type'])) $orderBy = $param['sort_type'];
		else $orderBy = 'ASC';
	
	
		$queryDay = $param['day'];
		$page = isset($param['page'])?max($param['page'],1):1;
		$page_size = isset($param['page_size'])?$param['page_size']:20;
		$field = [
		'ORDER_SN as orderSn','BOOKING_DATE as bookingDate','UPDATED_BOOKING_DATE as updateBookDate',
		'BOOKER_PHONE as bookerPhone','BOOKER_NAME as bookerName','BOOKER_SEX as bookerSex','PAYABLE as amount',
		'BOOKING_DESC as bookingDesc','CONSUME_CALL_PHONE as consumeCallPhone','COME_SHOP as comeShop'
				];
		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
			return $page;
		});
		$result = BookingOrder::select($field)->whereRaw("(( BOOKING_DATE='$queryDay' AND UPDATED_BOOKING_DATE IS NULL) OR UPDATED_BOOKING_DATE='$queryDay') AND  STATUS in ('PYD','CSD')")->orderBy($orderField,$orderBy)->paginate($page_size)->toArray();
	
		$associateItem = [];
		$temp = [];
		foreach( $result['data'] as $k => $v ){
			$associateItem[] = $v['orderSn'];
			if( $v['updateBookDate'] ) $result['data'][$k]['bookingTime'] = $v['updateBookDate'];
			else $result['data'][$k]['bookingTime'] = $v['bookingDate'];
			unset( $result['data'][$k]['updateBookDate'] );
			unset( $result['data'][$k]['bookingDate'] );
			$temp[ $v['orderSn'] ] = $v['orderSn'];
		}
		// 关联查找信息
		$itemNames = BookingOrderItem::select(['ITEM_NAME','ORDER_SN'])->whereIn('ORDER_SN',$associateItem)->orderBy('ORDER_SN','asc')->get()->toArray();
		$i = 0;
		$prev = '';
		$len = count($itemNames)-2;
		foreach( $itemNames as $k => $v ){
			$prev = $temp[ $v['ORDER_SN'] ];
			if( !isset($result['data'][$i]['itemName']) ) $result['data'][$i]['itemName'] = '';
			if( $temp[ $v['ORDER_SN'] ] == $v['ORDER_SN'] &&  $k<= $len && $prev == $itemNames[$k+1]['ORDER_SN']){
				$result['data'][$i]['itemName'] .= $v['ITEM_NAME'] . '，';
			}else{
				$result['data'][$i]['itemName'] .= $v['ITEM_NAME'];
				$i++;
			}
		}
		$result = $result['data'];
		$tempData = [];
		$temp1 = ['F'=>'女','M'=>'男'];
		$temp2 = ['DEF'=>'未选择','MORNING'=>'上午','AFTERNOON'=>'下午'];
		$temp3 = ['NON'=>'未拨打','CALL'=>'已拨打'];
		$temp4 = ['NON'=>'未到店','COME'=>'已到店'];
		foreach( $result as $k => $v ){
			$tempData[$k][] = $v['bookerPhone'];
			$tempData[$k][] = $v['bookerName'];
			$tempData[$k][] = $temp1[ $v['bookerSex'] ];
			$tempData[$k][] = $v['itemName'];
			$tempData[$k][] = $v['amount'];
			$tempData[$k][] = $temp4[ $v['comeShop'] ];
			$tempData[$k][] = $v['bookingTime'];
			$tempData[$k][] = $temp2[ $v['bookingDesc'] ];
// 			$tempData[$k][] = $temp3[ $v['consumeCallPhone'] ];
			
		}

		Event::fire('calendar.export','导出某日的订单预约列表 ');
		$title = '顾客预约调整' .date('Ymd');
		//导出excel
		$header = ['手机号','姓名','性别','预约项目','预约金额','订单状态','预约日期','预约调整 '/*,'客服是否拨打电话'*/];
		Excel::create($title, function($excel) use($tempData,$header){
			$excel->sheet('Sheet1', function($sheet) use($tempData,$header){
				$sheet->fromArray($tempData, null, 'A1', false, false);//第五个参数为是否自动生成header,这里设置为false
				$sheet->prependRow(1, $header);//添加表头
			});
		})->export('xls');
		exit;
	}
}