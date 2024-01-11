<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Eccube;
use Guzzle\Service\Client;
use Plugin\MdlPaygent\Form\Type\PaygentSettlementType;
use Eccube\Common\Constant;

class PaygentSettlementService
{
	private $app;
	private $errorMsg;
	private $error = array();

	public function __construct(\Eccube\Application $app)
	{
		$this->app = $app;
		$this->errorMsg="";
	}

	function setError($msg)
	{
		$this->error[] = $msg;
	}

	function getError()
	{

		return $this->error;
	}

	/**
	 * PAY_PAYGENT_ATM
	 * @param unknown $Order

	 */
	public function paygentSettlementProcess($Order) {
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
		$formPaygentSettlement = new PaygentSettlementType ( $this->app, $subData);
		$form = $this->app ['form.factory']->createBuilder ( $formPaygentSettlement )->getForm ();
		$transactionIdName = $this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME'];
		if ('POST' === $this->app ['request']->getMethod ()) {
			$form->handleRequest ( $this->app ['request'] );
			if ($form->isValid ()) {
				// Check if token exists in transaction
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
				$arrInput = $form->getData();
				$order_id = $Order->getId();
				$arrData = $this->app['eccube.repository.order']->findOneBy(array('id' => $order_id));
				// get service
				$pluginService = $this->app['eccube.plugin.service.plugin'];
				// 決済申込電文送信
                $arrRet = $this->sendPaygent($arrData, $arrInput, $transactionid);
				// 受注＆ページ遷移
                return $this->linkPaygentPage($arrRet, $arrData['payment_total'], $form);
			}
		}

		return $this->app['view']->render('MdlPaygent/View/paygent_settlement.twig', array(
				'formPaygentSettlement' => $form->createView(),
				'error' => $this->errorMsg,
		));
	}

