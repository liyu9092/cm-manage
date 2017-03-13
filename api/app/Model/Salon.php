<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\SalonUser;
use App\Merchant;
use App\SalonRatingsRecord;
use App\SalonScoreLog;
use App\SalonWorks;
use Event;
use App\Manager;

class Salon extends Model {

	protected $table = 'salon';
	
	protected $primaryKey = 'salonid';
	
	public $timestamps = false;
	
    public function rebate(){
        return $this->hasMany('App\Rebate');
    }    

	/**
	 * 店铺列表
	 */
	public static  function getSalonList( $where = '' , $page=1, $page_size=20,$orderName = ' s.salonid  ',$order = 'desc' )
	{
		$fields = array(
				's.salonid',
				's.salonname',
				's.shopType',
				's.zone',
				's.district',
				's.salestatus',
                's.businessId',
				's.sn',
				's.add_time',
				'm.name',
				'm.id as merchantId',
				//'b.businessName',
			);
		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});

		$query = self::getQueryByParam( $where,$orderName,$order,$fields,1);
        $salonList =    $query->paginate($page_size);
        $result = $salonList->toArray();

		$list['total'] = $result['total'];
		$list['per_page'] = $result['per_page'];
		$list['current_page'] = $result['current_page'];
		$list['last_page'] = $result['last_page'];
		$list['from'] = $result['from'];
		$list['to'] = $result['to'];
		unset($result['total']);
		unset($result['per_page']);
		unset($result['current_page']);
		unset($result['last_page']);
		unset($result['from']);
		unset($result['to']);
		unset($result['next_page_url']);
		unset($result['prev_page_url']);
		$data = array();
		$rs = array();
		foreach($result['data'] as $key=>$val)
		{
			$tmpVal = (array)$val;
			$data[$key] = $tmpVal;
			$areaArr = Salon::getAreaMes(array('zone'=>$tmpVal['zone'],'district'=>$tmpVal['district'])) ;
			if($areaArr)
			{
				$rs[$key] = array_merge($data[$key],$areaArr);
			}
			else 
			{
				$rs[$key] = $data[$key];
			}
			
		}
		
		foreach($rs as $key=>$val)
		{
			if(is_null($val) == true)//null 数据转化为 空字符串
			{
				$rs[$key] = '';
			}
			$rs[$key]['businessName'] = Manager::where(['id'=>$val['businessId']])->lists('name')->first()?:'';//获取业务代表	
		}
		$list['data'] = $rs;
           
        return $list;
	}
	
	/**
	 * 店铺查询
	 * 
	 * */
	private static function getQueryByParam( $where,$orderName,$order,$fields,$act = 0)
	{
		$query =  DB::table('salon as s')
					->leftjoin('salon_info as i', 'i.salonid', '=', 's.salonid')
					->leftjoin('merchant as m', 'm.id', '=', 's.merchantId')
					//->leftjoin('business_staff as b', 'b.id', '=', 's.businessId')
					->select($fields)
					->orderBy($orderName,$order);
		if($act == 0)
		{
			$query = $query->leftjoin('dividend as d', 'd.salon_id', '=', 's.salonid');
		}
		
		if(isset($where['salestatus']))
		{
			$query =  $query ->where('s.salestatus','=',$where['salestatus']);
		}
		else
		{
			$query =  $query ->where('s.salestatus','!=','2');//剔除删除
		}

		if(isset($where['shopType']))
		{
			$query =  $query ->where('s.shopType','=',$where['shopType']);
		}
		if(isset($where['zone']))
		{
			$query =  $query ->where('s.zone','=',$where['zone']);
		}
		if(isset($where['district']))
		{
			$query =  $query ->where('s.district','=',$where['district']);
		}
		if(isset($where['businessName']))
		{
			$keyword = '%'.$where['businessName'].'%';
			$query = $query->whereRaw("businessId in (SELECT `id` FROM `cm_managers` WHERE `name` LIKE '{$keyword}')");
		}
		if(isset($where['salonname'])&&$where['salonname'])
		{
			$keyword = '%'.$where['salonname'].'%';
			$query = $query->where('s.salonname','like',$keyword);
		}
		if(isset($where['sn'])&&$where['sn'])
		{
			$keyword = '%'.$where['sn'].'%';
			$query = $query->where('s.sn','like',$keyword);
		}
		if(isset($where['merchantName'])&&$where['merchantName'])
		{
			$keyword = '%'.$where['merchantName'].'%';
			$query = $query->where('m.name','like',$keyword);
		}
		return $query;
	}
	
	
	/**
	 * 店铺列表导出
	 */
	public static  function getSalonListExport( $where = '',$orderName = 's.salonid  ',$order = 'desc' )
	{
		$fields = array(
				's.salonid',
				's.salonname',
				's.addr',
				's.addrlati',
				's.addrlong',
				's.zone',
				's.district',
				's.shopType',
				's.contractTime',
				's.contractPeriod',
				's.bargainno',
				's.bcontacts',
				's.tel',
				's.phone',
				's.corporateName',
				's.corporateTel',
				's.sn',
				's.salestatus',
				's.businessId',
				's.add_time',
				's.contractEndTime',
				's.salonGrade',
				's.salonChangeGrade',
				's.changeInTime',
				's.salonCategory',
				'i.bankName',
				'i.beneficiary',
				'i.bankCard',
				'i.branchName',
				'i.accountType',
				'i.salonArea',
				'i.dressingNums',
				'i.staffNums',
				'i.stylistNums',
				'i.monthlySales',
				'i.totalSales',
				'i.price',
				'i.payScale',
				'i.payMoney',
				'i.payMoneyScale',
				'i.payCountScale',
				'i.cashScale',
				'i.blowScale',
				'i.hdScale',
				'i.platformName',
				'i.platformScale',
				'i.receptionNums',
				'i.receptionMons',
				'i.setupTime',
				'i.hotdyeScale',
				'i.lastValidity',
				'i.salonType',
				'i.contractPicUrl',
				'i.licensePicUrl',
				'i.corporatePicUrl',
				//财务信息
				'i.floorDate',
				'i.advanceFacility',
				'i.commissionRate',
				'i.dividendPolicy',
				'i.rebatePolicy',
				'i.basicSubsidies',
				'i.bsStartTime',
				'i.bsEndTime',
				'i.strongSubsidies',
				'i.ssStartTime',
				'i.ssEndTime',
				'i.strongClaim',
				'i.subsidyPolicy',
				
				'm.name',
				'm.id as merchantId',
				'm.sn as msn',
				'd.status as dividendStatus',
				'd.recommend_code',	
		);

		
		$rs = array();
		$query = self::getQueryByParam( $where,$orderName,$order,$fields);
		$salonList =    $query->get();
		if($salonList)
		{
			foreach ($salonList as $key=>$val)
			{
				$result[$key] = (array)$val;
			}
			$data = array();
			$rs = array();
			foreach($result as $key=>$val)
			{
				$tmpVal = (array)$val;
				$data[$key] = $tmpVal;
				$areaArr = Salon::getAreaMes(array('zone'=>$tmpVal['zone'],'district'=>$tmpVal['district'])) ;
				if($areaArr)
				{
					$rs[$key] = array_merge($data[$key],$areaArr);
				}
				else
				{
					$rs[$key] = $data[$key];
				}
			}
			
			foreach($rs as $key=>$val)
			{
				if(is_null($val) == true)//null 数据转化为 空字符串
				{
					$rs[$key] = '';
				}
				$rs[$key]['businessName'] = Manager::where(['id'=>$val['businessId']])->lists('name')->first()?:'';//获取业务代表
				
			}	
		}
		return $rs;
	}
	
	/**
	 * 终止合作 恢复店铺
	 * type = 1 终止合作  2 恢复店铺
	 */
	public static function doendact($salonid,$type,$merchantId)
	{
		if($type == 1)
		{
			$save['salestatus'] = 0;
			$save['status'] = 2;
		}
		else
		{
			$save['salestatus'] = 1;
			$save['status'] = 1;
		}
		DB::beginTransaction();
		$affectid = self::where(['salonid'=>$salonid])->update($save);
		if($affectid && $type == 1)
		{
			SalonUser::where(['salonid'=>$salonid])->update(['status'=>SalonUser::STATUS_STOP]);//停用普通用户账号
			$usersCount = SalonUser::where(['merchantId'=>$merchantId,'status'=>SalonUser::STATUS_UP])->where('salonid','!=',0)->count();
			if(!$usersCount)
			{
				SalonUser::where(['salonid'=>0])//停用账号  超级管理员
		            ->where('merchantId','=' ,$merchantId)
		            ->update(['status'=>SalonUser::STATUS_STOP]);
			}			
			$flag = Merchant::where(['id'=>$merchantId])->decrement('salonNum',1);//店铺数量减1
			
		}
		elseif($affectid && $type == 2)
		{
			SalonUser::where(['salonid'=>$salonid])->update(['status'=>SalonUser::STATUS_UP]);//恢复普通用户账号
			$usersCount = SalonUser::where(['merchantId'=>$merchantId,'status'=>1])
						->where('salonid','!=' ,0)
						->count();
			if($usersCount)
			{
		         SalonUser::where(['merchantId'=>$merchantId,'status'=>0])->update(['status'=>SalonUser::STATUS_UP]);//恢复账号  超级管理员  
			}
			
			$flag = Merchant::where(['id'=>$merchantId])->increment('salonNum',1);//店铺数量加1
		}
		if($flag)
		{
			DB::commit();
		}
		else
		{
			DB::rollBack();  
		}
		return $flag;
	}
	
	/**
	 * 删除店铺
	 * */
	public static function dodel($salonid)
	{
		$result = Salon::where(['salonid'=>$salonid])->select(['salestatus','merchantId'])->first();
		if(!$result)
		{
			return -2;
		}
		elseif($result->salestatus == 1 )
		{
			return -1;//未停止合作
		}
		DB::beginTransaction();
		$affectid =  Salon::where(['salonid'=>$salonid])->update(array('salestatus'=>2,'status'=>3));
		if(!$affectid)
		{
			DB::rollBack();
			return $affectid;
		}
		SalonUser::where(['salonid'=>$salonid])->delete();//删除普通用户账号
		$merchantId = $result->merchantId;
		$usersCount = SalonUser::where(['status'=>1,'merchantId'=>$merchantId])->where('salonid','!=',0)->count();
		if(!$usersCount)
		{
			//删除账号  超级管理员
			SalonUser::where(['salonid'=>0,'merchantId'=>$merchantId])->delete();
		}
		DB::commit();
		return $affectid;
	}
	
	/**
	 * 获取店铺详情
	 * */
	public static function getSalon($salonid)
	{
		$fields = array(
					's.salonid',
					's.salonname',
					's.addr',
					's.addrlati',
					's.addrlong',
					's.zone',
	   			    's.district',
					's.shopType',
					's.contractTime',
					's.logo',
					's.bargainno',
					's.bcontacts',
					's.tel',
					's.phone',
					's.corporateName',
					's.corporateTel',
	                's.sn',
	                's.salestatus',
	                's.businessId',
					's.contractEndTime',
					's.salonGrade',
					's.salonChangeGrade',
					's.changeInTime',
					's.salonCategory',
					's.salonLogo',
					'i.bankName',
					'i.beneficiary',
					'i.bankCard',
					'i.branchName',
					'i.accountType',
					'i.salonArea',
					'i.dressingNums',
					'i.staffNums',
					'i.stylistNums',
					'i.monthlySales',
					'i.totalSales',
					'i.price',
					'i.payScale',
					'i.payMoney',
					'i.payMoneyScale',
					'i.payCountScale',
					'i.cashScale',
					'i.blowScale',
					'i.hdScale',
					'i.platformName',
					'i.platformScale',
					'i.receptionNums',
					'i.receptionMons',
					'i.setupTime',
					'i.hotdyeScale',
					'i.lastValidity',
					'i.salonType',
					'i.contractPicUrl',
					'i.licensePicUrl',
					'i.corporatePicUrl',
					'i.floorDate',
					'i.advanceFacility',
					'i.commissionRate',
					'i.dividendPolicy',
					'i.rebatePolicy',
					'i.basicSubsidies',
					'i.bsStartTime',
					'i.bsEndTime',
					'i.strongSubsidies',
					'i.ssStartTime',
					'i.ssEndTime',
					'i.strongClaim',
					'i.subsidyPolicy',
					'm.name',
					'm.id as merchantId',
					'b.name as businessName',
					'd.status as dividendStatus',
					'd.recommend_code',
					
				);
			$salonList =  DB::table('salon as s')
            				->leftjoin('salon_info as i', 'i.salonid', '=', 's.salonid')
           					->leftjoin('merchant as m', 'm.id', '=', 's.merchantId')
            				->leftjoin('managers as b', 'b.id', '=', 's.businessId')
            				->leftjoin('dividend as d', 'd.salon_id', '=', 's.salonid')
            				->select($fields)
          					->where(['s.salonid'=>$salonid])
            				->first();

			$salonList = (array)$salonList;
			
			if($salonList)
			{
				foreach($salonList as $key=>$val)
				{
					if(is_null($val) == true)//null 数据转化为 空字符串
					{
						$salonList[$key] = '';
					}
					
					if($val === '0.00')//0 0.00默认值 数据转化为 空字符串
					{
						$salonList[$key] = '';
					}
				}
				$salonList['salonImg'] = SalonWorks::getSalonWorks($salonid,3);//店铺图集
				$salonList['workImg'] = SalonWorks::getSalonWorks($salonid,4);//团队图集
				
				$areaArr = self::getAreaMes(array('zone'=>$salonList['zone'],'district'=>$salonList['district'])) ;
				if($areaArr)
				{
					$salonList = array_merge($salonList,$areaArr);
				}
			}
			return $salonList;
	}
	
	/**
	 * 获取省市区信息
	 * */
	public static  function getAreaMes($salonList)
	{
			$rs['zoneName'] = '';
			$rs['districtName'] = '';
			$rs['citiesName'] = '';
			$rs['citiesId'] = '';
			$rs['provinceName'] = '';
			$rs['provinceId'] = '';
			//商圈
			$zoneList = SalonArea::where(array('areaid'=>$salonList['zone']))->first();   
            if($zoneList)
            {
            	$rs['zoneName'] = $zoneList->areaname? $zoneList->areaname:'';
            } 
			//区
			$districtList = Town::where(array('tid'=>$salonList['district']))->first();  
            if($districtList)
            {
            	$rs['districtName'] = $districtList->tname? $districtList->tname:'';
				//市
				$cityList = SalonCity::where(array('pid'=>$districtList->iid))->first(); 
	            if($cityList)
	            {
	            	$rs['citiesName'] = $cityList->iname? $cityList->iname:'';
					$rs['citiesId'] = $cityList->iid? $cityList->iid:'';
					//省
					$provinceList = Province::where(array('pid'=>$cityList->pid))->first();  
	                if($provinceList) 
	                {
	                	$rs['provinceName'] = $provinceList->pname? $provinceList->pname:'';
						$rs['provinceId'] = $provinceList->pid? $provinceList->pid:'';
	                }  
	            }    
            }
            return $rs;
	}
	
	/**
	 * 自动生成店铺编号
	 * */
	public static function getSn($merchantId,$addNums = 1)
	{
		$merchantSn = Merchant::where(array('id'=>$merchantId))->select(array('sn'))->first();
		$sCount = Salon::where(array('merchantId'=>$merchantId))->count();
		$sn = intval($sCount)+$addNums; //店铺编号，根据商户编号+01，自增长3位
	    $tps = '';	 
		for($i=3;$i>strlen($sn);$i--)
		{
			$tps .= 0; 
		}
		$tmpSn = $merchantSn->sn.$tps.$sn;	
		$num = self::getCheckSn($tmpSn);
		if($num)
		{
			$twoNums = $addNums+1;
			$tmpSn = self:: getSn($merchantId,$twoNums);
		}
		return $tmpSn;
	}
    
    public static function getSalonsByIds($salon_ids)
    {
        $salons = self::whereIn('salonid',$salon_ids)->get();
        return $salons;
    }
    
    public static function getSalonById($salon_id)
    {
        $salon = self::where('salonid','=',$salon_id)->first();
        return $salon;
    }
	
	/**
	 * 店铺等级调整
	 * */
	public static function setSalonGrade($salonid,$data,$dataInfo,$addAct)
	{
		$salonResult = self::where(array('salonid'=>$salonid))->first();
		if($data['changeInTime'] > $salonResult->changeInTime || $data['salonChangeGrade'] != $salonResult->salonChangeGrade || $addAct == 1)//1 代表添加
		{
			SalonRatingsRecord::where(['salonid'=>$salonid])->where('changeTime','>',time())->delete();
			$logRs = SalonRatingsRecord::where(['salonid'=>$salonid])->orderBy('id','desc')->first();
			if($logRs)
			{
				SalonRatingsRecord::where(['id'=>$logRs->id])->update(['endTime'=>$data['changeInTime']-1]);
			}
			SalonRatingsRecord::insertGetId(['changeTime'=>$data['changeInTime'],'addTime'=>time(),'grade'=>$data['salonChangeGrade'],'salonid'=>$salonid,'commissionRate'=>$dataInfo['commissionRate']]);
			
		}
	}
        
        /*
         * 更新店铺积分
         */

    public static function updateScore($salonInfo, $score,$options,$userName) {
        DB::beginTransaction();
        $updateRes = Salon::where(['salonid' => $options['salonid']])->update(['score' => $score]);
        $saveStatus = false;
        if ($updateRes!==false) {
            $saveStatus = SalonScoreLog::Create([
                        'salon_id' => $salonInfo->salonid,
                        'score' => $options['type'] == 1 ? "+" . $options['score'] : "-" . $options['score'],
                        'description' => $userName .' '. $options['msg'],
                        'create_time' => date("Y-m-d H:i:s",time()),
            ]);
        }
        if ($saveStatus) {
            DB::commit();
            return true;
        } else {
            DB::rollback();
            return false;
        }
    }

	/**
	 * 添加修改操作
	 *
	 * */
	public static  function doadd($data,$dataInfo,$img,$where='',$whereInfo='',$joinDividend=0)
	{	
		DB::beginTransaction();
		if($where)//修改
		{
			$salonId = $whereInfo['salonid'];
			self::setSalonGrade($salonId,$data,$dataInfo,2);//店铺等级调整
			self::where($where)->update($data);
			$salonTmpInfo = SalonInfo::where($whereInfo)->first();
			if(!$salonTmpInfo)
			{
				SalonInfo::insertGetId(array('salonid'=>$whereInfo['salonid']));
			}
			$affectid = SalonInfo::where($where)->update($dataInfo);
				
			if($affectid)
			{
				//触发事件，写入日志
				Event::fire('salon.update','店铺Id:'.$salonId.' 店铺名称：'.$data['salonname']);
			}
			Dividend::addSalonCode($data,$salonId,2,$joinDividend);//店铺邀请码
	
		}
		else //添加
		{
				
			$data['sn'] = self::getSn($data['merchantId']);//店铺编号
			$salonId = self::insertGetId($data);
				
			if($salonId)
			{
				$dataInfo['salonid'] = $salonId;
				$affectid = SalonInfo::insertGetId($dataInfo);
				if($affectid)
				{
					Merchant::where(['id'=>$data['merchantId']])->increment('salonNum',1);//店铺数量加1
					//触发事件，写入日志
					Event::fire('salon.save','店铺Id:'.$salonId.' 店铺名称：'.$data['salonname']);
				}
			}

			Dividend::addSalonCode($data,$salonId,1,$joinDividend);//添加店铺邀请码
			self::setSalonGrade($salonId,$data,$dataInfo,1);//店铺等级调整
		}
		//店铺图集  团队图集
		SalonWorks::saveImgs($salonId,3,$img['salonImg']);
		SalonWorks::saveImgs($salonId,4,$img['workImg']);
		
		//超级管理员设置
		self::where(array('salonid'=>$salonId))->update(array('puserid'=>SalonUser::setAdminAccount($data['merchantId'])));
		if($affectid)
		{
			DB::commit();
		}
		else
		{
			DB::rollBack();
		}
		return $affectid;
	}         
        
    public static function getSalonInfoByCodeArr($field,$where,$codeArr){        
        $salonInfo = self::select($field)
                     ->rightjoin('dividend', function ($join) {
                         $join->on('salon.salonid', '=', 'dividend.salon_id');
                     })->where($where)->whereIn('dividend.recommend_code',$codeArr)->get()->toArray(); 
        return $salonInfo;
    }


	/**
	 * 检测店铺编号是否存在
	 *
	 * */
	public static function getCheckSn($sn,$id=0)
	{
		$query = self::getQuery();
		$query->where('sn',$sn);
		if($id)
		{
			$query->where('id',$id);
		}
		return  $query->count();
	}
	
	
	public static function getMercharId($salonid)
	{
	    $base = self::where('salonid',$salonid)->select('merchantId')->first();
	    if(!empty($base))
	    {
	        return $base->merchantId;
	    }
	    
	    return 0;
	}

}

