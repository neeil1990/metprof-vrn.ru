<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetPageProperty("description", "Компания «Металлинвест Профиль» реализует кровельные и фасадные материалы по низким ценам | Широкий каталог товаров ведущих производителей | Акции и скидки");
$APPLICATION->SetTitle("«Металлинвест Профиль» — главная страница сайта");
?>
<div class="mp__categories">

	<?$APPLICATION->IncludeComponent("bitrix:menu", "mobile-cat-menu", Array(
		"ALLOW_MULTI_SELECT" => "N",	// Р Р°Р·СЂРµС€РёС‚СЊ РЅРµСЃРєРѕР»СЊРєРѕ Р°РєС‚РёРІРЅС‹С… РїСѓРЅРєС‚РѕРІ РѕРґРЅРѕРІСЂРµРјРµРЅРЅРѕ
		"CHILD_MENU_TYPE" => "left",	// РўРёРї РјРµРЅСЋ РґР»СЏ РѕСЃС‚Р°Р»СЊРЅС‹С… СѓСЂРѕРІРЅРµР№
		"DELAY" => "N",	// РћС‚РєР»Р°РґС‹РІР°С‚СЊ РІС‹РїРѕР»РЅРµРЅРёРµ С€Р°Р±Р»РѕРЅР° РјРµРЅСЋ
		"MAX_LEVEL" => "1",	// РЈСЂРѕРІРµРЅСЊ РІР»РѕР¶РµРЅРЅРѕСЃС‚Рё РјРµРЅСЋ
		"MENU_CACHE_GET_VARS" => array(	// Р—РЅР°С‡РёРјС‹Рµ РїРµСЂРµРјРµРЅРЅС‹Рµ Р·Р°РїСЂРѕСЃР°
			0 => "",
		),
		"MENU_CACHE_TIME" => "3600",	// Р’СЂРµРјСЏ РєРµС€РёСЂРѕРІР°РЅРёСЏ (СЃРµРє.)
		"MENU_CACHE_TYPE" => "N",	// РўРёРї РєРµС€РёСЂРѕРІР°РЅРёСЏ
		"MENU_CACHE_USE_GROUPS" => "Y",	// РЈС‡РёС‚С‹РІР°С‚СЊ РїСЂР°РІР° РґРѕСЃС‚СѓРїР°
		"ROOT_MENU_TYPE" => "mobilecategories",	// РўРёРї РјРµРЅСЋ РґР»СЏ РїРµСЂРІРѕРіРѕ СѓСЂРѕРІРЅСЏ
		"USE_EXT" => "N",	// РџРѕРґРєР»СЋС‡Р°С‚СЊ С„Р°Р№Р»С‹ СЃ РёРјРµРЅР°РјРё РІРёРґР° .С‚РёРї_РјРµРЅСЋ.menu_ext.php
	),
		false
	);?>


	<ul class="tabslist cl">

		<?
		$arFilter = array('IBLOCK_ID' => 24,'DEPTH_LEVEL' => 1,"ACTIVE" => "Y");
		$rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'),$arFilter);
		$inc = 1;
		$arrIdSection = array();
		while ($arSect = $rsSect->GetNext())
		{
			$arrIdSection[] = $arSect['ID'];
			?>
			<li class="maincategory maincategory--<?=$inc?> cl">
				<span class="img"></span>
				<span class="name"><?=$arSect['NAME'];?></span>
			</li>
			<?
			$inc++;
		}
		?>
		<li class="maincategory maincategory--3 cl">
			<span class="img"></span>
			<span class="name">Прайс-листы</span>
		</li>
	</ul>
   	<div class="tablist_content">

		<? foreach($arrIdSection as $sect): ?>
		<div class="tabitem">

			<?
			$arFilter = array('IBLOCK_ID' => 24,"SECTION_ID" => $sect,'DEPTH_LEVEL' => 2,"ACTIVE" => "Y");
			$rsSect = CIBlockSection::GetList(array('left_margin' => 'asc'),$arFilter);
			$inc = 1;
			$arrIdSection = array();
			$arrSection = array();
			while ($arSect = $rsSect->GetNext())
			{
				$arrSection[] = $arSect;
			}
			$rows = ceil(count($arrSection)/6);
			$section = array_chunk($arrSection,6);
			?>
			<? for($i = 0;$i < $rows;$i++):?>
			<div class="row cl category__line">
				<? foreach($section[$i] as $item):?>
				<div class="category">
					<a href="<?=$item['SECTION_PAGE_URL']?>" class="link">
                        <? if($item["PICTURE"]):?>
						<img src="<?=CFile::ResizeImageGet($item["PICTURE"], array('width' => 200, 'height' => 200), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];?>" alt="<?=$item['NAME']?>" height="120" class="img">
                        <?endif;?>
						<span class="name"><?=$item['NAME']?></span>
					</a>
				</div>
				<? endforeach; ?>
			</div><!-- row cl category__line-->
			<? endfor; ?>

		</div><!-- end: tabitem -->
		<? endforeach; ?>
      	<div class="tabitem"></div>
		<!-- end: tabitem -->
   	</div><!-- end: tablist_content -->
