<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Eccube;
use Plugin\MdlPaygent\Form\Type\ConvenienceType;
use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;

/**
 * 決済モジュール用 汎用関数クラス
 */
class ConvenienceService
{

    private $app;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }    
    
    /**
     * 関数名：sfSendPaygentConveni
     * 処理内容：コンビニ(番号方式)情報の送信
     * 戻り値：取得結果
     */
    function sfSendPaygentConveni($arrData, $arrInput, $uniqid) {
    	$pluginService = $this->app['eccube.plugin.service.plugin'];
    	// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
    	$p = new PaygentB2BModule($this->app);
    	$p->init();
    	$payPaygentConveni = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CONVENI_NUM'];
    	$paygentConveni = $this->app['config']['MdlPaygent']['const']['PAYGENT_CONVENI_NUM'];
    
    	// コンビニ用パラメータの取得
    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentConveni);
    	$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);
    
    	// 共通データの取得
    	$arrSend = $pluginService->sfGetPaygentShare($paygentConveni, $arrData->getId(), $arrPaymentDB[0]);
    
    	/** 個別電文 **/
    	// 決済金額
    	$arrSend['payment_amount'] = $arrData['payment_total'];
    	// 利用者姓
    	$arrSend['customer_family_name'] = mb_convert_kana($arrInput['customer_family_name'], 'KVA');
    	// 利用者名
    	$arrSend['customer_name'] = mb_convert_kana($arrInput['customer_name'], 'KVA');
    	// 利用者姓半角カナ
    	$arrSend['customer_family_name_kana'] = mb_convert_kana($arrInput['customer_family_name_kana'],'k');
    	$arrSend['customer_family_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $arrSend['customer_family_name_kana']);
    	// 利用者名半角カナ
    	$arrSend['customer_name_kana'] = mb_convert_kana($arrInput['customer_name_kana'],'k');
    	$arrSend['customer_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $arrSend['customer_name_kana']);
    	// 利用者電話番号
    	$arrSend['customer_tel'] = mb_convert_kana($arrInput['customer_tel'], 'n');
    	// 支払期限日
    	$arrSend['payment_limit_date'] = $arrOtherParam['payment_limit_date'];
    	// 有効期限日
    	// TODO : 上と同じパラメータ・・・。
    	$arrSend['payment_limit_date'] = $arrOtherParam['payment_limit_date'];
    	// コンビニ企業コード
    	$arrSend['cvs_company_id'] =  $arrInput['cvs_company_id'];
    	// 支払種別
    	$arrSend['sales_type'] = '1';
    
    	// 電文の送付
    	foreach($arrSend as $key => $val) {
    		// Shift-JISにエンコードする必要あり
    		$enc_val = mb_convert_encoding($val, "Shift-JIS", $this->app['config']['char_code']);
    		$p->reqPut($key, $enc_val);
    	}
    	$p->post();
    	// 応答を処理
    	$arrRet = $pluginService->sfPaygentResponse($paygentConveni, $p, $uniqid, $arrInput);    	
    	
    	return $arrRet;
    }
    
    public function conveniProcess($Order) {
    	$error = "";
    	$paymentService = $this->app['eccube.plugin.service.payment'];
    	 $arrConvenience = $paymentService->getConvenience(); 
    	 if (is_null($Order->getCustomer())) {
    	 	$Customer = $this->app['session']->get("customer-not-login");
    	 	$subData = array(
    	 			'customer_family_name'=>$Customer->getName01(),
    	 			'customer_name'=>$Customer->getName02(),
    	 			'customer_family_name_kana'=>$Customer->getKana01(),
    	 			'customer_name_kana'=>$Customer->getKana02(),
    	 			'customer_tel' => $Order->getTel01().$Order->getTel02().$Order->getTel03(),
    	 			'cvs_company_id' => "",
    	 	);
    	 } else {
    	 	$subData = array(
    	 	 		'customer_family_name'=>$Order->getCustomer()->getName01(),
					'customer_name'=>$Order->getCustomer()->getName02(),
					'customer_family_name_kana'=>$Order->getCustomer()->getKana01(),
					'customer_name_kana'=>$Order->getCustomer()->getKana02(), 
    	 			'customer_tel' => $Order->getTel01().$Order->getTel02().$Order->getTel03(),
    	 			'cvs_company_id' => "",
    	 			);
    	 }
    	 $formConvenience = new ConvenienceType($this->app, $subData, $arrConvenience);
    	 $form = $this->app['form.factory']->createBuilder($formConvenience)->getForm(); 
    	if ('POST' === $this->app ['request']->getMethod ()) {
    		$form->handleRequest ( $this->app ['request'] );
    		if ($form->isValid ()) {
    			$arrInput = $form->getData();
    			$order_id = $this->app['eccube.service.cart']->getPreOrderId();
    			
    			$arrData = $this->app['eccube.repository.order']->findOneBy(array('pre_order_id' => $order_id));
		    	// get service
		    	$pluginService = $this->app['eccube.plugin.service.plugin'];
		    	$paygentConveni = $this->app['config']['MdlPaygent']['const']['PAYGENT_CONVENI_NUM'];		    	
		    	//get Order
		    	$orderId = $arrData['id'];	 
		    	$arrRet = $this->sfSendPaygentConveni($arrData, $arrInput, $orderId);		    	
		    	$sqlVal = array();
		    	$sqlVal['quick_flg'] = "1";
		    	$quick_memo['cvs_company_id'] = $arrInput['cvs_company_id'];
		    	$quick_memo['customer_family_name'] = $arrInput['customer_family_name'];
		    	$quick_memo['customer_name'] = $arrInput['customer_name'];
		    	$quick_memo['customer_family_name_kana'] = $arrInput['customer_family_name_kana'];
		    	$quick_memo['customer_name_kana'] = $arrInput['customer_name_kana'];
		    	$quick_memo['customer_tel'] = $arrInput['customer_tel'];
		    	$sqlVal['quick_memo'] = serialize($quick_memo);
		    
		    	if ($arrRet ['result'] == 0) {
		    		$shopInfo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getShopInfo();
		    		//check result
		    		$result = $pluginService->sendData($arrRet, $arrData['payment_total'], $orderId, $paygentConveni, $sqlVal);
		    		if ($result) {
		    			$arrRet['phoneNum'] = $arrInput['customer_tel'];
		    			$this->app['session']->set('shopInfo', $shopInfo);		    			
		    			$this->app['session']->set('orderIdConvi', $orderId);
		    			//remove card here
		    			$this->app['eccube.service.cart']->clear()->save();
		    			
		    			return $this->app->redirect($this->app['url_generator']->generate('shopping_complete'));		    			
		    		}
		    		
		    	} else {
		    		$error = "決済に失敗しました。" . $arrRet ['response'];
		    	}
    		}
    	}
    	return $this->app['view']->render('MdlPaygent/View/convenience_store.twig', array(
    			'formConvenience' => $form->createView(),
    			'error' => $error,
    	));    	
    }
    
    function formatDate($string) {
    	if (strlen($string) < 8) {
    		return $string;
    	}
    	return substr($string, 0, 4) . "年" . substr($string, 4, 2) . "月" . substr($string, 6, 2) . "日";    	
    }

}
