<?php
/**
 * 订单表
 * @author zhunian
 */
namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends  Model
{
    protected $table = 'order';
    protected $primaryKey = 'orderid';
    public $timestamps = false;

    protected $fillable = ['commission'];

    public function salonInfo(){
        return $this->belongsTo('App\SalonInfo','salonid','salonid');
    }

    public function salon(){
        return $this->belongsTo('App\Salon','salonid','salonid');
    } 

    public function user(){
        return $this->belongsTo(\App\User::class);
    } 
    
    public function fundflow(){
        return $this->hasMany(\App\Fundflow::class,'record_no','ordersn');
    }
}

?>