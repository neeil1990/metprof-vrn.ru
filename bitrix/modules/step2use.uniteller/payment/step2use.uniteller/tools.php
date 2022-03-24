<?php
/**
 * ????? ?????? ??????????? ??? ????????? ???????, ?????????? ? ????????? ??????????? ?????? ? ???????.
 *
 * @author Lobanov Alexey - https://atlant2010.ru
 */
class ps_uniteller {
	/**
	 * 64 - ????? ?????? ?? ???????????? ??????????.
	 *
	 * @var int
	 */
	const LOGIN_MAX = 64;
	/**
	 * 80 - ????? ?????? ?? ???????????? ??????????.
	 *
	 * @var int
	 */
	const PASSWORD_MAX = 80;
	/**
	 * ???????? ?? ????????? - ?????????? ???? ? ??????? ??????? ?????? ??????? 'paid' ????? ???????????? ? 'canceled'.
	 *
	 * @var int
	 */
	const DEF_TIME_PAID_CHANGE = 14;
	/**
	 * ???????? ?? ????????? - ?????????? ???? ? ??????? ??????? ?????? ?????? ????? ??????????????????.
	 *
	 * @var int
	 */
	const DEF_TIME_ORDER_SYNC = 30;
	/**
	 * ??? ??????: ??????????? ??????? ?????????.
	 *
	 * @var string
	 */
	const RESPONSE_CODE_SUCCES = 'AS000';
	/**
	 * ???? ? ??????????? (?????????????????) ????????? ???????.
	 *
	 * @var string
	 */
	const UNITELLER_SALE_PATH = '/bitrix/php_interface/include/sale_payment/step2use.uniteller';

	/**
	 * ???? ?????? ???? 'paid' ?????? ??? $date_fix_paid_change, ?? ?????? ??? ?? ????? ????? 'canceled'.
	 *
	 * @var int
	 */
	public static $date_fix_paid_change = 0;

	/**
	 * ???? ????????? ????????? ??????? ??? ?????? ??? $date_fix_order_sync ???? ?????, ?? ????? ?? ????? ??????????????????.
	 *
	 * @var int
	 */
	public static $date_fix_order_sync = 0;

	/**
	 * URL ??? ???????? ??????.
	 *
	 * @var string
	 */
	public static $url_uniteller_pay = '';

	/**
	 * URL ??? ????????? ??????????? ???????????.
	 *
	 * @var string
	 */
	public static $url_uniteller_results = '';

	/**
	 * URL ??? ?????? ???????.
	 *
	 * @var string
	 */
	public static $url_uniteller_unblock = '';

	/**
	 * ????????????? ????? ??????? ? ??????? Uniteller.
	 *
	 * @var string
	 */
	public static $Shop_ID = '';

	/**
	 * ????? ?? ??????? "????????? ???????????" ??????? ???????? ??????? Uniteller.
	 *
	 * @var string
	 */
	public static $Login = '';

	/**
	 * ?????? ?? ??????? "????????? ???????????" ??????? ???????? ??????? Uniteller.
	 *
	 * @var string
	 */
	public static $Password = '';

