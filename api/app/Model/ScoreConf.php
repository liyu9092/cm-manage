<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\AbstractPaginator;

class ScoreConf extends Model {

    protected $table = 'salon_score_conf';
    protected $fillable = array('verySatisfy', 'satisfy', 'unsatisfy');
    public $timestamps = false;

}
