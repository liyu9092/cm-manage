<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\VoucherConf;
use App\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\AbstractPaginator;

class LaiseeConfig extends Model {

    protected $table = 'laisee_config';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false;

    /*
     * 添加或修改红包活动
     */

    public static function doAdd($where, $data) {
        DB::beginTransaction();
        DB::enableQueryLog();
        if ($where) {//修改
            $vcsns = self::updateVoucherConfLaisee($data);
            if ($vcsns) {
                $gift_vcsn = self::updateVoucherConfGift($data);
                $lcData = [
                    'laisee_name' => $data['laisee_name'],
                    'lc_remark' => $data['lc_remark'],
                    'vcsns' => $vcsns,
                    'gift_vcsn' => !empty($gift_vcsn) ? $gift_vcsn : '',
                    'over_time' => $data['effective'] * 24 * 60 * 60, //秒数
                    'total_money' => $data['total_money'],
                    'amount_warning' => $data['amount_warning'],
                    'warning_phone' => $data['warning_phone'],
                    'share_icon' => $data['share_icon'],
                    'share_title' => $data['share_title'],
                    'share_desc' => $data['share_desc'],
                    'bonus_bg_img' => $data['bonus_bg_img'],
                    'sms_on_gained' => $data['sms_on_gained'],
                    'send_warning_sms' => $data['send_warning_sms'],
                ];
                $res = self::where('id', $data['id'])->update($lcData);
                if ($res !== false) {
                    DB::commit();
                    return true;
                } else {
                    DB::rollBack();
                    return false;
                }
            }
        } else {//添加
            //先插入到voucher_conf中  非礼包
            $vcsns = self::addVoucherConfLaisee($data);
            if ($vcsns) { //插入voucher_conf 中 礼包
                $gift_vcsn = self::addVoucherConfGift($data);
                $lcData = [
                    'laisee_name' => $data['laisee_name'],
                    'lc_remark' => $data['lc_remark'],
                    'status' => 'F',
                    'vcsns' => $vcsns,
                    'gift_vcsn' => !empty($gift_vcsn) ? $gift_vcsn : '',
                    'over_time' => $data['effective'] * 24 * 60 * 60, //秒数
                    'total_money' => $data['total_money'],
                    'amount_warning' => $data['amount_warning'],
                    'warning_phone' => $data['warning_phone'],
                    'share_icon' => $data['share_icon'],
                    'share_title' => $data['share_title'],
                    'share_desc' => $data['share_desc'],
                    'bonus_bg_img' => $data['bonus_bg_img'],
                    'sms_on_gained' => $data['sms_on_gained'],
                    'create_time' => date("Y-m-d H:i:s"),
                    'send_warning_sms' => (isset($data['amount_warning']) && $data['amount_warning'] > 0) ? 'Y' : 'N',
                ];
                $id = self::insertGetId($lcData);
                if ($id) {
                    DB::commit();
                    return true;
                } else {
                    DB::rollBack();
                    return false;
                }
            } else {
                DB::rollBack();
                return false;
            }
        }
    }

//先插入到voucher_conf中  非礼包
    private static function addVoucherConfLaisee($data) {
        $voucher = $data['voucher'];
        $vcsns = [];
        foreach ($voucher as $val) {
            $vcSn = VoucherConf::getVcSn('hb');
            $vcData = [
                'vcTitle' => $data['laisee_name'],
                'vcSn' => $vcSn,
                'FEW_DAY' => ($val['few_day']), //TODO  有效时间需要再次确认
                'useMoney' => $val['vUseMoney'],
                'useTotalNum' => $val['vNumber'],
                'useItemTypes' => empty($val['vUseItemTypes'])?'':$val['vUseItemTypes'],
                'useNeedMoney' => $val['vUseNeedMoney'],
                'status' => 2,
                'SMS_ON_GAINED' => $data['sms_on_gained'],
                'vType' => 2,
            ];
            $vcId = VoucherConf::addVoucherConf($vcData);
            if ($vcId) {
                for ($j = 0; $j < $val['vNumber']; $j++) {  //如果数量大于1 则重复
                    $vcsns[] = $vcData['vcSn'];
                }
            }
        }
        return $vcsns ? implode(",", $vcsns) : '';
    }

