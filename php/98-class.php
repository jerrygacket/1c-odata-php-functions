<?php

class order
{
    private $DocTypes = array('Задолженность','Платежка','Реализация','Чек');
    private $Даты = array();
    private $Суммы = array();
    
    public $Number = '';
    public $СуммаДокумента = 0;
    public $СуммаОплаты = 0;
    public $СуммаРеализации = 0;
    public $ДатаЗаказа = '';
    public $МенеджерЗаказа = '';
    public $МенеджерКлиента = '';
    public $Контрагент = '';
    public $Партнер = '';
    public $Организация = '';
    public $КаналПервичногоИнтереса = 'не указан';
    public $ПоследняяДата = 0;
    
    // заполняем даты и суммы отгрузог, платежек, чеков и взаимозачетов
    function newDoc($DocType, $Date, $Money) {
        if (!in_array($DocType,$this->DocTypes)) {
            return false;
        }
        if (!array_key_exists($DocType,$this->Даты)) {
            $this->Даты[$DocType] = array();
        }
        $this->Даты[$DocType][] = strtotime(trim($Date));
        
        if (!array_key_exists($DocType,$this->Суммы)) {
            $this->Суммы[$DocType] = array();
        }
        $this->Суммы[$DocType][] = trim($Money);
        return true;
    }
    
    function Собрать($DocType) {
        if (!array_key_exists($DocType,$this->Даты)) {
            return '';
        }
        $result = '<ol>';
        foreach ($this->Даты[$DocType] as $key => $Дата) {
            $result .= '<li>'.date('d.m.Y',$Дата).'&nbsp;';
            $result .= $this->Суммы[$DocType][$key].'</li>';
        }
        $result .= '</ol>';
        return $result;
    }
    
    function ОплаченПолностью() {
        $result = false;
        $this->СуммаОплаты = array_sum( $this->Суммы['Платежка'] ?? array(0) );
        $this->СуммаОплаты += array_sum( $this->Суммы['Чек'] ?? array(0) );
        $this->СуммаОплаты += array_sum( $this->Суммы['Задолженность'] ?? array(0) );
        // далее нужен ceil, потому что иногда сумма оплаты почемуто
        // на 3.43232342343653676Е-12 меньше чем сумма документа
        $result = (ceil($this->СуммаОплаты*100)/100) >= (ceil($this->СуммаДокумента*100)/100);
        //~ if ($this->Number == '0000'){file_put_contents('debug.log',$result);}
        return $result;
    }
    
    function РеализованПолностью() {
        $result = false;
        $this->СуммаРеализации = array_sum( $this->Суммы['Реализация'] ?? array(0) );
        // далее нужен ceil, потому что иногда сумма почемуто
        // на 3.43232342343653676Е-12 меньше чем сумма документа
        $result = (ceil($this->СуммаРеализации*100)/100) >= (ceil($this->СуммаДокумента*100)/100);
        //~ if ($this->Number == '0000'){file_put_contents('debug.log',$result);}
        return $result;
    }
    
    function ЗакрытВЭтотПериод($begin, $end) {
        $result = false;
        $ПоследняяДатаЗадолженность = max( $this->Даты['Задолженность'] ?? array(0) );
        $ПоследняяДатаПлатежка = max( $this->Даты['Платежка'] ?? array(0) );
        $ПоследняяДатаРеализация = max( $this->Даты['Реализация'] ?? array(0) );
        $ПоследняяДатаЧек = max( $this->Даты['Чек'] ?? array(0) );
        $this->ПоследняяДата = max($ПоследняяДатаЗадолженность,$ПоследняяДатаПлатежка,$ПоследняяДатаРеализация,$ПоследняяДатаЧек);
        if (($ПоследняяДатаПлатежка != 0) or ($ПоследняяДатаЧек != 0)) {
            if (($this->ПоследняяДата >= $begin) and ($this->ПоследняяДата < $end)) {
                $result = true;
            }
        }
        return $result;
    }
    
    function СуммаДляУчета() {
        return $this->СуммаДокумента - array_sum( $this->Суммы['Задолженность'] ?? array(0) );
    }
}
