<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
?>

<div class="h1"><?=$arParams["PAGER_TITLE"]?></div>
<?foreach($arResult["ITEMS"] as $arItem):?>
<a href="<?=$arItem['DETAIL_PAGE_URL']?>"><?echo $arItem["NAME"]?></a>
<p class="text" style="margin-top: 5px"><?echo $arItem["PREVIEW_TEXT"]?></p>
<?endforeach;?>

<a href="/articles/" class="allarticles">Все <?=$arParams["PAGER_TITLE"]?></a>







