<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Position extends Model {

	protected $table = 'positions';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['id', 'department_id', 'title', 'description'];

	public function department(){
		return $this->belongsTo('App\Department');
	}

}
