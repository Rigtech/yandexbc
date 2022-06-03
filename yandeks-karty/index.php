<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("title", "Яндекс карты");
$APPLICATION->SetTitle("Яндекс карты");

use Bitrix\Main\UI\Extension;
Extension::load('ui.bootstrap4');
CModule::IncludeModule("iblock");
?><?
//// Сборка данных

// не знаю каким создастся инфоблок, ищу его ID
$db_iblock = CIBlock::GetList([],['CODE'=>'offices'],false);
if($ar_iblock = $db_iblock->Fetch()) {
    $iblock_id = $ar_iblock['ID'];
}
$arSelect = [
    'ID',
    'IBLOCK_ID',
    'NAME',
    'PROPERTY_PHONE',
    'PROPERTY_EMAIL',
    'PROPERTY_LAT',
    'PROPERTY_LNG',
    'PROPERTY_CITY'
];
$arFilter = [
    'IBLOCK_ID'=>$iblock_id,
    'ACTIVE'=>'Y'
];
$hashArr = [
    $arSelect,
    $arFilter
];

//стартуем кэш
$cache = \Bitrix\Main\Data\Cache::createInstance();

// тут еще можно мультисорт сделать, малоли вызвать в другом месте будем, где $hashArr криво соберется
$md5Hash = md5(serialize($hashArr));
// Кэш на день
$cache_time = 3600 * 24;
$cache_id = 'offices:' . $md5Hash;
$cache_path = '/offices/';
$cache_reset = false;
if($_REQUEST['clear_cache'] == 'Y'){
    $cache_reset = true;
}
if ( $cache_reset !== false ) {
    $cache->clean($cache_id, $cache_path);
}
if ( $cache->initCache($cache_time, $cache_id, $cache_path) and $cache_reset === false ) {

    $vars = $cache->getVars();
    $offices = $vars[$cache_id];
}

if ( !isset($offices) ) {
    $officesDB = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
    while ($office = $officesDB->Fetch()) {

        // Верстку добавлять не круто сюда, но ее мало и не хочется пересобирать массив
        $buff_office = [
                'LON'=>$office['PROPERTY_LNG_VALUE'],
                'LAT'=>$office['PROPERTY_LAT_VALUE'],
                'TEXT'=>"<strong>{$office['NAME']}</strong><br>{$office['PROPERTY_PHONE_VALUE']}<br>{$office['PROPERTY_EMAIL_VALUE']}"
        ];

        $offices[] = $buff_office;
    }
}



//// отрисовка карты
// не придумал куда тут адаптив запихать, сделал просто колами
?>
<div class="container">
	<div class="row justify-content-center">
		<div class="col col-md-8 col-xl-6">
            <div class="m-5">
            <?
            $map_data = [
                'yandex_lat' => 59.8653042,
                'yandex_lon' => 30.3049762,
                'yandex_scale' => 10,
                'PLACEMARKS' => $offices
            ];
            $APPLICATION->IncludeComponent(
                "bitrix:map.yandex.view",
                "",
                Array(
                    "API_KEY" => "",
                    "CONTROLS" => array("ZOOM","MINIMAP","TYPECONTROL","SCALELINE"),
                    "INIT_MAP_TYPE" => "MAP",
                    "MAP_DATA" => serialize($map_data),
                    "MAP_HEIGHT" => "500",
                    "MAP_ID" => "",
                    "MAP_WIDTH" => "600",
                    "OPTIONS" => array("ENABLE_SCROLL_ZOOM","ENABLE_DBLCLICK_ZOOM","ENABLE_DRAGGING")
                )
            );

            ?>
            </div>
		</div>
	</div>
</div>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
