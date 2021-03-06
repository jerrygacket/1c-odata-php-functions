<?php
function get1cData($client,$userName, $userAccessKey,$fromtable,$select,$where) {
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
	//$body = $response->getBody()->getContents();
	$body = $response->getBody();
	$jsonResponse=json_decode($body,true);
	return $jsonResponse['value'];
}

function getMongoData($mongodb,$dbname,$collectionname,$key,$value) {
    $result = array();
    $collection = $mongodb->$dbname->$collectionname;
    $search = $collection->find([])->toArray();
    foreach ($search as $tmp) {
        $result[$tmp[$key]] = $tmp[$value] ?? 'не указан';
    }
    return $result;
}

function getAllMongoData($mongodb,$dbname,$collectionname) {
    $result = array();
    $collection = $mongodb->$dbname->$collectionname;
    $result = $collection->find([])->toArray();
    return $result;
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
	$string_to_clear = trim(str_replace(array(" ","."), "_", $string_to_clear)); // заменяем пробелы и точки на подчеркивание
    $string_to_clear = trim(str_replace(array("-"), "_", $string_to_clear)); // заменяем тире на подчеркивание
    return $string_to_clear;
}
