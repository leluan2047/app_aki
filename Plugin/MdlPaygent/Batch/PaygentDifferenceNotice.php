<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Batch;

use \Eccube\Application;

/**
 * 差分通知
 */
class PaygentDifferenceNotice {

	private $pathLog;

	public function index(\Eccube\Application $app)
	{
		$this->pathLog = $app['config']['root_dir'].$app['config']['MdlPaygent']['const']['PAYGENT_LOG_PATH'];

		ob_end_clean();

		$this->line($app);
		$this->logTrace($app, "BEGIN PAYGENT ACCEPTED THE REQUEST!");
		$this->line($app);

		$this->requestMerchant($app);

		$this->line($app);
		$this->logTrace($app, "END PAYGENT ACCEPTED THE REQUEST!");
		$this->line($app);

		return $app['view']->render('MdlPaygent/View/paygent_difference_notice.twig');
	}

	/**
	 * ペイジェントサーバーから送られてくるリクエストを処理して、ステータスの変更処理を行う。<br>
	 */
	function requestMerchant($app) {

		$arrParam = array();

		// 存在チェック
		$issetFlg = $this->checkError($app);

		if ($issetFlg) {
			// ペイジェントから送られてくるリクエストパラメータを取得
			$arrParam['trading_id'] = $_POST['trading_id'];
			$arrParam['payment_id'] = $_POST['payment_id'];
			$arrParam['payment_status'] = $_POST['payment_status'];
			$arrParam['payment_type'] = $_POST['payment_type'];
			$arrParam['payment_notice_id'] = $_POST['payment_notice_id'];
			$arrParam['payment_date'] = $_POST['payment_date'];
			$arrParam['clear_detail'] = $_POST['clear_detail'];
			$arrParam['payment_amount'] = $_POST['payment_amount'];

			// 取得したパラメータをログに出力する
			foreach ($arrParam as $key => $val) {
				$convertedKey = mb_convert_encoding($key, "UTF-8", "SJIS-win");
				$convertedVal = mb_convert_encoding($val, "UTF-8", "SJIS-win");
				$this->logTrace($app, "$convertedKey => $convertedVal");
			}

			// 入金ステータスを更新する
			$ret = $app['eccube.plugin.mdl_paygent.repository.mdl_plugin']->getSubData($app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE']);

			if (isset($ret)) {
				$arrConfig = unserialize($ret);
			}
			$app['eccube.plugin.service.plugin']->sfUpdatePaygentOrder($arrParam, $arrConfig);

		}
	}

	/**
	 * 関数名：checkError
	 * 処理内容：チェック処理を行う。</br>
	 * エラーの場合は、ログにエラー内容を出力する。
	 *
	 * @return $issetFlg
	 */
	function checkError($app) {
		$issetFlg = true;
		// 送られてくるパラメータがnullでないかの確認

		if (empty($_POST['payment_notice_id'])) {
			// nullの場合はログに出力する
			$this->logTrace($app, "決済種別ID -> ". $_POST['payment_notice_id'] ."に値がありません。");
			$issetFlg = false;
		}
		if (empty($_POST['payment_id'])) {
			// nullの場合はログに出力する
			$this->logTrace($app, "決済ID -> ". $_POST['payment_id'] ."に値がありません。");
			$issetFlg = false;
		}
		if (empty($_POST['trading_id'])) {
			if ($_POST['payment_type'] != $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_VIRTUAL_ACCOUNT']) {
				// nullの場合はログに出力する
				$this->logTrace($app, "マーチャント取引ID -> ". $_POST['trading_id'] ."に値がありません。");
				$issetFlg = false;
			}
		}
		if (empty($_POST['payment_type'])) {
			// nullの場合はログに出力する
			$this->logTrace($app, "決済種別CD -> ". $_POST['payment_type'] ."に値がありません。");
			$issetFlg = false;
		}
		if (empty($_POST['payment_status'])) {
			// nullの場合はログに出力する
			$this->logTrace($app, "決済ステータス -> ". $_POST['payment_status'] ."に値がありません。");
			$issetFlg = false;
		}
		if (empty($_POST['payment_amount'])) {
			if ($_POST['payment_type'] != $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_VIRTUAL_ACCOUNT']) {
				// nullの場合はログに出力する
				$this->logTrace($app, "決済金額 -> ". $_POST['payment_amount'] ."に値がありません。");
				$issetFlg = false;
			}
		}

		// 型チェック
		if (! empty($_POST['payment_date'])) {
			if (! preg_match('/^\d{14}$/', $_POST['payment_date']) || ! strtotime($_POST['payment_date'])) {
				// 支払日時が null ではなく、yyyyMMddHHmmss の日付として不正な場合はログに出力する
				$this->logTrace($app, "支払日時 -> ". $_POST['payment_date'] ."の値が不正です。");
				$issetFlg = false;
			}
		}

		// 存在チェック
		$MdlOrderPayment = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
		$arrOrder = $MdlOrderPayment->getOrderInfo($_POST['trading_id']);

		if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_VIRTUAL_ACCOUNT']) {
			// 仮想口座決済の場合
			// 存在チェックをスキップして後続の処理でメール送信する
		} else if (count($arrOrder[0]) <= 0) {
			// 存在しなかった場合
			$this->logTrace($app, "マーチャント取引ID ->" . $_POST['trading_id'] . "が一致するデータは受注情報に存在しません。");
			$issetFlg = false;
		} else {
			// 銀行ネット決済時
			if ($arrOrder[0]['memo08'] == $app['config']['MdlPaygent']['const']['PAYGENT_BANK']) {
				if ($arrOrder[0]['payment_total'] != $_POST['payment_amount'] ) {
					$this->logTrace($app, "マーチャント取引ID ->" . $_POST['trading_id'] .
							"決済金額 -> ". $_POST['payment_amount'] . "が一致するデータは受注情報に存在しません。");
					$issetFlg = false;
				}
			} else {
				if ($arrOrder[0]['payment_total'] != $_POST['payment_amount'] || $arrOrder[0]['memo06'] != $_POST['payment_id']) {
					if ($arrOrder['memo09'] == $app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_REVICE']
							|| $arrOrder[0]['memo09'] == $app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_REVICE']
							|| $arrOrder[0]['memo09'] == $app['config']['MdlPaygent']['const']['PAYGENT_EMONEY_COMMIT_REVICE']
							|| $arrOrder[0]['memo09'] == $app['config']['MdlPaygent']['const']['PAYGENT_YAHOOWALLET_COMMIT_REVICE']) {
								$issetFlg = false;
							} else {
								$this->logTrace($app, "決済ID -> " . $_POST['payment_id'] . "マーチャント取引ID ->" . $_POST['trading_id'] .
										"決済金額 -> ". $_POST['payment_amount'] . "が一致するデータは受注情報に存在しません。");
								$issetFlg = false;
							}
				}
			}
		}

		// 複合チェック
		if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_ATM']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PAYMENT_EXPIRED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_CREDIT']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_REGISTRATION']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_NG_AUTHORITY']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_3DSECURE_INTERRUPTION']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_OK']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_CANCELED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_EXPIRED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED_EXPIRATION_CANCELLATION_SALES']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_SALES_CANCELLATION']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_CONVENI_NUM']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PAYMENT_EXPIRED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRELIMINARY_PRE_DETECTION']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRELIMINARY_CANCELLATION']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_BANK']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_REGISTRATION_SUSPENDED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_CAREER']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_REGISTRATION_SUSPENDED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_OK']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_COMPLETED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_CANCELED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_EXPIRED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PENDING_SALES']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_NO_PENDING']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED_EXPIRATION_CANCELLATION_SALES']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_COMPLETE_CLEARED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_SALES_CANCELLATION']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_COMPLETE_CANCELLATION']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_EMONEY']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED_EXPIRATION_CANCELLATION_SALES']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_SALES_CANCELLATION']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_YAHOOWALLET']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_NG_AUTHORITY']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_OK']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_CANCELED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_EXPIRED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED_EXPIRATION_CANCELLATION_SALES']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_SALES_CANCELLATION']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_VIRTUAL_ACCOUNT']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PAYMENT_EXPIRED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PAYMENT_INVALIDITY_NO_CLEAR']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->" . $_POST['payment_type'] . "で決済ステータス -> " . $_POST['payment_status'] . "は存在しません。");
						$issetFlg = false;
					}
		} else if ($_POST['payment_type'] == $app['config']['MdlPaygent']['const']['PAYMENT_TYPE_LATER_PAYMENT']) {
			if ($_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORIZE_NG']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORIZED_BEFORE_PRINT']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORIZED']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORIZE_CANCEL']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_AUTHORIZE_EXPIRE']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_SALES_RESERVE']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_CLEAR']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_CLEAR_SALES_CANCEL_INVALIDITY']
					|| $_POST['payment_status'] == $app['config']['MdlPaygent']['const']['STATUS_SALES_CANCEL']) {
						return $issetFlg;
					} else {
						$this->logTrace($app, "決済種別CD ->" . $_POST['payment_type'] . "で決済ステータス -> " . $_POST['payment_status'] . "は存在しません。");
						$issetFlg = false;
					}
		} else {
			$this->logTrace($app, "決済種別CD ->". $_POST['payment_type'] ."で決済ステータス -> ".$_POST['payment_status'] ."は存在しません。");
			$issetFlg = false;
		}
		return $issetFlg;
	}

	/**
	 * 罫線を出力する.
	 */
	function line($app) {
		$log = "-----------------------------------------------------------";
		$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, $this->pathLog);
		$this->ln($app);
	}

	/**
	 * 改行(LF)を出力する.
	 */
	function ln($app) {
		$log = "\n";
		if (defined(PHP_EOL)) {
			$log = PHP_EOL;
		}
		$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, $this->pathLog);
	}

	/**
	 * ログのプレフィクスを出力する.
	 */
	function logPrefix($app) {
		$log = "[";
		$log .= date("Y-m-d H:i:s");
		$log .= "] ";
		$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, $this->pathLog);
	}

	/**
	 * トレースログを出力する.
	 */
	function logTrace($app, $log) {
		$this->logPrefix($app);
		$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, $this->pathLog);
		$this->ln($app);
	}
}
?>