	/**
	 * ????????????? ???????? ???????:
	 * - $url_uniteller_pay;
	 * - $url_uniteller_results;
	 * - $url_uniteller_unblock;
	 * - $Shop_ID;
	 * - $Login;
	 * - $Password;
	 * - $date_fix_paid_change;
	 * - $date_fix_order_sync;
	 *
	 * @param int $order_real_id
	 * @return void
	 */
	public static function setMerchantData($order_real_id) {
		if (self::$Shop_ID == '') {
			$arOrder = CSaleOrder::GetByID((int)$order_real_id);
			CSalePaySystemAction::InitParamArrays($arOrder, $arOrder['ID']);

			if(CSalePaySystemAction::GetParamValue('USE_FISKAL', 'N') == 'Y'){
				self::$url_uniteller_pay = 'https://fpay.uniteller.ru/v2/pay';
				self::$url_uniteller_results = 'https://fpay.uniteller.ru/v2/results';
				self::$url_uniteller_unblock = 'https://fpay.uniteller.ru/v2/unblock';
			}else{
				self::$url_uniteller_pay = 'https://wpay.uniteller.ru/pay/';
				self::$url_uniteller_results = 'https://wpay.uniteller.ru/results/';
				self::$url_uniteller_unblock = 'https://wpay.uniteller.ru/unblock/';
			}


			self::$Shop_ID = trim(CSalePaySystemAction::GetParamValue('SHOP_IDP'));
			self::$Login = substr(trim(CSalePaySystemAction::GetParamValue('SHOP_LOGIN')), 0, self::LOGIN_MAX);
			self::$Password = substr(trim(CSalePaySystemAction::GetParamValue('SHOP_PASSWORD')), 0, self::PASSWORD_MAX);

			$time_paid_change = (int)CSalePaySystemAction::GetParamValue('UT_TIME_PAID_CHANGE');
			if ($time_paid_change <= 0) {
				$time_paid_change = self::DEF_TIME_PAID_CHANGE;
			}
			self::$date_fix_paid_change = time() - (int)($time_paid_change * 24 * 60 * 60);

			$time_order_sync = (int)CSalePaySystemAction::GetParamValue('UT_TIME_ORDER_SYNC');
			if ($time_order_sync <= 0) {
				$time_order_sync = self::DEF_TIME_ORDER_SYNC;
			}
			self::$date_fix_order_sync = time() - (int)($time_order_sync * 24 * 60 * 60);
		}
	}

	public static function buildReceipt($orderId, $taxmode, $includeDelivery, $deliveryPrice, $deliveryId, $payattr=1, $lineattr=1){
		CModule::IncludeModule('catalog');

		$taxmode = intval($taxmode);
        $payattr = intval($payattr);
        $lineattr = intval($lineattr);
        
		$ReceiptObject = array();
		$arBasketItems = array();

		if (!CSaleOrder::GetByID($orderId)) {
			$res = CSaleOrder::GetList(array(), Array("ACCOUNT_NUMBER" => $orderId));
			while($ob = $res->GetNext(false,false)):
			   $orderId = (int)$ob['ID'];
			endwhile;
			$arOrder = CSaleOrder::GetByID($orderId);
		} else {
			$arOrder = CSaleOrder::GetByID((int)$orderId);
		}


		//$arOrder = CSaleOrder::GetByID((int)$orderId);

		$itemsID = array();
		$basketItems = CSaleBasket::GetList(array(), array("ORDER_ID" => $orderId),false, false, array('NAME', 'PRICE', 'QUANTITY', 'PRODUCT_ID'));
		while($item = $basketItems->GetNext()){
			$arBasketItems[] = $item;
			$itemsID[] = $item['PRODUCT_ID'];
		}
		$lines = array();
		$total = 0;

		$dbProducts = CCatalogProduct::GetList(
			array("QUANTITY" => "DESC"),
			array("ID" => $itemsID),
			false,
			array(),
			array('ID', 'VAT_ID')
		);

		$arVAT = array();

		while($product = $dbProducts->GetNext()){
			$vatId = $product['VAT_ID'];
			$vatDb = CCatalogVat::GetByID($vatId)->Fetch();
			$arVAT[$product['ID']] = intval($vatDb['RATE']);
		}

		$arVatRates = array(
			"0" => 0,
			"10" => 10,
			"18" => 18,
            "20" => 20,
		);


		foreach($arBasketItems as $arItem){
			
			//var_dump($arItem['PRICE']);
			
			$price = $arItem['PRICE'];
			
			//var_dump($price);

			if($arVatRates[$arVAT[$arItem['PRODUCT_ID']]]){
				$vat = $arVAT[$arItem['PRODUCT_ID']];
			}else{
				$vat = -1;
			}

			$lines[] = array(
				'vat' => $vat,
				'taxmode' => $taxmode,
				'name' => self::toUtf($arItem['NAME']),
				'price' => round($price,2),
				'qty' => $arItem['QUANTITY'],
				'sum' => round($price * $arItem['QUANTITY'],2),
                'payattr' => $payattr,
                'lineattr' => $lineattr,
			);
			$total += round($price * $arItem['QUANTITY'], 2);
		}

		if($includeDelivery){
			$deliveryObj = Bitrix\Sale\Delivery\Services\Table::getList(array(
				'filter' => array(
					'ID' => $deliveryId,
				),
				'select' => array('CODE', 'NAME')
			));

			$deliveryInfo = $deliveryObj->Fetch();
			$lines[] = array(
				'vat' => -1,
				//'taxmode' => $taxmode,
				'name' => self::toUtf($deliveryInfo['NAME']),
				'price' => round($deliveryPrice),
				'qty' => 1,
				'sum' => round($deliveryPrice),
                'payattr' => $payattr,
                'lineattr' => 4, // �� ����������� ������ (������������ � ���� ��������, ����������� ������)
			);

			$total += $deliveryPrice;
		}
		
		//var_dump($total);

		$ReceiptObject['lines'] = $lines;
		$ReceiptObject['total'] = $total; //$arOrder['PRICE'];
        
        $ReceiptObject['taxmode'] = $taxmode;
        
        $ReceiptObject['payments'] = array(
            array(
                'kind' => 1,
                'type' => 0,
                'amount' => $total,
            )
        );
        
		$ReceiptJSON = json_encode($ReceiptObject, JSON_PRETTY_PRINT);
		return base64_encode($ReceiptJSON);
	}

