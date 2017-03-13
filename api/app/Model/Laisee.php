<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\VoucherConf;
use App\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\AbstractPaginator;

class Laisee extends Model {

    protected $table = 'laisee';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    public $timestamps = false;

    public static function getLaiseeList($data, $page, $size) {
        DB::enableQueryLog();
        $query = Self::leftJoin('laisee_config', 'laisee_config.id', '=', 'laisee.laisee_config_id')
                ->leftJoin('salon_itemcomment', 'laisee.order_ticket_id', '=', 'salon_itemcomment.order_ticket_id')
                ->leftJoin('user', 'user.user_id', '=', 'salon_itemcomment.user_id');
        //商户名筛选
        if (!empty($data['bonusSn'])) {
            $bonusSn = (int) substr($data['bonusSn'], 2);
            $query = $query->where('laisee.order_ticket_id', $bonusSn);
        }
        if ($data['mobilephone']) {
            $query = $query->where('user.mobilephone', $data['mobilephone']);
        }
        if ($data['laisee_name']) {
            $query = $query->where('laisee_config.laisee_name', "like", "%" . $data['laisee_name'] . "%");
        }
        if ($data['bonusStatus']) {
            if ($data['bonusStatus'] == 'Y') {
                $query = $query->where('laisee.status', 'Y')->where('laisee.end_time', '>=', date("Y-m-d H:i:s"));
            } else {
                $query = $query->where('laisee.end_time', '<=', date("Y-m-d H:i:s"));
            }
        }
        if ($data['start_time']) {
            $start_time = strtotime($data['start_time']);
            $query = $query->where('salon_itemcomment.add_time', ">=", $start_time);
        }
        if ($data['end_time']) {
            $end_time = strtotime($data['end_time']) + (24 * 3600 - 1);
            $query = $query->where('salon_itemcomment.add_time', "<=", $end_time);
        }
        $query->orderBy('salon_itemcomment.add_time', 'desc')->groupBy('laisee.order_ticket_id');

        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });
        $fields = array(
            'laisee_config.laisee_name',
            'laisee_config.id',
            'laisee.order_ticket_id',
            'salon_itemcomment.add_time',
            'laisee.end_time',
            'laisee.status',
        );

        //分页
        $result = $query->select($fields)->paginate($size)->toArray();
        unset($result['next_page_url']);
        unset($result['prev_page_url']);
        return $result;
    }

    /*
     * 现金券  详情
     */

    public static function getVoucher($laiseeConfig) {
        $laisee = VoucherConf::whereIn('vcSn', explode(",", $laiseeConfig->vcsns))->get();
        $itemTypes = DB::table("salon_itemtype")->get();
        $itemType = [];
        foreach ($itemTypes as $type) {
            $itemType[$type->typeid] = $type->typename;
        }
        $voucher = [];
        if ($laisee) {
            $laisee = $laisee->toArray();
            foreach ($laisee as $key => $val) {
                $voucher[$key]['useMoney'] = $val['useMoney'];
                $voucher[$key]['useItemTypes'] = isset($itemType[$val['useItemTypes']]) ? $itemType[$val['useItemTypes']] : '';
                $voucher[$key]['useTotalNum'] = $val['useTotalNum'];
            }
        }
        return $voucher;
    }

    /*
     * 已领取现金券想请 
     */

    public static function getReceiveVoucher($id, $laiseeConfig) {
        $laisee = Laisee::select('vsn', 'mobilephone')->where('order_ticket_id', $id)->whereNotNull('mobilephone')->whereIn('vcSn', explode(",", $laiseeConfig->vcsns))->get();
        if ($laisee) {
            return $laisee->toArray();
        } else {
            return [];
        }
    }

    /*
     * 礼包详情
     */

    public static function getGiftVoucher($laiseeConfig) {
        $laisee = VoucherConf::whereIn('vcSn', explode(",", $laiseeConfig->gift_vcsn))->get();
        $itemTypes = DB::table("salon_itemtype")->get();
        $itemType = [];
        foreach ($itemTypes as $type) {
            $itemType[$type->typeid] = $type->typename;
        }
        $voucher = [];
        if ($laisee) {
            $laisee = $laisee->toArray();
            foreach ($laisee as $key => $val) {
                $voucher[$key]['useMoney'] = $val['useMoney'];
                $voucher[$key]['useItemTypes'] = isset($itemType[$val['useItemTypes']]) ? $itemType[$val['useItemTypes']] : '';
                $voucher[$key]['useTotalNum'] = $val['useTotalNum'];
            }
        }
        return $voucher;
    }

    /*
     * 领取礼包用户
     */

    public static function getGiftUser($id, $laiseeConfig) {
        $laisee = Laisee::select('vsn', 'mobilephone')->where('order_ticket_id', $id)->whereNotNull('mobilephone')->whereIn('vcSn', explode(",", $laiseeConfig->gift_vcsn))->get();
        if ($laisee) {
            return $laisee->toArray();
        } else {
            return [];
        }
    }

}
