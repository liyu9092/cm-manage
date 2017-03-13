<?php
namespace Choumei;
use Response;

class Choumei{
	public function success(){
		return Response::json(['love','you']);
	}
}


?>