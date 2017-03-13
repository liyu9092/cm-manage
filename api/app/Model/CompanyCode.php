<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class CompanyCode extends Model {

	protected $table = 'company_code';
    protected $primaryKey = 'companyId';
    public $timestamps = false;
    
     public static function getCompanyCodeInfo($field,$status = 1){
         
        $res = self::select($field)->where('status','=',$status)->get();
        
        return $res->toArray();   
    }

}
