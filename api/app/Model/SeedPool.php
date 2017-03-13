<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use DB;
use Log;

use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class SeedPool extends Model
{
    protected $table = 'seed_pool';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    /**
     * 根据条件，获取所需要的赠送券,提供给线下活动，队列使用
     */
    public static function getArticleTicketFromPool($limit=1,$orderby = 'desc'){
        $sql = "select CONCAT('SZ',SEED) as articleTicket from cm_seed_pool where TYPE = 'GSN' and STATUS = 'NEW' order by ID $orderby limit $limit;";
        $res = DB::select($sql);
        if(empty($res)){
            Log::info('线下活动无法获取定妆赠送活动券,券数量不足');
            return false;    
        }
        if(count($res) != $limit){
            Log::info('线下活动臭美池中的券不够');
            return false;
        }
        foreach ($res as $key => $value) {
            $seedRes[] = $value->articleTicket;
        }
        return $seedRes;
    }
    /**
     * 
     * @param type $limit
     * @param type $orderby
     * @return type
     * @throws ApiException
     * 线下活动发券
     */
    
    public static function getArticleTicketFromPoolForOnline($limit=1,$orderby = 'desc'){
        $sql = "select CONCAT('SZ',SEED) as articleTicket from cm_seed_pool where TYPE = 'GSN' and STATUS = 'NEW' order by ID $orderby limit $limit;";
        $res = DB::select($sql);
        if(empty($res)){
            Log::info('无法获取定妆赠送活动券,券数量不足');
            throw new ApiException('无法获取定妆赠送活动券,券数量不足');       
        }
        if(count($res) != $limit){
            Log::info('臭美池中的券不够');
            throw new ApiException('臭美池中的券不够');    
        }
        foreach ($res as $key => $value) {
            $seedRes[] = $value->articleTicket;
        }
        return $seedRes;
    }
    

    /***
     * 获取预约订单号，即臭美券号
     */
    public static function getReservateSnFromPool(){
        $where = array('TYPE' => 'TKT','STATUS' => 'NEW');
        $res =  self::select('SEED as reservateSn')
               ->where($where)->first();
        if(empty($res)){
            throw new ApiException('无法获取定妆预约券号');    
        }
        return $res->toArray();
    }
    
    public function isFillable($key)
    {
        return true;
    }
}
