<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VoucherTrend extends Model
{
    protected $table = 'voucher_trend';
    protected $primaryKey = 'vBindId';
    public $timestamps = false;
    
    public function voucher()
    {
        return $this->belongsTo(\App\Voucher::class,'vId','vId');
    }
}
