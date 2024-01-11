<?php

namespace Plugin\MdlPaygent\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Entity\Order;
use Plugin\MdlPaygent\Form\Type\CareerType;

class PaygentController
{
	private $app;

	/**
	 *
	 * @param \Eccube\Application $app
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
    public function index(\Eccube\Application $app, Request $request) {
        $this->app = $app;

        $Order = null;
        $hash = null;

        if ($app['eccube.service.cart']->getPreOrderId() != null) {
            $Order = $app['eccube.repository.order']->findOneBy(array('pre_order_id' => $app['eccube.service.cart']->getPreOrderId()));
        } else {
            if (array_key_exists('hash', $_REQUEST)) {
                $hash = $_REQUEST['hash'];
            }
            if (isset($_REQUEST['order_id']) && !is_null($hash)) {
                $Order = $app['eccube.repository.order']->findOneBy(array('id' => $_REQUEST['order_id']));
            }
        }
        

        if (
            is_null($Order)
            || 
            (
                !is_null($hash)
                && 
                $hash !== $this->app['eccube.plugin.service.plugin']->createPaygentHash($Order->getId(), $Order->getCreateDate()->format('Y-m-d H:i:s'))
            )
        ) {
            $error_title = 'エラー';
            $error_message = "不正なページ移動です。";
            return $app['view']->render('error.twig', array('error_message' => $error_message, 'error_title'=> $error_title));
        }

        if (is_null($Order->getCustomer())) {
        	$Customer = $app['eccube.service.shopping']->getNonMember("eccube.front.shopping.nonmember");
        	if (!is_null($Customer)) {
        		$app['session']->set('customer-not-login', $Customer);
        	}

        } else {
        	if ($app['session']->has("customer-not-login")) {
        		$app['session']->remove("customer-not-login");
        	}
        }
        $objPlugin = $this->app['eccube.plugin.service.plugin'];
        // plugin統合対応
        $objSystem = $this->app['eccube.plugin.service.system'];
        $objUtil = $this->app['eccube.plugin.service.payment'];
        $PaymentExtension = $objUtil->getPaymentTypeConfig($Order->getPayment()->getId());
        $paymentCode = $PaymentExtension->getPaymentCode();
        $paymentInfo = $PaymentExtension->getArrPaymentConfig();

        if (empty($paymentInfo)) {
            $paymentInfo = array();
            $paymentInfo['use_securitycd'] = null;
            $paymentInfo['enable_customer_regist'] = false;
            $paymentInfo['credit_pay_methods'] = array();
            $paymentInfo['conveni'] = array();
        }

        $mode = $this->getMode();
        if (isset($_GET['mode']) && $_GET['mode'] === "career_auth") {
        	$mode = $this->getSetMode($_GET['mode']);
        	// キャリア決済オーソリ後の処理
        	return $this->lfMoveCareerComplete($Order);
        } else {
        	if (is_null($mode)) {
	        	if ('POST' === $this->app['request']->getMethod()) {
	        		$mode = "next";
	        	}
        	}
        	$mode = $this->getSetMode($mode, $Order);
        	$this->validate($Order, $mode, $paymentCode);
        }
        if (is_null($mode)) {
        	$mode = "start";
        }
        switch($mode) {
        	// 前のページに戻る
        	case 'career_authentication_cancel':
        	case 'career_auth_cancel':
        	case 'return':
        		$this->app['session']->remove('career_type');
        		// 正常な推移であることを記録しておく
        		$objPlugin->rollbackOrder($Order->getId(), $this->app['config']['order_cancel'], true);
        		return $this->app->redirect($this->app['url_generator']->generate('shopping'));
        		break;
        	// 次へ
        	case 'start':
        	case 'next':
        		// MdlPaygent
        		switch ($paymentCode) {
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_CREDIT']:
        				return $this->app['eccube.plugin.service.credit']->creditProcess($Order);
        				break;
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_CONVENI_NUM']:
        				return $this->app['eccube.plugin.service.convenience']->conveniProcess($Order);
        				break;
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_ATM']:
        				return $this->app['eccube.plugin.service.atm']->ATMProcess($Order);
        				break;
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK']:
        				return $this->app['eccube.plugin.service.banknet']->bankProcess($Order);
        				break;
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER']:
        				return $this->app['eccube.plugin.service.career']->careerProcess($Order, $mode);
        				break;
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT']:
        				return $this->app['eccube.plugin.service.later']->laterPaymentProcess($Order);
        				break;
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_LINK']:
        				return $this->app['eccube.plugin.service.paygent']->paygentSettlementProcess($Order);
        				break;
        			case $app['config']['MdlPaygent']['const']['PAY_PAYGENT_VIRTUAL_ACCOUNT']:
        				return $this->app['eccube.plugin.service.virtual.account']->VirtualAccountProcess($Order);
        				break;
        			default:
        				$pathLog = $this->app['config']['root_dir'].$this->app['config']['MdlPaygent']['const']['PAYGENT_LOG_PATH'];
        				$this->app['eccube.plugin.service.plugin']->gfPrintLog($this->app, "モジュールタイプエラー：".$paymentCode, $pathLog);
        				break;
        		}
        		break;
	        // 3Dセキュア実施後のクレジット電文送信
	        case '3d_secure':
	        	$creditService = $this->app['eccube.plugin.service.credit'];
	        	$PAYGENT_CREDIT = $this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT'];

	        	$arrData['memo06'] = $this->app['session']->get('payment_id_server');
	        	$arrData['order_id'] = $this->app['session']->get('order_id');

	        	//remove session
	        	$this->app['session']->remove('payment_id_server');
	        	$this->app['session']->remove('order_id');

	        	$arrData['payment_total'] = $Order->getPaymentTotal();

	        	$pluginService = $this->app ['eccube.plugin.service.plugin'];

	        	$arrRet = $creditService->sfSendPaygentCredit3d($arrData, $_POST, $arrData['order_id']);

	        	$result = $pluginService->sendData($arrRet, $arrData['payment_total'], $arrData['order_id'], $PAYGENT_CREDIT);

	        	if ($result === true) {
	        		$this->app['eccube.service.cart']->clear()->save();
	        		return $this->app->redirect($this->app['url_generator']->generate('shopping_complete'));
	        	} else {
	        		$this->app['session']->set('credit3dError', $result);
	        		return $this->app->redirect($this->app['url_generator']->generate('mdl_paygent'));
	        	}
        	break;
        	// 登録カード削除
        	case 'deletecard':
			//get cardSeq
			if(isset($_POST['cardSeq'])){
        		$arrInput['cardSeq'] = $_POST['cardSeq'];
			}else{
				$errCardSeq = 1;
				$this->app['session']->set('cardSeqNochecked', $errCardSeq);
                $response = $objSystem->procExit($this->app->url('mdl_paygent'), $this->app);
				return $response;
			}
			//get arrData
        	$arrData['customer_id'] = $Order->getCustomer()->getId();
        	// 入力エラーなしの場合
        	$creditService = $this->app['eccube.plugin.service.credit'];
        	// 入力データの取得
        	$arrRet = $creditService -> sfDelPaygentCreditStock($arrData, $arrInput);
        	// 失敗
        	if ($arrRet[0]['result'] !== "0") {
        		$arrErr['CardSeq'] = "登録カード情報の削除に失敗しました。". $arrRet[0]['response'];
        	}
            $response = $objSystem->procExit($this->app->url('mdl_paygent'), $this->app);
            return $response;
        	break;
        	// 携帯キャリア決済ユーザ認証要求電文送信後
	        case 'career_authentication':
	        	$open_id = $_GET['open_id'];
	        	// 端末が PC の場合
	        	$transactionId = $this->app['session']->get('transactionid');
	        	$pcMobileTypePc = $this->app['config']['MdlPaygent']['const']['PC_MOBILE_TYPE_PC'];
	        	$order_id = $Order->getId();

	        	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
	        	$arrInput = array();
	        	if ($this->app['session']->has('career_type')) {
	        		$arrInput['career_type'] = $this->app['session']->get('career_type');
	        	} else {
	        		$arrInput['career_type'] = null;
	        	}

	        	$arrRet = $objPlugin->sfSendPaygentCareer($Order, $arrInput, $order_id, $transactionId, $pcMobileTypePc, $open_id);

	        	$result = $objPlugin->sendData_Career($arrRet, $order_id, $mode, $arrInput['career_type']);
                if ($result != null && $result === true){
                    return $this->app->redirect($arrRet['redirect_url']);
                }
	        	$flag = true;
	        	$error = null;
	        	$redirectHtml = null;

	        	$careerType = new CareerType($this->app);
	        	$form = $this->app['form.factory']->createBuilder($careerType)->getForm();

	        	if ($result != null && $result !== true) {
	        		$error = $result;
	        	} else {
	        		// 注文一時IDを解除する。
	        		$this->app['session']->remove($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);

	        		if ($this->app['session']->has("redirectHtml")) {

	        			$redirectHtml = $this->app['session']->get("redirectHtml");
	        			$this->app['session']->remove("redirectHtml");
	        		}

	        		if ($this->app['session']->has("flag")) {
	        			$flag = $this->app['session']->get("flag");
	        			$this->app['session']->remove("flag");
	        		}
	        	}

	        	return $this->app['view']->render('MdlPaygent/View/career.twig', array(
	        			'formMobileCarrier' => $form->createView(),
	        			'flag' =>$flag,
	        			'redirectHtml' => $redirectHtml,
	        			'error' => $error,
	        	));

	        break;
        }
    }

    /**
     * Return cart page when click back button
     * @param Application $app
     * @param Request $request
     */

