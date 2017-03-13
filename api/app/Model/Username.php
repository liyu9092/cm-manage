<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Username extends Model {
    protected $table = 'username';
    
    public static function makeUsername()
    {
        $attr= ['addTime'=>time()];
        return self::insertGetId($attr);
    }
    
    public function isFillable($key)
    {
        return true;
    }
    

}