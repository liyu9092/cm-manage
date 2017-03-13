<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model {

    protected $table = 'banner';
    protected $fillable = ['banner_id', 'type', 'name', 'image', 'behavior', 'url', 'created_at', 'updated_at','salonName','sort','price','priceOri','introduce','bargainPrice'];
    public $timestamps = false;
    protected $primaryKey = 'banner_id';

}