    public function goBack(Application $app, Request $request)
    {
    	return $app->redirect($app->url('shopping'));
    }

    /**
     * キャリア決済の場合、エンドユーザーの操作によっては必ずしも完了画面に戻るとは限らないため
     * 他の決済とは異なり特にデータの更新は行わず、メールの送信を以って完了とする
     * データの更新はpaygent_batchにより代替する
     */
    public function lfMoveCareerComplete($Order) {

    	$MdlOrderRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
    	$MdlOrderRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	// 銀行NET用パラメータの取得
    	$arrRet = $MdlOrderRepo->getCountPaymentId($_GET['payment_id'], $_GET['trading_id']);
    	$count_payment_id = $arrRet[0]['cnt'];


    	// 実際に注文した内容なら仮完了ページへ遷移させる
    	if($count_payment_id > 0) {
    		// 受注完了メールを送信する。
    		$arrOrder = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->getMemo02FromMdlOrderPayment($Order->getId());
    		$arrOther = $arrOrder[0]['memo02'];

    		$this->app['eccube.plugin.service.plugin']->sendOrderMail($Order, $arrOther);

    		// セッションカート内の商品を削除する。
    		$this->app['eccube.service.cart']->clear()->save();
    		// 注文一時IDを解除する。
    		$this->app['session']->remove($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);

    		$this->app['session']->remove('career_type');

    		// 購入完了ページへリダイレクト
    		return $this->app->redirect($this->app['url_generator']->generate('shopping_complete'));
    	} else {
    		// $_GET内に不正な値が入っていた場合はエラーページを表示
            return $this->app['view']->render('error.twig', array('error_message' => "不正なページ移動です。", 'error_title'=> "エラー"));
    	}
    }

