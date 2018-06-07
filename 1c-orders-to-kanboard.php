<?php
$installpath = '/home/user/proj';
require $installpath.'/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

include_once $installpath.'/php/00-conf.php';
include_once $installpath.'/php/50-functions.php';

//***************************************************************************
// mongo connect
$mongo = new MongoDB\Client("mongodb://$mongo_user:$mongo_pass@$mongo_serv");
// 1c connect
$client = new Client([
	'base_uri' => "$server1c/$base1c/odata/standard.odata/",
	'timeout'  => 600.0,
]);
// kanboard connect
$kanboard = new JsonRPC\Client($apiurl);
$kanboard->authentication('jsonrpc', $apikey);
//~ $kanboard = new JsonRPC\Client($testapiurl);
//~ $kanboard->authentication('jsonrpc', $testapikey);
//***************************************************************************

//ТехОперацииДизайн
$ТехОперацииДизайн = getMongoData($mongo,'ut','Catalog__ТехнологическиеОперации');
//подразделения
$Типография = getMongoData($mongo,'ut','Catalog_СтруктураПредприятия');
//менеджеры
$Пользователи = getMongoData($mongo,'ut','Catalog_Пользователи');
//контрагенты
$Контрагенты = getMongoData($mongo,'ut','Catalog_Контрагенты');
//Организации
$Организации = getMongoData($mongo,'ut','Catalog_Организации');
//***************************************************

$begining = date('Y-m-d\TH:i:s',mktime(0, 0, 0, date("m"), date("d"), date("Y"))); //сегодня полночь
$ending = date('Y-m-d\TH:i:s',mktime(0, 0, 0, date("m"), date("d")+1, date("Y"))); //завтра полночь
echo date('Y-m-d\TH:i:s').' Обрабатываем только заказы для типографии'.PHP_EOL;
$ВсеЗаказыЗаСегодня = get1cData($client,$userName, $userAccessKey,'Document_ЗаказКлиента',
								'Ref_Key,Number,Date,ДатаОтгрузки,Партнер_Key,Контрагент_Key,Подразделение_Key,Менеджер_Key,_РасчетыЗаказов,Комментарий',
								'Date gt datetime\''.$begining.'\' and Date lt datetime\''.$ending.'\''
								);
//***************************************************
//print_r($ВсеЗаказыЗаСегодня);

foreach ($ВсеЗаказыЗаСегодня as $ОдинЗаказ) {
	//только заказы для типографии
    if (!array_key_exists($ОдинЗаказ['Подразделение_Key'],$Типография)) { continue; }
	//~ //ищем тех операции в расчетах заказа
	$Operations = array();
	foreach ($ОдинЗаказ['_РасчетыЗаказов'] as $РасчетЗаказа) {//Всего расчетов в заказе:
		$raw = get1cData($client,$userName, $userAccessKey,'Document__РасчетЗаказаКлиента_ПреПресс',
				'Ref_Key,ТехнологическаяОперация_Key',
				'(Ref_Key eq guid\''.$РасчетЗаказа['РасчетЗаказаКлиента_Key'].'\')'
				);
        if (empty($raw)) { continue; }
		foreach ($raw as $ОднаОперация) {
			//Есть ли Операции Дизайнеров в расчетах
			if (!empty(array_key_exists($ОднаОперация['ТехнологическаяОперация_Key'],$ТехОперацииДизайн))) {
				$Operations[] = $ТехОперацииДизайн[$ОднаОперация['ТехнологическаяОперация_Key']];
			};
		}
	}

	//если нет операций для дизайнеров в Л.Ю.Б.Ы.Х расчетах заказа, то следующий
	if (empty($Operations)) { continue; }
	$Operations = array_unique($Operations);
    
	if (!array_key_exists($ОдинЗаказ['Контрагент_Key'],$Контрагенты)) { continue; }
    if (!array_key_exists($ОдинЗаказ['Менеджер_Key'],$Пользователи)) { continue; }
    
    //вычищаем строки от запятых, слешей звездочек и т.п.
    $Контрагент_Description = ClearString($Контрагенты[$ОдинЗаказ['Контрагент_Key']]);
	$МенеджерЗаказа_Description = ClearString($Пользователи[$ОдинЗаказ['Менеджер_Key']]);
	$Подразделение_Description = ClearString($Типография[$ОдинЗаказ['Подразделение_Key']]);
	$ЗаказКлиента_Комментарий = ClearString($ОдинЗаказ['Комментарий']);
	$НомерЗаказа = ClearString($ОдинЗаказ['Number']);
    
    $ТехнологичкиЗаказа = get1cData($client,$userName, $userAccessKey,'Document__ТехнологическаяКарта',
								'Number',
								'ЗаказКлиента_Key eq guid\''.$ОдинЗаказ['Ref_Key'].'\''
								);
    
    echo date('Y-m-d\TH:i:s')." Добавляем заказ $НомерЗаказа в канборд. Менеджер заказа: ".$МенеджерЗаказа_Description.PHP_EOL;
    include $installpath.'/add-single-task.php';
    //~ print_r($ОдинЗаказ);
    
    echo date('Y-m-d\TH:i:s')." Создаем дирректории для файлов заказа $НомерЗаказа".PHP_EOL;
    $ПапкаЗаказа = NoSpice($Контрагент_Description).'/'.NoSpice($НомерЗаказа);
    $output = shell_exec("ssh -T -q user@$SambaServer << EOF".PHP_EOL
						."mkdir -p $bigdatapath/$ПапкаЗаказа/Исходные".PHP_EOL
						."mkdir -p $bigdatapath/$ПапкаЗаказа/В\ Печать".PHP_EOL
						."EOF"
						);
	if (trim($output)!='') {
		echo date('Y-m-d\TH:i:s').' '.trim($output).PHP_EOL;
		echo " ---------------------------------------".PHP_EOL;
	}
}
echo date('Y-m-d\TH:i:s').' Закончили'.PHP_EOL;
echo "***************************************************".PHP_EOL;
