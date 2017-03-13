<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PaymentLog extends Model {

    protected $table = 'payment_log';
    public $timestamps = false;

    public static function getPaymentLogsBySns($ordersn) {
        $flows = self::select('ordersn', 'tn')->whereIn("ordersn", $ordersn)->get();
        return $flows;
    }
    
    public static function getPaymentLogsByTns($tns) {
        $flows = self::select('ordersn', 'tn')->whereIn("tn", $tns)->get();
        return $flows;
    }

    public static function getBountyPaymentLogBySn($ordersn, $logtype) {
        $flow = self::getQuery()->where("ordersn", "=", $ordersn)->where("logtype", "=", $logtype)->get();
        if (empty($flow)) {
            return [];
        } else {
            return $flow[0];
        }
    }
    
    public static function getBountyPaymentLogBySns($ordersn, $logtype) {
        return $flow = self::getQuery()->whereIn("ordersn", $ordersn)->where("logtype", "=", $logtype)->get();
    }

}
