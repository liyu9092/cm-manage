<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use App\SalonUser;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\Salon;

class SalonAccount extends Model {
	
	
	/**
	 * 获取账号列表
	 * 
	 * */
	public static function getList($where = '' , $page=1, $page_size=20,$orderName = 'salon_user_id',$orederAsc = 'desc')
	{
		//手动设置页数
		AbstractPaginator::currentPageResolver(function() use ($page) {
		    return $page;
		});
		$fields = array("u.salon_user_id as salonUserId","u.username","u.salonid","u.roleType","u.addTime","u.status","u.merchantId","m.name","s.salonname");
		$query =  DB::table('salon_user as u')
            ->leftjoin('salon as s', 'u.salonid', '=', 's.salonid')
            ->leftjoin('merchant as m', 'm.id', '=', 'u.merchantId')
            ->select($fields)
            ->orderBy($orderName,$orederAsc)
            ;
        $query = $query->where('u.status','!=',3);//排除删除账号
        if(isset($where['salonname'])&&$where['salonname'])//店铺名
		{
			$keyword = '%'.urldecode($where['salonname']).'%';
			$query = $query->where('s.salonname','like',$keyword);
		}
		if(isset($where['username'])&&$where['username'])//账号名
		{
			$keyword = '%'.urldecode($where['username']).'%';
			$query = $query->where('u.username','like',$keyword);
		}
		if(isset($where['name'])&&$where['name'])//商户名
		{
			$keyword = '%'.urldecode($where['name']).'%';
			$query = $query->where('m.name','like',$keyword);
		}
         
        $salonList =  $query->paginate($page_size);
        $result = $salonList->toArray();
        return $result;
	}
	
	
	/***
	 * 店铺账号添加操作
	 * */
	public static function dosave($data)
	{
		return SalonUser::insertGetId($data);
	}
	
	/***
	 * 店铺账号修改 删除
	 * */
	public static function doUpdate($salon_user_id,$data)
	{
		 if($data['status'] != 3)
		 {
		 	return SalonUser::where("salon_user_id","=",$salon_user_id)->update($data);
		 }
		 else
		 {
			Salon::where("puserid","=",$salon_user_id)->update(['puserid'=>0]);
		 	return SalonUser::where("salon_user_id","=",$salon_user_id)->delete();
		 } 
	}
	
	/***
	 * 单个店铺只允许一个普通用户
     * 单个商户只允许一个超级管理员
     *    
	 * */
	public static function getAccountNums($data)
	{
	   $query = SalonUser::getQuery();
	   $query = $query->where('roleType','=',$data["roleType"]);
	   if($data["roleType"] == 1)
	   {
	  	 	$query = $query->where('salonid','=',$data["salonid"]);
	   }
	   $query = $query->where('status','!=',3);//排除删除账号
	   $query = $query->where('merchantId','=',$data["merchantId"]);
	   return $query->select(array("salon_user_id","username"))->count();
	}
	
	/***
	 * 
	 * 获取店铺名称
	 * */
	public static function getSalonNamebyCon($where)
	{
		$data["data"] = array();
		$fields = array("merchantId","salonid","salonname","name","s.sn");
		$query =  DB::table('salon as s')->leftjoin('merchant as m', 'm.id', '=', 's.merchantId');//第一次精确查找
		if(isset($where['salonname']) && urldecode($where['salonname']))
		{
			$keyword = urldecode($where['salonname']);
			$query = $query->where('s.salonname','=',$keyword);
			$query = $query->where('s.status','!=',3);//正常合作  终止合作的店铺
			$query = $query->where('s.merchantId','!=',0);//有商户Id
		}
		$result = $query->select($fields)->paginate(5)->toArray();

		if(count($result["data"]) < 5)
		{
			$query =  DB::table('salon as s')->leftjoin('merchant as m', 'm.id', '=', 's.merchantId');
			$keyword = '%'.urldecode($where['salonname']).'%';
			$query = $query->where('s.salonname','like',$keyword);
			$query = $query->where('s.status','!=',3);//正常合作 终止合作的店铺
			$query = $query->where('s.merchantId','!=',0);//有商户Id
			
			if($result["data"])
			{
				foreach($result["data"] as $val)
				{
					$salonidArr[] = $val->salonid;
				}
				$query = $query->whereNotIn('s.salonid', $salonidArr);
			}
			
			$limit = 5-count($result["data"]);
			$data = $query->select($fields)->paginate($limit)->toArray();
		}
		if($result["data"] && $data["data"])
		{
			return array_merge($result["data"],$data["data"]);
		}
		elseif(!$result["data"] && $data["data"])
		{
			return $data["data"];
		}
		elseif($result["data"] && !$data["data"])
		{
			return $result["data"];
		}
		
		
	}
	
	
	

}