	public static function getReceiptSignature($Receipt, $orderId, $sHouldPay){
		$basketItems = CSaleBasket::GetList(array(), array("ORDER_ID" => $orderId),false, false, array('NAME', 'PRICE', 'QUANTITY'));
		while($item = $basketItems->GetNext()){
			$arBasketItems[] = $item;
		}
		$total = 0;

		foreach($arBasketItems as $arItem){
			$price = round($arItem['PRICE'], 2);
			$total += $price * $arItem['QUANTITY'];
		}

		$ReceiptSignature =
//strtoupper( self::sha256( self::sha256(self::$Shop_ID) . '&' . self::sha256($orderId) . '&' . $sHouldPay ? self::sha256(str_replace(',', '.', $sHouldPay)) : '' . '&' . self::sha256($Receipt) . '&' . self::sha256(self::$Password)));
strtoupper(hash('sha256', hash('sha256', self::$Shop_ID) . "&" . hash('sha256', $orderId) . "&" . hash('sha256', $sHouldPay) . "&" . hash('sha256', $Receipt) . "&" . hash('sha256', self::$Password)));


		return $ReceiptSignature;
	}

	public static function sha256($str){
		return hash('sha256', $str);
	}

	/**
	 * ?? ID ?????? ?????????? ??? ??????? ??????.
	 *
	 * @param int $order_real_id
	 * @return string
	 */
	public static function getStatusCode($order_real_id) {
		// ???????? ?????? ??????.
		$arOrder = CSaleOrder::GetByID($order_real_id);
		CSalePaySystemAction::InitParamArrays($arOrder, $arOrder['ID']);
		$status = $arOrder['STATUS_ID'];

		// ???????????? ?? ???????.
		if ($status == 'N' && $arOrder['CANCELED'] == 'N') {
			// ?????? ?????? '??????' ???????????? ?????????? ???????.
			return 'O';
		} elseif ($status == 'B' && $arOrder['CANCELED'] == 'N') {
			// ?????? ?????? '???????? ?????????????' (??????????? ??? ??????????? ??????) ???????????? ??????? ??????? 'athorized'.
			return 'A';
		} elseif (($status == 'P' || $status == 'F') && $arOrder['CANCELED'] == 'N') {
			// ??????? ?????? '???????' ? '?????????' ???????????? ??????? ??????? 'paid'.
			return 'P';
		} elseif ($arOrder['CANCELED'] == 'Y') {
			// ?????? ?????? '???????' ???????????? ??????? ??????? 'canceled'.
			return 'C';
		} elseif (false) {
			// ????????? ?????? ????????? ????????.
			return 'Z';
		} else {
			return 'W';
		}
	}

