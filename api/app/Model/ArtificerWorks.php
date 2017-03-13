<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class ArtificerWorks extends  Model
{
    protected $table = 'artificer_works';
    protected $primaryKey = 'id';
    public $timestamps = false;
    
}