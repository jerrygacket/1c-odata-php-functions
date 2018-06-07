<?php
$proj = $kanboard->getProjectByName('Движение заказа');
$columns = $kanboard->getColumns($proj['id']);
$columnid = getColumnId($columns,'Поступившие');
$swimline = $kanboard->getSwimlaneByName($proj['id'],'Стандартная дорожка');

// установка даты завершения задачи по дате отгрузки заказа
$stopdate = date('Y/m/d H:i', strtotime($ОдинЗаказ['ДатаОтгрузки']));

//вгоняем задачи в канбоард
//Все задачи серого цвета. 
//Заголовок: ЗаказКлиента_Number - ткНомерТехКарты - Контрагент_Description
//В коментарии указаны ЗаказКлиента_Комментарий и Подразделение_Description
//получаем ид менеджера из канборда, который создал заказ в 1с
$managerFIO = explode(' ',$МенеджерЗаказа_Description);
if (count($managerFIO)>1) {
	$manager = $kanboard->getUserByName($managerFIO[0].$managerFIO[1][0].$managerFIO[1][1].$managerFIO[2][0].$managerFIO[2][1]);
} else {
	$manager = $kanboard->getUserByName('Стажёр');
}

//собираем описание для задания в канбоард. там есть поддержка маркдауна
$newtaskdescription = '';
/****** проверка на пустые поля контактов контактных лиц  ***/
$raw = get1cData($client,$userName, $userAccessKey,'Catalog_КонтактныеЛицаПартнеров',
			'Description,КонтактнаяИнформация',
			'Owner_Key eq guid\''.$ОдинЗаказ['Партнер_Key'].'\''
				);
if (empty($raw)) { $newtaskdescription .= "НЕ ЗАПОЛНЕНЫ КОНТАКТНЫЕ ЛИЦА КОНТРАГЕНТА $Контрагент_Description\n\n";}

$newtaskdescription .= "$ЗаказКлиента_Комментарий\n\n";
$newtaskdescription .= "Подразделение: $Подразделение_Description\n\n";
$newtaskdescription .= "Менеджер: $МенеджерЗаказа_Description\n\n";
$newtaskdescription .= "---------------------\n\n";
$newtaskdescription .= "> Процессы:\n";
foreach ($Operations as $operation) {
        $newtaskdescription .= ">* $operation\n";
    }
$newtaskdescription .= "\n---------------------\n\n";
$newtaskdescription .= ">Расположение файлов:\n\ntipografiya/10-Типография/Текущие/";
$newtaskdescription .= NoSpice($Контрагент_Description).'/'.NoSpice($НомерЗаказа);
$newtaskdescription .= "\n\n";

echo $newtaskdescription.PHP_EOL;

foreach ($ТехнологичкиЗаказа as $Техкарта) {
    $newtasktitle=$НомерЗаказа.' - тк'.ltrim($Техкарта['Number'],'0').' - '.$Контрагент_Description;//собираем титл задачи для канборда

    //проверяем наличие задания с таким-же титлом. если нет, то создаем новую. если есть, то ничего не делаем
    $oldtask = $kanboard->searchTasks($proj['id'],$НомерЗаказа);
    //если не нашли в движении заказа (id=7), то ищем в производстве (id=5) и в дизайне (id=8)
    if ( empty($oldtask) ) { $oldtask = $kanboard->searchTasks(5,$ОдинЗаказ['Number']); }
    if ( empty($oldtask) ) { $oldtask = $kanboard->searchTasks(8,$ОдинЗаказ['Number']); }
    
    if ( empty($oldtask) ) { //!= null
        $newtaskid = $kanboard->createTask($newtasktitle,$proj['id'],"grey",$columnid,$manager['id'],$manager['id'],"$stopdate",$newtaskdescription,0,0,$swimline['id'],0,0,0,0,0,0,implode(',',$Operations));
        echo 'Результат добавления: '.$newtaskid.PHP_EOL;
        if ($newtaskid) {
            $tags = array ($Подразделение_Description);
            $newtaskid = $kanboard->setTaskTags($proj['id'],$newtaskid,$tags);
        } else {
            echo 'Задача '.$newtasktitle.' не добавлена'.PHP_EOL;
        }
    } else { // обновление задачи не работает в версии канборда 1.2.3
        //~ foreach ($oldtask as $task) {
            //~ $tsktitle = $task['title'];
            //~ $tskcolor = $task['color_id'];
            //~ $tskdate = $task['date_due'];
            //~ $updatetask = $kanboard->updateTask($task['id'],"$tsktitle","$tskcolor",$task['owner_id'],"$tskdate",$newtaskdescription);
            //~ print_r($task);
            //~ print_r($updatetask);
        //~ }
    }
}

