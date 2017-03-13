<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\SalonUser;
use App\Salon;
use App\Merchant;
use App\Dividend;
use App\Town;
use App\CompanyCodeCollect;

class SalonDividendSeeder extends Seeder
{
    /**
     * Run the database seeds.   分红联盟数据插入
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonDividendSeeder
     * 
     */
	//预定码
	private $codeArr = array('6890','9988','9966','5988','6990','8668','6666','6888','5800','6688','8918','9898','1200','1201','1202','1299','1211','8608','1206','1207','1208','1298','1210','1279','1212','1213','1222','1215','1217','1218','0202','1258','1290','1220','1221','1223','1224','1887','1881','1890','1818','1877','1876','1875','1873','1872','1871','1870','1869','1868','1867','1866','1865','1863','1861','1860','1858','1857','1856','1855','1853','1852','1851','1850','1839','1836','1835','1832','1831','1900','1909','1828','1827','1826','1825','1823','1822','1821','1820','1901','1902','1903','1905','1906','1907','1226','1227','1287','1230','1286','1717','5917','0628','7777','0718','3333','1111','0805','0810','0822','1777','1010','0626');
	 
    public function run()
    {
    	$sql = "select `cm_s`.`salonid`,`cm_s`.`district` from `cm_salon` as `cm_s` left join `cm_dividend` as `cm_d` on `cm_s`.`salonid` = `cm_d`.`salon_id` where `cm_d`.`salon_id` is NULL";
    	$list = DB::select($sql);
    	$i = 0;
    	foreach($list as $val)
    	{
    		$disId = $this->addSalonCode($val->district,$val->salonid);
    		if($disId)
    		{
    			echo $val->salonid;
    			echo "\r\n";
    			$i++;
    		}
    	}
    	echo "OK!".$i;
    }

    
    /**
     * 添加店铺邀请码
     *
     * */
    private function addSalonCode($district,$salonid)
    {
    	$code = $this->getRecommendCode();
    	$townInfo["tname"] = "";
    	if ($district)
    	{
    		$townInfo = Town::where(array("tid"=>$district))->first();
    	}
    	
    	// 写入推荐码表
    	$datas=array (
    			"salon_id" => $salonid,
    			"district" => $townInfo["tname"]?:'',
    			"recommend_code" => $code,
    			"status" => 1,
    			"add_time" => time ()
    	);
    	return DB::table('dividend')->insertGetId($datas);
    }
    
    /**
     * 获取推荐码
     *
     * @return integer
     */
    private function getRecommendCode() {
    	$code = $this->randNum ( 4 );
    	// 如果不是四位随机数则重新生成
    	if (intval ( $code ) < 1000){
    		return $this->getRecommendCode ();
    	}
		
		//屏蔽预定码
		$codeArr = $this->codeArr;
        if(in_array($code, $codeArr))
        {
        	return $this->getRecommendCode ();
        }
	
    	// 如果数据库中已在存在则继续执行
    	$codeTmpInfo = Dividend::where(array("recommend_code"=>$code))->first();
    	if ($codeTmpInfo){
    		return $this->getRecommendCode ();
    	}
		        
        // 集团码
        $codeComTmpInfo = CompanyCodeCollect::where(array("code"=>$code))->first();
        if ($codeComTmpInfo){
        	return $this->getRecommendCode ();
        }
    
    	return $code;
    }
    
    /**
     * 生成随即数字
     * @param int $length
     * @return string
     */
    private function randNum($length){
    	$pattern = '12356890';    //字符池,可任意修改
    	$key = '';
    
    	for($i=0;$i<$length;$i++)    {
    		$key .= $pattern{mt_rand(0, strlen($pattern) - 1)};    //生成php随机数
    	}
    
    	return $key;
    }

}