    /*
     * 获取现金券活动配置信息 字段
     */

    private static function getVoucherConfField($data) {
        $voucher = json_decode($data['voucher'], true);
        return $voucher;
    }

    /*
     *   获取礼包活动配置信息 字段
     */

    private static function getGiftConfField($data) {
        $gift = json_decode($data['gift'], true);
        return $gift;
    }

    //插入voucher_conf 中 礼包
    private static function addVoucherConfGift($data) {
        $gift = $data['gift'];
        $gVoucherCount = count($gift);
        $gift_vcsn = [];
        if ($gVoucherCount) {
            foreach ($gift as $val) {
                $vcSn = VoucherConf::getVcSn('hb');
                $vcData = [
                    'vcTitle' => $data['laisee_name'],
                    'vcSn' => $vcSn,
                    'FEW_DAY' => ($val['few_day']), //TODO  有效时间需要再次确认
                    'useMoney' => $val['vUseMoney'],
                    'useTotalNum' => $val['vNumber'],
                    'useItemTypes' => empty($val['vUseItemTypes'])?'':$val['vUseItemTypes'],
                    'useNeedMoney' => $val['vUseNeedMoney'],
                    'status' => 2,
                    'SMS_ON_GAINED' => $data['sms_on_gained'],
                    'vType' => 2,
                ];
                $vcId = VoucherConf::addVoucherConf($vcData);
                if ($vcId) {
                    for ($j = 0; $j < $val['vNumber']; $j++) {  //如果数量大于1 则重复
                        $gift_vcsn[] = $vcData['vcSn'];
                    }
                }
            }
        }
        return $gift_vcsn ? implode(",", $gift_vcsn) : [];
    }

    public static function getLaiseeList($laiseeName, $startTime, $endTime, $page, $size) {
        DB::enableQueryLog();
        $field = ['id', 'laisee_name', 'create_time', 'start_time', 'end_time', 'status', 'vcsns', 'gift_vcsn', 'over_time'];  //TODO
//        $query = Self::select("*");
        $query = Self::select($field);
        if (!empty($laiseeName)) {
            $query->where('laisee_name', 'like', "%" . $laiseeName . "%");
        }
        if ($startTime) {
            $query->where('create_time', '>=', $startTime);
        }
        if ($endTime) {
            $endTime = $endTime . " 23:59:59";
            $query->where('create_time', '<=', $endTime);
        }
        $query->orderBy('create_time', 'desc');
        AbstractPaginator::currentPageResolver(function () use($page) {
            return $page;
        });
        $laiseeList = $query->paginate($size)->toArray();
        unset($laiseeList['next_page_url']);
        unset($laiseeList['prev_page_url']);
        return $laiseeList;
    }

    /*
     *  更新代金券活动配置信息
     */

    private static function updateVoucherConfLaisee($data) {
        $conf = $data['voucher'];
//        print_r($conf);exit;
        $vVoucherCount = count($conf);
        $vcsns = [];
        //先修改 
        if ($vVoucherCount) {
            foreach ($conf as $val) {
                if ($val['vVcId']) {  //修改
                    $vcData = [
                        'vcTitle' => $data['laisee_name'],
                        'FEW_DAY' => ($val['few_day']),
                        'useMoney' => $val['vUseMoney'],
                        'useTotalNum' => $val['vNumber'],
                        'useItemTypes' => empty($val['vUseItemTypes'])?'':$val['vUseItemTypes'],
                        'useNeedMoney' => $val['vUseNeedMoney'],
                        'SMS_ON_GAINED' => $data['sms_on_gained'],
                    ];
                    $vcId = VoucherConf::where('vcId', $val['vVcId'])->update($vcData);
                    $voucherConfSn = VoucherConf::find($val['vVcId'])->vcSn;
                    for ($j = 0; $j < $val['vNumber']; $j++) {  //如果数量大于1 则重复
                        $vcsns[] = $voucherConfSn;
                    }
                } else {
                    //添加
                    $vcSn = VoucherConf::getVcSn('hb');
                    $vcData = [
                        'vcTitle' => $data['laisee_name'],
                        'vcSn' => $vcSn,
                        'FEW_DAY' => ($val['few_day']),
                        'useMoney' => $val['vUseMoney'],
                        'useTotalNum' => $val['vNumber'],
                        'useItemTypes' => empty($val['vUseItemTypes'])?'':$val['vUseItemTypes'],
                        'useNeedMoney' => $val['vUseNeedMoney'],
                        'status' => 2,
                        'SMS_ON_GAINED' => $data['sms_on_gained'],
                        'vType' => 2,
                    ];
                    $vcId = VoucherConf::addVoucherConf($vcData);
                    if ($vcId) {
                        for ($j = 0; $j < $val['vNumber']; $j++) {  //如果数量大于1 则重复
                            $vcsns[] = $vcData['vcSn'];
                        }
                    }
                }
            }
        }

        //删除
        $delVcId = isset($data['delVcId']) ? explode(",", $data['delVcId']) : [];
        if (!empty($delVcId)) {
            VoucherConf::whereIn("vcId", $delVcId)->delete();
        }
        return $vcsns ? implode(",", $vcsns) : '';
    }