	function sendPaygent($arrData, $arrInput, $transactionid) {
		$pluginService = $this->app['eccube.plugin.service.plugin'];
		// コンビニ用パラメータの取得
		$payPaygentSettlement = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LINK'];
		$transactionIdName = $this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME'];
		$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
		$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
		$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentSettlement);
		$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);

		// マーチャント取引ID
		$arrSend['trading_id'] = $arrData['id'];
		// 決済金額
		$arrSend['id'] = $arrData['payment_total'];
		// マーチャントID
		$arrSend['seq_merchant_id'] = $arrPaymentDB[0]['merchant_id'];
		// マーチャント名
		$arrSend['merchant_name'] = $arrOtherParam['merchant_name'];
		// 支払期間
		$arrSend['payment_term_day'] = $arrOtherParam['payment_term_day'];
		// 自由メモ欄
		$arrSend['free_memo'] = mb_convert_kana($arrOtherParam['free_memo'], 'KVA');
		// 戻りURL・中断時URL
		$sUrl = $this->app->url('homepage');
		$sUrl = rtrim($sUrl, "/");
		if (strpos($sUrl, 'index') !== false) {
			$arrSend['return_url'] = $sUrl . '?' . $transactionIdName . "=" . $transactionid;
			$arrSend['stop_return_url'] = $sUrl . '?' . $transactionIdName . "=" . $transactionid;
		} else {
			$arrSend['return_url'] = $sUrl . "/index.php?" . $transactionIdName . "=" . $transactionid;
			$arrSend['stop_return_url'] = $sUrl . "/index.php?" . $transactionIdName . "=" . $transactionid;
		}
		// コピーライト
		$arrSend['copy_right'] = $arrOtherParam['copy_right'];
		// 利用者姓
		$arrSend['customer_family_name'] = mb_convert_kana($arrInput['customer_family_name'], 'KVA');
		// 利用者名
		$arrSend['customer_name'] = mb_convert_kana($arrInput['customer_name'], 'KVA');
		// 利用者姓半角カナ
		$arrSend['customer_family_name_kana'] = mb_convert_kana($arrInput['customer_family_name_kana'],'k');
		$arrSend['customer_family_name_kana'] = preg_replace("/ｰ/", "-", $arrSend['customer_family_name_kana']);
		$arrSend['customer_family_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $arrSend['customer_family_name_kana']);
		// 利用者名半角カナ
		$arrSend['customer_name_kana'] = mb_convert_kana($arrInput['customer_name_kana'],'k');
		$arrSend['customer_name_kana'] = preg_replace("/ｰ/", "-", $arrSend['customer_name_kana']);
		$arrSend['customer_name_kana'] = preg_replace("/ﾞ|ﾟ/", "", $arrSend['customer_name_kana']);
		// 連携モード(URL連携方式)
		$arrSend['isbtob'] = 1;
		// 支払区分
		$arrSend['payment_class'] = $arrOtherParam['payment_class'];
		// カード確認番号利用フラグ
		$arrSend['use_card_conf_number'] = $arrOtherParam['use_card_conf_number'];
		// 利用者電話番号
		$arrSend['customer_tel'] = $arrData['tel01']. $arrData['tel02']. $arrData['tel03'];
		// 決済モジュール識別
		$arrSend['partner'] = 'lockon';
		// EC-CUBE本体のバージョン
		$arrSend['eccube_version'] = Constant::VERSION;
		// 決済プラグインのバージョン
		$arrSend['eccube_plugin_version'] = $pluginService->getPluginVersion();

		// ハッシュ値
		if (strlen($arrOtherParam['hash_key']) > 0) {
			$arrSend['hc'] = $this->setPaygentHash($arrSend, $arrOtherParam['hash_key']);
		}

		//受注テーブルの更新
		$PAYGENT_LINK = $this->app['config']['MdlPaygent']['const']['PAYGENT_LINK'];
		$MDL_PAYGENT_CODE = $this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE'];
		$sqlVal['memo01'] = $MDL_PAYGENT_CODE;
		$sqlVal['memo08'] = $PAYGENT_LINK;
		$pluginService->registerOrder($arrData['id'], $sqlVal);
		$pluginService->updateStock($arrData['id'], true);
		// リクエスト
		return $this->sendRequest($arrOtherParam['link_url'], $arrSend);
	}

	function setPaygentHash($arrSend, $hash_key) {
		// create hash hex string
		$default = array(
				'payment_class'=>'',
				'hash_key'=>$hash_key,
				'paygent_mark'=>'paygent2006',
				'trading_id'=>'',
				'id'=>'',
				'payment_type'=>'',
				'seq_merchant_id'=>'',
				'payment_term_day'=>'',
				'use_card_conf_number'=>'',
				'fix_params'=>'',
				'inform_url'=>'',
				'payment_term_min'=>'',
				'customer_id'=>'',
				'threedsecure_ryaku'=>'',
		);
		$org_str = '';
		foreach ($default as $key=>$value) {
			$org_str .= isset($arrSend[$key]) ? $arrSend[$key]:$value;
		}
		if (function_exists("hash")) {
			$hash_str = hash("sha256", $org_str);
		} elseif (function_exists("mhash")) {
			$hash_str = bin2hex(mhash(MHASH_SHA256, $org_str));
		} else {
			return;
		}

		// create random string
		$rand_str="";
		$rand_char = array('a','b','c','d','e','f','A','B','C','D','E','F','0','1','2','3','4','5','6','7','8','9');
		for ($i = 0; ($i < 20 && rand(1,10) != 10); $i++) {
			$rand_str .= $rand_char[rand(0, count($rand_char)-1)];
		}

		return $hash_str. $rand_str;
	}


	function sendRequest($url, $dataSend)
	{
		$listData = array();
		foreach ($dataSend as $key => $value) {
			$listData[$key] = $value;
		}

		$client = new Client(null, array('curl.options' => array('CURLOPT_SSLVERSION' => 6)));
		$request = $client->post($url, array(), $listData);
		$response = $request->send();

		$r_code = $response->getStatusCode();
		switch ($r_code) {
            case 200:
                break;
            case 404:
                $msg = 'レスポンスエラー:RCODE:' . $r_code;
                $this->setError($msg);
                return false;
                break;
            case 500:
            default:
                $msg = '決済サーバーエラー:RCODE:' . $r_code;
                $this->setError($msg);
                return false;
                break;
        }

        $response_body = $response->getBody(true);

        if (is_null($response_body)) {
            $msg = 'レスポンスデータエラー: レスポンスがありません。';
            $this->setError($msg);
            return false;
        }


		$arrRet = $this->parseResponse($response_body);
		if (!empty($this->error)) {
			return $this->getError();
		}
		return $arrRet;
	}

	/**
	 * レスポンスを解析する
	 *
	 * @param string $string レスポンス
	 * @return array 解析結果
	 */
	function parseResponse($string)
	{
		$string = split("\r\n", $string);
        //$logtext = "\n************ Response start ************";
        foreach ($string as $i => $line) {
            $item = split("=", $line, 2);
            if (strlen($item[0]) > 0) {
                $res[$item[0]] = $item[1];
            }
        }
        return $res;
	}

	/**
	 * 受注＆ページ遷移
	 */
	public function linkPaygentPage($arrRet, $payment_total, $form) {
		// 成功
		if ($arrRet['result'] === "0") {
			// get service
			$paymentService = $this->app['eccube.plugin.service.payment'];
			$pluginService = $this->app['eccube.plugin.service.plugin'];
			// get constant
			$PAYGENT_LINK = $this->app['config']['MdlPaygent']['const']['PAYGENT_LINK'];
			$PAY_PAYGENT_LINK = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LINK'];
			$MDL_PAYGENT_CODE = $this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE'];
			$arrInitStatus = $paymentService->getInitStatus();
			$order_status = $arrInitStatus[$PAYGENT_LINK];

			// 受注登録
			$arrMemo['title'] = $this->sfSetConvMSG("お支払", true);
			$arrMemo['payment_url'] = $this->sfSetConvMSG("お支払画面URL", $arrRet['url']);
			$year = substr($arrRet['limit_date'], 0, 4);
			$month = substr($arrRet['limit_date'], 4, 2);
			$day = substr($arrRet['limit_date'], 6, 2);
			$hour = substr($arrRet['limit_date'], 8, 2);
			$minute = substr($arrRet['limit_date'], 10, 2);
			$second = substr($arrRet['limit_date'], 12);
			$arrMemo['limit_date'] = $this->sfSetConvMSG("お支払期限", "$year/$month/$day $hour:$minute:$second");

			$sqlVal['memo01'] = $MDL_PAYGENT_CODE;
			$sqlVal['memo02'] = serialize($arrMemo);
			$sqlVal['memo03'] = $arrRet['result'];
			$sqlVal['memo08'] = $PAYGENT_LINK;
			$pluginService->orderComplete($arrRet['trading_id'], $sqlVal, $order_status, $PAY_PAYGENT_LINK);
			//remove card here
			$this->app['eccube.service.cart']->clear()->save();
			// ペイジェント決済画面に遷移
			return $this->app->redirect($arrRet['url']);
			// 失敗
		} elseif ($arrRet['result'] === "1") {
			$this->errorMsg .= "決済に失敗しました。";
			if (preg_match('/^[P|E]/', $arrRet['response_code']) <= 0) {
				$this->errorMsg .= "<br />". $arrRet['response_detail']. "（". $arrRet['response_code']. "）";
			} else {
				$this->errorMsg .= "（". $arrRet['response_code']. "）";
			}
			return $this->app['view']->render('MdlPaygent/View/paygent_settlement.twig', array(
				'formPaygentSettlement' => $form->createView(),
				'error' => $this->errorMsg,
			));
			// 通信エラー
		} else {
			$this->errorMsg = "決済に失敗しました。<br />". $arrRet;
			return $this->app['view']->render('MdlPaygent/View/paygent_settlement.twig', array(
				'formPaygentSettlement' => $form->createView(),
				'error' => $this->errorMsg,
			));
		}
	}

	/**
	 * 関数名：sfSetConvMSG
	 * 処理内容：コンビニ情報表示用
	 * 戻り値：取得結果
	 */
	function sfSetConvMSG($name, $value){
		return array("name" => $name, "value" => $value);
	}


}