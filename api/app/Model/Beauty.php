<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
Use PDO;
use Illuminate\Pagination\AbstractPaginator;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class Beauty extends Model {

    protected $table = 'beauty';
    protected $primaryKey = 'beauty_id';
    public $timestamps = false;

   
    
    public static function getBeauty(){
		$result = Self::getQuery()->first();
        return $result;
	}     

}
