<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class BeautyOthers extends  Model
{
    protected $table = 'beauty_others';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
    public static function getBaseInfo($id)
    {
        $base = self::where('id',$id)->first(['id','name','number']);
        if(empty($base))
        {
            return null;
        }
        return $base->toArray();
    }
    
    public static function getBaseInfos($ids)
    {
        $bases = self::whereIn('id',$ids)->get(['id','name'])->toArray();
        $bases = Utils::column_to_key('id',$bases);
        $res = [];
        foreach($ids as $id)
        {
            $res[$id] = null;
            if(isset($bases[$id]))
            {
                $res[$id] = $bases[$id];
            }
        }
        return $res;
    }
}