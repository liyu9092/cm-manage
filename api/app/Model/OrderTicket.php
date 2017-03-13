<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderTicket extends Model
{
    protected $table = 'order_ticket';
    protected $primaryKey = 'order_ticket_id';
    public $timestamps = false;
    
    public function salon(){
        return $this->belongsTo(\App\Salon::class,'salonid','salonid');
    }
    
    public function user(){
        return $this->belongsTo(\App\User::class);
    }
    
    public function fundflow(){
        return $this->hasMany(\App\Fundflow::class,'record_no','ordersn');
    }
    
    public function voucher()
    {
        return $this->belongsTo(\App\Voucher::class,'ordersn','vOrderSn');
    }
    
    public function paymentLog()
    {
        return $this->belongsTo(\App\PaymentLog::class,'ordersn','ordersn');
    }
}
