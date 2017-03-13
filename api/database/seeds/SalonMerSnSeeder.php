<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\SalonUser;
use App\SalonInfo;
class SalonMerSnSeeder extends Seeder
{
    /**
     * 店铺编号 商户编号完善  ---不用
     * Run the database seeds.
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonMerSnSeeder
     */
    public function run()
    {
		$file_handle = fopen("public/Uploads/sn.csv", "r");
		$s = 1;
		$i = 1;
		$t = 1;
   		 while (!feof($file_handle))
		{
			$line = fgetcsv($file_handle);
			if($line && $t>1)
			{
				$salonName = iconv("gbk","UTF-8",$line[6]);//店铺名
				$salonSn = iconv("gbk","UTF-8",$line[5]);//店铺编号
				if($salonName && $salonSn)
				{
					$row = DB::table('salon')->where('salonname', $salonName)->update(array("sn"=>$salonSn));
					$i++;
				}
	
				$name = iconv("gbk","UTF-8",$line[2]);//商户名
				$sn = iconv("gbk","UTF-8",$line[1]);//商户编号
				if($name && $sn)
				{
					DB::table('merchant')->where('name', $name)->update(array("sn"=>$sn));
					$s++;
				}
			}
			$t++;
	
		}
		echo "s".$i."m".$s."ok";
		
		fclose($file_handle);
    }
 
}
