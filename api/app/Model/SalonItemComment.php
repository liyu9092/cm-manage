<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalonItemComment extends Model {

    protected $table = 'salon_itemcomment';
    protected $primaryKey = 'itemcommentid';
    public $timestamps = false;
}

