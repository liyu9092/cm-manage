<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\AbstractPaginator;

class SalonScoreLog extends Model {

    protected $table = 'salon_score_log';
    protected $fillable = array('salon_id', 'score', 'description', 'create_time');
    public $timestamps = false;

    public static function getLogList($options, $startTime, $endTime, $page, $size) {
        $query = self::select(['score', 'description', 'create_time']);
        DB::enableQueryLog();
        $query->where('salon_id', $options['salonid']);
        if (!empty($startTime)) {
            $query->where('create_time', '>=', $startTime);
        }
        if (!empty($endTime)) {
            $endTime=date("Y-m-d",  strtotime($endTime))." 23:59:59";
            $query->where('create_time', '<=', $endTime);
        }
        $query->orderBy('create_time', 'desc');
        AbstractPaginator::currentPageResolver(function () use($page) {
            return $page;
        });
        $salonList = $query->paginate($size)->toArray();
        $salonInfo = \App\Salon::select(['score'])->find($options['salonid']);
        foreach ($salonList['data'] as $key => $val) {
            if ($key != 0) {
                $salonList['data'][$key]['totalScore'] = $salonList['data'][$key - 1]['totalScore'] - $salonList['data'][$key - 1]['score'];
            } else {
                $salonList['data'][$key]['totalScore'] = $salonInfo->score;
            }
        }
//        print_r(DB::getQueryLog());
        unset($salonList['next_page_url']);
        unset($salonList['prev_page_url']);
        return $salonList;
    }

}
