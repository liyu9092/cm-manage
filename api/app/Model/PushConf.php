<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use App\Salon ;
use App\Exceptions\ApiException;

class PushConf extends Model
{
    protected $table = 'push_conf';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    private static $pushConfField = array(
           'ID as id',
            'RECEIVE_TYPE as receiveType',
            'COMPANY_CODE as companyCode',
            'ACTIVITY_CODE as activityCode',
            'SHOP_CODE as shopCode',
            'TITLE as title',
            'CONTENT as content',
            'SEND_TIME as sendTime',
            'LINK as link',
            'DETAIL as detail',
            'IS_PUSH as isPush',
            'READ_NUM as readNum',
            'PUSH_NUM as pushNum', 
            'STATUS as status',
            'CREATE_TIME as createTime',
           'UPDATE_TIME as updateTime',
       );
    
    public static function getMessageBoxInfo($title,$status,$receiveType,$startTime,$endTime, $page, $pageSize){
       $field = self::$pushConfField;
       $query =  self::where('STATUS','<>',$status)->where('RECEIVE_TYPE','<>',$receiveType);     //
       if($title){
           $query = $query->where('TITLE','like','%'.$title.'%');
       }
       if($startTime){
           $query = $query->where('SEND_TIME','>=',$startTime);
       }
       if($endTime){
           $query = $query->where('SEND_TIME','<=',$endTime);
       }
       $query = $query->orderBy('CREATE_TIME','desc');
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
              return $page;
        });      
        $messageBoxInfo = $query->select($field)->paginate($pageSize)->toArray();
        unset($messageBoxInfo['next_page_url']);
        unset($messageBoxInfo['prev_page_url']);
        return $messageBoxInfo;
    }
    
    //根据ID获取消息盒子信息
    public static function getMessageBoxInfoByID($pushId) {
        $field = self::$pushConfField;
        $messageInfo = self::select($field)->where('ID','=',$pushId)->first();
        if($messageInfo === null){
            return [];
        }else{
            return $messageInfo->toArray();
        }
        
    }
    
    /***
     * 查看消息盒子信息(组装)
     */
    public static function showMessageBoxInfoById($pushId){
        
        $messageInfo = self::getMessageBoxInfoByID($pushId);
        if(empty($messageInfo)){
            throw new ApiException('pushId不存在');
        }
        $messageInfo['companyCodeArr'] = empty($messageInfo['companyCode']) ? array() : explode(',',$messageInfo['companyCode']);
        $messageInfo['activityCodeArr'] = empty($messageInfo['activityCode']) ? array() : explode(',',$messageInfo['activityCode']);
        $messageInfo['shopCodeArr'] = empty($messageInfo['shopCode']) ? array() : explode(',',$messageInfo['shopCode']);
        
        //根据店铺码获取店铺名
        if($messageInfo['shopCodeArr']){
            $field = array('dividend.recommend_code as recommendCode','salon.salonname as salonName'); 
            $where['salon.status'] = 1;
            $where['salon.salestatus'] = 1;
            $where['dividend.activity'] = 2;
            $where['dividend.status'] = 0;
            $messageInfo['salonInfo'] = Salon::getSalonInfoByCodeArr($field,$where,$messageInfo['shopCodeArr']);           
        }
        return $messageInfo;
        
    }
    //获取消息盒子信息
    public static function getMessageBoxInfoOnWhere($where,$orderBy = '',$OrderByVal = ''){
        $field = self::$pushConfField;
        $query = self::select($field)->where($where);
        if($orderBy && $OrderByVal){
            $query = $query->orderBy($orderBy,$OrderByVal);
        }
        $messageInfo = $query->first();
        if($messageInfo === null){
            return [];
        }else{
            return $messageInfo->toArray();
        }
    }
    
    
    
}
