<?php
// структура
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
CModule::IncludeModule("iblock");

function na($text){
    echo '<pre>';
    print_r($text);
    echo '</pre>';
}

$type_name = "Контент";
$type_code = "test";
$type_exist = 0;
$iblock_exist = 0;
$elems_exist = 0;

$obIBlockType = new CIBlockType;
$db_iblock_type = CIBlockType::GetList(array(), array("ID" => $type_code));
if ($db_iblock_type->SelectedRowsCount() > 0) {
    na('Тип инфоблока уже создан');
    $type_exist = 1;
} else {
    $arFields = Array(
        "ID" => $type_code,
        "SECTIONS" => "Y",
        "LANG" => Array(
            "ru" => Array(
                "NAME" => $type_name,
            )
        )
    );
    $res = $obIBlockType->Add($arFields);
    if (!$res) {
        $mess = $obIBlockType->LAST_ERROR;
        na($mess);

    } else {
        $type_exist = 1;
        na('Тип инфоблока уже создан');

    }
}


if($type_exist){

    $ib_name = 'Офисы';
    $ib_code = 'offices';
    $db_iblock = CIBlock::GetList([],['CODE'=>$ib_code],false);
    if($ar_iblock = $db_iblock->Fetch()){
        $iblock_exist = 1;
        $iblock_id = $ar_iblock['ID'];
        na('Инфоблок уже создан');

        // очистим его
        $result = CIBlockElement::GetList
        (
            array("ID"=>"ASC"),
            array
            (
                'IBLOCK_ID'=>$iblock_id,
                'SECTION_ID'=>0,
                'INCLUDE_SUBSECTIONS'=>'N'
            )
        );

        while($element = $result->Fetch())
           CIBlockElement::Delete($element['ID']);

    } else {
        $arFields = Array(
            "ACTIVE" => "Y",
            "NAME" => $ib_name,
            "CODE" => $ib_code,
            "LIST_PAGE_URL" => "#",
            "SECTION_PAGE_URL" => "#",
            "DETAIL_PAGE_URL" => "#",
            "INDEX_SECTION" => "N", // Индексировать разделы для модуля поиска
            "INDEX_ELEMENT" => "N", // Индексировать элементы для модуля поиска
            "IBLOCK_TYPE_ID" => $type_code,
            "SITE_ID" => Array("s1"),
            "SORT" => "100",
            "GROUP_ID" => Array("2" => "R"),
            "FIELDS" => array(
                "ACTIVE_FROM"=>array("DEFAULT_VALUE"=>"=today"),

            )
        );

        $prop_mass = array(
            array(
                "NAME" => "Телефон",
                "ACTIVE" => "Y",
                "CODE" => "PHONE",
                "PROPERTY_TYPE" => "S",
            ),
            array(
                "NAME" => "E-mail",
                "ACTIVE" => "Y",
                "CODE" => "EMAIL",
                "PROPERTY_TYPE" => "S",
            ),
            // координаты делаем числом, а не привязкой и делим на два свойтва - проще взаимодействовать
            array(
                "NAME" => "LAT",
                "ACTIVE" => "Y",
                "CODE" => "LAT",
                "PROPERTY_TYPE" => "N",
            ),
            array(
                "NAME" => "LNG",
                "ACTIVE" => "Y",
                "CODE" => "LNG",
                "PROPERTY_TYPE" => "N",
            ),
            // город делаем строкой, т.к. списка городов нету
            array(
                "NAME" => "Город",
                "ACTIVE" => "Y",
                "CODE" => "CITY",
                "PROPERTY_TYPE" => "S",
            ),
        );

        $ib = new CIBlock;
        $ID = $ib->Add($arFields);

        if ($ID > 0) {
            foreach ($prop_mass as &$prop) {
                $prop["IBLOCK_ID"] = $ID;
            }
            $ibp = new CIBlockProperty;
            $prop_mass_1 = $prop_mass;
            foreach ($prop_mass_1 as $key => $prop_1) {
                $ibp->Add($prop_1);
            }
            $iblock_exist = 1;
            $iblock_id = $ID;
        } else {
            $mess = $ib->LAST_ERROR;
            na($mess);
        }
    }
}

if($type_exist && $iblock_exist){

    // количество элементов не проверяю, пусть генерятся
    $quant = 5;
    for ($i=1;$i<=$quant;$i++) {

        $el = new CIBlockElement;
        $name = "Офис №" . $i;
        $elem_props = [
            'PHONE' => '+79112223344',
            'EMAIL' => 'admin@admin.ru',
            // кординаты рандом
            'LAT' => 59.86 + rand(1,9999999)/100000000,
            'LNG' => 30.30 + rand(1,9999999)/100000000,
            'CITY' => 'Санкт-Петербург',
        ];
        $arElemFields = array(
            "IBLOCK_ID" => $iblock_id,
            "PROPERTY_VALUES" => "",
            "NAME" => $name,
            "CODE" => Cutil::translit($name, "ru", array("replace_space" => "-", "replace_other" => "-")),
            "ACTIVE" => "Y",
            "ACTIVE_FROM" => date("d.m.Y"),
            "PROPERTY_VALUES" => $elem_props,
        );
        $ELEM_ID = $el->Add($arElemFields, false, false, true);
        if ($ELEM_ID > 0) {
            $mess = "Добавлен элемент: " . $name . "<br>";
        } else {
            $mess = $el->LAST_ERROR;
        }
        na($mess);
    }
}