</div><!--end::mp__categories-->



<div class="mp__products">
   	<div class="tabs">
      	<span class="tab active">Популярные товары</span>
      	<span class="tab">Новые поступления</span>
      	<span class="tab">Акции и скидки</span>
   	</div><!--end::tabs-->
	<div class="tab_content">
		<div class="tab_item ac">
			<?$APPLICATION->IncludeComponent(
	"bitrix:sale.bestsellers",
	"sale-bestsellers",
	array(
		"ACTION_VARIABLE" => "action",
		"ADDITIONAL_PICT_PROP_10" => "MORE_PHOTO",
		"ADDITIONAL_PICT_PROP_11" => "MORE_PHOTO",
		"ADDITIONAL_PICT_PROP_12" => "MORE_PHOTO",
		"ADD_PROPERTIES_TO_BASKET" => "Y",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"BASKET_URL" => "/personal/basket.php",
		"BY" => "AMOUNT",
		"CACHE_TIME" => "86400",
		"CACHE_TYPE" => "A",
		"CART_PROPERTIES_10" => array(
			0 => ",",
		),
		"CART_PROPERTIES_11" => array(
			0 => "",
			1 => ",",
			2 => "",
		),
		"CART_PROPERTIES_12" => array(
			0 => "",
			1 => "",
		),
		"CONVERT_CURRENCY" => "N",
		"DETAIL_URL" => "",
		"DISPLAY_COMPARE" => "N",
		"FILTER" => array(
			0 => "N",
			1 => "P",
			2 => "F",
		),
		"HIDE_NOT_AVAILABLE" => "N",
		"LABEL_PROP_10" => "-",
		"LABEL_PROP_11" => "-",
		"LINE_ELEMENT_COUNT" => "3",
		"MESS_BTN_BUY" => "Купить",
		"MESS_BTN_DETAIL" => "Подробнее",
		"MESS_BTN_SUBSCRIBE" => "Подписаться",
		"MESS_NOT_AVAILABLE" => "Нет в наличии",
		"OFFER_TREE_PROPS_12" => "",
		"PAGE_ELEMENT_COUNT" => "10",
		"PARTIAL_PRODUCT_PROPERTIES" => "N",
		"PERIOD" => "0",
		"PRICE_CODE" => array(
			0 => "SITE",
		),
		"PRICE_VAT_INCLUDE" => "Y",
		"PRODUCT_ID_VARIABLE" => "id",
		"PRODUCT_PROPS_VARIABLE" => "prop",
		"PRODUCT_QUANTITY_VARIABLE" => "quantity",
		"PRODUCT_SUBSCRIPTION" => "N",
		"PROPERTY_CODE_10" => array(
			0 => ",",
		),
		"PROPERTY_CODE_11" => array(
			0 => "",
			1 => ",",
			2 => "",
		),
		"PROPERTY_CODE_12" => array(
			0 => "",
			1 => "",
		),
		"SHOW_DISCOUNT_PERCENT" => "N",
		"SHOW_IMAGE" => "Y",
		"SHOW_NAME" => "Y",
		"SHOW_OLD_PRICE" => "N",
		"SHOW_PRICE_COUNT" => "1",
		"SHOW_PRODUCTS_10" => "N",
		"SHOW_PRODUCTS_11" => "Y",
		"TEMPLATE_THEME" => "blue",
		"USE_PRODUCT_QUANTITY" => "N",
		"COMPONENT_TEMPLATE" => "sale-bestsellers",
		"SHOW_PRODUCTS_24" => "Y",
		"PROPERTY_CODE_24" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_24" => array(
			0 => "",
			1 => "",
		),
		"ADDITIONAL_PICT_PROP_24" => "MORE_PHOTO",
		"LABEL_PROP_24" => "-",
		"PROPERTY_CODE_22" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_22" => array(
			0 => "",
			1 => "",
		),
		"ADDITIONAL_PICT_PROP_22" => "MORE_PHOTO",
		"OFFER_TREE_PROPS_22" => "",
		"SHOW_PRODUCTS_26" => "N",
		"PROPERTY_CODE_26" => "",
		"CART_PROPERTIES_26" => "",
		"ADDITIONAL_PICT_PROP_26" => "MORE_PHOTO",
		"LABEL_PROP_26" => "-",
		"PROPERTY_CODE_27" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_27" => array(
			0 => "",
			1 => "",
		),
		"ADDITIONAL_PICT_PROP_27" => "MORE_PHOTO",
		"OFFER_TREE_PROPS_27" => "",
		"PROPERTY_CODE_25" => array(
			0 => "",
			1 => "",
		),
		"CART_PROPERTIES_25" => array(
			0 => "",
			1 => "",
		),
		"ADDITIONAL_PICT_PROP_25" => "MORE_PHOTO",
		"OFFER_TREE_PROPS_25" => array(
		)
	),
	false
);?>

		</div><!-- end::tab_item -->
		<div class="tab_item">
			<div class="slider_product" id="mp__product__new">
				<?
				$arSelect = Array("ID","IBLOCK_ID", "NAME", "DETAIL_PAGE_URL","PREVIEW_PICTURE","PROPERTY_SALE","PROPERTY_CML2_BASE_UNIT");
				$arFilter = array('IBLOCK_ID' => 24, 'ACTIVE' => 'Y','>=TIMESTAMP_X' => date($DB->DateFormatToPHP(CLang::GetDateFormat("SHORT")), strtotime('-30 day')));
				$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nPageSize" => 10), $arSelect);
				while($ob = $res->GetNextElement()):
					$arFields = $ob->GetFields();
					$arProps = $ob->GetProperties();
					?>
					<div>
						<div class="product">
							<a href="<?=$arFields['DETAIL_PAGE_URL']?>" style="display: block;height: 110px;">
								<img src="<?=CFile::ResizeImageGet($arFields["PREVIEW_PICTURE"], array('width' => 150, 'height' => 150), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];?>" alt="" height="110" style="max-height: 110px;margin: 0 auto;" class="img">
							</a>
							<a href="<?=$arFields['DETAIL_PAGE_URL']?>" class="name"><?=$arFields['NAME']?></a>
							<div class="price"><span><?=price($arFields['ID']);?></span> <?=RUB?>/<?=$arProps['CML2_BASE_UNIT']['VALUE'];?></div>
							<a href="<?=$arFields["DETAIL_PAGE_URL"]?>" class="cart">Купить</a>
						</div>
					</div>
				<? endwhile; ?>
			</div><!-- end::slider_product -->
		</div><!-- end::tab_item -->
		<div class="tab_item">
			<div class="slider_product" id="mp__product__action">
				<?
				$arSelect = Array("ID","IBLOCK_ID", "NAME", "DETAIL_PAGE_URL","PREVIEW_PICTURE","PROPERTY_SALE","PROPERTY_CML2_BASE_UNIT");
				$arFilter = Array("IBLOCK_ID" => 24, "ACTIVE" => "Y", "PROPERTY_SALE_VALUE" => "Y");
				$res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
				while($ob = $res->GetNextElement()):
				$arFields = $ob->GetFields();
					$arProps = $ob->GetProperties();
				?>
				<div>
					<div class="product">
						<a href="<?=$arFields['DETAIL_PAGE_URL']?>" style="display: block;height: 110px;">
							<img src="<?=CFile::ResizeImageGet($arFields["PREVIEW_PICTURE"], array('width' => 150, 'height' => 150), BX_RESIZE_IMAGE_PROPORTIONAL, true)['src'];?>" alt="" height="110" style="max-height: 110px;margin: 0 auto;" class="img">
						</a>
						<a href="<?=$arFields['DETAIL_PAGE_URL']?>" class="name"><?=$arFields['NAME']?></a>
						<div class="price"><span><?=price($arFields['ID']);?></span> <?=RUB?>/<?=$arProps['CML2_BASE_UNIT']['VALUE'];?></div>
						<a href="<?=$arFields["DETAIL_PAGE_URL"]?>" class="cart">Купить</a>
					</div>
				</div>
				<? endwhile; ?>


			</div><!-- end::slider_product -->
		</div><!-- end::tab_item -->
   	</div><!-- end::tab_content -->
