<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Eccube;
use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;

/**
 * 決済モジュール用 汎用関数クラス
 */
class AdminOrderService
{

	private $app;
	private $pluginService;
	private $paymentService;

	public function __construct(\Eccube\Application $app)
	{
		$this->app = $app;
		$this->pluginService = $this->app ['eccube.plugin.service.plugin'];
		$this->paymentService = $this->app['eccube.plugin.service.payment'];

	}

	// 一括受注連携
	function lfPaygentAllOrder($arr_paygent_commit) {

		// 初期設定
		$max = count($arr_paygent_commit);
		$cnt = 1;
		$success_cnt = 0;
		$fail_cnt = 0;
		$arrDispKind = $this->paymentService->getDispKind();

		// 受注連携
		foreach ($arr_paygent_commit as $val) {
			// 連携種別と受注ID
			$paygent_commit = split(",", $val);

			echo "$cnt/$max 受注番号：$paygent_commit[1] → ";
			// 連携
			$res = $this->sfPaygentOrder($paygent_commit[0], $paygent_commit[1]);
			// 結果出力
			if ($res['return'] === true) {
				$output = $arrDispKind[$res['kind']]. "成功<br />\n";
				$success_cnt++;
			} else {
				if (strlen($res['revice_price_error']) <= 0) {
					$output = $arrDispKind[$res['kind']]. "失敗 ". $res['response']. "<br />\n";
				} else {
					$output = "失敗 ". $res['revice_price_error']. "<br />\n";
				}
				$fail_cnt++;
			}
			echo $output;
			$cnt++;
		}

		return array($success_cnt, $fail_cnt);
	}

