<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Plugin\MdlPaygent\Form\Type\BankNetType;
use Eccube;

/**
 * 決済モジュール用 汎用関数クラス
 */
class PaymentBankNetService
{

    private $app;

    private $session;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }

    /**
     * PAY_PAYGENT_BANK
     *
     * @param unknown $Order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function bankProcess($Order) {
    	$subData = null;
    	if (is_null($Order->getCustomer())) {
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

    	$formBankNet = new BankNetType($this->app, $subData);
    	$form = $this->app['form.factory']->createBuilder($formBankNet)->getForm();
    	$error = null;
    	if ('POST' === $this->app['request']->getMethod()) {
    		try {
    			$form->handleRequest($this->app['request']);
    			if ($form->isValid()) {
    				$arrData = $Order;
    				$arrInput = $form->getData();
    				$order_id = $Order->getId();
    				// Check if token exists in transaction
    				$transactionIdName = $this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME'];
    				$transactionid = '';
    				$this->session = $this->app['session'];
    				if ($this->session->has($transactionIdName)) {
    					$transactionid = $this->session->get($transactionIdName);
    				} else {
    					// If token does not exist in
    					if (empty($transactionid)) {
    						$transactionid = sha1(uniqid(rand(), true));
    						$this->session->set($transactionIdName, $transactionid);
    					}
    				}
    				$pluginService = $this->app['eccube.plugin.service.plugin'];

    				$this->app['orm.em']->getConnection()->beginTransaction();

    				$arrRet = $this->sfSendPaygentBANK($arrData, $arrInput, $order_id, $transactionid);
    				$sqlVal = array();
    				$sqlVal['quick_flg'] = "1";
    				$quick_memo['customer_family_name'] = $arrInput['customer_family_name'];
    				$quick_memo['customer_name'] = $arrInput['customer_name'];
    				$quick_memo['customer_family_name_kana'] = $arrInput['customer_family_name_kana'];
    				$quick_memo['customer_name_kana'] = $arrInput['customer_name_kana'];
    				$sqlVal['quick_memo'] = serialize($quick_memo);

    				if (isset($arrRet['result']) && $arrRet['result'] == 0) {
    					return $this->sendData_Bank($arrRet, $order_id, $arrData['payment_total'], $sqlVal);
    				} else {
    					$error = "決済に失敗しました。". $arrRet['response'];
    					$this->app['orm.em']->getConnection()->commit();
    				}
    			}
    		} catch (Exception $ex) {
    			$this->app['orm.em']->getConnection()->rollback();
    			$this->session->delete($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);
    			return false;
    		}
    	}

    	return $this->app['view']->render('MdlPaygent/View/bank_net.twig', array(
    			'formBanknet' => $form->createView(),
    			'error' => $error,
    	));
    }

    /**
     * 関数名：sfSendPaygentBANK
     * 処理内容：銀行NET決済情報の送信
     * 戻り値：取得結果
     */
    function sfSendPaygentBANK($arrData, $arrInput, $order_id, $transactionid = null) {
    	$pluginService = $this->app['eccube.plugin.service.plugin'];
    	// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
    	$p = new PaygentB2BModule($this->app);
    	$p->init();
    	$mdlPaygentCode = $this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE'];
    	$payPaygentBank = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK'];
    	$paygentBank = $this->app['config']['MdlPaygent']['const']['PAYGENT_BANK'];
    	$transactionIdName = $this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME'];

    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	// 銀行NET用パラメータの取得
    	$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentBank);
    	$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);

    	// 共通データの取得
    	$arrSend = $pluginService->sfGetPaygentShare($paygentBank, $arrData->getId(), $arrPaymentDB[0]);

    	/** 個別電文 **/
    	// 決済金額
    	$arrSend['amount'] = $arrData['payment_total'];
    	// 請求内容カナ
    	$arrSend['claim_kana'] = mb_convert_kana($arrOtherParam['claim_kana'],'k');
    	$arrSend['claim_kana'] = preg_replace("/ｰ/", "-", $arrSend['claim_kana']);
    	// 請求内容漢字
    	// TODO : $arrOtherParam['claim_kanji']の内容がおかしい気がする・・・。
    	$arrSend['claim_kanji'] = mb_convert_kana($arrOtherParam['claim_kanji'], "KVA");
    	// 利用者姓
    	$arrSend['customer_family_name'] = mb_convert_kana($arrInput['customer_family_name'], "KVA");
    	// 利用者名
    	$arrSend['customer_name'] = mb_convert_kana($arrInput['customer_name'], "KVA");
    	// 利用者姓半角カナ
    	$arrSend['customer_family_name_kana'] = mb_convert_kana($arrInput['customer_family_name_kana'],'k');
    	$arrSend['customer_family_name_kana'] = preg_replace("/ｰ/", "-", $arrSend['customer_family_name_kana']);
    	$arrSend['customer_family_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $arrSend['customer_family_name_kana']);
    	// 利用者名半角カナ
    	$arrSend['customer_name_kana'] = mb_convert_kana($arrInput['customer_name_kana'],'k');
    	$arrSend['customer_name_kana'] = preg_replace("/ｰ/", "-", $arrSend['customer_name_kana']);
    	$arrSend['customer_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $arrSend['customer_name_kana']);
    	// PC-Mobile区分
    	/*
    	 * 0:PC
    	 * 1:docomo
    	 * 2:au
    	 * 3:softbank
    	 */
    	// TODO : 電文に存在しないパラメータ
    	$arrSend['pc_mobile_type'] = '0';
    	// 店舗名
    	// TODO : $arrOtherParam['claim_kanji']に店舗名が入っている。
    	$arrSend['merchant_name'] = mb_convert_kana($arrOtherParam['claim_kanji'], "KVA");
    	// 完了後の戻りＵＲＬ
    	$arrSend['return_url'] = $this->app->url('homepage');
    	// 戻りボタンＵＲＬ
    	$arrSend['stop_return_url'] = $this->app->url('homepage'). "?" . $transactionIdName . "=" . $transactionid;
    	// コピーライト
    	$arrSend['copy_right'] = $arrOtherParam['copy_right'];
    	// 自由メモ欄
    	$arrSend['free_memo'] = mb_convert_kana($arrOtherParam['free_memo'], "KVA");
    	// 支払期間(0DDhhmm)
    	$arrSend['asp_payment_term'] = sprintf("0%02d0000", $arrOtherParam['asp_payment_term']);

    	// 電文の送付
    	foreach($arrSend as $key => $val) {
    		// Shift-JISにエンコードする必要あり
    		$enc_val = mb_convert_encoding($val, "Shift-JIS", $this->app['config']['char_code']);
    		$p->reqPut($key, $enc_val);
    	}
    	$p->post();

    	// 応答を処理
    	$arrRet = $pluginService->sfPaygentResponse($paygentBank, $p, $order_id, $arrInput);

    	return $arrRet;
    }

    /**
     * データ送信（ネット銀行）
     */
    function sendData_Bank($arrRet, $order_id, $payment_total, $sqlVal = array()) {
    	// 成功
    	if(strlen($arrRet['asp_url']) > 0) {
    		$pluginService = $this->app['eccube.plugin.service.plugin'];
    		$paygentBank = $this->app['config']['MdlPaygent']['const']['PAYGENT_BANK'];
    		$payPaygentBank = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK'];
    		$arrInitStatus = $this->app['eccube.plugin.service.payment']->getInitStatus();
    		$order_status = $arrInitStatus[$paygentBank];

    		// 受注登録
    		$sqlVal['memo08'] = $paygentBank;
    		$pluginService->orderComplete($order_id, $sqlVal, $order_status, $payPaygentBank);

    		$this->app['eccube.service.cart']->clear()->save();
    		$this->app['session']->remove($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);
    		$this->app['orm.em']->getConnection()->commit();

    		if ($this->app['session']->has("customer-not-login")) {
    			$this->app['session']->remove("customer-not-login");
    		}

    		// ペイジェント決済画面に遷移
    		return $this->app->redirect($arrRet['asp_url']);
    	}
    }
}