	/**
	 * ???????? ?????? ??????.
	 *
	 * @param int $order_real_id
	 * @param string $status
	 * @return boolean
	 */
	public static function setStatusCode($order_real_id, $status) {
		$arFields = array();
		if (strtolower($status) == 'empty') {
			// ?????????? ??????? ???????????? ??????? ?????? '??????'.
			$arFields = array(
				//'STATUS_ID' => 'N',
				'DATE_STATUS' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'PS_STATUS' => 'N',
				'PS_RESPONSE_DATE' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'PAYED' => 'N',
				'DATE_PAYED' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'CANCELED' => 'N',
				'PS_STATUS_CODE' => (string)$status,
				'PS_STATUS_MESSAGE' => (string)$status,
			);
		} elseif (strtolower($status) == 'authorized') {
			// ?????? ??????? 'authorized' ???????????? ??????? ?????? '???????? ?????????????' (??????????? ??? ??????????? ??????).
			$arFields = array(
				//'STATUS_ID' => 'B',
				'DATE_STATUS' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'PS_STATUS' => 'Y',
				'PS_RESPONSE_DATE' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'PAYED' => 'Y',
				'DATE_PAYED' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'CANCELED' => 'N',
				'PS_STATUS_CODE' => (string)$status,
				'PS_STATUS_MESSAGE' => (string)$status,
			);
		} elseif (strtolower($status) == 'paid') {
			// ?????? ??????? 'paid' ???????????? ??????? ?????? '???????'.
			$arFields = array(
				//'STATUS_ID' => 'P',
				'DATE_STATUS' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'PS_STATUS' => 'Y',
				'PS_RESPONSE_DATE' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'PAYED' => 'Y',
				'DATE_PAYED' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'CANCELED' => 'N',
				'PS_STATUS_CODE' => (string)$status,
				'PS_STATUS_MESSAGE' => (string)$status,
			);
		} elseif (strtolower($status) == 'canceled') {
			// ?????? ??????? 'canceled' ???????????? ??????? ?????? '???????'.
			$arFields = array(
				'PS_STATUS' => 'N',
				'PAYED' => 'N',
				'PS_RESPONSE_DATE' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'DATE_PAYED' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'CANCELED' => 'Y',
				'DATE_CANCELED' => Date(CDatabase::DateFormatToPHP(CLang::GetDateFormat('FULL', LANG))),
				'PS_STATUS_CODE' => (string)$status,
				'PS_STATUS_MESSAGE' => (string)$status,
			);
		} else {
			return false;
		}

		// ???????? ?????? ??????.
		if (!empty($arFields)) {
            
            //var_dump($arFields);//exit;
            
			if ((CSalePaySystemAction::GetParamValue('AUTO_STATUS') !== "Y") and (strtolower($status) == 'paid')) {
				
			} else {
			$q = CSaleOrder::Update((int)$order_real_id, $arFields);
            //var_dump($q);
            global $APPLICATION;
            //var_dump($APPLICATION->LAST_ERROR);
            //exit;

			// ??????????? ??????? ??????, ???? ?????? 'paid'
			if(strtolower($status) == 'authorized') {
				CSaleOrder::PayOrder((int)$order_real_id, "Y", true, true, 0);
			}
			// ??????? ??????? ??????, ???? ?????? 'canceled'
			if(strtolower($status) == 'canceled') {
				CSaleOrder::PayOrder((int)$order_real_id, "N", true, true, 0);
			}
			}
		}
		return true;
	}