	/**
	 * 関数名：sfPaygentOrder($paygent_type)
	 * 処理内容：受注連携
	 * 戻り値：取得結果
	 */
	function sfPaygentOrder($paygent_type, $order_id, $payment_id = '', $beforeStatus = '', $arrRequest = array()) {
		$arrPaymentCard = $this->app ['orm.em']->getRepository ( '\Plugin\MdlPaygent\Entity\MdlPaymentMethod' );
		$arrPaymentCard->setConfig($this->app['config']['MdlPaygent']['const']);

		$PAYGENT_CARD_COMMIT = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT'];
		$PAYGENT_AUTH_CANCEL = $this->app['config']['MdlPaygent']['const']['PAYGENT_AUTH_CANCEL'];
		$PAYGENT_CARD_COMMIT_CANCEL = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_CANCEL'];
		$PAYGENT_CARD_COMMIT_REVICE_PROCESSING = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_REVICE_PROCESSING'];
		$PAYGENT_CREDIT_PROCESSING = $this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT_PROCESSING'];
		$PAYGENT_CARD_COMMIT = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT'];
		$PAYGENT_CARD_COMMIT_REVICE = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_REVICE'];

		$PAYGENT_CREDIT = $this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT'];
		$PAYGENT_CAREER_COMMIT = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT'];
		$PAYGENT_CAREER_COMMIT_REVICE = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_REVICE'];
		$PAYGENT_CAREER_COMMIT_CANCEL = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_CANCEL'];
		$PAYGENT_LATER_PAYMENT_PRINT = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_PRINT'];
		$PAYGENT_LATER_PAYMENT_REDUCTION = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_REDUCTION'];
		$PAYGENT_LATER_PAYMENT_BILL_REISSUE = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_BILL_REISSUE'];
		$PAYGENT_LATER_PAYMENT_CLEAR = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_CLEAR'];
		$PAYGENT_CARD_COMMIT_REVICE = $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_REVICE'];

		$PAYGENT_LATER_PAYMENT_CANCEL = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_CANCEL'];
		$PAYGENT_LATER_PAYMENT_ST_AUTHORIZED = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED'];
		$PAYGENT_LATER_PAYMENT_ST_CLEAR = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR'];
		$PAYGENT_LATER_PAYMENT_ST_SALES_CANCEL = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_SALES_CANCEL'];
		$PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_CANCEL = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_CANCEL'];
		$PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN'];

		$PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_RESERVE = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_RESERVE'];
		$PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_NG = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_NG'];

		$INVOICE_SEND_TYPE_INCLUDE = $this->app['config']['MdlPaygent']['const']['INVOICE_SEND_TYPE_INCLUDE'];
		$PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT'];
		$CHAR_CODE = $this->app['config']['char_code'];

		$arrDispKind = $this->paymentService->getDispKind();

		// 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
		$objPaygent = new PaygentB2BModule($this->app);
		$objPaygent->init();

		// 設定パラメータの取得
		$arrPaymentDB = $arrPaymentCard->getPaymentDB();


		// 処理分岐
		switch($paygent_type) {
			case 'change_auth':
			case 'change_commit_auth':
				$kind = $PAYGENT_CREDIT;
				break;
			case 'auth_cancel':
			case 'change_auth_cancel':
				$kind = $PAYGENT_AUTH_CANCEL;
				break;
			case 'card_commit':
			case 'change_commit':
				$kind = $PAYGENT_CARD_COMMIT;
				break;
			case 'card_commit_cancel':
			case 'change_commit_cancel':
				$kind = $PAYGENT_CARD_COMMIT_CANCEL;
				break;
			case 'career_commit':
				$arrTelegram = array($PAYGENT_CAREER_COMMIT);
				$kind = $arrTelegram[0];
				break;
			case 'change_carrer_auth':
				// 売上変更ボタンが押下された場合
				// 存在チェック
				$MdlOrderPayment = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
				$arrTelegram = $MdlOrderPayment->sfCheckRevice($order_id);
				$arrTelegram[0] = $arrTelegram[0]['payment_total'];
				$kind = $PAYGENT_CAREER_COMMIT_REVICE;
				break;
			case 'career_commit_cancel':
				$kind = $PAYGENT_CAREER_COMMIT_CANCEL;
				break;
			case 'later_payment_print':
				// 後払い請求書印字データ出力
				$kind =$PAYGENT_LATER_PAYMENT_PRINT;
				break;
			case 'later_payment_reduction':
				// 後払い決済 オーソリ変更
				$kind =$PAYGENT_LATER_PAYMENT_REDUCTION;
				break;
			case 'later_payment_bill_reissue':
				// 後払い決済 請求書再発行
				$kind = $PAYGENT_LATER_PAYMENT_BILL_REISSUE;
				break;
			case 'later_payment_clear':
				// 後払い決済 売上
				$kind = $PAYGENT_LATER_PAYMENT_CLEAR;
				break;
			case 'later_payment_cancel':
				// 後払い決済 取消
				$kind = $PAYGENT_LATER_PAYMENT_CANCEL;
				break;
		}

		if(!isset($arrReturn) || count($arrReturn) === 0) {
			// ペイジェントステータスのチェック
			$MdlOrderPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
			$MdlOrder = $MdlOrderPaymentRepo->getMdlOrder($order_id);

			$status = $MdlOrder->getMemo09();

			if ((($status == $PAYGENT_AUTH_CANCEL || $status == $PAYGENT_CARD_COMMIT_CANCEL
					|| $status == $PAYGENT_CARD_COMMIT_REVICE_PROCESSING || $status == $PAYGENT_CREDIT_PROCESSING) && ($beforeStatus === '' && $beforeStatus != null) )
					|| (($status == $PAYGENT_CARD_COMMIT || $status == $PAYGENT_CARD_COMMIT_REVICE) && $paygent_type == 'change_auth')) {
						// ステータスが取消または売上変更処理中になっている、
						// またはステータスが売上or売上変更になっているときのオーソリ変更は処理を中断
						if ($paygent_type == 'change_commit') {
							$arrReturn['kind'] = $PAYGENT_CARD_COMMIT_REVICE;
						} else {
							$arrReturn['kind'] = $kind;
						}
						$arrReturn['return'] = false;
						$arrReturn['response'] = "ステータス矛盾エラー";
						return $arrReturn;
			}
			// 決済IDの取得
			if(strlen($payment_id) === 0) {
				$payment_id = $MdlOrder->getMemo06();
			}
			// 共通データの取得
			$arrSend = $this->pluginService->sfGetPaygentShare($kind, $order_id, $arrPaymentDB[0], $payment_id);
			// $arrSendに個別詳細情報を付け加える
			switch($paygent_type) {
		    case 'change_auth':
		    case 'change_commit_auth':
		    	if ($paygent_type == 'change_auth') {
		    		// ステータスをオーソリ変更処理中に変更
		    		$arrVal['memo09'] = $PAYGENT_CREDIT_PROCESSING;
		    		$this->pluginService->updateMdlOrderPayment($order_id, $arrVal);
		    	}
		    	$arrOrder = $MdlOrderPaymentRepo->getMdlOrderAndOrder($order_id);
		    	$arrSend['payment_amount'] = $arrOrder['payment_total'];
		    	$arrSend['ref_trading_id'] = $order_id;
		    	$arrPaymentParam = unserialize($arrOrder[0]['quick_memo']);
		    	$arrSend['payment_class'] = isset($arrPaymentParam['payment_class']) ? $arrPaymentParam['payment_class'] : '';
		    	$arrSend['split_count'] = isset($arrPaymentParam['split_count']) ? $arrPaymentParam['split_count'] : '';
		    	$arrSend['3dsecure_ryaku'] = '1';
		    	unset($arrSend['payment_id']);
		    	break;
		    case 'change_carrer_auth':
			    // 携帯キャリア決済補正売上要求電文の場合
			    $arrSend['amount'] = $arrTelegram[0];
			    break;
		    case 'change_commit':
		    	// ステータスを売上変更処理中に変更
		    	$arrVal['memo09'] = $PAYGENT_CARD_COMMIT_REVICE_PROCESSING;
		    	$this->pluginService->updateMdlOrderPayment($order_id, $arrVal);
		    	// 新規オーソリ処理
		    	$arrRetAuth = $this->sfPaygentOrder('change_commit_auth', $order_id, $payment_id, $status);
		    	// オーソリ失敗
		    	if($arrRetAuth['return'] == false) {
		    		$arrRetAuth['kind'] = $PAYGENT_CARD_COMMIT_REVICE;
		    		return $arrRetAuth;
		    	} else {
		    		// 決済IDを更新
		    		$arrSend['payment_id'] = $arrRetAuth['payment_id'];
		    	}
		    	break;
		    case 'later_payment_reduction':
		    	// 後払い決済 オーソリ変更
		    	$arrSend += $arrRequest;
		    	$arrShippings = $this->pluginService->getShippings($order_id);
		    	if (count($arrShippings) > 1) {
		    		// 配送先が複数件ある場合は後払い決済不可
		    		$arrReturn['kind'] = $kind;
		    		$arrReturn['return'] = false;
		    		$arrReturn['response'] = "後払い決済は複数配送先をご指定いただいた場合はご利用いただけません。<br>別の決済手段をご検討ください。";
		    		return $arrReturn;
		    	}
		    	$arrSend += $this->pluginService->sfGetPaygentLaterPaymentModule($order_id);
		    	//「請求書送付方法が同梱」かつ「注文者と配送先が同じ」場合
		    	if ($arrRequest['invoice_send_type'] == $INVOICE_SEND_TYPE_INCLUDE && $this->paymentService->isSameOrderShip($order_id)) {
		    		//配送先をクリア(JACCSの仕様に準拠するため)
		    		$arrSend = $this->paymentService->clearShipParam($arrSend);
		    	}
		    	break;
		    case 'later_payment_bill_reissue':
		    	// 後払い決済 請求書再発行
		    	$arrSend += $arrRequest;
		    	$arrSend['invoice_to'] = "1";

		    	$arrOrder = $this->app['eccube.repository.order']->findOneBy(array('id' => $order_id));
		    	$arrSend['zip_code'] = $arrOrder->getZip01() . $arrOrder->getZip02();
		    	$arrPref =  $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getPref();
		    	$arrSend['address'] = $arrPref[$arrOrder->getPref()->getId()-1]['name'] . $arrOrder->getAddr01().$arrOrder->getAddr02();
		    	break;
		    case 'later_payment_clear':
		    	// 後払い決済 売上
		    	$arrSend += $arrRequest;
		    	break;
			}
			if ($paygent_type === 'later_payment_reduction'
				|| $paygent_type === 'later_payment_bill_reissue') {
				// 全角文字を送信する電文はSJISに変換する
				foreach($arrSend as $key => $val) {
					$enc_val = mb_convert_encoding($val, "Shift-JIS", $CHAR_CODE);
						$arrSend[$key] = $enc_val;
				}
			}

			// 電文の送付
			foreach($arrSend as $key => $val) {
				$objPaygent->reqPut($key, $val);
			}

			$objPaygent->post();
			// レスポンスの取得
			while($objPaygent->hasResNext()) {
			# データが存在する限り、取得
			$arrRes[] = $objPaygent->resNext(); # 要求結果取得
			}
			$arrRes[0]['result'] = $objPaygent->getResultStatus(); # 処理結果 0=正常終了, 1=異常終了

			foreach($arrRes[0] as $key => $val) {
				// Shift-JISで応答があるので、エンコードする。
				$arrRes[0][$key] = mb_convert_encoding($val, $CHAR_CODE, "Shift-JIS");
				if ($arrRes[0]['result'] == 1) {
				}
			}

			$arrReturn['kind'] = $kind;
			$arrVal = array();
			// 正常終了
			if($arrRes[0]['result'] === '0') {
				// オーソリ変更
				switch($paygent_type) {
					case 'change_commit_auth':
						$arrReturn['payment_id'] = $arrRes[0]['payment_id'];
						break;
					case 'change_auth_cancel':
					case 'change_commit_cancel':
						 break;
					case 'change_auth':
						// オーソリ変更前の決済に対してオーソリキャンセル電文を送信
						$arrRetCancel = $this->sfPaygentOrder('change_auth_cancel', $order_id, $payment_id, $status);
						// オーソリキャンセル失敗
						if($arrRetCancel['return'] == false) {
							$arrVal['memo09'] = $kind;
							$arrVal['memo06'] = $arrRes[0]['payment_id'];
							$this->pluginService->updateMdlOrderPayment($order_id, $arrVal);
							return $arrRetCancel;
						} else {
							$arrVal['memo09'] = $kind;
							$arrVal['memo06'] = $arrRes[0]['payment_id'];
							$arrVal['memo05'] = '';
						}
						break;
			        // 売上変更
					case 'change_commit':
						// 売上変更前の決済に対して売上キャンセル電文を送信
						$arrRetCancel = $this->sfPaygentOrder('change_commit_cancel', $order_id, $payment_id, $status);
						// 売上キャンセル失敗
						if($arrRetCancel['return'] == false) {
							$arrRetCancel['kind'] = $PAYGENT_CARD_COMMIT_REVICE;
							$arrVal['memo09'] = $PAYGENT_CARD_COMMIT_REVICE;
							$arrVal['memo06'] = $arrRes[0]['payment_id'];
							$this->pluginService->updateMdlOrderPayment($order_id, $arrVal);
							return $arrRetCancel;
						} else {
							$arrReturn['kind'] = $PAYGENT_CARD_COMMIT_REVICE;
							$arrVal['memo09'] = $PAYGENT_CARD_COMMIT_REVICE;
							$arrVal['memo06'] = $arrRes[0]['payment_id'];
							$arrVal['memo05'] = '';
						}
						break;
					case 'later_payment_print':
						// 後払い請求書印字データ出力

						//請求書印字データをCSV出力
						$this->outputPrintCsv($arrRes[0]);

						$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_AUTHORIZED;
						$arrVal['memo06'] = $arrRes[0]['payment_id'];
						$arrVal['memo05'] = '';

						//処理終了前にDB更新
						$this->pluginService->updateMdlOrderPayment($order_id, $arrVal);
						//処理終了(これがないとCSVにHTMLが出力される)
						// plugin統合対応
                        $this->systemService = $this->app ['eccube.plugin.service.system'];
						$response = $this->systemService->procExitResponse(null, new Response(
                                'Content',
                                Response::HTTP_OK,
                                array('content-type' => 'text/html')
                        ));
						return $response;
						break;
					case 'later_payment_reduction':
						// 後払いオーソリ変更
						if ($arrRequest['invoice_send_type'] == $INVOICE_SEND_TYPE_INCLUDE) {
							$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT;
						} else {
							$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_AUTHORIZED;
						}
						$arrVal['invoice_send_type'] = $arrRequest['invoice_send_type'];
						$arrVal['memo06'] = $arrRes[0]['payment_id'];
						$arrVal['memo05'] = '';
						break;
					case 'later_payment_cancel':
						// 後払い取消し
						$arrVal['memo06'] = $arrRes[0]['payment_id'];
						$arrVal['memo05'] = '';
						if ($status === $PAYGENT_LATER_PAYMENT_ST_CLEAR) {
							$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_SALES_CANCEL;
						} else {
							$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_CANCEL;
						}
						break;
					case 'later_payment_clear':
						// 後払い決済 売上
						$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN;
						$arrVal['memo06'] = $arrRes[0]['payment_id'];
						$arrVal['memo05'] = '';
						break;
					case 'later_payment_bill_reissue':
						// 後払い決済 請求書再発行
						$arrVal['memo06'] = $arrRes[0]['payment_id'];
						$arrVal['memo05'] = '';
						break;
					default:
						$arrVal['memo09'] = $kind;
						$arrVal['memo06'] = $arrRes[0]['payment_id'];
						$arrVal['memo05'] = '';
						break;
				}
				$arrReturn['return'] = true;
			} else {
				$arrReturn['return'] = false;
				$responseCode = $objPaygent->getResponseCode(); # 異常終了時、レスポンスコードが取得できる

				if ($beforeStatus != '' && $paygent_type != 'change_auth_cancel') {
					$arrVal['memo09'] = $beforeStatus;
				} else {
					$arrVal['memo09'] = $status;
				}

				switch($paygent_type) {
					case 'change_commit':
					// 売上変更に失敗
					$arrVal['memo05'] = "変更後の金額での売上に失敗しました。（" . $responseCode . "）<br />取引ID:" . $order_id . ", 決済ID:" . $arrSend['payment_id'];
						break;
		        	// 売上変更時のオーソリに失敗
					case 'change_commit_auth':
						$arrVal['memo05'] = "新規のオーソリ確保に失敗しました。（" . $responseCode . "）<br />ペイジェントオンラインから売上変更してください。";
						break;
		        	// 売上変更時の売上キャンセルに失敗
					case 'change_commit_cancel':
						$arrVal['memo05'] = "変更後の金額による売上が成功しましたが、変更前の売上取消に失敗しました。（" . $responseCode . "）<br />同一取引IDで複数の売上が発生しているため、取引ID:" . $order_id . ", 決済ID:" . $payment_id . "の売上をペイジェントオンラインから取り消してください。";
						break;
		        		// オーソリ変更時のオーソリキャンセルに失敗
					case 'change_auth_cancel':
						$arrVal['memo05'] = "変更後の金額によるオーソリが成功しましたが、変更前のオーソリ取消に失敗しました。（" . $responseCode . "）<br />同一取引IDで複数のオーソリが発生しているため、取引ID:" . $order_id . ", 決済ID:" . $payment_id . "のオーソリをペイジェントオンラインから取り消してください。";
						break;
					case 'later_payment_reduction':
						// 後払いオーソリ変更
						$responseDetail = $objPaygent->getResponseDetail(); # 異常終了時、レスポンス詳細が取得できる
						$responseDetail = mb_convert_encoding($responseDetail, $CHAR_CODE, "Shift-JIS");
						if ($responseCode === '15013') {
							$arrVal['memo05'] = "請求書送付方法を同梱にする場合は、お届け先情報を注文者情報に合わせて下さい。" . " エラーコード" . $responseCode;
						} else {
							$arrVal['memo05'] = "エラー詳細 : ".$responseDetail . "エラーコード" . $responseCode;
						}
						if ($responseCode === '15007') {
							// 保留
							$arrReturn['return'] = true;
							$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_RESERVE;
							$arrVal['invoice_send_type'] = $arrRequest['invoice_send_type'];
						} else if ($responseCode === '15006') {
							// NG
							$arrVal['memo09'] = $PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_NG;
							$arrVal['invoice_send_type'] = $arrRequest['invoice_send_type'];
						}
						break;
					case 'later_payment_print':
					case 'later_payment_cancel':
					case 'later_payment_bill_reissue':
					case 'later_payment_clear':
						$responseDetail = $objPaygent->getResponseDetail(); # 異常終了時、レスポンス詳細が取得できる
						$responseDetail = mb_convert_encoding($responseDetail, $CHAR_CODE, "Shift-JIS");
						$arrVal['memo05'] = "エラー詳細 : ".$responseDetail . "エラーコード" . $responseCode;
						unset($arrVal['memo09']);
						break;
					default:
						$responseDetail = $objPaygent->getResponseDetail(); # 異常終了時、レスポンス詳細が取得できる
						$responseDetail = mb_convert_encoding($responseDetail, $CHAR_CODE, "Shift-JIS");
						$arrVal['memo05'] = "エラー詳細 : ".$responseDetail . "エラーコード" . $responseCode;
						if (preg_match('/^[P|E]/', $responseCode) <= 0) {
							$arrReturn['response'] = $responseDetail. "（". $responseCode. "）";
						} elseif (strlen($responseCode) > 0) {
							$arrReturn['response'] = "（". $responseCode. "）";
						} else {
							$arrReturn['response'] = "";
						}
					break;
				}
			}

			if(0 < count($arrVal)) {
				$this->pluginService->updateMdlOrderPayment($order_id, $arrVal);
			}
		}
		return $arrReturn;
	}

