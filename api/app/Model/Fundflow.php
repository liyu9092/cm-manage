<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Fundflow extends Model
{
    protected $table = 'fundflow';
    protected $primaryKey = 'ffid';
    public $timestamps = false;
}
