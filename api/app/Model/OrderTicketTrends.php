<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderTicketTrends extends Model
{
    protected $table = 'order_ticket_trends';
    protected $primaryKey = 'order_ticket_trends_id';
    public $timestamps = false;
    
    protected $fillable = ['order_ticket_trends_id', 'ordersn','ticketno','add_time','status','remark'];
}
