<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_item';
    protected $primaryKey = 'order_item_id';
    public $timestamps = false;
}
