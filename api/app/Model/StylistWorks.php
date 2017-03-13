<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use DB;

class StylistWorks extends Model{
    protected $table = 'stylist_works';
    protected $fillable = ['id','stylist_id','image_ids','description','add_time'];
    public $timestamps = false;
}
