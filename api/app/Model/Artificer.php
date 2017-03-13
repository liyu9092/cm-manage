<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Artificer extends  Model
{
    protected $table = 'artificer';
    protected $primaryKey = 'artificer_id';
    public $timestamps = false;
    
    public static function getBaseInfo($id)
    {
        $base = self::where('artificer_id',$id)->first(['artificer_id','name','number']);
        if(empty($base))
        {
            return null;
        }
        return $base->toArray();
    }
    
    public static function getBaseInfos($ids)
    {
        $bases = self::whereIn('artificer_id',$ids)->get(['artificer_id','name','number'])->toArray();
        $bases = Utils::column_to_key('artificer_id',$bases);
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