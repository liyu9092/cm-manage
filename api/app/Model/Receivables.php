<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis as Redis;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\PayManage;
use App\PrepayBill;
use Event;

class Receivables extends Model {

	protected $table = 'receivables';
	
	public $timestamps = false;
	
	public  static 	$typeArr = [0=>'',1=>'业务投资款返还',2=>'交易代收款返还'] ;
	
	public  static 	$paymentStyleArr =  [0=>'',1=>'银行存款',2=>'账扣返还',3=>'现金',4=>'支付宝',5=>'财付通',6=>'其他'];
	
	public  static 	$statusArr =  [0=>'',1=>'待确认',2=>'已确认'];
	
	/**
	 * 查询列表
	 * */
	public static function getList( $where = '' , $page=1, $page_size=20,$orderName = ' r.addTime  ',$order = 'desc' )
	{
		$fields = array(
				's.salonid',
				's.salonname',
				's.sn',
				'm.name',
				'r.type',
				'r.paymentStyle',
				'r.money',
				'r.addTime',
				'r.singleNumber',
				'r.status',
				'r.receiptDate',
				'r.id',
				'r.remark',
				'mg.name as preparedByName',
			);
		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
		$query =  self::getQueryByParam($where,$orderName,$order,$fields);
         
        $salonList =    $query->paginate($page_size);
        $result = $salonList->toArray();
        return $result;
	}
	
	/**
	 * 获取查询对象
	 * */
	
	private static function getQueryByParam($where = '',$orderName = ' r.addTime  ',$order = 'desc',$fields)
	{
		$query =  DB::table('receivables as r')
		->leftjoin('salon as s', 's.salonid', '=', 'r.salonid')
		->leftjoin('merchant as m', 'm.id', '=', 's.merchantId')
		->leftjoin('managers as mg', 'mg.id', '=', 'r.preparedBy')
		->leftjoin('managers as mgs', 'mgs.id', '=', 'r.cashier')
		->select($fields)
		->orderBy($orderName,$order);

		if($orderName == 'receiptDate')//收款日期排序 时 先按状态排序，eq：  待确认 默认不显示收款日期
		{
			$query =  $query ->orderBy('status','desc');
		}
		$query =  $query ->where('r.status','!=',3);//删除
		if(isset($where['type']) && $where['type'])
		{
			$query =  $query ->where('r.type','=',$where['type']);
		}
		if(isset($where['paymentStyle']) && $where['paymentStyle'])
		{
			$query =  $query ->where('r.paymentStyle','=',$where['paymentStyle']);
		}
		if(isset($where['status']) && $where['status'])
		{
			$query =  $query ->where('r.status','=',$where['status']);
		}
		$selectStatus = 0;
		if(isset($where['startTime']) && $where['startTime'])
		{
			$query =  $query ->where('r.receiptDate','>=',$where['startTime']);
			$selectStatus = 1;
		}
		if(isset($where['endTime']) && $where['endTime'])
		{
			$query =  $query ->where('r.receiptDate','<=',$where['endTime']);
			$selectStatus = 1;
		}
		if($selectStatus)
		{
			$query =  $query ->where('r.status','=',2);//只要确认的收款
		}
		if(isset($where['salonname']) && $where['salonname'])
		{
			$keyword = '%'.$where['salonname'].'%';
			$query = $query->where('s.salonname','like',$keyword);
		}
		if(isset($where['salonSn']) && $where['salonSn'])
		{
			$keyword = '%'.$where['salonSn'].'%';
			$query = $query->where('s.sn','like',$keyword);
		}
		if(isset($where['merchantName']) && $where['merchantName'])
		{
			$keyword = '%'.$where['merchantName'].'%';
			$query = $query->where('m.name','like',$keyword);
		}
			
		return $query;
	}
	
	/**
	 * 导出
	 * 
	 * */
	public static function getListExport($where = '',$orderName = ' r.addTime  ',$order = 'desc')
	{
		$fields = array(
				's.salonid',
				's.salonname',
				's.sn',
				'm.name',
				'r.type',
				'r.paymentStyle',
				'r.money',
				'r.addTime',
				'r.singleNumber',
				'r.status',
				'r.receiptDate',
				'r.id',
				'r.checkTime',
				'r.payCode',
				'r.remark',
				'r.paySingleCode',
				'mg.name as preparedByName',
				'mgs.name as cashierName',
		);
		$query = self::getQueryByParam($where,$orderName,$order,$fields);
		Event::fire('Receivables.export','导出收款单');
		return  $query->get();
	}
	
	/**
	 * 生成单号
	 * */
	public static function createSingleNumber($type)
	{
		$tps = '';
		$redis = Redis::connection();
		$query = self::getQuery();
		if($type == 1)
		{
			$key = 'STZ';
		}
		elseif($type == 2)
		{
			$key = 'SDS';
		}
		$value = Redis::hget('receivables',$key);
		if (!$value)//redis 不存在 查询数据库总数
		{
			$query->where('addTime','>=',strtotime(date('Y-m-d')));
			$query->where('type','=',$type);
			$value = $query->count();
			Redis::hset('receivables',$key,$value);
		}
		$value += 1;
		Redis::HINCRBY('receivables',$key,1);//写入redis 加1
		Redis::EXPIREAT('receivables',strtotime(date('Y-m-d').'23:59:59'));//当天过期
		
		for($i=5;$i>strlen($value);$i--)
		{
			$tps .= 0;
		}
		return $key.'-'.date('ymd').$tps.$value;//随机测试
	}
	
