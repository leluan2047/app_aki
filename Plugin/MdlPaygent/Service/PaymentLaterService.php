<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Eccube;

/**
 * 決済モジュール用 汎用関数クラス
 */
class PaymentLaterService
{

    private $app;

    private $session;

    private $tpl_exam_error = null;

    private $tpl_length_error = null;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }

    /**
     * PAY_PAYGENT_LATER_PAYMENT
     * @param unknown $Order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function laterPaymentProcess($Order)
    {
    	$error = null;

    	if ('POST' === $this->app['request']->getMethod()) {
    		try {
    			$paymentService = $this->app['eccube.plugin.service.payment'];
    			$paygentLaterPayment = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT'];
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

    			$arrData = $Order;
    			$arrInput = array();

    			$invoiceSendType = $paymentService->getInvoiceSendType($arrData->getId());
    			
    			$arrRet = $this->sfSendPaygentLaterPayment($arrData, $arrInput, $transactionid, $invoiceSendType);
    			
    			$retDB = $this->app['orm.em']
    			->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod')
    			->updateInvoiceSendType($invoiceSendType, $arrData->getId());
    			
    			$sqlVal = array();
    			$sqlVal['quick_flg'] = "1";

    			$result = $this->sendData_LaterPayment($arrRet, $arrData->getPaymentTotal(), $arrData->getId(), $paygentLaterPayment, $sqlVal, $invoiceSendType);

    			if ($result === true) {
    				$this->app['eccube.service.cart']->clear()->save();
    				$this->session->remove($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);
    				$this->app['orm.em']->getConnection()->commit();

    				$this->app['session']->set('payment_id', $Order->getPayment()->getId());
    				$this->app['session']->set('orderIdLater', $Order->getId());

    				return $this->app->redirect($this->app['url_generator']->generate('shopping_complete'));
    			} else {
    				$error = "決済に失敗しました。". $arrRet['response'];
    				$this->app['orm.em']->getConnection()->commit();
    			}
    		} catch (Exception $ex) {
    			$this->app['orm.em']->getConnection()->rollback();
    			$this->session->delete($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);
    			return false;
    		}
    	}
    	$imgPath =  $this->app["config"]["root"].'plugin/mdl_pg/banner_atodene_pc.gif';
    	return $this->app['view']->render('MdlPaygent/View/later_payment.twig', array(
    			'title' => $Order->getPayment()->getMethod(),
    			'error' => $error,
    			'charge' => $Order->getPayment()->getCharge(),
    			'tpl_exam_error'=> $this->tpl_exam_error,
    			'tpl_length_error'=> $this->tpl_length_error,
    			'img_path'=>$imgPath,
    	));
    }

    /**
     * 関数名：sfSendPaygentLaterPayment
     * 処理内容：後払い決済情報の送信
     * 戻り値：取得結果
     */
    function sfSendPaygentLaterPayment($arrData, $arrInput, $uniqid, $invoiceSendType) {
    	// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
    	$p = new PaygentB2BModule($this->app);
    	$p->init();

    	// 後払い決済用パラメータの取得
    	$pluginService = $this->app['eccube.plugin.service.plugin'];
    	$paymentService = $this->app['eccube.plugin.service.payment'];
    	$payPaygentLaterPayment = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT'];
    	$paygentLaterPayment = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT'];


    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	// 銀行NET用パラメータの取得
    	$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentLaterPayment);
    	$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);

    	// 共通データの取得
    	$arrSend = $pluginService->sfGetPaygentShare($paygentLaterPayment, $arrData->getId(), $arrPaymentDB[0]);

    	/** 個別電文 **/
    	$arrSend += $pluginService->sfGetPaygentLaterPaymentModule($arrData->getId());
    	// 結果取得区分
    	$arrSend['result_get_type'] = $arrOtherParam['result_get_type'];

    	//請求書送付方法
    	$arrSend['invoice_send_type'] = $invoiceSendType;

    	//同梱の場合は配送先をクリア(JACCSの仕様に準拠するため)
    	if ($invoiceSendType == $this->app['config']['MdlPaygent']['const']['INVOICE_SEND_TYPE_INCLUDE']) {
    		$arrSend = $paymentService->clearShipParam($arrSend);
    	}

    	// 電文の送付
    	foreach($arrSend as $key => $val) {
    		// Shift-JISにエンコードする必要あり
    		$enc_val = mb_convert_encoding($val, "Shift-JIS", $this->app['config']['char_code']);
    		$p->reqPut($key, $enc_val);
    	}
    	$p->post();
    	// 応答を処理
    	$arrRet = $pluginService->sfPaygentResponse($paygentLaterPayment, $p, $arrData->getId(), $arrInput);

    	return $arrRet;
    }

    /**
     * データ送信(後払い)
     */
    function sendData_LaterPayment($arrRet, $payment_total, $orderId, $telegram_kind, $sqlVal = array(), $invoiceSendType) {
    	$pluginService = $this->app['eccube.plugin.service.plugin'];
    	$authorize = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED'];
    	$authorizeReserve = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_RESERVE'];
    	if($arrRet['result'] === "0") {
    		if ($invoiceSendType == $this->app['config']['MdlPaygent']['const']['INVOICE_SEND_TYPE_INCLUDE']) {
    			$sqlVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT'];
    		} else {
    			$sqlVal['memo09'] = $authorize;
    		}
    		return $pluginService->sendData($arrRet, $payment_total, $orderId, $telegram_kind, $sqlVal);
    	} else {
    		if ($arrRet['code'] === "15007") {
    			// 審査保留
    			$arrRet['result'] = "0";
    			$sqlVal['memo09'] = $authorizeReserve;
    			return $pluginService->sendData($arrRet, $payment_total, $orderId, $telegram_kind, $sqlVal);
    		} else if ($arrRet['code'] === "15006") {
    			// 審査NG
    			$this->tpl_exam_error = "後払い決済の審査が通りませんでした。お手数ですが、別の決済手段をご検討ください。<br>";
    			$this->tpl_exam_error .= "<br>";
    			$this->tpl_exam_error .= "審査はジャックス・ペイメント・ソリューションズ株式会社が行っております。<br>";
    			$this->tpl_exam_error .= "審査結果についてはお問い合わせいただいてもお答えすることが出来ません。";
    		} else {
    			// その他のエラー
    			if ($arrRet['code'] === "P009") {
    				// 桁数エラー
    				$this->tpl_length_error = "<br><br>";
    				$this->tpl_length_error .= "後払いをご利用するには、お客様情報・お届け先情報は以下の文字数以内で入力してください。<br>";
    				$this->tpl_length_error .= "お名前：姓・名 合計21文字<br>";
    				$this->tpl_length_error .= "お名前（フリガナ）：姓・名 合計25文字<br>";
    				$this->tpl_length_error .= "住所：都道府県・市区町村名・番地・ビル名 合計55文字<br>";
    				$this->tpl_length_error .= "メールアドレス：100文字";
    			}
    		}
    		return false;
    	}
    }
}
