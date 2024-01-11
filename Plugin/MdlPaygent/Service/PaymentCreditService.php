<?php

/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Plugin\MdlPaygent\Form\Type\CreditType;
use Eccube;

class PaymentCreditService {
	private $app;
	private $pluginService;
	private $stock_flg;
	public function __construct(\Eccube\Application $app) {
		$this->app = $app;
		$this->pluginService = $this->app['eccube.plugin.service.plugin'];
	}


	/**
	 * 有効な支払回数を取得する
	 *
	 */
	public function getPaymentClass() {

		$paymentService = $this->app ['eccube.plugin.service.payment'];
		$arrPaymentClassAll = $paymentService->getPaymentClass ();

		//get subdata
		$arrRet = $this->app['eccube.plugin.mdl_paygent.repository.mdl_plugin']->getSubData($this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE']);

		if (isset($arrRet)) {
			$arrSubData = unserialize($arrRet);
		}
		$arrPaymentDivision = $arrSubData['payment_division'];
		$arrRet = array();
		foreach ($arrPaymentDivision as $val) {
			switch($val) {
				// 一括払い
				case '10':
					$arrRet['10'] = $arrPaymentClassAll['10'];
					break;
					// 分割払い
				case '61':
					$arrRet['61-2'] = $arrPaymentClassAll['61-2'];
					$arrRet['61-3'] = $arrPaymentClassAll['61-3'];
					$arrRet['61-6'] = $arrPaymentClassAll['61-6'];
					$arrRet['61-10'] = $arrPaymentClassAll['61-10'];
					$arrRet['61-15'] = $arrPaymentClassAll['61-15'];
					$arrRet['61-20'] = $arrPaymentClassAll['61-20'];
					break;
					// リボ払い
				case '80':
					$arrRet['80'] = $arrPaymentClassAll['80'];
					break;
					// ボーナス一括払い
				case '23':
					$arrRet['23'] = $arrPaymentClassAll['23'];
					break;
			}
		}

		return $arrRet;
	}


	/**
	 * PAY_PAYGENT_CREDIT
	 *
	 * @param $Order
	 */
	public function creditProcess($Order) {
		$error = "";
		$arrConfig = array();
		$arrData = array();
		$arrData['security_code'] = 0;
		$listData = null;
		$cnt_card = 0;

// 		$paymentService = $this->app ['eccube.plugin.service.payment'];
		$arrPaymentClass = $this->getPaymentClass();
		$subData = array (
				'arrPaymentClass' => $arrPaymentClass
		);
		// セキュリティコード入力要・不要チェック - get security config

		// 入金ステータスを更新する
		$ret = $this->app['eccube.plugin.mdl_paygent.repository.mdl_plugin']->getSubData($this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE']);
		if (isset($ret)) {
			$arrConfig = unserialize($ret);
		}
		if ($arrConfig['security_code'] == 1) {
			$arrData['security_code'] = 1;
		}

		// if customer has card created in sub data-> get payment_card from server
		$arrPaymentCard = $this->app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentCard->setConfig($this->app['config']['MdlPaygent']['const']);
		if(is_null( $Order->getCustomer())){
			$arrDataToGetCard ['customer_id'] = null;
		}else{
			$arrDataToGetCard ['customer_id'] = $Order->getCustomer()->getId();
		}
		if ($arrConfig['stock_card'] == 1) {
			$paymentReturn = $this->getStockCardData ( $arrDataToGetCard );
			if ($paymentReturn [0] ['result'] === "0"){
				for($i = 1; $i<count($paymentReturn); $i++){
					$listData[$i]['cardSeq'] = $paymentReturn[$i]['customer_card_id'];
					$listData[$i]['card_number'] = $paymentReturn[$i]['card_number'];
					$listData[$i]['card_valid_term'] = $this->convertMonthYeah($paymentReturn[$i]['card_valid_term']);
					$listData[$i]['cardholder_name'] = $paymentReturn[$i]['cardholder_name'];
				}
			}else{
				$listData = null;
			}
		}

		$arrData['stock_flg'] = $this->stock_flg;
		$this->stock_flg = 0;

		$stock = null;
		$cardSeq = null;
		if(!empty($_POST['stockFlag']))
		{
			$stock = $_POST['stockFlag'];
		}
		if(!empty($_POST['cardSeq']))
		{
			$cardSeq = $_POST['cardSeq'];
		}
		
		//get token
		$token_pay = 0;
		if(isset($arrConfig['token_pay'])){
			$token_pay = $arrConfig['token_pay'];
		}
		$token_key = $arrConfig['token_key'];
		$merchant_id = $arrConfig['merchant_id'];
		$token_env = $arrConfig['token_env'];
		
		$CreditType = new CreditType ( $this->app, $subData, $stock, $token_pay, $arrData['security_code']);
		$form = $this->app ['form.factory']->createBuilder ( $CreditType )->getForm ();
		//check deletecard for no card checked.
		$arrNoCardCheckedFlg = $this->app['session']->get('cardSeqNochecked');
		$this->app['session']->remove('cardSeqNochecked');
		if(!is_null($arrNoCardCheckedFlg)){
			return $this->app ['view']->render ('MdlPaygent/View/credit.twig', array (
					'formCredit' => $form->createView (),
					'list_data' => $listData,
					'arrData' => $arrData,
					'token_pay' => $token_pay,
					'token_key' => $token_key,
					'merchant_id' => $merchant_id,
					'token_env' => $token_env,
					'error_card' => '※ 削除カードが入力されていません。',
			));
		}
		if ('POST' === $this->app ['request']->getMethod ()) {
			$form->handleRequest ( $this->app ['request'] );
			if ($form->isValid ()) {
				$orderId = $Order->getId ();
				$arrInput = $form->getData ();
				$arrInput['cardSeq'] = $cardSeq;
				
				//set arrInput to session:
				//true =1; false =0;
				if($arrInput['stock_new'] == 'true'){
					$arrInput['stock_new'] = 1;
				}else{
					$arrInput['stock_new'] = 0;
				}
				if($arrInput['stock'] =='false'){
					$arrInput['stock'] = 1;
				}else{
					$arrInput['stock'] = 0;
				}
				$arrData ['payment_total'] = $Order->getPaymentTotal ();
				if (is_null($Order->getCustomer())){
					$arrData ['customer_id'] = null;
				}else{
					$arrData ['customer_id'] = $Order->getCustomer()->getId ();
				}
				$arrData ['order_id'] = $orderId;
				$arrData['create_date'] = $Order->getCreateDate()->format('Y-m-d H:i:s');
				$transactionid = sha1 ( uniqid ( rand (), true ) );

				$arrRet = $this->sfSendPaygentCredit ( $arrData, $arrInput, $orderId, $transactionid );

				if($arrRet === false){
					return $this->app ['view']->render ('MdlPaygent/View/credit.twig', array (
							'formCredit' => $form->createView (),
							'list_data' => $listData,
							'arrData' => $arrData,
							'token_pay' => $token_pay,
							'token_key' => $token_key,
							'merchant_id' => $merchant_id,
							'token_env' => $token_env,
							'error' => '※ 登録カードが入力されていません。',
					));
				}
				$result = $this->sendData_Credit($arrRet, $arrData, $arrInput);

				if ($arrRet ['result'] == 0 && $result == true) {
					//remove card order
					$this->app['eccube.service.cart']->clear()->save();
					return $this->app->redirect($this->app->url ( 'shopping_complete' ));
				} else {
					$error = "決済に失敗しました。".$arrRet ['response'];
				}
			}
		}
		if(!is_null($this->app['session']->get('credit3dError'))){
			$error = $this->app['session']->get('credit3dError');
			//remove session
			$this->app['session']->remove('credit3dError');
		}
		return $this->app ['view']->render ( 'MdlPaygent/View/credit.twig', array (
				'formCredit' => $form->createView (),
				'list_data' => $listData,
				'error' => $error,
				'token_pay' => $token_pay,
				'token_key' => $token_key,
				'merchant_id' => $merchant_id,
				'token_env' => $token_env,
				'arrData' => $arrData,
		) );
	}

	function sfSendPaygentCredit($arrData, $arrInput, $order_id, $transactionid) {
		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$app = $this->app;
		$p = new PaygentB2BModule ( $app );
		$p->init ();
		// クレジット用パラメータの取得

		$paygentCreditConst = $this->app ['config'] ['MdlPaygent'] ['const'] ['PAY_PAYGENT_CREDIT'];
		$paygentCredit = $app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CREDIT'];
		$TRANSACTION_ID_NAME = $app ['config'] ['MdlPaygent'] ['const'] ['TRANSACTION_ID_NAME'];

		$arrPaymentDB = $app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentDB->setConfig ( $this->app ['config'] ['MdlPaygent'] ['const'] );

		$arrPaymentDB = $arrPaymentDB->getPaymentDB ( $paygentCreditConst );

		$arrOtherParam = unserialize ( $arrPaymentDB [0] ['other_param'] );
		// 共通データの取得
		$arrSend = $this->pluginService->sfGetPaygentShare ( $paygentCredit, $order_id, $arrPaymentDB [0] );

		/**
		 * 個別電文 *
		 */
		// 決済金額
		$arrSend ['payment_amount'] = $arrData ['payment_total'];
		// カード番号
		$arrSend ['card_number'] = $arrInput ['card_no01'] . $arrInput ['card_no02'] . $arrInput ['card_no03'] . $arrInput ['card_no04'];
		// セキュリティコード
		$arrSend ['card_conf_number'] = $arrInput ['security_code'];
		// カード有効期限(MMYY)
		$arrSend ['card_valid_term'] = $arrInput ['card_month'] . $arrInput ['card_year'];
		// トークン
		$arrSend['card_token'] = $arrInput['card_token'];
		// 支払い区分
		/*
		 * 10:1回
		 * 23:ボーナス1回
		 * 61:分割
		 * 80:リボルビング
		 */
		if (strpos ( $arrInput ['payment_class'], '-' ) !== false) {
			list ( $payment_class, $split_count ) = split ( "-", $arrInput ['payment_class'] );
			$arrSend ['payment_class'] = $payment_class;
			// 分割回数
			$arrSend ['split_count'] = $split_count;
		} else {
			$arrSend ['payment_class'] = $arrInput ['payment_class'];
		}
		/**
		 * 3Dセキュア関連 *
		 */
		if ($arrOtherParam ['credit_3d'] != 1) {
			// 3Dセキュア不要区分
			$arrSend ['3dsecure_ryaku'] = "1";
		} else {
			// HttpAccept
			if (isset ( $_SERVER ['HTTP_ACCEPT'] )) {
				$arrSend ['http_accept'] = $_SERVER ['HTTP_ACCEPT'];
			} else {
				$arrSend ['http_accept'] = "*/*";
			}
			// HttpUserAgent
			$arrSend ['http_user_agent'] = $_SERVER ['HTTP_USER_AGENT'];
			// 3Dセキュア戻りURL
			$arrSend ['term_url'] = $app->url('homepage');
			$arrSend ['term_url'] .= "shopping/mdl_paygent?mode=credit_3d&order_id=" . $order_id . "&" . $TRANSACTION_ID_NAME . "=" . $transactionid . '&hash=' . $this->pluginService->createPaygentHash($order_id, $arrData['create_date']);
		}
		/**
		 * カード情報お預かり機能 *
		 */
		if (isset ( $arrInput ['stock'] )) {
			if ($arrInput ['stock'] == 1) {
				if (isset ( $arrInput ['cardSeq'] )) {
					// 不要
					unset ( $arrSend ['card_number'] );
					unset ( $arrSend ['card_valid_term'] );
					// カード情報お預かりモード
					$arrSend ['stock_card_mode'] = "1";
					// 顧客ID
					$arrSend ['customer_id'] = $arrData ['customer_id'];
					// 顧客カードID
					$arrSend ['customer_card_id'] = $arrInput ['cardSeq'];
				}else{
					return false;
				}
			}
		}
		// 電文の送付
		foreach ( $arrSend as $key => $val ) {
			$p->reqPut ( $key, $val );
		}
		$p->post ();
		// 応答を処理
		$arrRet = $this->pluginService->sfPaygentResponse ( $paygentCredit, $p, $arrData ['order_id'], $arrInput );
		$paymentId = $arrRet['payment_id'];
		//set sesstion to send paymentController
		$this->app['session']->set('payment_id_server', $paymentId);
		$this->app['session']->set('order_id', $order_id);

		return $arrRet;
	}

	/**
	 * 登録カード情報取得
	 */
	public function getStockCardData($arrData) {
		// 登録者の確認
		$arrRet = array();
		$arrPaymentCard = $this->app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentCard->setConfig ( $this->app ['config'] ['MdlPaygent'] ['const'] );
		$customerId =$arrData ['customer_id'];
        
		$ret = $arrPaymentCard->getPaymentCardCustomerById ( $customerId );
		// 登録者の情報取得
		if (count ( $ret ) > 0) {
			$this->stock_flg = 1;
			if ($ret [0] ['paygent_card'] == 1) {
				$arrRet = $this->sfGetPaygentCreditStock ( $arrData );
				if(count($arrRet) >=6){
					$this->stock_flg = 0;
				}
				return $arrRet;
			}
		}
	}
	function sfGetPaygentCreditStock($arrData) {
		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$arrPaymentCard = $this->app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentCard->setConfig($this->app['config']['MdlPaygent']['const']);

		$PAYGENT_CARD_STOCK_GET = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_STOCK_GET'];
		$MDL_PAYGENT_CODE = $this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE'];

		$p = new PaygentB2BModule ($this->app);
		$p->init ();

		// 設定パラメータの取得
		$arrPaymentDB = $arrPaymentCard->getPaymentDB();

		// 共通データの取得
		$arrSend = $this->pluginService->sfGetPaygentShare ( $PAYGENT_CARD_STOCK_GET, 0, $arrPaymentDB [0] );
		/**
		 * 個別電文 *
		 */
		// 顧客ID
		$arrSend ['customer_id'] = $arrData ['customer_id'];
		// 顧客カードID

		// 電文の送付
		foreach ( $arrSend as $key => $val ) {
			$p->reqPut ( $key, $val );
		}
		$p->post ();
		// 応答を処理
		$arrRet = $this->pluginService->sfPaygentResponseCard ( $PAYGENT_CARD_STOCK_GET, $p, $arrData['customer_id'] );

		return $arrRet;
	}

	/**
	 * データ送信（クレジット）
	 */
	public function sendData_Credit($arrRet, $arrData, $arrInput) {
		$this->quick_flg = "0";
		$this->cardSeq = "";
		$stock_new = $arrInput ['stock_new'];
		$stock = $arrInput ['stock'];
		// カード登録
		if ($stock_new == 1 && $stock != 1 && ($arrRet ['result'] === "0" || $arrRet ['result'] === "7")) {
			$arrRetStock = $this->sfSetPaygentCreditStock ( $arrData, $arrInput );
			if (isset($arrRetStock [0] ['result']) && $arrRetStock [0] ['result'] == "0") {
				$this->quick_flg = "1";
				$this->cardSeq = $arrRetStock [1] ['customer_card_id'];
			}
		}
		// 成功（3Dセキュア未対応）
		if ($arrRet ['result'] === "0") {
			$PAYGENT_CREDIT = $this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT'];
			$sqlVal = array ();
			$sqlVal ['memo08'] = $PAYGENT_CREDIT;
			if ($arrInput ['stock'] == 1) {
				$this->quick_flg = "1";
			}
			$sqlVal ['quick_flg'] = $this->quick_flg;

			if (strpos ( $arrInput ['payment_class'], '-' ) !== false) {
				list ( $payment_class, $split_count ) = split ( "-", $arrInput ['payment_class'] );
				$quick_memo ['payment_class'] = $payment_class;
				// 分割回数
				$quick_memo ['split_count'] = $split_count;
			} else {
				$quick_memo ['payment_class'] = $arrInput ['payment_class'];
			}

			if ($arrInput ['stock'] == 1) {
				$quick_memo ['CardSeq'] = $arrInput ['cardSeq'];
			} else if ($this->quick_flg == "1") {
				$quick_memo ['CardSeq'] = $this->cardSeq;
			}
			$sqlVal ['quick_memo'] = serialize ( $quick_memo );
			return $this->pluginService->orderComplete ( $arrData ['order_id'], $sqlVal );
			// 成功（3Dセキュア対応）
		} elseif ($arrRet ['result'] === "7") {
			// 正常に登録されたことを記録
			$sqlVal = array ();
			if ($this->quick_flg == "1" || $arrInput ['stock'] == 1) {
				$sqlVal ['quick_flg'] = "1";
			}

			if (strpos ( $arrInput ['payment_class'], '-' ) !== false) {
				list ( $payment_class, $split_count ) = split ( "-", $arrInput ['payment_class'] );
				$quick_memo ['payment_class'] = $payment_class;
				// 分割回数
				$quick_memo ['split_count'] = $split_count;
			} else {
				$quick_memo ['payment_class'] = $arrInput ['payment_class'];
			}
			if ($arrInput ['stock'] == 1) {
				$quick_memo ['CardSeq'] = $arrInput ['cardSeq'];
			} else if ($this->quick_flg == "1") {
				$quick_memo ['CardSeq'] = $this->cardSeq;
			}
			$sqlVal ['quick_memo'] = serialize ( $quick_memo );
			$this->pluginService->registerOrder ( $arrData ['order_id'], $sqlVal );
			$CHAR_CODE = $this->app['config']['char_code'];
			// カード会社画面へ遷移（ACS支払人認証要求HTMLを表示）
			print mb_convert_encoding ( $arrRet ['out_acs_html'], $CHAR_CODE, "Shift-JIS" );
			// plugin統合対応
            $this->systemService = $this->app ['eccube.plugin.service.system'];
            $response = $this->systemService->procExitResponse(null, new Response(
                    'Content',
                    Response::HTTP_OK,
                    array('content-type' => 'text/html')
            ));
            return $response;
			// 失敗
		} else {
			return false;
		}
	}


	/**
	 * 関数名：sfSetPaygentCreditStock
	 * 処理内容：カード情報の設定
	 * 戻り値：取得結果
	 */
	function sfSetPaygentCreditStock($arrData, $arrInput) {
		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$p = new PaygentB2BModule($this->app);
		$p->init();

		// 設定パラメータの取得
		$arrPaymentCard = $this->app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentCard->setConfig($this->app['config']['MdlPaygent']['const']);
		$arrPaymentDB = $arrPaymentCard->getPaymentDB();

		// 共通データの取得
		$PAYGENT_CARD_STOCK_SET = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_STOCK_SET'];
		$paygentCredit = $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CREDIT'];
		$arrSend = $this->pluginService->sfGetPaygentShare ( $PAYGENT_CARD_STOCK_SET, 0, $arrPaymentDB [0] );

		/** 個別電文 **/
		// 顧客ID
		$arrSend['customer_id'] = $arrData['customer_id'];
		// カード番号
		$arrSend['card_number'] = $arrInput['card_no01'].$arrInput['card_no02'].$arrInput['card_no03'].$arrInput['card_no04'];
		// カード有効期限(MMYY)
		$arrSend['card_valid_term'] = $arrInput['card_month'].$arrInput['card_year'];
		// カード名義人
		$arrSend['cardholder_name'] = ($arrInput['card_name01'] || $arrInput['card_name02']) ? $arrInput['card_name01']." ".$arrInput['card_name02'] : "";
		// トークン
		$arrSend['card_token'] = $arrInput['card_token_stock'];

		// 電文の送付
		foreach($arrSend as $key => $val) {
			$p->reqPut($key, $val);
		}
		$p->post();
		// 応答を処理
		$arrRet = $this->pluginService->sfPaygentResponseCard ( $PAYGENT_CARD_STOCK_SET, $p, $arrData['customer_id'] );

		return $arrRet;
	}

	function sfDelPaygentCreditStock($arrData, $arrInput) {
		$arrPaymentCard = $this->app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentCard->setConfig($this->app['config']['MdlPaygent']['const']);

		$PAYGENT_CARD_STOCK_DEL = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_STOCK_DEL'];

		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$p = new PaygentB2BModule($this->app);
		$p->init();

		// 設定パラメータの取得
		$arrPaymentDB = $arrPaymentCard->getPaymentDB();

		// 共通データの取得
		$arrSend = $this->pluginService->sfGetPaygentShare($PAYGENT_CARD_STOCK_DEL, 0, $arrPaymentDB[0]);

		/** 個別電文 **/
		// 顧客ID
		$arrSend['customer_id'] = $arrData['customer_id'];
		// 顧客カードID
		$arrSend['customer_card_id'] = $arrInput['cardSeq'];

		// 電文の送付
		foreach($arrSend as $key => $val) {
			$p->reqPut($key, $val);
		}
		$p->post();
		// 応答を処理
		$arrRet = $this->pluginService->sfPaygentResponseCard($PAYGENT_CARD_STOCK_DEL, $p, $arrData['customer_id']);

		return $arrRet;
	}

	function sfSendPaygentCredit3d($arrData, $arrInput, $order_id) {
		$arrPaymentCard = $this->app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentCard->setConfig($this->app['config']['MdlPaygent']['const']);
		$PAY_PAYGENT_CREDIT = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CREDIT'];
		$PAYGENT_CREDIT = $this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT'];
		$PAYGENT_CARD_3D = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_3D'];

		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$p = new PaygentB2BModule($this->app);
		$p->init();

		// クレジット用パラメータの取得
		$arrPaymentDB = $arrPaymentCard->getPaymentDB($PAY_PAYGENT_CREDIT);
		$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);

		// 共通データの取得
		$arrSend = $this->pluginService->sfGetPaygentShare($PAYGENT_CARD_3D, $arrData['order_id'], $arrPaymentDB[0], $arrData['memo06']);

		/** 個別電文 **/
		// ACS応答
		$arrInput['PaRes'] = $_POST['PaRes'];

		$arrSend['PaRes'] = $arrInput['PaRes'];
		// マーチャントデータ
		$arrSend['MD'] = $arrInput['MD'];

		// 電文の送付
		foreach($arrSend as $key => $val) {
			$p->reqPut($key, $val);
		}
		$p->post();
		// 応答を処理
		$arrRet = $this->pluginService->sfPaygentResponse($PAYGENT_CREDIT, $p, $order_id, $arrInput);

		return $arrRet;
	}
	function convertMonthYeah($str){
		if(!is_null($str)){
			$month = substr($str, 0, 2);
			$year = substr($str, 2, 3);
			$monthYeahConverted = $month."月/".$year."年";
			return $monthYeahConverted;
		}
		return str;
	}

}
