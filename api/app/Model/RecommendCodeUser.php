<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RecommendCodeUser extends Model
{
    protected $table = 'recommend_code_user';
    public $timestamps = false;
    
    public function isFillable($key)
    {
        return true;
    }
}
