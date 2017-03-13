<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class SalonChangeGradeSeeder extends Seeder
{
    /**
     * 每天0：3分    调整店铺等级
     * Run the database seeds.
     *
     * @return void
     * 
     * php artisan db:seed --class=SalonChangeGradeSeeder  
     */
    public function run()
    {
    	$sql = 'update cm_salon set salonGrade=salonChangeGrade where salonGrade!=salonChangeGrade and changeInTime <='.time();
		$affected = DB::update($sql);
		echo $affected." ".date("Y-m-d H:i:s")."\r\n";
    }
 
}
