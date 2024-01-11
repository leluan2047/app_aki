<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Plugin\MdlPaygent\Form\Type\CareerType;
use Eccube;

/**
 * 決済モジュール用 汎用関数クラス
 */
class PaymentCareerService
{

    private $app;

    private $session;

    private $redirectHtml;

    private $flag;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }

    /**
     * PAY_PAYGENT_CAREER
     * @param unknown $Order
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function careerProcess($Order, $mode)
    {
    	$this->flag = true;
    	$error = null;
    	$careerType = new CareerType($this->app);
    	$form = $this->app['form.factory']->createBuilder($careerType)->getForm();

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

    				$arrRet = array();
    				// set session value of career_type
					$this->app['session']->set('career_type', $arrInput['career_type']);

    				$pcMobileTypePc = $this->app['config']['MdlPaygent']['const']['PC_MOBILE_TYPE_PC'];
    				// 端末が PC の場合
    				switch ($arrInput['career_type']) {
    					case $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_DOCOMO']:
    						// ドコモケータイ払いの場合、携帯キャリア決済ユーザ認証要求電文を送信
    						$arrRet = $this->sfSendPaygentAuthCareer($arrData, $arrInput, $order_id, $transactionid, $pcMobileTypePc);
    						break;
    					case $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_AU']:
    						// auかんたん決済の場合、携帯キャリア決済ユーザ認証要求電文を送信
    						$arrRet = $this->sfSendPaygentAuthCareer($arrData, $arrInput, $order_id, $transactionid, $pcMobileTypePc);
    						break;
    					case $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_SOFTBANK']:
    						// ソフトバンクの場合、携帯キャリア決済申込電文を送信
    						$arrRet = $pluginService->sfSendPaygentCareer($arrData, $arrInput, $order_id, $transactionid, $pcMobileTypePc);
    						break;
    				}

    				$result = $pluginService->sendData_Career($arrRet, $order_id, $mode, $arrInput['career_type']);
    				    
    				if ($result != null && $result === true){
                        return $this->app->redirect($arrRet['redirect_url']);
                    }
                        
                    if ($result != null && $result !== true) {
    					$error = $result;
    				}

    				if ($this->app['session']->has("redirectHtml")) {
    					// セッションカート内の商品を削除する。
    					$this->app['eccube.service.cart']->clear()->save();

    					$this->redirectHtml = $this->app['session']->get("redirectHtml");
    					$this->app['session']->remove("redirectHtml");
                        
    				}

    				if ($this->app['session']->has("flag")) {
    					$this->flag = $this->app['session']->get("flag");
    					$this->app['session']->remove("flag");
    				}
    			}
    		} catch (Exception $ex) {
    			$this->session->delete($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);
    			return false;
    		}
    	}

    	return $this->app['view']->render('MdlPaygent/View/career.twig', array(
    			'formMobileCarrier' => $form->createView(),
    			'flag' =>$this->flag,
    			'redirectHtml' => $this->redirectHtml,
    			'error' => $error,
    	));
    }

    /**
     * 携帯キャリア決済ユーザ認証要求電文（ID=104）を送信する。
     *
     * @param $arrData 受注情報
     * @param $arrInput 入力情報
     * @param $order_id 受注ID
     * @param $transactionid EC-CUBE側のトランザクションID
     * @param $pc_mobile_type PC/Mobile区分
     * @return 応答情報
     */
    function sfSendPaygentAuthCareer($arrData, $arrInput, $order_id, $transactionid, $pc_mobile_type) {
    	$pluginService = $this->app['eccube.plugin.service.plugin'];
    	// 支払方法情報テーブル（dtb_payment）から、携帯キャリア決済に関する情報を取得
    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	$payPaygentCareer = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER'];
    	// 銀行NET用パラメータの取得
    	$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentCareer);

    	// --- パラメータを設定 ------------------------------
    	// 共通ヘッダ
    	$paygentCareerCommitAuth = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_AUTH'];
    	$arrSend = $pluginService->sfGetPaygentShare($paygentCareerCommitAuth, $arrData->getId(), $arrPaymentDB[0]);

    	$transactionIdName = $this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME'];

    	// 認証OKURL
    	$arrSend['redirect_url'] = $this->app->url('homepage') . "shopping/mdl_paygent?mode=career_authentication&order_id=" . $order_id . "&" . $transactionIdName . "=" . $transactionid . '&hash=' . $pluginService->createPaygentHash($order_id, $arrData->getCreateDate()->format('Y-m-d H:i:s'));
    	// 認証NGURL
    	$arrSend['cancel_url'] = $this->app->url('homepage') . "shopping/mdl_paygent?mode=career_authentication_cancel&order_id=" . $order_id . "&" . $transactionIdName . "=" . $transactionid . '&hash=' . $pluginService->createPaygentHash($order_id, $arrData->getCreateDate()->format('Y-m-d H:i:s'));
    	// PC/Mobile区分
    	$arrSend['pc_mobile_type'] = $pc_mobile_type;
    	// キャリア種別
    	if ($arrInput['career_type'] == $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_DOCOMO']) {
    		$arrSend['career_type'] = $this->app['config']['MdlPaygent']['const']['CAREER_TYPE_DOCOMO'];
    	} else if ($arrInput['career_type'] == $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_AU']) {
    		$arrSend['career_type'] = $this->app['config']['MdlPaygent']['const']['CAREER_TYPE_AU'];
    	} else {
    		$arrSend['career_type'] = $arrInput['career_type'];
    	}

    	// --- 電文を送信 ------------------------------
    	// 接続モジュールのインスタンスを取得、及び初期化
    	$p = new PaygentB2BModule($this->app);
    	$p->init();

    	foreach ($arrSend as $key => $val) {
    		// Shift-JIS でエンコードをした値を設定
    		$p->reqPut($key, mb_convert_encoding($val, "Shift-JIS", $this->app['config']['char_code']));
    	}

    	// 電文を送信
    	$p->post();

    	// 応答情報を取得
    	$arrRet = $pluginService->sfPaygentResponse($this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_AUTH'], $p, $order_id, $arrInput);

    	return $arrRet;
    }
}
