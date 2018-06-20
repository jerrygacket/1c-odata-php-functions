<?php
//***************************************************
// mongo login
$mongo_user = 'mongouser';
$mongo_pass = 'mongopassword';
$mongo_serv = 'mongoserver:port';
//***************************************************
// 1c login
$userAccessKey = '1cpassword';
$userName="1cuser";
$server1c = '1cserver';
$base1c = '1cbasename';
//***************************************************
// kanboard login
$apikey = '111111111111111111111111111111111abcdf';
$apiurl = 'http://kanboard.server/jsonrpc.php';
//***************************************************
$htmlfile = $installpath.'/html/index.html';
$loadfile = $installpath.'/html/load.html';

// список наших контрагентов. заказы на них не идут в зарплату
$nouse_Контрагенты = array( '969a3140-0a1f-11e7-6983-001e62748e39' => 'ИП Иванов Иван Иванович',
                            '4986ea16-2436-11e7-208c-001e62748e39' => 'Рога и копыта ООО',
                            '58353082-c18b-11e4-7299-002590d86530' => 'Сотрудники'
                            );
