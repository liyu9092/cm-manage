<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use DB;
Use PDO;

class BookingOrderItem extends Model
{
    protected $table = 'booking_order_item';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    
    public function isFillable($key)
    {
        return true;
    }
}
