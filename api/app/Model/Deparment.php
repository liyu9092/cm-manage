<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Department extends Model {

	protected $table = 'departments';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['id', 'title', 'description'];

	public function positions(){
		return $this->hasMany('App\Position');
	}

}