</div><!-- end::mp__products -->
<div class="mp__content cl">
	<div class="mp__company">
		<div class="content__title">О компании</div>
		<div class="company__text">
			<p>
				Компания ООО «Металлинвест Профиль» была основана в 2014 году как дочернее предприятие ООО «Металлинвест плюс»
			 (одного из крупнейших поставщиков стального металлопроката и труб в Воронежской области с почти 20-летней историей).
			</p>
			<p>
				ООО «Металлинвест Профиль» реализует кровельные и фасадные материалы для промышленного и гражданского строительства.
			Наш ассортимент постоянно расширяется и уже можно выделить несколько основных товарных групп:
			</p>
			<ul class="content__list">
				<li>Кровельные материалы (металлочерепица, профнастил, гибкая черепица, водосточные системы)</li>
				<li>Фасадные материалы (сэндвич – панели, сайдинг, фасадные плиты, фасадные кассеты, утеплитель.)</li>
			</ul>
		</div>
		<a href="/about/" class="content__link">Подробнее</a>
	</div>
	<div class="mp__articles cl">
		<?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"news-list-home",
	array(
		"ACTIVE_DATE_FORMAT" => "j F Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "7",
		"IBLOCK_TYPE" => "news",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "Y",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "2",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "новости",
		"LINK_TITLE" => "news",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "50",
		"PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_BY2" => "SORT",
		"SORT_ORDER1" => "DESC",
		"SORT_ORDER2" => "ASC",
		"COMPONENT_TEMPLATE" => "news-list-home"
	),
	false
);?>

		<?$APPLICATION->IncludeComponent(
	"bitrix:news.list",
	"articles-list-home",
	array(
		"ACTIVE_DATE_FORMAT" => "j F Y",
		"ADD_SECTIONS_CHAIN" => "N",
		"AJAX_MODE" => "N",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "N",
		"AJAX_OPTION_STYLE" => "Y",
		"CACHE_FILTER" => "N",
		"CACHE_GROUPS" => "Y",
		"CACHE_TIME" => "36000000",
		"CACHE_TYPE" => "A",
		"CHECK_DATES" => "Y",
		"DETAIL_URL" => "",
		"DISPLAY_BOTTOM_PAGER" => "Y",
		"DISPLAY_DATE" => "Y",
		"DISPLAY_NAME" => "Y",
		"DISPLAY_PICTURE" => "Y",
		"DISPLAY_PREVIEW_TEXT" => "Y",
		"DISPLAY_TOP_PAGER" => "N",
		"FIELD_CODE" => array(
			0 => "",
			1 => "",
		),
		"FILTER_NAME" => "",
		"HIDE_LINK_WHEN_NO_DETAIL" => "N",
		"IBLOCK_ID" => "4",
		"IBLOCK_TYPE" => "news",
		"INCLUDE_IBLOCK_INTO_CHAIN" => "N",
		"INCLUDE_SUBSECTIONS" => "Y",
		"MESSAGE_404" => "",
		"NEWS_COUNT" => "2",
		"PAGER_BASE_LINK_ENABLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
		"PAGER_SHOW_ALL" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TEMPLATE" => ".default",
		"PAGER_TITLE" => "статьи",
		"LINK_TITLE" => "articles",
		"PARENT_SECTION" => "",
		"PARENT_SECTION_CODE" => "",
		"PREVIEW_TRUNCATE_LEN" => "50",
		"PROPERTY_CODE" => array(
			0 => "",
			1 => "",
		),
		"SET_BROWSER_TITLE" => "N",
		"SET_LAST_MODIFIED" => "N",
		"SET_META_DESCRIPTION" => "N",
		"SET_META_KEYWORDS" => "N",
		"SET_STATUS_404" => "N",
		"SET_TITLE" => "N",
		"SHOW_404" => "N",
		"SORT_BY1" => "ACTIVE_FROM",
		"SORT_BY2" => "SORT",
		"SORT_ORDER1" => "DESC",
		"SORT_ORDER2" => "ASC",
		"COMPONENT_TEMPLATE" => "articles-list-home"
	),
	false
);?>



	</div><!--end::mp__articles-->
</div><!--end::mp__content-->

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
