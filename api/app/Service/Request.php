<?php namespace Service;

class Request
{
	public static function post($url, $data)
	{
		$data = http_build_query($data);
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $response = curl_exec($ch);
	    curl_close($ch);
	    return $response;
	} 


	public static function get($url,$data)
	{
		$data = http_build_query($data);
		$url.='?'.$data;
		$response = file_get_contents($url);  
		return $response; 
	}
}


