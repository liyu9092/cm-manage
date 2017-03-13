<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Push extends Model {

    protected $table = 'push';
    protected $primaryKey = 'ID';
    public $timestamps = false;
}
