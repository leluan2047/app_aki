<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Plugin\MdlPaygent\Form\Type\VirtualAccountType;
use Eccube;
use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class PaymentVirtualAccountService extends AbstractMigration
{
	private $app;
	private $pluginService;

	public function __construct(\Eccube\Application $app)
	{
		$this->app = $app;
		$this->pluginService = $this->app['eccube.plugin.service.plugin'];
	}
	
	/**
	 * @param Schema $schema
	 */
	public function up(Schema $schema)
	{
		
	}
	
	/**
	 * @param Schema $schema
	 */
	public function down(Schema $schema)
	{
		
	
	}


	/**
	 * PAY_PAYGENT_ATM
	 * @param unknown $Order
	 */
	public function virtualAccountProcess($Order) {
		$error = "";
		//check customer null
		$subData = null;
		if(is_null($Order->getCustomer())){
			$Customer = $this->app['session']->get('customer-not-login');
			$subData = array(
					'billing_family_name'=>$Customer->getName01(),
					'billing_name'=>$Customer->getName02(),
					'billing_family_name_kana'=>$Customer->getKana01(),
					'billing_name_kana'=>$Customer->getKana02(),
			);
		} else {
			$subData = array(
					'billing_family_name'=>$Order->getCustomer()->getName01(),
					'billing_name'=>$Order->getCustomer()->getName02(),
					'billing_family_name_kana'=>$Order->getCustomer()->getKana01(),
					'billing_name_kana'=>$Order->getCustomer()->getKana02(),
			);
		}

		$formVirtualSettlement = new VirtualAccountType ( $this->app, $subData);
		$form = $this->app ['form.factory']->createBuilder ( $formVirtualSettlement )->getForm ();

		if ('POST' === $this->app ['request']->getMethod ()) {
			$form->handleRequest ( $this->app ['request'] );
			if ($form->isValid ()) {
				$paygentVirtualAcc = $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_VIRTUAL_ACCOUNT'];
				$orderId = $Order->getId ();
				$paymentTotal = $Order->getPaymentTotal ();

				$formData = $form->getData ();
				
				$arrRet = $this->sfSendPaygentVirtualAccount ( $formData );
				//set value to session
				$this->app['session']->set('dataVAReturn', $arrRet);
				$sqlVal = array ();
				$sqlVal ['quick_flg'] = "1";
				$quick_memo ['billing_family_name'] = mb_convert_kana ( $formData ['billing_family_name'], "KVA" );
				$quick_memo ['billing_name'] = mb_convert_kana ( $formData ['billing_name'], "KVA" );
				$quick_memo ['billing_family_name_kana'] = $formData ['billing_family_name_kana'];
				$quick_memo ['billing_name_kana'] = $formData ['billing_name_kana'];
				$sqlVal ['quick_memo'] = serialize ( $quick_memo );
				if ($arrRet ['result'] == 0) {
					$result = $this->pluginService->sendData($arrRet, $paymentTotal, $orderId, $paygentVirtualAcc, $sqlVal);
					if ($result) {
						//remove card here
						$this->app['eccube.service.cart']->clear()->save();
						$this->app['session']->set('orderIdVA', $orderId);

						return $this->app->redirect($this->app['url_generator']->generate('shopping_complete'));
					}
				} else {
					if(isset($arrRet['code']) && $arrRet['code'] == "8001") {
						$error = "現在、このお支払方法をご利用いただけません。<br>お手数ですが別のお支払方法をお選びください。";
					} else {
						$error = "決済に失敗しました。". $arrRet['response'];
					}
				}
			}
		}
		return $this->app['view']->render('MdlPaygent/View/paygent_virtual_account.twig', array(
				'formVirutalSettlement' => $form->createView(),
				'error' => $error,
		));
	}
	
	/**
	 * 関数名：sfSendPaygentVirtualAccount
	 * 処理内容：仮想口座決済情報の送信
	 * 戻り値：取得結果
	 */
	function sfSendPaygentVirtualAccount($arrInput) {
		$app = $this->app;
		$Order = $app['eccube.repository.order']->findOneBy(array('pre_order_id' => $app['eccube.service.cart']->getPreOrderId()));
		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$p = new PaygentB2BModule($app);
		$p->init();
		
		// ペイジェント 銀行振込
		$arrPaymentDB = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
		
		$arrPaymentDB->setConfig($this->app['config']['MdlPaygent']['const']);
		
		$paygentVAConst = $this->app ['config'] ['MdlPaygent'] ['const'] ['PAY_PAYGENT_VIRTUAL_ACCOUNT'];
		
		$payment = $arrPaymentDB->getPaymentDB($paygentVAConst);
		
		$arrOtherParam = unserialize($payment[0]['other_param']);
		
		$paygentVA = $app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'];
		$telegramVersion= $app['config']['MdlPaygent']['const']['TELEGRAM_VERSION'];
		$orderId = $Order->getId();
		// 共通データの取得
		$arrSend = $this->pluginService->sfGetPaygentShare($paygentVA, $orderId, $payment[0]);
		
		/** 個別電文 **/
		// 請求金額
		$arrSend['claim_amount'] = $Order->getPaymentTotal();
		// 請求先名
		$billing_name = $arrInput['billing_family_name'] . $arrInput['billing_name'];
		$arrSend['billing_name'] = mb_convert_kana($billing_name, "KVA");
		// 請求先名ｶﾅ
		$arrSend['billing_name_kana'] = $arrInput['billing_family_name_kana'] . $arrInput['billing_name_kana'];
		$arrSend['billing_name_kana'] = mb_convert_kana($arrSend['billing_name_kana'],'k');
		$arrSend['billing_name_kana'] = preg_replace("/ｰ/", "-", $arrSend['billing_name_kana']);
		$arrSend['billing_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $arrSend['billing_name_kana']);
		// 支払期限日数
		$arrSend['expire_days'] = $arrOtherParam['payment_limit_date'];
	
		$is_fix = false;
		$is_first = false;
		
		if($Order->getCustomer()){
			$customerId = $Order->getCustomer()->getId();
			if (0 < $customerId) {
				// ログインユーザ
				$arrCustomer = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod')->sfGetCustomerData($customerId);
				$arrCustomer = reset($arrCustomer);
				
				if (!empty($arrCustomer['virtual_account_bank_code']) &&
					!empty($arrCustomer['virtual_account_branch_code']) &&
					!empty($arrCustomer['virtual_account_number'])) {
						// 仮想口座番号発番済み
						$is_fix = true;
				} else if ($arrOtherParam['numbering_type'] == '1') {
					// 仮想口座番号未発番 && 付番区分："1"固定付番
					$is_fix = true;
					$is_first = true;
				}
			}
		}
	
		if ($is_fix) {
			/** 固定付番 **/
			// 付番区分
			$arrSend['numbering_type'] = '1';
			// 固定付番登録先
			$arrSend['fix_numbering_reg'] = $customerId;
			if (!$is_first) {
				// 仮想口座金融機関
				$arrSend['virtual_account_bank_code'] = $arrCustomer['virtual_account_bank_code'];
				// 仮想口座支店
				$arrSend['virtual_account_branch_code'] = $arrCustomer['virtual_account_branch_code'];
				// 仮想口座番号
				$arrSend['virtual_account_number'] = $arrCustomer['virtual_account_number'];
			}
		} else {
			/** 回転付番 **/
			// 付番区分
			$arrSend['numbering_type'] = '0';
		}
		// 電文の送付
		foreach($arrSend as $key => $val) {
			// Shift-JISにエンコードする必要あり
			$enc_val = mb_convert_encoding($val, "Shift-JIS", $app['config']['char_code']);
			$p->reqPut($key, $enc_val);
		}
		$p->post();
		// 応答を処理
		$arrRet = $this->pluginService->sfPaygentResponse($paygentVA, $p, $orderId, $arrInput);
		if ($arrRet['result'] == "0") {
			// 処理結果コードが0の場合は顧客情報を更新
			$retDB = $this->app['orm.em']
			->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod')
			->deleteVirtualAccountInf(
					$customerId,
					$arrRet['virtual_account_bank_code'],
					$arrRet['virtual_account_branch_code'],
					$arrRet['virtual_account_number']);
			if ($is_first) {
				$retDB = $this->app['orm.em']
				->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod')
				->updateVirtualAccountInfo( 
						$customerId,
						$arrRet['virtual_account_bank_code'],
						$arrRet['virtual_account_branch_code'],
						$arrRet['virtual_account_number']);
			}
		}
		return $arrRet;
	}

}