	/**
	 * ???????? ?????? ? ?????????? ????? ? ???? simplexml_object, ?????? ? ??????? ??? false.
	 * ?????? ?????????? ? ??????? b_uniteller_agent.
	 *
	 * @param string $url
	 * @param array $data
	 * @return simplexml_object|string|boolean
	 */
	public static function setCurlRequest($url, $data) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, (string)$url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, (array)$data);
		curl_setopt($ch, CURLOPT_FAILONERROR, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		$error = curl_error($ch);
		curl_close($ch);

		if ($errno == 0) {
			libxml_use_internal_errors(true);
			$xml = simplexml_load_string($result);
			if ($xml !== false) {
				return $xml;
			} else {
				libxml_clear_errors();
				global $DB;
				if (!isset($data['ShopOrderNumber'])) {
					$data['ShopOrderNumber'] = '';
				}
				$DB->Query("INSERT INTO
							 b_uniteller_agent (ORDER_ID, TYPE_ERROR, TEXT_ERROR)
							 VALUES ('" . (int) $data['ShopOrderNumber'] . "', 'UT_ERROR_UNITELLER','" . htmlspecialchars($result, ENT_QUOTES) . "');"
				, false
				, 'File: ' . __FILE__ . '<br>Line: ' . __LINE__
				);

				return $result;
			}
		} else {
			global $DB;
			if (!isset($data['ShopOrderNumber'])) {
				$data['ShopOrderNumber'] = '';
			}
			$DB->Query("INSERT INTO
						 b_uniteller_agent (ORDER_ID, TYPE_ERROR, TEXT_ERROR)
						 VALUES ('" . (int) $data['ShopOrderNumber'] . "', 'UT_ERROR_CONNECT','" . htmlspecialchars($errno . ': ' . $error, ENT_QUOTES) . "');"
			, false
			, 'File: ' . __FILE__ . '<br>Line: ' . __LINE__
			);

			return false;
		}
	}

	/**
	 * ???????? ?????? ?? ????????? ??????????? ??????????? ?? ????????? ?????? ?????? ? ?????????? ?????? ?????? ??? ????.
	 *
	 * @param int $order_real_id
	 * @return array
	 */
	public static function getCheckData($order_real_id) {
		self::setMerchantData($order_real_id);
		$data = array(
			'ShopOrderNumber' => (int)$order_real_id,
			'Shop_ID' => self::$Shop_ID,
			'Login' => self::$Login,
			'Password' => self::$Password,
			'Format' => '4',
		);

		$arOrder = CSaleOrder::GetByID((int)$order_real_id);
		CSalePaySystemAction::InitParamArrays($arOrder, $arOrder['ID']);
		$return = array(
			'order_id' => (int)$order_real_id,
			'name_merchant' => trim(COption::GetOptionString('main', 'site_name', '')),
			'name_lat' => trim(CSalePaySystemAction::GetParamValue('UNI_SITE_NAME_LAT')),
			'name_url' => trim(SITE_SERVER_NAME),
			'phone' => '',
			'email' => trim(CSite::GetByID(SITE_ID)->arResult[0]['EMAIL']),

			'total' => '',
			'currency' => '',
			'date' => '',
			'approvalcode' => '',
			'lastname' => '',
			'firstname' => '',
			'middlename' => '',
			'billnumber' => '',
			'paymenttransactiontype_id' => '',
			'response_code' => '',
			'ordernumber' => '',
			'status' => '',

			'error_protocol' => '',
			'error_count' => '',
			'error_message' => '',
			'error_authentication' => '',
		);

		$xml = self::setCurlRequest(self::$url_uniteller_results, $data);
		$array_xml = (array)$xml;
		if ($xml === false) {
			// o????? ??? ?????????? ???????.
			$return['error_protocol'] = true;
		} elseif (isset($xml->orders->order)) {
			$xml_order = $xml->orders->order;

			$total = (array)$xml_order->total;
			$currency = (array)$xml_order->currency;
			$date = (array)$xml_order->date;
			$approvalcode = (array)$xml_order->approvalcode;
			$lastname = (array)$xml_order->lastname;
			$firstname = (array)$xml_order->firstname;
			$middlename = (array)$xml_order->middlename;
			$billnumber = (array)$xml_order->billnumber;
			$paymenttransactiontype_id = (array)$xml_order->paymenttransactiontype_id;
			$response_code = (array)$xml_order->response_code;
			$ordernumber = (array)$xml_order->ordernumber;
			$status = (array)$xml_order->status;

			$return['total'] = ( ( isset($total[0]) ) ? $total[0] : '' );
			$return['currency'] = ( ( isset($currency[0]) ) ? $currency[0] : '' );
			$return['date'] = ( ( isset($date[0]) ) ? $date[0] : '' );
			$return['approvalcode'] = ( ( isset($approvalcode[0]) ) ? $approvalcode[0] : '' );
			$return['lastname'] = ( ( isset($lastname[0]) ) ? $lastname[0] : '' );
			$return['firstname'] = ( ( isset($firstname[0]) ) ? $firstname[0] : '' );
			$return['middlename'] = ( ( isset($middlename[0]) ) ? $middlename[0] : '' );
			$return['billnumber'] = ( ( isset($billnumber[0]) ) ? $billnumber[0] : '' );
			$return['paymenttransactiontype_id'] = ( ( isset($paymenttransactiontype_id[0]) ) ? $paymenttransactiontype_id[0] : '' );
			$return['response_code'] = ( ( isset($response_code[0]) ) ? $response_code[0] : '' );
			$return['ordernumber'] = ( ( isset($ordernumber[0]) ) ? $ordernumber[0] : '' );
			$return['status'] = ( ( isset($status[0]) ) ? $status[0] : '' );
		} elseif (isset($array_xml['@attributes']['count']) && (int)$array_xml['@attributes']['count'] == 0) {
			// ??????? ???, ?? ???????? ???????? xml ? ????????? count ?????? 0
			$return['error_count'] = true;
		} else {
			// ????????? ?????? ? ???????
			$return['error_message'] = $xml;
			$return['error_authentication'] = true;
		}

		return $return;
	}

	/**
	 * ???????? ?????? ? ??????? Uniteller ?? ????????? ?????? ?????? ? ?????????? true ??? false.
	 *
	 * @param int $order_real_id
	 * @return boolean
	 */
	public static function setUnitellerCancel($order_real_id) {
		self::setMerchantData($order_real_id);
		$data = array(
			'ShopOrderNumber' => (int)$order_real_id,
			'Shop_ID' => self::$Shop_ID,
			'Login' => self::$Login,
			'Password' => self::$Password,
			'Format' => '4',
			'S_FIELDS' => 'BillNumber;Status',
		);

		$xml = self::setCurlRequest(self::$url_uniteller_results, $data);
		$array_xml = (array)$xml;
		if ($xml === false) {
			// ?????? ?? ?????? ? ???????, ???? ?????? ?????????.
			return false;
		} elseif (isset($xml->orders->order->billnumber)) {
			$Billnumber = (array)$xml->orders->order->billnumber;
			if (isset($Billnumber[0])) {
				$Billnumber = (string)$Billnumber[0];
			} else {
				// ???????? ?????, ????: ??? ?????? ???????.
				return true;
			}

			$Status = (array)$xml->orders->order->status;
			if (isset($Status[0])) {
				$Status = (string)$Status[0];
			} else {
				$Status = '';
			}
			if (strtolower($Status) == 'canceled') {
				// ???????? ?????, ????: ?????? ??????? ????? 'canceled'.
				return true;
			}
		} elseif (isset($array_xml['@attributes']['count']) && (int)$array_xml['@attributes']['count'] == 0) {
			// ???????? ?????, ????: ??????? ??? (???????? ???????? xml ? ????????? count ?????? 0)
			return true;
		} else {
			// ?????? ?? ?????? ? ???????, ???? ????????? ?????? ? ???????
			return false;
		}

		// 12 - ??? ????? Billnumber ?? ???????????? ??????????.
		if (is_string($Billnumber) && strlen($Billnumber) == 12) {
			$data = array(
				'Billnumber' => $Billnumber,
				'Shop_ID' => self::$Shop_ID,
				'Login' => self::$Login,
				'Password' => self::$Password,
				'Format' => '3',
			);

			$response = self::setCurlRequest(self::$url_uniteller_unblock, $data);
			$array_response = (array)$response;
			if ($response === false) {
				// ?????? ?? ?????? ? ???????, ???? ?????? ?????????.
				$response_code = false;
			} elseif (isset($response->orders->order->response_code)) {
				$response_code = (array)$response->orders->order->response_code;
				if (isset($response_code[0])) {
					$response_code = (string)$response_code[0];
				} else {
					$response_code = false;
				}
			} elseif (isset($array_response['@attributes']['count']) && (int)$array_response['@attributes']['count'] == 0) {
				// ?????? ?? ?????? ? ???????, ???? ??????? ??? (???????? ???????? xml ? ????????? count ?????? 0).
				$response_code = false;
			} else {
				// ?????? ?? ?????? ? ???????, ???? ????????? ?????? ? ???????.
				$response_code = false;
			}

			// ????????? ????????? ??????
			$response = (array)$response;
			if (isset($response['@attributes']['firstcode'])) {
				$response_code = (int)$response['@attributes']['firstcode'];
			}
			// ??????: ?? ?????? ?????? - ?????? ????? ????? ????????.
			if ($response_code == 4) {
				$response_code = self::RESPONSE_CODE_SUCCES;
				// ??????: ?????????? ???? ???????? ????? - ?????? ????? ????? ????????.
			} elseif ($response_code == 16) {
				$response_code = self::RESPONSE_CODE_SUCCES;
			}

			if ($response_code == self::RESPONSE_CODE_SUCCES) {
				// ???????? ??????
				return true;
			} else {
				// ?????? ?? ?????? ? ???????
				return false;
			}
		} else {
			// ???? ??? $Billnumber, ?????? ??? ???????, ?????? ????? ????? ????????.
			return true;
		}
	}

	/**
	 * ?????????????? ?????? ??????? ?? ???????? ??????.
	 *
	 * @param array $arOrder
	 * @param array $aCheckData ???? ???? ???????? ????, ?? ????? ?????? ?-??? ? ??? ????? ?????? ??? ????.
	 * @return void
	 */
	public static function doSyncStatus($arOrder, &$aCheckData = array()) {
        
        // calceled
        return false;
        
		$order_real_id = $arOrder['ID'];
		if (empty($aCheckData) || !is_array($aCheckData)) {
			$aCheckData = self::getCheckData($order_real_id);
		}

		$statusCode = self::getStatusCode($order_real_id);

		self::setMerchantData($order_real_id);

		$uniteller_paid_time = time();
		// ??????? ?????? 'Confirmed' ? 'Shipped' ???????????? ??????? ??????? 'paid'.
		if ($statusCode == 'P') {
			$uniteller_paid_time = strtotime($arOrder['PS_RESPONSE_DATE']);
		}

		if ($aCheckData['error_protocol'] === true || $aCheckData['error_count'] === true || $aCheckData['error_authentication'] === true) {
			// ?????? ????????? - ?????? ?? ??????.
			$status = 'error';
		} elseif ((int)$aCheckData['ordernumber'] == (int)$order_real_id) {
			$status = strtolower($aCheckData['status']);
		} else {
			// ??????? ???.
			$status = 'empty';
		}

		// ????? ? ????????? '?? ?????????? ???????', ? ?????? ??????????.
		if ($statusCode === 'O'
			 && ($status === 'authorized' || $status === 'paid' || $status === 'canceled')
		) {
			self::setStatusCode($order_real_id, $status);
		}
		// ????? ? ????????? '?????????? ???????', ? ?????? - ???.
		if ($statusCode === 'A'
			 && ($status === 'empty' || $status === 'paid' || $status === 'canceled')
		) {
			self::setStatusCode($order_real_id, $status);
		}
		// ????? ? ????????? '???????? ?????' ? ?? ?????? ??? ?????, ?????????? ?? ?????? ????? ??????, ? ?????? - ???.
		if ($statusCode === 'P' && $uniteller_paid_time >= self::$date_fix_paid_change
			 && ($status === 'empty' || $status === 'authorized' || $status === 'canceled')
		) {
			self::setStatusCode($order_real_id, $status);
		}
		// ????? ? ????????? '???????? ??????????', ? ?????? ? ????????? '???????? ??????????????' ??? '???????? ?????'.
		if ($statusCode === 'C'
			 && ($status === 'authorized' || $status === 'paid')
		) {
			if (!self::setUnitellerCancel($order_real_id)) {
				// ???? ???????? ?????? ?? ???????, ?? ?????? ?????? ??????.
				self::setStatusCode($order_real_id, $status);
			} else {
				// ???? ?????? ???????, ?? ?????? ???????? ?????? ??? ????.
				$aCheckData = self::getCheckData($order_real_id);
			}
		}
		// ????? ? ????????? '??????????', ? ?????? ??????????.
		if ($statusCode === 'W'
			 && ($status === 'authorized' || $status === 'paid' || $status === 'canceled')
		) {
			self::setStatusCode($order_real_id, $status);
		}
	}
    
    /**
     * ��������� � UTF-8 �� ������� ��������� �����
     */
    public static function toUtf($string) {
	    if(defined('BX_UTF') || mb_check_encoding($string, 'utf-8')) {
	        return $string;
	    }
	    else {
            $q = mb_convert_encoding($string, 'utf-8', 'windows-1251');
            //var_dump("Z");
            //var_dump($q);exit;
	        return $q;
	    }
	}
}
