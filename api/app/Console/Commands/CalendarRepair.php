<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\BookingCalendar;
use App\BookingOrder;
use App\BookingOrderItem;
use App\BookingReceive;
use DB;

class CalendarRepair extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calendar:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        echo ' ----------- --------- 开始处理处理 ---------------------------  ' . "\n";
        
        
//         1.修复项目和订单

        $order = BookingOrder::select(['ORDER_SN AS orderSn','quantity'])->get()->toArray();
        
        $orderItem = BookingOrderItem::select(DB::Raw('order_sn as orderSn,sum(quantity) as quantity'))->groupBy('orderSn')->get()->toArray();
        
        $tempOrder = $tempOrderItem = [];
        $tempOrderErr = $tempOrderItemErr = [];
        
        $tempOrderCountErr = [];
        
        foreach( $order as $k => $v ){
        	$tempOrder[ $v['orderSn'] ] = $v;
        }
        
        foreach( $orderItem as $k => $v ){
        	
        	if( !isset( $tempOrder[ $v['orderSn'] ] ) ) $tempOrderItemErr[] = $v['orderSn'];
        	elseif( $tempOrder[ $v['orderSn'] ]['quantity'] != $v['quantity'] ){
        		$tempOrderCountErr[ $v['orderSn'] ] = $v['quantity'];
        	}
        	$tempOrderItem[ $v['orderSn'] ] = $v;
        }
        
        foreach( $order as $k => $v ){
        	if( !isset( $tempOrderItem[ $v['orderSn'] ] ) ) $tempOrderErr[] = $v['orderSn'];
        }
		$iTempOrderErr = count($tempOrderErr);
		$iTempOrderItemErr = count( $tempOrderItemErr );
		$iTempOrderCount = count( $tempOrderCountErr );
        foreach( $tempOrderErr as $k => $v ){
        	$d = BookingOrder::where(['order_sn'=>$v])->delete();
        	if( $d ) $iTempOrderErr--;
        }
        
        foreach( $tempOrderItemErr as $k => $v ){
        	$d = BookingOrderItem::where(['order_sn'=>$v])->delete();
        	if( $d ) $iTempOrderItemErr--;
        }
        foreach( $tempOrderCountErr as $k=>$v ){
        	$d = BookingOrder::where(['order_sn'=>$k])->update(['quantity'=>$v]);
        	if( $d ) $iTempOrderCount--;
        }
        if( !$iTempOrderErr && !$iTempOrderItemErr && !$iTempOrderCount )
        	echo '1. 订单和项目对应数量和订单条目修复完成'."\n\n";
        
        
//         2.修复日历对应其订单日历相对应问题
        
//         $calendar = BookingCalendar::select(['BOOKING_DATE AS bookingDate','ITEM_ID AS itemId','QUANTITY AS quantity'])->orderBy('bookingDate','asc')->get()->toArray();
        
        $orderCalendar = BookingOrder::whereRaw('STATUS in ("PYD","CSD")')->select([
	        				'BOOKING_DATE AS bookingDate',
	        				'UPDATED_BOOKING_DATE as updateBookingDate',
	        				'ORDER_SN as orderSn',
	        				'BOOKING_DESC as bookingDesc',
        				])
        				->orderBy('updateBookingDate','asc')
        				->orderBy('bookingDate','asc')
        				->get()
        				->toArray();
		
        foreach($orderCalendar as $k => $v){
        	$tempArrTime = BookingReceive::select(['arrive_at'])->where(['order_sn'=>$v['orderSn']])->first();
        	if( empty($tempArrTime) )
        		$orderCalendar[$k]['arriveAt'] = 0;
        	else{
        		$tempArrTime = $tempArrTime->toArray();
        		if( empty( $tempArrTime['arrive_at'] ) )
        			$orderCalendar[$k]['arriveAt'] = 0;
        		else {
        			$orderCalendar[$k]['arriveAt'] = 1;
        			// 将到店设为已到店
        			BookingOrder::where(['order_sn'=>$v['orderSn']])->update(['COME_SHOP'=>'COME']);
        		}
        	}
        }
        // 项目的数量
		$tempOrderItemCalendar = [];
		// 统计上午和下午的数量
		$tempOrderOtherCalendar = [];
	 	// 统计到店数量
	 	$tempOrderCome = [];
