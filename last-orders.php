<?php
/***********************************************************
 *
 * 
 **********************************************************/
$installpath = '/home/user/proj/03-1c-report';
require $installpath.'/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

include_once $installpath.'/php/00-conf.php';
include_once $installpath.'/php/50-functions.php';
include_once $installpath.'/php/98-class.php';

//***************************************************************************
// mongo connect
$mongo = new MongoDB\Client("mongodb://$mongo_user:$mongo_pass@$mongo_serv");
// 1c connect
$client = new Client([
	'base_uri' => "$server1c/$base1c/odata/standard.odata/",
	'timeout'  => 600.0,
]);
//***************************************************************************
$monthes = [
  'январь',
  'февраль',
  'март',
  'апрель',
  'май',
  'июнь',
  'июль',
  'август',
  'сентябрь',
  'октябрь',
  'ноябрь',
  'декабрь'
];


// В выгрузке несколько тысяч закзов и искать в монго дольше чем выгрузить все и искать в массивах.
//источник клиента
$КаналыРекламныхВоздействий = getMongoData($mongo,'ut','ChartOfCharacteristicTypes_КаналыРекламныхВоздействий','Ref_Key','Description');
//источник клиента
$КаналПервичногоИнтереса = getMongoData($mongo,'ut','InformationRegister_ИсточникиПервичногоИнтереса','Партнер_Key','КаналПервичногоИнтереса_Key');
//менеджеры
$Менеджеры = getMongoData($mongo,'ut','Catalog_Пользователи','Ref_Key','Description');
//контрагенты
$Контрагенты = getMongoData($mongo,'ut','Catalog_Контрагенты','Ref_Key','Description');
//контрагенты партнер_кей
$Контрагенты_партнеры = getMongoData($mongo,'ut','Catalog_Контрагенты','Ref_Key','Партнер_Key');
//Организации
$Организации = getMongoData($mongo,'ut','Catalog_Организации','Ref_Key','Description');
//Партнеры
$raw = get1cData($client,$userName, $userAccessKey,'Catalog_Партнеры',
			'Ref_Key,Description,ОсновнойМенеджер_Key',
			''
			);
foreach($raw as $tmp) {
	$Партнеры[$tmp['Ref_Key']]['Description'] = $tmp['Description'];
	$Партнеры[$tmp['Ref_Key']]['ОсновнойМенеджер_Key'] = $tmp['ОсновнойМенеджер_Key'];
}

$Заказы = array(); // массив для сбора инфы по заказам
//******************************************************************************
// заполняем данные по реализациям: даты, суммы.
$raw = get1cData($client,$userName, $userAccessKey,'Document_РеализацияТоваровУслуг',
            'ЗаказКлиента,Date,СуммаДокумента',
            'ЗаказКлиента_Type eq \'StandardODATA.Document_ЗаказКлиента\''
				);
                
foreach ($raw as $Реализация) {
    if ($Реализация['ЗаказКлиента'] == '00000000-0000-0000-0000-000000000000') { continue; }
    
    $Заказ_Key = trim($Реализация['ЗаказКлиента']);
    
    if (!array_key_exists($Заказ_Key,$Заказы)) { 
        $Заказы[$Заказ_Key] = new order();
    }
    
    $Заказы[$Заказ_Key]->newDoc('Реализация',$Реализация['Date'], $Реализация['СуммаДокумента']);
}

//******************************************************************************
$Заказы_raw = get1cData($client,$userName, $userAccessKey,'Document_ЗаказКлиента',
								'Ref_Key,Number,Товары,СуммаДокумента,Менеджер_Key,Контрагент_Key,Организация_Key,Партнер_Key,Date,Комментарий',
                                'Контрагент_Key ne guid\'55555555-6666-7777-8888-454433453454\' and '.  //Исключаем заказы для сотрудников и т.п.,
                                'Контрагент_Key ne guid\'33333333-4444-2222-6666-342233343223\''
								);
