<?php
namespace Choumei;
use Illuminate\Support\Facades\Facade;

class ChoumeiFacade extends Facade{

	protected static function getFacadeAccessor(){
		return 'Choumei';
	}
}

?>