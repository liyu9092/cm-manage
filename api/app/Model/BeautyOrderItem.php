<?php
namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
Use PDO;

class BeautyOrderItem extends Model
{
    protected $table = 'beauty_order_item';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    public static function formatSingle($data,$userIdx=[],$guider_index=[])
    {     
        $data = self::makeJsonInfo($data,'attr',$userIdx);
        $data = self::makeJsonInfo($data,'makeup1',$userIdx);
        $data = self::makeJsonInfo($data,'makeup2',$userIdx);        
        $data = self::makeUserInfo($data,$userIdx,$guider_index);
        return $data;
    }
    
    public static function makeJsonInfo($data,$attr,$userIdx=[],$guider_index=[])
    {
        if(isset($data[$attr]))
        {
            $attr_info = @json_decode($data[$attr],true);
          
            if(!is_array($attr_info))
            {
                $attr_info=null;
            }
            else
            {
                $attr_info = self::makeUserInfo($attr_info,$userIdx,$guider_index);
            }           
            $data[$attr] = $attr_info;
        }
        return $data;
    }
    
    public static function formatMutil($datas)
    {
        $res = [];
        
        $makeup1_str_arr = array_column($datas, 'makeup1');
        $makeup2_str_arr = array_column($datas, 'makeup2');
        $all_user_ids = [];
        $all_user_ids = array_merge($all_user_ids,array_column($datas, 'guideId'));
        $all_user_ids = array_merge($all_user_ids,array_column($datas, 'beauticianId'));
        $all_user_ids = array_merge($all_user_ids,array_column($datas, 'deanId'));
        $all_user_ids = array_merge($all_user_ids,array_column($datas, 'assistantId'));
        $all_user_ids = array_merge($all_user_ids, self::getAlluserId($makeup1_str_arr));
        $all_user_ids = array_merge($all_user_ids, self::getAlluserId($makeup2_str_arr));
        
        $user_idx = Artificer::getBaseInfos($all_user_ids);
        $guider_index = BeautyOthers::getBaseInfos($all_user_ids);
        foreach($datas as $key => $data)
        {
            $res[$key] = self::formatSingle($data,$user_idx,$guider_index);
        }
        return $res;
    }
    
    public static function makeUserInfo($data,$userIdx=[],$guider_index=[])
    {
        if(isset($data['guideId']) && isset($guider_index[$data['guideId']]))
        {
            $data['guide_user'] = $guider_index[$data['guideId']];
        }
        if(isset($data['beauticianId']) && isset($guider_index[$data['beauticianId']]))
        {
            $data['beautician_user'] = $guider_index[$data['beauticianId']];
        }
        if(isset($data['deanId']) && isset($userIdx[$data['deanId']]))
        {
            $data['dean_user'] = $userIdx[$data['deanId']];
        }
        if(isset($data['assistantId']) && isset($userIdx[$data['assistantId']]))
        {
            $data['assistant_user'] = $userIdx[$data['assistantId']];
        }
        return $data;
    }
    
    public static function getAlluserId($str_arr)
    {
        $res = [];
        foreach($str_arr as $str)
        {
            $attr = @json_decode($str,true);
            if(!is_array($attr))
            {
                continue;
            }
            if(isset($attr['guideId']))
            {
                $res[] = $attr['guideId'];
            }
            if(isset($attr['beauticianId']))
            {
                $res[] = $attr['beauticianId'];
            }
            if(isset($attr['deanId']))
            {
                $res[] = $attr['deanId'];
            }
            if(isset($attr['assistantId']))
            {
                $res[] = $attr['assistantId'];
            }
        }
        return $res;
    }
    
    public function isFillable($key)
    {
        return true;
    }
}
