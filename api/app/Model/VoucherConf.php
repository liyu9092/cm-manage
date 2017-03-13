<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Voucher;
use Illuminate\Support\Facades\DB;

class VoucherConf extends Model {

    protected $table = 'voucher_conf';
    protected $primaryKey = 'vcId';
    public $timestamps = false;

    /*
     * 获取活动编号 
     * 
     */

    public static function getVcSn($type = 'cm') {
        $i = 0;
        for ($i; $i <= 10; $i++) {
            $pre = substr(time(), 7);
            $end = rand(0, 999);
            $end = str_pad($end, 3, '0', STR_PAD_LEFT);
            $code = $type . $pre . $end;
            /*
              $where = array('vcSn' => $code);
              $count = M("voucher_conf")->where($where)->count();
             */
            $voucherConf = self::getVoucherConfBySn($code);
            $count = empty($voucherConf) ? 0 : 1;
            if ($count) {
                continue;
            } else {
                return $code;
            }
        }
    }

    public static function getVoucherConfBySn($code) {
        return self::where(['vcSn' => $code])->first();
    }

    /*
     * 添加活动
     */

    public static function addVoucherConf($data) {
        return self::insertGetId($data);
    }

    /*
     * 根据vcsn获取活动信息
     */

    public static function getVoucherConfByVcSns($vcsns) {
        $voucherConf = self::whereIn("vcSn", explode(",", $vcsns))->get();
        $res = [];
        if ($voucherConf) {
            $voucherConf = $voucherConf->toArray();
            foreach ($voucherConf as $key => $conf) {
                $res[$key]['vcSn'] = $conf['vcSn'];
                $res[$key]['vcId'] = $conf['vcId'];
                $res[$key]['useItemTypes'] = $conf['useItemTypes'];
                $res[$key]['useTotalNum'] = $conf['useTotalNum'];
                $res[$key]['useMoney'] = $conf['useMoney'];
                $res[$key]['few_day'] = $conf['FEW_DAY'];  //TODO  活动有效时间
                $res[$key]['useNeedMoney'] = $conf['useNeedMoney'];  //TODO  满足金额可用
            }
        }
        return $res;
    }

    /*
     * 已消费 代金券
     */

    public static function getVoucherConfConsume($laisee) {
        $usedWhere = $laisee->vcsns . "," . $laisee->gift_vcsn;
        $query = Voucher::whereIn('vcSn', array_filter(array_unique(explode(",", $usedWhere))));
        $voucher = $query->where('vStatus', 2)->get();
        $consumeVsns = [];
        foreach ($voucher as $v) {
            $consume = DB::table('order')->where('ordersn', $v->vOrderSn)->where('status', 4)->first();
            if ($consume) {
                $consumeVsns[] = $v->vSn;
            }
        }
        $consumeNum = $consumeVsns ? Voucher::whereIn('vSn', $consumeVsns)->count() : 0;
        $consumeAmount = $consumeVsns ? Voucher::whereIn('vSn', $consumeVsns)->sum('vUseMoney') : 0;
        return ['consumeNum' => $consumeNum, 'consumeAmount' => $consumeAmount];
    }

}
