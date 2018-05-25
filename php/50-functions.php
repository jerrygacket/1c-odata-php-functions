<?php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

function get1cData($fromtable,$select,$where) {
	$client = new Client([
		// Base URI is used with relative requests
		'base_uri' => 'http://10.1.1.10/basename/odata/standard.odata/',
		// You can set any number of default request options.
		'timeout'  => 600.0,
	]);
	$userAccessKey = 'password';
	//username of the user who is to logged in. 
	$userName="username";
	//***************************************************
	$jsonformat = '$format=json;odata=nometadata';
	$requesturl = $fromtable.'?';
	if (!empty($select)) {
		$requesturl .= '$select='.$select.'&';
	}
	if (!empty($where)) {
		$requesturl .= '$filter='.$where.'&';
	}
	if (!empty($expand)) {
		$requesturl .= $expand.'&';
	}
	$requesturl .= $jsonformat;
	//print_r($requesturl);
	$response = $client->request('GET', $requesturl, [
		'auth' => [$userName, $userAccessKey]
	]);
	$body = $response->getBody();
	$jsonResponse=json_decode($body,true);
	return $jsonResponse['value'];
}

function ClearString($string_to_clear) {
	$string_to_clear = trim(str_replace(array("\r\n", "\r", "\n", "\t"), " ", $string_to_clear)); //удаляем переносы строки, табуляцию, 
	$string_to_clear = trim(str_replace(array("\""), "", $string_to_clear)); //удаляем кавычки
	$string_to_clear = trim(str_replace(array("(",")","[","]","{","}","."), "_", $string_to_clear)); //заменяем скобки, точки на подчеркивание
    $string_to_clear = preg_replace('/ {2,}/',' ',$string_to_clear); //удаляем лишние пробелы
    $string_to_clear = preg_replace('/;/','/',$string_to_clear); //удаляем тчкзпт, меняем на палочку
    return $string_to_clear;
}

function NoSpice($string_to_clear) {
	$string_to_clear = trim(str_replace(array(" ","."), "_", $string_to_clear)); //удаляем кавычки
    $string_to_clear = trim(str_replace(array("-"), "_", $string_to_clear)); //удаляем кавычки
    return $string_to_clear;
}
