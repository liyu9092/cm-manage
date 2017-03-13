<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SalonArea extends Model {

    protected $table = 'salon_area';
    protected $primaryKey = 'areaid';
    public $timestamps = false;

    public static function getSalonAreaById($areaid) {
        $salonArea = Self::getQuery()->where("areaid", "=", $areaid)->get();

        if (empty($salonArea)) {
            return [];
        } else {
            return $salonArea[0];
        }
    }

}