//******************************************************************************
$ЗаказыКонтрагента = array();
$ВсеЗаказы = array();
$TimeLine = array();
foreach ($Заказы_raw as $Заказ) {
    $Заказ_Key = trim($Заказ['Ref_Key']);
    $Контрагент_Key = $Заказ['Контрагент_Key'];
    $ДатаЗаказа = strtotime($Заказ['Date']);
    if (!array_key_exists($Заказ_Key,$Заказы)) { continue; }
    
    if (!array_key_exists($Контрагент_Key,$TimeLine)) {
        $TimeLine[$Контрагент_Key] = 0;
    }
    if ($TimeLine[$Контрагент_Key] < $ДатаЗаказа) {
        $TimeLine[$Контрагент_Key] = $ДатаЗаказа;
    }
    
    if (!array_key_exists($Контрагент_Key,$ЗаказыКонтрагента)) {
        $ЗаказыКонтрагента[$Контрагент_Key] = array();
    }
    
    $ЗаказыКонтрагента[$Контрагент_Key][$Заказ_Key] = $ДатаЗаказа;
    
    $ВсеЗаказы[$Заказ_Key] = $Заказ;
}
//******************************************************************************
$currentmonth = 0;
$currentdate = strtotime(date('Y-m-d\TH:i:s',mktime(0, 0, 0, date("m")-$currentmonth, date("1"), date("Y"))));
echo '<!DOCTYPE html><html>'.PHP_EOL.
		'<head>'.PHP_EOL.
		'<title>Последние заказы</title>'.PHP_EOL.
		'<meta charset="utf-8">'.PHP_EOL.
        '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'.PHP_EOL.
        '<!-- Bootstrap CSS --><link rel="stylesheet" href="css/bootstrap.min.css">'.PHP_EOL.
		'</head>'.PHP_EOL.
		'<body>'.PHP_EOL;
echo '<h1>Последние заказы</h1>'.PHP_EOL.
    '<div class="container-fluid">
      <div class="row">
        <div class="col-md-2">
        
        <nav id="navbar-example3" data-spy="affix" class="navbar navbar-light bg-light">
          <nav class="nav nav-pills flex-column">';
            
            for ($i=2018;$i>=2015;$i--) {
                for ($l=count($monthes)-1;$l>=0;$l--) {
                    echo '<a class="nav-link" href="#'.$monthes[$l].'_'.$i.'">'.$monthes[$l].' '.$i.'</a>';
                }
            }
            
echo '     </nav>
        </nav>
        
        </div>
        <div class="col-md-10">'.
        '<div data-spy="scroll" data-target="#navbar-example3" data-offset="0">'.
		'<table class="table table-hover">'.PHP_EOL;
echo '<th>Дата</th>'.
            '<th style="width:  36%">Клиент</th>'.
            '<th>Заказы</th>'.
            '<th>Всего заказов / Сумма</th>'.PHP_EOL;
