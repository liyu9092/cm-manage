<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Level extends Model {

	protected $table = 'user_level';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['id', 'level', 'growth','addtime'];

	public $timestamps = false;

}
