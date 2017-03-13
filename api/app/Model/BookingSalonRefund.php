<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use DB;
Use PDO;

class BookingSalonRefund extends Model
{
    protected $table = 'booking_salon_refund';
    protected $primaryKey = 'id';
    public $timestamps = false;

    public static function getByBookingId($booking_id,$fields=['*'])
    {
        $base = self::where('booking_id',$booking_id)->first($fields);
        if(empty($base))
        {
            return null;
        }
        $res = $base->toArray();
        $res['manager']=Manager::getBaseInfo($res['uid']);
        return $res;
    }
    
    public static function getManager($uid)
    {
        $base = Manager::where('id',$uid)->first(['id','name']);
        if(empty($base))
        {
            return null;
        }
        return $base->toArray();
    }
    
    public function isFillable($key)
    {
        return true;
    }
}
