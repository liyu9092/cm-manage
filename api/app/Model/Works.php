<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
use DB;

class Works  extends Model {
    protected $table = 'hairstylist_works';
    protected $fillable = ['recId','stylistId','commoditiesImg','description','thumbImg','img','addTime'];
    public $timestamps = false;
   
}
