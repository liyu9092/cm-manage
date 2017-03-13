<?php

namespace App\Http\Middleware;

use Closure;
use Log;
use File;

class AfterMiddleware
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

    	$path = storage_path().'/logs/request.log';
	    $start = json_encode($response->getData()).PHP_EOL;
	  	File::append($path, $start);

        return $response;
    }
}