	function checkError($paygent_type) {
		$arrErr = array();
		switch($paygent_type) {
			case 'later_payment_bill_reissue':
				if (!isset($_POST['client_reason_code']) || $_POST['client_reason_code'] == "") {
					$arrErr[0]['client_reason_code'] = '※ 依頼理由が選択されていません。<br />';
				}else{
					$arrErr[1]['client_reason_code_key'] = $_POST['client_reason_code'];
				}
				break;
			case 'later_payment_clear':
				if (!isset($_POST['carriers_company_code']) || $_POST['carriers_company_code'] == "") {
					$arrErr[0]['carriers_company_code'] = '※ 運送会社コードが選択されていません。<br />';
				}else{
					$arrErr[1]['carriers_company_code_key'] = $_POST['carriers_company_code'];
				}

				if (!isset($_POST['delivery_slip_number']) || $_POST['delivery_slip_number'] =="") {
					$arrErr[0]['delivery_slip_number'] = '※ 配送伝票番号が入力されていません。<br />';
				} else if (!preg_match("/^[a-zA-Z0-9-]+$/", $_POST['delivery_slip_number'])) {
					$arrErr['delivery_slip_number'] = '※ 配送伝票番号は半角英数・ハイフンで入力してください。<br />';
				} else if (mb_strlen($_POST['delivery_slip_number']) < 5 || mb_strlen($_POST['delivery_slip_number']) > 20) {
					$arrErr[0]['delivery_slip_number'] = '※ 配送伝票番号は5桁から20桁で入力してください。<br />';
					$arrErr[1]['delivery_slip_number_key'] = $_POST['delivery_slip_number'];
				}
				break;
		}
		return $arrErr;
	}

