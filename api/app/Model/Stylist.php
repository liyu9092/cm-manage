<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use DB;
use App\StylistWorks;
use Log;

class Stylist extends Model {

    protected $table = 'hairstylist';
    protected $fillable = ['stylistId', 'salonId', 'stylistName', 'stylistImg', 'job', 'mobilephone', 'addTime', 'likeNum', 'signature', 'status', 'sex', 'wechat', 'qq', 'email', 'birthday', 'IDcard', 'sNumber', 'workYears', 'grade', 'workExp', 'educateExp', 'description', 'gradeType', 'osType', 'fastGrade'];
    public $timestamps = false;
    protected $primaryKey = 'stylistId';

    public static function getStylistList($param) {
        $query = Self::getQuery();
        $field = ['stylistId', 'stylistName', 'mobilephone', 'sNumber', 'grade', 'fastGrade', 'status'];
        if (!empty($param['salonId'])) {
            $query = $query->where('salonId', $param['salonId']);
        }
        if (isset($param['stylistName']) && $param['stylistName']) {
            $query = $query->where('stylistName', 'like', '%' . $param['stylistName'] . '%');
        }
        if (!empty($param['mobilephone'])) {
            $query = $query->where('mobilephone', 'like', '%' . $param['mobilephone'] . '%');
        }
        $query = $query->whereIn('status', [1, 3]);
        $sortable_keys = ['addTime', 'grade', 'fastGrade', 'status'];
        $sortKey = "addTime";
        $sortType = "DESC";
        if (isset($param['sortKey']) && in_array($param['sortKey'], $sortable_keys)) {
            $sortKey = $param['sortKey'];
            $sortType = $param['sortType'];
            if (strtoupper($sortType) != "DESC") {
                $sortType = "ASC";
            }
        }
        $query->orderBy($sortKey, $sortType);
        $page = isset($param['page']) ? max($param['page'], 1) : 1;
        $page_size = isset($param['page_size']) ? $param['page_size'] : 20;
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
            return $page;
        });

        $results = $query->select($field)->paginate($page_size)->toArray();
        unset($results['next_page_url']);
        unset($results['prev_page_url']);
        foreach ($results['data'] as $key => $value) {
            $num = 0;
            $works = StylistWorks::where('stylist_id', '=', $value->stylistId)->where('status', 'ON')->get();
            foreach ($works as $key1 => $value) {
                if (!empty($value->image_ids)) {
                    $imageArr = explode(',', $value->image_ids);
                    $num = $num + (count($imageArr));
                }
            }
            $results['data'][$key]->num = $num;
        }
        return $results;
    }

    public static function updateStylist($stylistId, $param) {
        $data = array();
        $data['salonid'] = $param['salonid'];
        if (isset($param['stylistName']) && $param['stylistName']) {
            $data['stylistName'] = $param['stylistName'];
        }
        $data['sex'] = $param['sex'];
        $data['mobilephone'] = $param['mobilephone'];
        $data['job'] = $param['job'];
        $data['birthday'] = strtotime($param['birthday']);
        $data['sNumber'] = $param['sNumber'];
        $data['workYears'] = $param['workYears'];
        $data['signature'] = $param['signature'];
        $data['stylistImg'] = $param['stylistImg'];
        if (!empty($param['img'])) {
            $data['img'] = $param['img'];
        }
        if (isset($param['IDcard']) && $param['IDcard']) {
            $data['IDcard'] = $param['IDcard'];
        }
        if (isset($param['drivingLicense']) && $param['drivingLicense']) {
            $data['drivingLicense'] = $param['drivingLicense'];
        }
        if (isset($param['passport']) && $param['passport']) {
            $data['passport'] = $param['passport'];
        }
        if (isset($param['officerCert']) && $param['officerCert']) {
            $data['officerCert'] = $param['officerCert'];
        }
        if (isset($param['wechat']) && $param['wechat']) {
            $data['wechat'] = $param['wechat'];
        }
        if (isset($param['qq']) && $param['qq']) {
            $data['qq'] = $param['qq'];
        }
        if (isset($param['email']) && $param['email']) {
            $data['email'] = $param['email'];
        }
        if (!empty($param['grade'])) {
            $data['grade'] = $param['grade'];
        }
        if (!empty($param['fastGrade'])) {
            $data['fastGrade'] = $param['fastGrade'];
        }
        if (!empty($param['workExp'])) {
            $data['workExp'] = $param['workExp'];
        }
        if (!empty($param['educateExp'])) {
            $data['educateExp'] = $param['educateExp'];
        }
        if (isset($param['description']) && $param['description']) {
            $data['description'] = $param['description'];
        }
        //清理证件,这四个为一个下拉菜单
        $data2 = array();
        $data2['IDcard'] = "";
        $data2['drivingLicense'] = "";
        $data2['passport'] = "";
        $data2['officerCert'] = "";
        $query2 = Self::where(array('stylistId' => $stylistId))->update($data2);
        $query = Self::where(array('stylistId' => $stylistId))->update($data);
        //修改失败且清理成功，则回滚数据
        if ($query === false && $query2 == true) {
            DB::rollback();
        }
        return $query;
    }

    public static function createStylist($salonid, $param) {
        $data = array();
        $data['salonId'] = $salonid;
        $data['stylistImg'] = $param['stylistImg'];
        $data['stylistName'] = $param['stylistName'];
        $data['sex'] = $param['sex'];
        $data['mobilephone'] = $param['mobilephone'];
        $data['job'] = $param['job'];
        $data['birthday'] = strtotime($param['birthday']);
        $data['sNumber'] = $param['sNumber'];
        $data['workYears'] = $param['workYears'];
        $data['signature'] = $param['signature'];

        if (!empty($param['img'])) {
            $data['img'] = $param['img'];
        }
        if (isset($param['IDcard']) && $param['IDcard']) {
            $data['IDcard'] = $param['IDcard'];
        }
        if (isset($param['drivingLicense']) && $param['drivingLicense']) {
            $data['drivingLicense'] = $param['drivingLicense'];
        }
        if (isset($param['passport']) && $param['passport']) {
            $data['passport'] = $param['passport'];
        }
        if (isset($param['officerCert']) && $param['officerCert']) {
            $data['officerCert'] = $param['officerCert'];
        }
        if (isset($param['wechat']) && $param['wechat']) {
            $data['wechat'] = $param['wechat'];
        }
        if (isset($param['qq']) && $param['qq']) {
            $data['qq'] = $param['qq'];
        }
        if (isset($param['email']) && $param['email']) {
            $data['email'] = $param['email'];
        }
        if (!empty($param['grade'])) {
            $data['grade'] = $param['grade'];
        }
        if (!empty($param['fastGrade'])) {
            $data['fastGrade'] = $param['fastGrade'];
        }
        if (!empty($param['workExp'])) {
            $data['workExp'] = $param['workExp'];
        }
        if (!empty($param['educateExp'])) {
            $data['educateExp'] = $param['educateExp'];
        }
        if (isset($param['description']) && $param['description']) {
            $data['description'] = $param['description'];
        }
        $query = self::create($data);
        return $query;
    }

}
