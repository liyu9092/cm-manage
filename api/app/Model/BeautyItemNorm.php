<?php 
namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\AbstractPaginator;
class BeautyItemNorm extends Model {

	protected $table = 'beauty_item_norm';
	
	public $timestamps = false;
	
	protected $primaryKey = 'item_id';
	
	
	
}