$accounts = 0;
$allaccs = 0;
$output = '';
arsort($TimeLine);
foreach ($TimeLine as $key => $val) {
    if ($val >= $currentdate) {
        $accounts++;
        arsort($ЗаказыКонтрагента[$key]);
        $output .= '<td>'.$Контрагенты[$key].'<br>';
        $ОсновнойМенеджер_Key = $Партнеры[$Контрагенты_партнеры[$key]]['ОсновнойМенеджер_Key'];
        $output .= Clearstring($Менеджеры[$ОсновнойМенеджер_Key] ?? 'Нет').'<br>'.PHP_EOL;
        $collection = $mongo->ut->Catalog_Контрагенты;
        $Контакты = $collection->find(['Ref_Key' => $key],['projection' => ['КонтактнаяИнформация' => 1]])->toArray();
        
        if( !empty($Контакты)) {
            $output .=  '<p>
          <a class="btn btn-link" data-toggle="collapse" href="#'.$key.'_contact" role="button" aria-expanded="false" aria-controls="'.$key.'_contact">
            Контактная информация ('.count($Контакты[0]['КонтактнаяИнформация']).')
          </a>
        </p>
        <div class="collapse" id="'.$key.'_contact">
          <div class="card card-body">';
            foreach ($Контакты[0]['КонтактнаяИнформация'] as $Контакт) {
                $output .= $Контакт['Тип'].': '.$Контакт['Представление'].'<br>'.PHP_EOL;
            }
            $output .=  '</div></div>';
        } else {$output .=  'Нет контактов';}
        
        $output .= '</td><td>';
        $output .=  '<p>
          <a class="btn btn-link" data-toggle="collapse" href="#'.$key.'" role="button" aria-expanded="false" aria-controls="'.$key.'">
            Последний заказ '.date('d.m.Y',$val).'
          </a>
        </p>
        <div class="collapse" id="'.$key.'">
          <div class="card card-body">';
        $output .= '<table class="table table-hover">';
        $output .= '<th>Дата</th>'.
            '<th>Номер</th>'.
            '<th>Сумма</th>'.
            '<th>Менеджер</th>'.
            '<th style="width:  16%">Коммент</th>'.
            '<th>Товары</th>'.PHP_EOL;
        $ordersumm = 0;
        foreach ($ЗаказыКонтрагента[$key] as $Заказ_Key => $ДатаЗаказа) {
            $ordersumm += $ВсеЗаказы[$Заказ_Key]['СуммаДокумента'];
            $output .= '<tr>';
            $tmpp = strtotime($ВсеЗаказы[$Заказ_Key]['Date']);
            $output .= '<td>'.date('d.m.Y',$tmpp).'</td>';
            $output .= '<td>'.$ВсеЗаказы[$Заказ_Key]['Number'].'</td>';
            $output .= '<td>'.$ВсеЗаказы[$Заказ_Key]['СуммаДокумента'].' рублей'.'</td>';
            $output .= '<td>'.$Менеджеры[$ВсеЗаказы[$Заказ_Key]['Менеджер_Key']].'</td>';
            $output .= '<td>'.$ВсеЗаказы[$Заказ_Key]['Комментарий'].'</td>';
            $output .= '<td>';
            foreach ($ВсеЗаказы[$Заказ_Key]['Товары'] as $goods) {
                $collection = $mongo->ut->Catalog_Номенклатура;
                $Материал = $collection->find(['Ref_Key' => $goods['Номенклатура_Key']])->toArray();
                if( !empty($Материал)) {
                    foreach ($Материал as $value) {
                        $output .= $value['Description'].'<br>'.PHP_EOL;
                    }
                }
            }
            $output .= '</td></tr>';
        }
        $output .= '</table>';
        $output .= '</td><td>';
        $output .= count($ЗаказыКонтрагента[$key]).' / '.$ordersumm;
        $output .=  '</div></div></td></tr>'.PHP_EOL.'<tr>';
    } else {
        $month = date('n',$currentdate)-1;
        echo '<tr><td rowspan="'.$accounts.'"><h4 id="'.$monthes[$month].'_'.date('Y',$currentdate).'">'.$monthes[$month].' '.date('Y',$currentdate).'</h4></td>'.$output.'</tr>';
        $output = '';
        $currentmonth++;
        $currentdate = strtotime(date('Y-m-d\TH:i:s',mktime(0, 0, 0, date("m")-$currentmonth, date("1"), date("Y"))));
        $allaccs += $accounts;
        $accounts = 0;
    }
}
echo '</table>'.PHP_EOL;
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo 'Всего клиентов: '.$allaccs.PHP_EOL;
echo '<script src="js/bootstrap.bundle.js"></script>'.PHP_EOL.
'<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>'.
            '</body></html>'.PHP_EOL;
