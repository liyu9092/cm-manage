<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis as Redis;

class Voucher extends Model {

    protected $table = 'voucher';
    protected $primaryKey = 'vId';
    public $timestamps = false;
    public static $getVoucherSn = 10000;

    /*
     * 获取代金券sn
     */

    public static function getVoucherSn() {
        //11位  减小重复的几率
        $i = 1;
        for ($i; $i <= 10; $i++) {
            $pre = substr(time(), 2);
            $end = rand(0, 999);
            $end = str_pad($end, 3, '0', STR_PAD_LEFT);
            $code = "CM" . $pre . $end;
            /*
              $where = array('vSn' => $code);
              $count = M("voucher")->where($where)->count();
             */
            $voucher = $this->getVoucherBySn($code);
            $count = empty($voucher) ? 0 : 1;
            if ($count) {
                continue;
            } else {
                return $code;
            }
        }
    }

    public static function getVoucherBySn($vcSn) {
        return self::where(['vcSn' => $vcSn])->first();
    }

    /*
     * 插入voucher 代金券
     */

    public static function addVoucher($data) {
        return self::insertGetId($data);
    }


   //  // 获取代金劵编号
   //  public static function getNewVoucherSn( $p = 'DH' ) {
   //      $pre = date('ymd');
   //      $end = Self::$getVoucherSn++;
   //      $code = $p . $pre  . $end;
   //      return $code;
   // }

   
    // 获取代金劵编号
    public static function getNewVoucherSn($p = 'DH'){
        $pre = $p.date('ymd');
        $redis = Redis::connection();
        $key = 'XJJ-'.date('ymd');
        if($redis->get($key)==FALSE)
            $redis->setex($key,3600*24,0);
        $sn = $redis->incr($key);
        $sn = str_pad($sn, 5,'0',STR_PAD_LEFT);
        $sn = $pre.$sn;
        return $sn;
    }



}
