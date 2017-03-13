<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\SalonUser;
class SalonDataSeeder extends Seeder
{
    /**
     * 商户店铺建立对应关系
     * Run the database seeds.
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonDataSeeder
     */
    public function run()
    {
    	$file_handle = fopen("public/Uploads/20150729/merchantsalon.csv", "r");
    	$s = 1;
    	$i = 1;
    	$bindArr = array();
    	while (!feof($file_handle))
    	{
    		$line = fgetcsv($file_handle);
    		if($line && $s>1)
    		{
    			if($line[0] && $line[1])
    			{
    				$bindArr[$s]["salonname"] = iconv("gbk","UTF-8",$line[0]);//店铺名
    				$bindArr[$s]["salonname"] = str_replace("?", "•", $bindArr[$s]["salonname"]);//• 转换变成了  ？  替换
    				$bindArr[$s]["name"] = iconv("gbk","UTF-8",$line[1]);//商户名
    			}
    		}
    		$s++;
    	}
    	fclose($file_handle);
    	
		$mList = DB::table('merchant')->get();
		$sList = DB::table('salon')->get();
		
		foreach($mList as $key=>$val)//商户
		{
			foreach($bindArr as $each)//对应关系
			{
				if($each["name"] == $val->name)    
				{
					foreach($sList as $k=>$v)
					{
						if($each["salonname"] == $v->salonname)
						{
							//修改店铺表 
							DB::table('salon')->where('salonid', $v->salonid)->update(array("merchantId"=>$val->id));
							$i++;
						}
					}
				}
			}
			
		}
		echo $i."ok";
    }
    
}