	/**
	 * 添加修改 操作
	 * */
	public static function dosave($save,$id = 0,$user = 0)
	{
		if($id)
		{
			$save['upTime'] = time();
			$status = self::where('id',$id)->update($save);
			Event::fire('Receivables.update','修改收款单id：'.$id);
		}
		else
		{
			$save['addTime'] = time();
			$save['preparedBy'] = $user;
			$save['singleNumber'] = self::createSingleNumber($save['type']);//收款单号
			$status = self::insertGetId($save);
			Event::fire('Receivables.save','添加收款单id：'.$status);
			
		}
		return $status;
	}
	
	/**
	 * 获取单条记录
	 * 
	 */
	public static  function  getOneById($id)
	{
		if(!$id)
		{
			return false;
		}
		$fields = array(
				'r.salonid',
				's.salonname',
				's.sn',
				'm.name',
				'r.type',
				'r.paymentStyle',
				'r.money',
				'r.addTime',
				'r.singleNumber',
				'r.status',
				'r.receiptDate',
				'r.preparedBy',
				'r.status',
				'r.cashier',
				'r.id',
				'r.checkTime',
				'r.payCode',
				'r.remark',
				'r.paySingleCode',
				'mg.name as preparedByName',
				'mgs.name as cashierName',
		);
		$query =  DB::table('receivables as r')
					->leftjoin('salon as s', 's.salonid', '=', 'r.salonid')
					->leftjoin('merchant as m', 'm.id', '=', 's.merchantId')
					->leftjoin('managers as mg', 'mg.id', '=', 'r.preparedBy')
					->leftjoin('managers as mgs', 'mgs.id', '=', 'r.cashier')
					->select($fields);
		$query =  $query ->where('r.id','=',$id);
		return $query->first();
	}
	
	/**
	 * 删除
	 * */
	public static function dodel($id)
	{
		if(!$id)
		{
			return false;
		}
		$save['upTime'] = time();
		$status = self::where('id',$id)->update(['status'=>3,'upTime'=>time()]);//删除
		Event::fire('Receivables.delete','删除收款单id：'.$id);
		return $status;
	}
	
	/**
	 * 删除修改时 检测状态
	 * */
	public static function getCheckRecRsStatus($id)
	{
		if(!$id)
		{
			return false;
		}
		$status = self::select(['status'])->where("id","=",$id)->first();
		if($status->status != 1)
		{
			return false;
		}
		else
		{
			return true;
		}
		
	}
	
	/**
	 * 确认收款
	 * var  type 		   收款类型1业务投资款返还 2交易代收款返还
	 * var  paymentStyle 收款方式 1银行存款2账扣返还3现金4支付宝5财付通.
	 * 
	 * */
	public static function confirmReceivables($idArr,$userId,$payTypeId)
	{
		DB::beginTransaction();
		//更新状态
		$status = Self::whereIn('id', $idArr)->update(['checkTime'=>time(),'status'=>2,'cashier'=>$userId]);
		if(!$status)
		{
			DB::rollBack();
			return false;
		}
		
		if($payTypeId && $status)
		{
			//选择为账扣返还类型时，确认付款后自动在付款单中生成‘付交易代收款’单，且此订单为已付款状态。同时在转付单生成‘付交易代收款’单，此订单也为已付款状态
			foreach ($payTypeId as $k=>$v)
			{
				$data = [
						'id'			=>	$v['id'],
						'code'			=>	$v['singleNumber'],
						'salon_id'		=>	$v['salonid'],
						'merchant_id'	=>	$v['merchantId'],
						'money'			=>	$v['money'],
						'receive_type'	=>	$v['paymentStyle'],
						'require_day'	=>	$v['receiptDate'],
						'receive_day'	=>	$v['checkTime'],
						'cash_uid'		=>	$v['cashier'],
						'make_uid'		=>	$v['preparedBy'],
						'make_at'		=>	$v['addTime'],
						'remark'		=>	$v['remark'],
					];
				if($v['paymentStyle'] == 2 && $v['type'] == 1)//账扣返还---业务投资款
				{
					$retData = PayManage::makeFromReceive($data);//付款单
					if($retData)
					{
						$status = Receivables::where('id', '=', $v['id'])->update(['payCode' => $retData['code'],'payId'=>$retData['id']]);
						if(!$status)
						{
							DB::rollBack();
							return false;
						}
					}
				}
		
				if($v['type'] == 2)
				{
					$data['money'] = '-'.$v['money'];//交易代收款
					$data['type'] = 3;
					$retData = PrepayBill::makeReturn($data);
					if($retData)
					{
						$status = self::where('id', '=', $v['id'])->update(['paySingleCode' => $retData['code'],'paySingleId'=>$retData['id']]);
						if(!$status)
						{
							DB::rollBack();
							return false;
						}
					}
				}
					
			}
		}
		DB::commit();
		Event::fire('Receivables.confirmReceivables','确认收款单id：'.join(',',$idArr));
		return true;
	}
	
	
	
	
}
