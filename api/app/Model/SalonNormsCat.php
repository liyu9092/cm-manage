<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use App\SalonNorms;
class SalonNormsCat extends  Model
{
    protected $table = 'salon_norms_cat';
    
    public $timestamps = false;
    
    public static  function delNorms($id) 
    {
    	if(!$id) return false;
    	$row = self::where(['salon_norms_cat_id'=>$id])->delete();
    	if($row)
    	{
    		$normsRow = SalonNorms::where(['salon_norms_cat_id'=>$id])->delete();
    		if($normsRow) return true;
    	}
    	return false;
    }
    
}