// 		$tempOrderDescErr = [];
		$tempField = ['MORNING'=>'BOOKING_MORN_COUNT','AFTERNOON'=>'BOOKING_AFTERNOON_COUNT'];
        foreach( $orderCalendar as $k => $v ){
        	if( !empty($v['updateBookingDate']) ) $bookingTime = $v['updateBookingDate'];
        	else $bookingTime = $v['bookingDate'];
        	if( !isset( $tempOrderItemCalendar[ $bookingTime ]) )	$tempOrderItemCalendar[ $bookingTime ] = [];
        	if( !isset( $tempOrderOtherCalendar[ $bookingTime ] ) )	$tempOrderOtherCalendar[ $bookingTime ] = [];
        	if( !isset( $tempOrderCome[ $bookingTime ] ) )	$tempOrderCome[ $bookingTime ] = [];
        	
        	if( empty($v['bookingDesc']) )	{
        		//         		$tempOrderDescErr[] = $v['orderSn'];
        		BookingOrder::where(['order_sn'=>$v['orderSn']])->update(['BOOKING_DESC'=>'DEF']);
        	}
        	$t = BookingOrderItem::select(DB::Raw('ITEM_ID AS itemId,QUANTITY AS quantity'))->where(['ORDER_SN'=>$v['orderSn']])->get()->toArray();
        	
        	foreach( $t as $k1 => $v1 ){
        		if( !isset( $tempOrderItemCalendar[ $bookingTime ][ $v1['itemId'] ] ) ) $tempOrderItemCalendar[ $bookingTime ][ $v1['itemId'] ] = 0;
        		if( !isset( $tempOrderOtherCalendar[ $bookingTime ][  $v1['itemId']  ] ) ) $tempOrderOtherCalendar[ $bookingTime ][  $v1['itemId']  ] = [];
        		
        		if( !isset( $tempOrderCome[ $bookingTime ][  $v1['itemId']  ] ) ) $tempOrderCome[ $bookingTime ][  $v1['itemId']  ] = 0;
        		
        		if( $v['bookingDesc'] && $v['bookingDesc'] != 'DEF' && !isset( $tempOrderOtherCalendar[ $bookingTime ][ $v1['itemId'] ][ $tempField[ $v['bookingDesc'] ] ] ))
        			$tempOrderOtherCalendar[ $bookingTime ][ $v1['itemId'] ][ $tempField[ $v['bookingDesc'] ] ] = 0;
        		
        		if( $v['bookingDesc'] && $v['bookingDesc'] != 'DEF' )
        			$tempOrderOtherCalendar[ $bookingTime ][ $v1['itemId'] ][ $tempField[ $v['bookingDesc'] ] ] += $v1['quantity'];
        		

        		$tempOrderItemCalendar[ $bookingTime ][ $v1['itemId'] ] += $v1['quantity'];
        		if( $v['arriveAt'] != 0 )	$tempOrderCome[ $bookingTime ][  $v1['itemId']  ] += 1;
        	}
        }
		
        foreach( $tempOrderOtherCalendar as $k => $v ){
        	foreach( $v as $k1 => $v1 ){
        		if(empty($v1)) unset( $tempOrderOtherCalendar[$k][$k1] );
        	}
        }
        foreach( $tempOrderOtherCalendar as $k => $v ){
        		if(empty($v)) unset( $tempOrderOtherCalendar[$k] );
        }
        
        BookingCalendar::where('id','!=',0)->delete();
        
//         print_r($tempOrderCome);exit;
        $insert = [];
        $insert['BEAUTY_ID'] = 1;
        $insert['ITEM_ID'] = 0;
        $insert['BOOKING_DATE'] = '';
        $insert['QUANTITY'] = 0;
        $insert['CREATE_TIME'] = date('Y-m-d');
        $insert['UPDATE_TIME'] = date('Y-m-d');
        $insert['CAME'] = 0;
        
        foreach( $tempOrderItemCalendar as $k => $v ){
        	$insert['BOOKING_DATE'] = $k;
//         	if( $k == '2015-12-30' ){
        		foreach( $v as $k1 => $v1 ){
        			$insert['ITEM_ID'] = $k1;
        			$insert['QUANTITY'] = $v1;
			        $insert['BOOKING_MORN_COUNT'] = 0;
			        $insert['BOOKING_AFTERNOON_COUNT'] = 0;
        			if( isset( $tempOrderOtherCalendar[$k] ) && isset($tempOrderOtherCalendar[$k][$k1])){
        				$key = array_keys($tempOrderOtherCalendar[$k][$k1]);
        				$key = $key[0];
        				$insert[ $key ] = $tempOrderOtherCalendar[$k][$k1][$key];
        			}
        			$insert['CAME'] = $tempOrderCome[$k][$k1];
        			BookingCalendar::insertGetId($insert);
// var_dump( $tempOrderCome[$k][$k1] );
        		}
//         	}
        }
        
//         print_r( $tempOrderItemCalendar );
        
//         print_r( $tempOrderOtherCalendar['2015-12-30'] );
        
        echo "2. 修复日历数据完成\n";
        exit;
//         var_dump( count($calendar),count($order) );
        
    }
}
