<?php namespace App\Http\Middleware;

use Closure;
use Route;
use App\Exceptions\ApiException;
use App\Exceptions\ERROR;
use File;
use Event;
use DateTime;
use Log;

class BeforeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {

    	$path = storage_path().'/logs/query.log';
    	$time_now = (new DateTime)->format('Y-m-d H:i:s');
	    $start = PHP_EOL.'=| '.$time_now.' | '.$request->method().' | '.$request->path().' |='.PHP_EOL;
	  	File::append($path, $start);

	  	Event::listen('illuminate.query', function($sql, $bindings, $time) use($path) {
		    $sql = str_replace(array('%', '?'), array('%%', '%s'), $sql);
		    $sql = vsprintf($sql, $bindings);
		    $log = $time.'ms'."\t|\t".$sql.PHP_EOL;
		  	File::append($path, $log);
		});


    	$path = storage_path().'/logs/request.log';
        $start = PHP_EOL.'=| '.$time_now.' | '.$request->method().' | '.$request->path().' | '.json_encode($request->input()).' |='.PHP_EOL;
	  	File::append($path, $start);

        return $next($request);
    }
}


