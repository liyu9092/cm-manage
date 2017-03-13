<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\AbstractPaginator;

class SalonStarConf extends Model {

    protected $table = 'salon_star_conf';
    public $timestamps = false;

    public static function getSalonLevelList() {
        $levelList = self::all()->toArray();
        $res = array();
        $maxKey = count($levelList);
        $business_salon_star = DB::table('business_switch')->where(['business_name' => 'salon_star'])->first()->status;
        foreach ($levelList as $key => $val) {
            $prevScore = $val['score'];
            if ($key != $maxKey) {
                if (isset($levelList[$key + 1])) {
                    $nextScore = $levelList[$key + 1]['score'] - 1;
                } else {
                    $nextScore = 999999;
                }
            }
            $salonCount = \App\Salon::query()->whereBetween('score', [$prevScore, $nextScore])->count();
            $levelList[$key]['salonCount'] = $salonCount;
            $levelList[$key]['status'] = $business_salon_star;
        }
        return $levelList;
    }

    public static function updateConf($conf) {
        if (self::checkScore($conf)) {
            $res = self::where(['id' => $conf['id']])->update(['score' => $conf['score']]);
            if ($res !== false) {
                return 0;
            } else {
                return 2;
            }
        } else {
            return 1;
        }
    }

    private static function checkScore($conf) {
        $id = $conf['id'];
        $maxLevelId = self::query()->max('id');
        $minLevelId = self::query()->min('id');
        if ($id == $maxLevelId) {
            $nextLevelScore = 9999999;
        }
        if ($id == $minLevelId) {
            $prevLevelScore = -1;
        }
        $id != $minLevelId && $prevLevelScore = self::find(($id - 1))->score;
        $id != $maxLevelId && $nextLevelScore = self::find(($id + 1))->score;
        if ($conf['score'] > $prevLevelScore && $conf['score'] < $nextLevelScore) {
            return true;
        } else {
            return false;
        }
    }

    public static function getPrevAndNextScore4Level($level) {
        $starConf = self::where(['level' => $level])->first();
        if ($starConf) {
            $prevScore = $starConf->score;
            $maxLevel = self::query()->max('level');
            if ($level == $maxLevel) {
                $nextScore = 9999999;
            } else {
                $nextScore = self::where(['level' => $level + 1])->first()->score - 1;
            }
            return ['prevScore' => $prevScore, 'nextScore' => $nextScore];
        } else {
            return false;
        }
    }

    public static function getSalonList($salonname, $score, $page, $size) {
        $query = \App\Salon::select(['salonname', 'score', 'salonid']);
        if (!empty($salonname)) {
            $query->where('salonname', 'like', "%" . $salonname . "%");
        }
        if ($score) {
            $query->whereBetween('score', [$score['prevScore'], $score['nextScore']]);
        }

        AbstractPaginator::currentPageResolver(function () use($page) {
            return $page;
        });
        $salonList = $query->paginate($size)->toArray();
        unset($salonList['next_page_url']);
        unset($salonList['prev_page_url']);
        return $salonList;
    }

    /*
     * 获取所有等级积分信息
     */

    private static function getStarConf() {
        $salonLevel = self::all()->toArray();
        $levelArr = array();
        foreach ($salonLevel as $level) {
            $levelArr[$level['score']] = $level['level'];
        }
        return $levelArr;
    }

    /*
     * 根据积分获取等级 
     */

    public static function getLevelByScore($score) {
        $levelArr = self::getStarConf();
        $levelArrKeys = array_keys($levelArr);
        $level = 0;
        $temp = [];
        foreach ($levelArrKeys as $levelKey) {
            if ($score >= $levelKey) {
                $temp[] = $levelKey;
            }
        }
        if (!empty($temp)) {
            $maxKey = max($temp);
            $level = $levelArr[$maxKey];
        }
        return $level;
    }

}
