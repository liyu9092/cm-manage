<?php
namespace App;
use Illuminate\Database\Eloquent\Model;
use DB;

class FileImage  extends  Model{
    protected $table = 'file_image';
    protected $fillable = ['id','url','width','height','file_name'];
    public $timestamps = false;
}
