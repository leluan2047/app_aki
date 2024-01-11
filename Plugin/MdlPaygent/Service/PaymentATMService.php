<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Plugin\MdlPaygent\Form\Type\ATMSettlementType;
use Eccube;

class PaymentATMService
{
	private $app;
	private $pluginService;

	public function __construct(\Eccube\Application $app)
	{
		$this->app = $app;
		$this->pluginService = $this->app['eccube.plugin.service.plugin'];
	}


	/**
	 * PAY_PAYGENT_ATM
	 * @param unknown $Order

	 */
	public function ATMProcess($Order) {
		$error = "";
		//check customer null
		$subData = null;
		if(is_null($Order->getCustomer())){
			$Customer = $this->app['session']->get('customer-not-login');
			$subData = array(
					'customer_family_name'=>$Customer->getName01(),
					'customer_name'=>$Customer->getName02(),
					'customer_family_name_kana'=>$Customer->getKana01(),
					'customer_name_kana'=>$Customer->getKana02(),
			);
		} else {
			$subData = array(
					'customer_family_name'=>$Order->getCustomer()->getName01(),
					'customer_name'=>$Order->getCustomer()->getName02(),
					'customer_family_name_kana'=>$Order->getCustomer()->getKana01(),
					'customer_name_kana'=>$Order->getCustomer()->getKana02(),
			);
		}

		$formATMSettlement = new ATMSettlementType ( $this->app, $subData);
		$form = $this->app ['form.factory']->createBuilder ( $formATMSettlement )->getForm ();

		if ('POST' === $this->app ['request']->getMethod ()) {
			$form->handleRequest ( $this->app ['request'] );
			if ($form->isValid ()) {
				$paygentATM = $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_ATM'];
				$orderId = $Order->getId ();
				$paymentTotal = $Order->getPaymentTotal ();

				$formData = $form->getData ();

				$arrRet = $this->sfSendPaygentATM ( $formData );
				//set value to session
				$this->app['session']->set('dataReturn', $arrRet);
				$sqlVal = array ();
				$sqlVal ['quick_flg'] = "1";
				$quick_memo ['customer_family_name'] = mb_convert_kana ( $formData ['customer_family_name'], "KVA" );
				$quick_memo ['customer_name'] = mb_convert_kana ( $formData ['customer_name'], "KVA" );
				$quick_memo ['customer_family_name_kana'] = $formData ['customer_family_name_kana'];
				$quick_memo ['customer_name_kana'] = $formData ['customer_name_kana'];
				$sqlVal ['quick_memo'] = serialize ( $quick_memo );

				if ($arrRet ['result'] == 0) {
					$result = $this->pluginService->sendData($arrRet, $paymentTotal, $orderId, $paygentATM, $sqlVal);
					if ($result) {
						//remove card here
						$this->app['eccube.service.cart']->clear()->save();
						$this->app['session']->set('orderIdATM', $orderId);

						return $this->app->redirect($this->app['url_generator']->generate('shopping_complete'));
					}
				} else {
					$error = $result;
				}
			}
		}
		return $this->app['view']->render('MdlPaygent/View/atm_settlement.twig', array(
				'formATMSettlement' => $form->createView(),
				'error' => $error,
		));
	}

	function sfSendPaygentATM($formData) {
		$app = $this->app;
		$Order = $app['eccube.repository.order']->findOneBy(array('pre_order_id' => $app['eccube.service.cart']->getPreOrderId()));
		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$p = new PaygentB2BModule($app);
		$p->init();

		// ATM決済用パラメータの取得
		$arrPaymentDB = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
		$arrPaymentDB->setConfig($this->app['config']['MdlPaygent']['const']);

		$paygentATMConst = $this->app ['config'] ['MdlPaygent'] ['const'] ['PAY_PAYGENT_ATM'];

		$payment = $arrPaymentDB->getPaymentDB($paygentATMConst);
		$arrOtherParam = unserialize($payment[0]['other_param']);

		$paygentATM = $app['config']['MdlPaygent']['const']['PAYGENT_ATM'];
		$telegramVersion= $app['config']['MdlPaygent']['const']['TELEGRAM_VERSION'];
		$orderId = $Order->getId();
		// 共通データの取得

		$arrSend = $this->pluginService->sfGetPaygentShare($paygentATM, $orderId, $payment[0]);

		/** 個別電文 **/
		// 決済金額
		$arrSend['payment_amount'] = $Order->getPaymentTotal();
		// 利用者姓
		$arrSend['customer_family_name'] = mb_convert_kana($formData['customer_family_name'],"KVA");
		// 利用者名
		$arrSend['customer_name'] = mb_convert_kana($formData['customer_name'],"KVA");
		// 利用者姓半角カナ
		$arrSend['customer_family_name_kana'] = mb_convert_kana($formData['customer_family_name_kana'],'k');
		$arrSend['customer_family_name_kana'] = preg_replace("/ｰ/", "-", $formData['customer_family_name_kana']);
		$arrSend['customer_family_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $formData['customer_family_name_kana']);
		// 利用者名半角カナ
		$arrSend['customer_name_kana'] = mb_convert_kana($formData['customer_name_kana'],'k');
		$arrSend['customer_name_kana'] = preg_replace("/ｰ/", "-", $formData['customer_name_kana']);
		$arrSend['customer_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $formData['customer_name_kana']);
		// 決済内容
		$arrSend['payment_detail'] = $arrOtherParam['payment_detail'];
		// 決済内容半角カナ
		$arrSend['payment_detail_kana'] = mb_convert_kana($arrOtherParam['payment_detail'],'k');
		$arrSend['payment_detail_kana'] = preg_replace("/ｰ/", "-", $arrSend['payment_detail_kana']);
		// 支払期限日
		$arrSend['payment_limit_date'] = $arrOtherParam['payment_limit_date'];

		// 電文の送付
		foreach($arrSend as $key => $val) {
			// Shift-JISにエンコードする必要あり
			$enc_val = mb_convert_encoding($val, "Shift-JIS", $app['config']['char_code']);
			$p->reqPut($key, $enc_val);
		}
		$p->post();
		// 応答を処理
		$arrRet = $this->pluginService->sfPaygentResponse($paygentATM, $p, $orderId, $formData);

		return $arrRet;
	}


}