	/**
	 * 関数名：outputPrintCsv
	 * 処理内容：請求書印字データ出力
	 */
	function outputPrintCsv($arrPrintData) {

		//出力項目を定義
		$arrColumn = array(
				array("zip","郵便番号"),
				array("address1","住所1"),
				array("address2","住所2"),
				array("companyName","会社名"),
				array("sectionName","部署名"),
				array("name","氏名"),
				array("siteNameTitle","加盟店名タイトル"),
				array("siteName","請求書記載店舗名"),
				array("shopOrderIdTitle","加盟店取引IDタイトル"),
				array("shopOrderId","ご購入店受注番号"),
				array("descriptionText1","請求書記載事項1"),
				array("descriptionText2","請求書記載事項2"),
				array("descriptionText3","請求書記載事項3"),
				array("descriptionText4","請求書記載事項4"),
				array("descriptionText5","請求書記載事項5"),
				array("billServiceName","請求書発行元企業名"),
				array("billServiceInfo1","請求書発行元情報1"),
				array("billServiceInfo2","請求書発行元情報2"),
				array("billServiceInfo3","請求書発行元情報3"),
				array("billServiceInfo4","請求書発行元情報4"),
				array("billState1","請求書ステータス"),
				array("billFirstGreet1","宛名欄挨拶文欄1"),
				array("billFirstGreet2","宛名欄挨拶文欄2"),
				array("billFirstGreet3","宛名欄挨拶文欄3"),
				array("billFirstGreet4","宛名欄挨拶文欄4"),
				array("expand1","予備項目1"),
				array("expand2","予備項目2"),
				array("expand3","予備項目3"),
				array("expand4","予備項目4"),
				array("expand5","予備項目5"),
				array("expand6","予備項目6"),
				array("expand7","予備項目7"),
				array("expand8","予備項目8"),
				array("expand9","予備項目9"),
				array("expand10","予備項目10"),
				array("billedAmountTitle","請求金額タイトル"),
				array("billedAmount","請求金額"),
				array("billedFeeTax","請求金額消費税"),
				array("billOrderdayTitle","注文日タイトル"),
				array("shopOrderDate","注文日"),
				array("billSendDateTitle","請求書発行日タイトル"),
				array("billSendDate","請求書発行日"),
				array("billDeadlineDateTitle","お支払期限日タイトル"),
				array("billDeadlineDate","お支払期限日"),
				array("transactionIdTitle","お問い合せ番号タイトル"),
				array("transactionId","お問い合せ番号"),
				array("billBankInfomation","銀行振込注意文言"),
				array("bankNameTitle","銀行名タイトル"),
				array("bankName","銀行名漢字"),
				array("bankCode","銀行コード"),
				array("branchNameTitle","支店名タイトル"),
				array("branchName","支店名漢字"),
				array("branchCode","支店コード"),
				array("bankAccountNumberTitle","口座番号タイトル"),
				array("bankAccountKind","預金種別"),
				array("bankAccountNumber","口座番号"),
				array("bankAccountNameTitle","口座名義タイトル"),
				array("bankAccountName","銀行口座名義"),
				array("receiptBillDeadlineDate","払込取扱用支払期限日"),
				array("receiptName","払込取扱用購入者氏名"),
				array("invoiceBarcode","バーコード情報"),
				array("receiptCompanyTitle","収納代行会社名タイトル"),
				array("receiptCompany","収納代行会社名"),
				array("docketbilledAmount","請求金額"),
				array("docketCompanyName","受領証用購入者会社名"),
				array("docketSectionName","受領証用購入者部署名"),
				array("docketName","受領証用購入者氏名"),
				array("docketTransactionIdTitle","お問い合せ番号タイトル"),
				array("docketTransactionId","お問い合せ番号"),
				array("voucherCompanyName","払込受領書用購入者会社名"),
				array("voucherSectionName","払込受領書用購入者部署名"),
				array("voucherCustomerFullName","払込受領書用購入者氏名"),
				array("voucherTransactionIdTitle","払込受領書用お問い合せ番号タイトル"),
				array("voucherTransactionId","払込受領書用お問い合せ番号"),
				array("voucherBilledAmount","払込受領書用請求金額"),
				array("voucherBilledFeeTax","払込受領書用消費税金額"),
				array("revenueStampRequired","収入印紙文言"),
				array("goodsTitle","明細内容タイトル"),
				array("goodsAmountTitle","注文数タイトル"),
				array("goodsPriceTitle","単価タイトル"),
				array("goodsSubtotalTitle","金額タイトル"),
				array("goods1","明細内容1"),
				array("goodsAmount1","注文数1"),
				array("goodsPrice1","単価1"),
				array("goodsSubtotal1","金額1"),
				array("goodsExpand1","金額消費税1"),
				array("goods2","明細内容2"),
				array("goodsAmount2","注文数2"),
				array("goodsPrice2","単価2"),
				array("goodsSubtotal2","金額2"),
				array("goodsExpand2","金額消費税2"),
				array("goods3","明細内容3"),
				array("goodsAmount3","注文数3"),
				array("goodsPrice3","単価3"),
				array("goodsSubtotal3","金額3"),
				array("goodsExpand3","金額消費税3"),
				array("goods4","明細内容4"),
				array("goodsAmount4","注文数4"),
				array("goodsPrice4","単価4"),
				array("goodsSubtotal4","金額4"),
				array("goodsExpand4","金額消費税4"),
				array("goods5","明細内容5"),
				array("goodsAmount5","注文数5"),
				array("goodsPrice5","単価5"),
				array("goodsSubtotal5","金額5"),
				array("goodsExpand5","金額消費税5"),
				array("goods6","明細内容6"),
				array("goodsAmount6","注文数6"),
				array("goodsPrice6","単価6"),
				array("goodsSubtotal6","金額6"),
				array("goodsExpand6","金額消費税6"),
				array("goods7","明細内容7"),
				array("goodsAmount7","注文数7"),
				array("goodsPrice7","単価7"),
				array("goodsSubtotal7","金額7"),
				array("goodsExpand7","金額消費税7"),
				array("goods8","明細内容8"),
				array("goodsAmount8","注文数8"),
				array("goodsPrice8","単価8"),
				array("goodsSubtotal8","金額8"),
				array("goodsExpand8","金額消費税8"),
				array("goods9","明細内容9"),
				array("goodsAmount9","注文数9"),
				array("goodsPrice9","単価9"),
				array("goodsSubtotal9","金額9"),
				array("goodsExpand9","金額消費税9"),
				array("goods10","明細内容10"),
				array("goodsAmount10","注文数10"),
				array("goodsPrice10","単価10"),
				array("goodsSubtotal10","金額10"),
				array("goodsExpand10","金額消費税10"),
				array("goods11","明細内容11"),
				array("goodsAmount11","注文数11"),
				array("goodsPrice11","単価11"),
				array("goodsSubtotal11","金額11"),
				array("goodsExpand11","金額消費税11"),
				array("goods12","明細内容12"),
				array("goodsAmount12","注文数12"),
				array("goodsPrice12","単価12"),
				array("goodsSubtotal12","金額12"),
				array("goodsExpand12","金額消費税12"),
				array("goods13","明細内容13"),
				array("goodsAmount13","注文数13"),
				array("goodsPrice13","単価13"),
				array("goodsSubtotal13","金額13"),
				array("goodsExpand13","金額消費税13"),
				array("goods14","明細内容14"),
				array("goodsAmount14","注文数14"),
				array("goodsPrice14","単価14"),
				array("goodsSubtotal14","金額14"),
				array("goodsExpand14","金額消費税14"),
				array("goods15","明細内容15"),
				array("goodsAmount15","注文数15"),
				array("goodsPrice15","単価15"),
				array("goodsSubtotal15","金額15"),
				array("goodsExpand15","金額消費税15"),
				array("detailInfomation","明細注意事項"),
				array("expand11","予備項目11"),
				array("expand12","予備項目12"),
				array("expand13","予備項目13"),
				array("expand14","予備項目14"),
				array("expand15","予備項目15"),
				array("expand16","予備項目16"),
				array("expand17","予備項目17"),
				array("expand18","予備項目18"),
				array("expand19","予備項目19"),
				array("expand20","予備項目20"),
		);

		$arrHeader = array();
		$arrData = array();

		foreach ($arrColumn AS $column) {
			$arrHeader[] = '"'.$column[1].'"';
			$arrData[] = '"'.$arrPrintData[$column[0]].'"';
		}

		$csv_data = implode(",",$arrHeader)."\r\n";
		$csv_data .= implode(",",$arrData)."\r\n";

		//ファイル名を設定
		$csv_file = "atodene_print_". date( "YmdHis" ) .'.csv';

		$csv_data = mb_convert_encoding($csv_data, "SJIS", 'UTF-8');

		header("Content-Type: application/octet-stream; charset=Shift_JIS");
		header("Content-Disposition: attachment; filename={$csv_file}");
		header("Cache-Control: private");
		header("Pragma: private");

		// データの出力
		echo($csv_data);
	}
}