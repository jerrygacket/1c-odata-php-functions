<?php
// запрос данных из 1с по интерфейсу одата в формате json
function get1cData($client,$userName, $userAccessKey,$fromtable,$select,$where) {
	$jsonformat = '$format=json;odata=nometadata';
	$requesturl = $fromtable.'?';
	if (!empty($select)) {
		$requesturl .= '$select='.$select.'&';
	}
	if (!empty($where)) {
		$requesturl .= '$filter='.$where.'&';
	}
	// expand у меня не заработал.
	//if (!empty($expand)) {
	//	$requesturl .= $expand.'&';
	//}
	$requesturl .= $jsonformat;
	$response = $client->request('GET', $requesturl, [
		'auth' => [$userName, $userAccessKey]
	]);
	$body = $response->getBody();
	$jsonResponse=json_decode($body,true);
	return $jsonResponse['value'];
}

// функции для работы с базой монго
// загрузить документ в монго базу
function loadToMongo($mongodb,$dbname,$collectionname, $data) {
	$collection = $mongodb->$dbname->$collectionname; //берем коллекцию $collectionname
	foreach ($data as $doc) {
		$result = $collection->find(['Ref_Key' => $doc['Ref_Key']])->toArray();
		if (!empty($result)) {
				$collection->updateOne( ['Ref_Key' => $doc['Ref_Key']], [ '$set' => $doc ] );
		} else {
			$insertOneResult = $collection->insertOne( $doc ); //если не находим, то добавляем один
		}
	}
}
// получить всю коллекцию в массиве вида ('ref_key_из_1с' => 'description')
// используется для получения списков менеджеров, контрагентов, подразделений
function getMongoData($mongodb,$dbname,$collectionname) {
    $result = array();
    $collection = $mongodb->$dbname->$collectionname; //берем коллекцию $collectionname
    $search = $collection->find([])->toArray();
    foreach ($search as $tmp) {
        $result[$tmp['Ref_Key']] = $tmp['Description'];
    }
    return $result;
}

//поиск контактных лиц по телефону в базе монго
function searchInMongo($mongodb,$dbname, $phone) {
	$outstr = array();
	$collection = $mongodb->$dbname->Catalog_КонтактныеЛицаПартнеров;
	$result = $collection->find(['КонтактнаяИнформация' => ['$elemMatch' => ['НомерТелефона' => $phone] ]])->toArray();
	if (empty($result)) {
		$collection = $mongodb->$dbname->Catalog_Контрагенты;
		$contragents = $collection->find(['КонтактнаяИнформация' => ['$elemMatch' => ['НомерТелефона' => $phone] ]])->toArray();
		if (empty($contragents)) {
			return 'Ничего не найдено';
		}
		$collection = $mongodb->$dbname->Catalog_КонтактныеЛицаПартнеров;
		foreach ($contragents as $contragent) {
			$outstr['Контрагент'] = $contragent['Description'];
			$contacts = $collection->find(['Owner_Key' => $contragent['Ref_Key']])->toArray();
			foreach ($contacts as $contact) {
				$outstr['Контактное лицо'][] = $contact['Description'];
			}
		}
	} else {
		$collection = $mongodb->$dbname->Catalog_Контрагенты;
		foreach ($result as $contact) {
			$outstr['Контактное лицо'] = $contact['Description'];
			$contragents = $collection->find(['Ref_Key' => $contact['Owner_Key']])->toArray();
			foreach ($contragents as $contragent) {
				$outstr['Контрагент'][] = $contragent['Description'];
			}
		}
		
	}
	$outstr['Телефон'] = $phone;
	return $outstr;
}

// для канборда
// получить id колонки по имени. В апи канборда такой функции нет
function getColumnId ($columns,$colimntitle) {
    $result = -1;
    foreach ($columns as $column) {
        if ( $colimntitle == $column['title'] ) {
            $result = $column['id'];
        }
    }
    return $result;
}

// остальные функции
// замена "пробел" на "\пробел". нужно при создании папок на сервере (debian).
function SpiceEsc($string_to_clear) {
	$string_to_clear = trim(str_replace(array(" "), "\ ", $string_to_clear)); //удаляем кавычки
    return $string_to_clear;
}

// очищаем строку от запрещенных и нежелательных символов.
// в самбе есть глюк при отображении папок вида "Иванов И. И." при монтировании в Thunar видно что-то типа: _67BF8~D
// нужно при создании папок на сервере (debian).
function ClearString($string_to_clear) {
	$string_to_clear = trim(str_replace(array("\r\n", "\r", "\n", "\t"), " ", $string_to_clear)); //удаляем переносы строки, табуляцию, 
	$string_to_clear = trim(str_replace(array("\""), "", $string_to_clear)); //удаляем кавычки
	$string_to_clear = trim(str_replace(array("(",")","[","]","{","}","."), "_", $string_to_clear)); //заменяем скобки, точки на подчеркивание
    $string_to_clear = preg_replace('/ {2,}/',' ',$string_to_clear); //удаляем лишние пробелы
    $string_to_clear = preg_replace('/;/','/',$string_to_clear); //удаляем тчкзпт, меняем на палочку
    return $string_to_clear;
}

// просто заменяем пробелы и минусы на нижнее подчеркивание.
// чтобы папки создавались однообразно
function NoSpice($string_to_clear) {
	$string_to_clear = trim(str_replace(array(" ","."), "_", $string_to_clear)); //удаляем пробелы
	$string_to_clear = trim(str_replace(array("-"), "_", $string_to_clear)); //удаляем минусы
    return $string_to_clear;
}

// приводим номер телефона к виду 89205634455
// нужно для определения номера по контрагентам из 1с (asterisk)
function normalizator($phone) {
	$number1c = preg_replace("#[^0-9]#","",$phone); //Все не цифры - нах
	switch ( 1 ) {
	//1 XX-XX-XX 6
		case ( preg_match("/^([0-9]{6})$/",$number1c) ):
			$number1c = '84932'.$number1c;
			break;
	//2 7 (XXX) XXX-XX-XX 11
		case ( preg_match("/^[7]{1}([0-9]{10})$/",$number1c) ):
			$number1c = '8'.substr($number1c, 1);
			break;
	//3 +7 (XXX) XXX-XX-XX 12
		case ( preg_match("/^[+]{1}[7]{1}([0-9]{10})$/",$number1c) ):
			$number1c = '8'.substr($number1c, 2);
			break;
	//4 8 (XXX) XXX-XX-XX 11
		case ( preg_match("/^[8]{1}([0-9]{10})$/",$number1c) ):
			$number1c = $number1c;
			break;
	//5 (XXX) XXX-XX-XX 10
		case ( preg_match("/^([0-9]{10})$/",$number1c) ):
			$number1c = '8'.$number1c;
			break;
		default:
			$number1c = $number1c;
	}
	return $number1c;
}
