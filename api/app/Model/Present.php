<?php

namespace App\Model;

use App\Manager;
use Illuminate\Pagination\AbstractPaginator;
use Illuminate\Database\Eloquent\Model;

use App\Exceptions\ApiException;
use App\Exceptions\ERROR;

class Present extends Model
{
    protected $table = 'present';
    protected $primaryKey = 'present_id';
    public $timestamps = false;
    
    private static $presentField = array(
            'present.present_id as presentId',
            'present.name as articleName',
            'present.item_id as itemId',
            'present.quantity',
            'present.use_num as useNum',
            'present.start_at as startTime',
            'present.end_at as endTime',
            'present.expire_at as expireTime',
            'present.department_id as departmentId',
            'present.user_id as userId',
            'present.creater_id as createrId',
            'present.detail',
            'present.article_status as articleStatus',
            'present.verify_status as verifyStatus',
            'present.article_type as articleType',
            'present.created_at as createTime',         
       );
    public static function getArticleInfoByWhere($where){
        $res = self::where($where)->first();
        if($res === null){
            throw new ApiException('找不到改活动'); 
        }else{
            return $res->toArray();
        }
    }
    
    public static function getArticlesList($name,$departmentId,$startTime,$endTime,$page,$pageSize){
        $field1 = self::$presentField;     
        $field2 = array('beauty_item.name as itemName','departments.title as departmentName');
        $field = array_merge($field1,$field2);
        $query = self::select($field)
                ->leftJoin('beauty_item', 'present.item_id', '=', 'beauty_item.item_id')
                ->leftJoin('departments','present.department_id', '=', 'departments.id');
        
        if($name){
           $query = $query->where('present.name','like','%'.$name.'%');
        }
        if($departmentId){
           $query = $query->where('present.department_id','=',$departmentId);
        }
        if($startTime){
            $query = $query->where('present.created_at','>=',$startTime);
        }
        if($endTime){
           $query = $query->where('present.created_at','<=',$endTime);
        }
        $query = $query->orderBy('present.created_at','desc');
        //手动设置页数
        AbstractPaginator::currentPageResolver(function() use ($page) {
              return $page;
        });      
        $articlesListInfo = $query->paginate($pageSize)->toArray();
        unset($articlesListInfo['next_page_url']);
        unset($articlesListInfo['prev_page_url']);
        return $articlesListInfo;
        
    }
    
    /**
     * 根据活动id,获取活动详情
     */
    public static function getArticlesInfoByWhere($where){
        $field1 = self::$presentField;
        $field2 = array('managers.name as managerName','beauty_item.name as itemName','departments.title as departmentName');
        $field = array_merge($field1,$field2);
        $query = self::select($field)
                ->leftJoin('beauty_item', 'present.item_id', '=', 'beauty_item.item_id')
                ->leftJoin('departments','present.department_id', '=', 'departments.id')
                ->leftJoin('managers','present.user_id','=','managers.id');
        $articlesInfo = $query->where($where)->first();
        if($articlesInfo === null){
            return [];
        }else{
            $allInfo = $articlesInfo->toArray();
            $createrInfo = Manager::select('name')->where('id','=',$articlesInfo['createrId'])->first();
            if($createrInfo === null){
                $allInfo['creater'] = '';
            }else{
                $createrName = $createrInfo->toArray();
                $allInfo['creater'] = $createrName['name'];
            }
            return $allInfo;
        }
        
    }
    
}
