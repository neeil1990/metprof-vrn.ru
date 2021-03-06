<?
IncludeModuleLangFile(__FILE__);

if(class_exists("rest")) return;
class rest extends CModule
{
	var $MODULE_ID = "rest";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "N";

	private $errors = false;

	function rest()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];

		$this->MODULE_NAME = GetMessage("REST_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("REST_MODULE_DESCRIPTION");
	}

	function InstallDB($arParams = array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;

		// Database tables creation
		if(strtolower($DB->type) !== 'mysql')
		{
			$this->errors = array(
				GetMessage('REST_DB_NOT_SUPPORTED'),
			);
		}
		elseif(!$DB->Query("SELECT 'x' FROM b_rest_app WHERE 1=0", true))
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/db/".strtolower($DB->type)."/install.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		RegisterModule("rest");

		COption::SetOptionString("rest", "server_path", "/rest");

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->registerEventHandler("main", "OnBeforeProlog", "rest", "CRestEventHandlers", "OnBeforeProlog", 49);

		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', 'CBitrixRestEntity', 'OnRestServiceBuildDescription');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\User', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\Placement', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\Event', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\UserFieldType', 'onRestServiceBuildDescription');
		$eventManager->registerEventHandler("rest", "OnRestServiceBuildDescription", "rest", "\\Bitrix\\Rest\\Engine\\RestManager", "OnRestServiceBuildDescription");



		$eventManager->registerEventHandler("main", "OnApplicationsBuildList", "main", '\Bitrix\Rest\APAuth\Application', "onApplicationsBuildList", 100, "modules/rest/lib/apauth/application.php");

		$eventManager->registerEventHandler("im", "OnAfterConfirmNotify", "rest", "\\Bitrix\\Rest\\NotifyIm", "receive");

		$eventManager->registerEventHandler("rest", "\\Bitrix\\Rest\\APAuth\\Password::OnDelete", "rest", "\\Bitrix\\Rest\\APAuth\\PermissionTable", "onPasswordDelete");

		if(CModule::IncludeModule('iblock'))
		{
			COption::SetOptionString("rest", "entity_iblock_type", "rest_entity");

			$arFields = array(
				'ID' => 'rest_entity',
				'SECTIONS' => 'Y',
				'IN_RSS' => 'N',
				'SORT' => 1000,
				'LANG' => array(
					LANGUAGE_ID => array(
						'NAME' => GetMessage('REST_IBLOCK_NAME'),
						'SECTION_NAME' => GetMessage('REST_IBLOCK_SECTION_NAME'),
						'ELEMENT_NAME' => GetMessage('REST_IBLOCK_ELEMENT_NAME'),
					)
				)
			);

			$dbRes = CIBlockType::GetByID($arFields['ID']);
			if(!$dbRes->Fetch())
			{
				$obBlocktype = new CIBlockType;
				$obBlocktype->Add($arFields);
			}
		}

		if(!\Bitrix\Main\ModuleManager::isModuleInstalled("oauth"))
		{
			$eventManager->registerEventHandler("rest", "onRestCheckAuth", "rest", "\\Bitrix\\Rest\\OAuth\\Auth", "onRestCheckAuth");
		}

		$eventManager->registerEventHandler("rest", "onRestCheckAuth", "rest", "\\Bitrix\\Rest\\APAuth\\Auth", "onRestCheckAuth");
		$eventManager->registerEventHandler("rest", "onRestCheckAuth", "rest", "\\Bitrix\\Rest\\SessionAuth\\Auth", "onRestCheckAuth");

		\CAgent::AddAgent("Bitrix\\Rest\\Marketplace\\Client::getNumUpdates();", "rest", "N", 86400);
		\CAgent::AddAgent("Bitrix\\Rest\\EventOfflineTable::cleanProcessAgent();", "rest", "N", 86400);

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;

		if(!array_key_exists("savedata", $arParams) || $arParams["savedata"] != "Y")
		{
			$this->errors = $DB->RunSQLBatch($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/db/".strtolower($DB->type)."/uninstall.sql");
		}

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}

		$eventManager = \Bitrix\Main\EventManager::getInstance();

		$eventManager->unRegisterEventHandler("main", "OnBeforeProlog", "rest", "CRestEventHandlers", "OnBeforeProlog");

		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', 'CBitrixRestEntity', 'OnRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\User', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\Placement', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\Event', 'onRestServiceBuildDescription');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'rest', '\Bitrix\Rest\Api\UserFieldType', 'onRestServiceBuildDescription');

		$eventManager->unRegisterEventHandler("rest", "onRestCheckAuth", "rest", "\\Bitrix\\Rest\\OAuth\\Auth", "onRestCheckAuth");

		$eventManager->unRegisterEventHandler("rest", "onRestCheckAuth", "rest", "\\Bitrix\\Rest\\APAuth\\Auth", "onRestCheckAuth");

		$eventManager->unRegisterEventHandler("rest", "onRestCheckAuth", "rest", "\\Bitrix\\Rest\\SessionAuth\\Auth", "onRestCheckAuth");

		$eventManager->unRegisterEventHandler("main", "OnApplicationsBuildList", "main", '\Bitrix\Rest\APAuth\Application', "onApplicationsBuildList", "modules/rest/lib/apauth/application.php");

		$eventManager->unRegisterEventHandler("im", "OnAfterConfirmNotify", "rest", "\\Bitrix\\Rest\\NotifyIm", "receive");

		$eventManager->unRegisterEventHandler("rest", "\\Bitrix\\Rest\\APAuth\\Password::OnDelete", "rest", "\\Bitrix\\Rest\\APAuth\\PermissionTable", "onPasswordDelete");

		CAgent::RemoveModuleAgents("rest");

		UnRegisterModule("rest");

		return true;
	}

	function InstallEvents()
	{
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles($arParams = array())
	{
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/tools", $_SERVER["DOCUMENT_ROOT"]."/bitrix/tools", true, true);
		CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/services", $_SERVER["DOCUMENT_ROOT"]."/bitrix/services", true, true);

		// delete old urlrewrite rule
		CUrlRewriter::Delete(array(
			'CONDITION' => '#^/rest/#',
			'PATH' => '/rest/index.php'
		));

		$siteId = \CSite::GetDefSite();

		\Bitrix\Main\UrlRewriter::add($siteId, array(
			"CONDITION" => "#^/rest/#",
			"RULE" => "",
			"PATH" => "/bitrix/services/rest/index.php",
		));

		if(\Bitrix\Main\ModuleManager::isModuleInstalled('intranet'))
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/public", $_SERVER["DOCUMENT_ROOT"]."/", true, true);

			\Bitrix\Main\UrlRewriter::add($siteId, array(
				"CONDITION" => "#^/marketplace/#",
				"RULE" => "",
				"ID" => "bitrix:rest.marketplace",
				"PATH" => "/marketplace/index.php",
			));

			\Bitrix\Main\UrlRewriter::add($siteId, array(
				"CONDITION" => "#^/marketplace/local/#",
				"RULE" => "",
				"ID" => "bitrix:rest.marketplace.localapp",
				"PATH" => "/marketplace/local/index.php",
			));

			\Bitrix\Main\UrlRewriter::add($siteId, array(
				"CONDITION" => "#^/marketplace/app/#",
				"RULE" => "",
				"ID" => "bitrix:app.layout",
				"PATH" => "/marketplace/app/index.php",
			));

			\Bitrix\Main\UrlRewriter::add($siteId, array(
				"CONDITION" => "#^/marketplace/hook/#",
				"RULE" => "",
				"ID" => "bitrix:rest.hook",
				"PATH" => "/marketplace/hook/index.php",
			));
		}

		return true;
	}

	function UnInstallFiles()
	{
		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $USER, $step, $DB;
		$step = IntVal($step);

		if(!$USER->IsAdmin())
			return;

		if(strtolower($DB->type) !== 'mysql')
		{
			$APPLICATION->ThrowException(GetMessage('REST_DB_NOT_SUPPORTED'));
			$APPLICATION->IncludeAdminFile(GetMessage("REST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/step1.php");
		}

		else
		{
			if(!check_bitrix_sessid())
			{
				$step = 1;
			}

			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("REST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/step1.php");
			}
			elseif($step == 2)
			{
				$this->InstallDB(array());
				$this->InstallFiles(array());

				$GLOBALS["errors"] = $this->errors;

				$APPLICATION->IncludeAdminFile(GetMessage("REST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/step2.php");
			}
		}
	}

	function DoUninstall()
	{
		global $APPLICATION, $USER, $step;
		if($USER->IsAdmin())
		{
			$step = IntVal($step);
			if($step < 2)
			{
				$APPLICATION->IncludeAdminFile(GetMessage("REST_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/unstep1.php");
			}
			elseif($step == 2)
			{
				$this->UnInstallDB(array(
					"savedata" => $_REQUEST["savedata"],
				));
				$this->UnInstallFiles();

				$GLOBALS["errors"] = $this->errors;

				$APPLICATION->IncludeAdminFile(GetMessage("REST_UNINSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/unstep2.php");
			}
		}
	}
}
?>