    /*
     * 更新礼包活动配置信息
     */

    private static function updateVoucherConfGift($data) {
        $conf = $data['gift'];
        $gVoucherCount = count($conf);
        $vcsns = [];
        //先修改 
        if ($gVoucherCount) {
            foreach ($conf as $val) {
                if ($val['vVcId']) {  //修改
                    $vcData = [
                        'vcTitle' => $data['laisee_name'],
                        'FEW_DAY' => ($val['few_day']),
                        'useMoney' => $val['vUseMoney'],
                        'useTotalNum' => $val['vNumber'],
                        'useItemTypes' => empty($val['vUseItemTypes'])?'':$val['vUseItemTypes'],
                        'useNeedMoney' => $val['vUseNeedMoney'],
                        'SMS_ON_GAINED' => $data['sms_on_gained'],
                    ];
                    $vcId = VoucherConf::where('vcId', $val['vVcId'])->update($vcData);
                    $voucherConfSn = VoucherConf::find($val['vVcId'])->vcSn;
                    for ($j = 0; $j < $val['vNumber']; $j++) {  //如果数量大于1 则重复
                        $vcsns[] = $voucherConfSn;
                    }
                } else {
                    //添加
                    $vcSn = VoucherConf::getVcSn('hb');
                    $vcData = [
                        'vcTitle' => $data['laisee_name'],
                        'vcSn' => $vcSn,
                        'FEW_DAY' => ($val['few_day']),
                        'useMoney' => $val['vUseMoney'],
                        'useTotalNum' => $val['vNumber'],
                        'useItemTypes' => empty($val['vUseItemTypes'])?'':$val['vUseItemTypes'],
                        'useNeedMoney' => $val['vUseNeedMoney'],
                        'SMS_ON_GAINED' => $data['sms_on_gained'],
                        'status' => 2,
                        'vType' => 2,
                    ];
                    $vcId = VoucherConf::addVoucherConf($vcData);
                    if ($vcId) {
                        for ($j = 0; $j < $val['vNumber']; $j++) {  //如果数量大于1 则重复
                            $vcsns[] = $vcData['vcSn'];
                        }
                    }
                }
            }
        }
        $delGiftVcId = isset($data['delGiftVcId']) ? explode(",", $data['delGiftVcId']) : [];
        //删除
        if ($delGiftVcId) {
            VoucherConf::whereIn("vcId", $delGiftVcId)->delete();
        }
        return $vcsns ? implode(",", $vcsns) : '';
    }

    /*
     *  判断在线的活动是否过期
     */

    public static function laiseeConfigAble() {
        //判断在线的活动是否有效
        $laiseeConfig = LaiseeConfig::where('status', 'Y')->first();
        if ($laiseeConfig) {
            $ableTime = $laiseeConfig->start_time + $laiseeConfig->over_time;
            $ableTime = date("Y-m-d", $ableTime) . " 23:59:59";
            $ableTimeStr = strtotime($ableTime);
            if (time() > $ableTimeStr) {
                LaiseeConfig::where('id', $laiseeConfig->id)->update(['status' => 'N', 'end_time' => date('Y-m-d H:i:s')]); //将活动设置为结束
                Laisee::where('laisee_config_id', $laiseeConfig->id)->update(['status' => 'N']); //将活动相关的红包设置为过期
            }
        }
    }

}