    /**
     * Method will validate Order
     * @param $Order Order
     * @param $mode mode action
     * @param $paymentCode payment code
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function validate($Order, $mode, $paymentCode) {
    	$ORDER_NEW = $this->app['config']['order_new'];
    	$ORDER_PROCESSING = $this->app['config']['order_processing'];
    	$ORDER_PAY_WAIT = $this->app['config']['order_pay_wait'];
    	$PAY_PAYGENT_BANK = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK'];
    	$PAY_PAYGENT_CAREER = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER'];
    	$PAY_PAYGENT_EMONEY = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_EMONEY'];

    	$PAYGENT_BANK = $this->app['config']['MdlPaygent']['const']['PAYGENT_BANK'];
    	$PAYGENT_CAREER_D = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_D'];
    	$PAYGENT_CAREER_A = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_A'];
    	$PAYGENT_CAREER_S = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_S'];
    	$PAYGENT_CAREER_AUTH_D = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_AUTH_D'];
    	$PAYGENT_CAREER_AUTH_A = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_AUTH_A'];
    	$PAYGENT_EMONEY_W = $this->app['config']['MdlPaygent']['const']['PAYGENT_EMONEY_W'];
    	$PAYGENT_YAHOOWALLET = $this->app['config']['MdlPaygent']['const']['PAYGENT_YAHOOWALLET'];
    	$PAY_PAYGENT_CREDIT = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CREDIT'];
    	$status = $Order->getOrderStatus()->getId();


    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$arrData = $MdlPaymentRepo->getMemoByOrderId($Order->getId());
    	if (count($arrData) <= 0) {
    		$arrData = $this->app['eccube.plugin.service.plugin']->resetMemo();
    	}
    	if (
    			// 受注情報が取得できない場合
    			null == $Order->getId()
    			||
    			// 受注状態が "8"：決済処理中 ではなく、
    			// 銀行ネット決済の 受注状態が "2"：入金待ち でもない場合
    			// 携帯キャリア決済の 受注状態が "1"：新規受付 でもない場合
    			// 電子マネー決済の 受注状態が "1"：新規受付 でもない場合
    			(
    					$status != $ORDER_PROCESSING
    					&& !($status == $ORDER_PAY_WAIT && $paymentCode == $PAY_PAYGENT_BANK)
    					//++
    					&& !($status == $ORDER_NEW && $paymentCode == $PAY_PAYGENT_CAREER)
    					&& !($status == $ORDER_NEW && $paymentCode == $PAY_PAYGENT_EMONEY)
    					//--
    					)
    			||
    			// 決済ベンダの画面からブラウザバックで遷移した場合
    			(
    					// 課題No.111 対応
    					( !isset($mode) || $mode === "next" || $mode === "quick" )
    					&& (isset($arrData['memo03']) && $arrData['memo03'] !== "1")	// 処理結果 が "1"：異常 ではない
    					&& !empty($arrData['memo06'])	// 決済ID が空ではない
    					&& empty($arrData['memo08'])	// 電文種別ID が空である
    					)
    			||
    			(
    					( !isset($mode) || $mode === "next" )
    					&& (
    							(isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_BANK)												// "060"：銀行ネット決済ASP申込電文
    							|| (empty($arrData['memo04']) && isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_CAREER_D)		// "100_1"：携帯キャリア決済申込電文（docomo）
    							|| (empty($arrData['memo04']) && isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_CAREER_A)		// "100_2"：携帯キャリア決済申込電文（au）
    							|| (empty($arrData['memo04']) && isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_CAREER_S)		// "100_3"：携帯キャリア決済申込電文（SoftBank）
    							|| (empty($arrData['memo04']) && isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_CAREER_AUTH_D)	// "104_1"：携帯キャリア決済ユーザ認証要求（docomo）
    							|| (empty($arrData['memo04']) && isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_CAREER_AUTH_A)	// "104_2"：携帯キャリア決済ユーザ認証要求（au）
    							|| (isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_EMONEY_W)										// "150_1"：電子マネー決済申込電文（WebMoney）
    							|| (isset($arrData['memo08']) && $arrData['memo08'] == $PAYGENT_YAHOOWALLET)									// "160"：Yahoo!ウォレット決済申込電文
    							)
    					)
    			||
    			(
    					$mode === "career_authentication"
    					&& (
    							(empty($arrData['memo04']) && $arrData['memo08'] == $PAYGENT_CAREER_D)		// "100_1"：携帯キャリア決済申込電文（docomo）
    							|| (empty($arrData['memo04']) && $arrData['memo08'] == $PAYGENT_CAREER_A)	// "100_2"：携帯キャリア決済申込電文（au）)
    							)
    					)
    			){
    				//「不正なページ移動です」エラー画面を表示
    				return $this->app['view']->render('error.twig', array('error_message' => "不正なページ移動です。", 'error_title'=> "エラー"));
    	}

//     	0円決済(手数料はを除く)
    	if ($Order->getTotal() == 0) {
    		$this->app['eccube.plugin.service.plugin']->orderComplete($Order->getId(), array(), $this->app['config']['order_pre_end'], '');
    	}
    }

    /**
     * モード設定
     */
    public function getSetMode($mode, $Order = null) {
    	$setMode = null;
    	// 3Dセキュアの戻り
    	if (isset($mode) && $mode == "credit_3d" &&
    	isset($_GET['order_id']) && $_GET['order_id'] == $Order->getId()) {
    		$setMode = '3d_secure';
    		// モバイル：登録カードの削除
    	} elseif (isset($_POST['deletecard']) && $_POST['deletecard'] == 1) {
    		$setMode = 'deletecard';
    		// 電子マネー決済時
    	}  else if (isset($mode) && ($mode == "emoney_commit" || $mode == "emoney_commit_cancel")) {
    		$setMode = $mode;
    		// その他
    	} elseif (isset($mode)) {
    		$setMode = $mode;
    		// キャリア決済仮完了処理
    	} elseif(isset($_GET['payment_id']) && isset($_GET['trading_id']) && isset($_GET['career_payment_id'])) {
    		$setMode = 'career_auth';
    	}
    	return $setMode;
    }

    /**
     * リクエストパラメーター 'mode' を取得する.
     *
     * 1. $_REQUEST['mode'] の値を取得する.
     * 2. 存在しない場合は null を返す.
     *
     * mode に, 半角英数字とアンダーバー(_) 以外の文字列が検出された場合は null を
     * 返す.
     *
     * @access protected
     * @return string|null $_REQUEST['mode'] の文字列
     */
    public function getMode()
    {
    	$pattern = '/^[a-zA-Z0-9_]+$/';
    	$mode = null;
    	if (isset($_REQUEST['mode']) && preg_match($pattern, $_REQUEST['mode'])) {
    		$mode =  $_REQUEST['mode'];
    	}

    	return $mode;
    }
}
?>
