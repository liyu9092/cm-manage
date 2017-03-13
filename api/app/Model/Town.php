<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Town extends Model {

    protected $table = 'town';
    protected $primaryKey = 'tid';
    public $timestamps = false;

    public static function getTownById($tid) {
        $town = Self::getQuery()->where("tid", "=", $tid)->get();
        if (empty($town)) {
            return [];
        } else {
            return $town[0];
        }
    }

}
