<?php
//***************************************************
//Список тех операций
$raw = get1cData('Catalog__ТехнологическиеОперации',
			'Ref_Key,Description',
			'(Description eq \'Дизайн\') or (Description eq \'Верстка\') or (Description eq \'Фотовывод\')'
			);
$ТехОперацииДизайн = array();
foreach($raw as $tmp) {
	$ТехОперацииДизайн[$tmp['Ref_Key']] = $tmp['Description'];
}
//***************************************************
//***************************************************
/*Список подразделений
	Подразделение1
  Подразделение2
*/
$raw = get1cData('Catalog_СтруктураПредприятия',
					'Ref_Key,Description',
					'(Description eq \'Подразделение1\') or (Description eq \'Подразделение2\')'
					);
$Типография = array();
foreach($raw as $tmp) {
	$Типография[$tmp['Ref_Key']] = $tmp['Description'];
}

//менеджеры
$raw = get1cData('Catalog_Пользователи',
			'Ref_Key,Description',
			''
			);
foreach($raw as $tmp) {
	$Менеджеры[$tmp['Ref_Key']] = $tmp['Description'];
}

//контрагенты
$raw = get1cData('Catalog_Контрагенты',
			'Ref_Key,Description',
			''
			);
foreach($raw as $tmp) {
	$Контрагенты[$tmp['Ref_Key']] = $tmp['Description'];
}

//Партнеры
$raw = get1cData('Catalog_Партнеры',
			'Ref_Key,Description,ОсновнойМенеджер_Key',
			''
			);
foreach($raw as $tmp) {
	$Партнеры[$tmp['Ref_Key']]['Description'] = $tmp['Description'];
	$Партнеры[$tmp['Ref_Key']]['ОсновнойМенеджер_Key'] = $tmp['ОсновнойМенеджер_Key'];
}

//Организации
$raw = get1cData('Catalog_Организации',
			'Ref_Key,Description',
			''
			);
foreach($raw as $tmp) {
	$Организации[$tmp['Ref_Key']] = $tmp['Description'];
}

