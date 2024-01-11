<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Batch;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Plugin\MdlPaygent\ServiceProvider\PaymentServiceProvider;

(@include_once __DIR__ . '/../../../../vendor/autoload.php') || @include_once __DIR__ . '/../../../../autoload.php';
require_once __DIR__ . '/../../../../vendor/twig/twig/lib/Twig/Autoloader.php';
require_once __DIR__ . '/../../../../vendor/swiftmailer/swiftmailer/lib/swift_required.php';

$autoLoader = new \Twig_Autoloader();
$autoLoader->register();

define('PAYMENT_NOTICE_IDS_CACHE', __DIR__ . "/../../../log/paygent_notice_id.log");
define('PAYGENT_LOG_PATH', __DIR__ . "/../../../log/paygent_cube.log");

if (!file_exists(PAYMENT_NOTICE_IDS_CACHE)) {
	touch(PAYMENT_NOTICE_IDS_CACHE);
}

if (!file_exists(PAYGENT_LOG_PATH)) {
	touch(PAYGENT_LOG_PATH);
}

$app = \Eccube\Application::getInstance();
$app->initialize();
$app->boot();

$objProvider = new PaymentServiceProvider();
$objProvider->register($app);

// load config
$service = $app['eccube.service.plugin'];

$pluginDir = $service->calcPluginDir("MdlPaygent");
$service->checkPluginArchiveContent($pluginDir);
$config = $service->readYml($pluginDir.'/config.yml');

$database = array();
$yml = __DIR__ . "/../../../config/eccube/database.yml";
if (file_exists($yml)) {
	$database = $service->readYml($yml);
}

$ymlMail = __DIR__ . "/../../../config/eccube/mail.yml";
if (file_exists($ymlMail)) {
	$mailInfo = $service->readYml($ymlMail);
}

$ymlClearCache = __DIR__ . "/../../../../src/Eccube/Resource/config/doctrine_cache.yml.dist";
if (file_exists($ymlClearCache)) {
	$clearCache = $service->readYml($ymlClearCache);
} else {
	$clearCache = array(
		'doctrine_cache' => array(
			'clear_cache'=> false
		),
	);
}

$app['config'] = array(
		'MdlPaygent' => array(
			'const' => $config['const'],
		),
		'order_back_order' => 4,
		'order_cancel' => 3,
		'order_deliv' => 5,
		'order_new' => 1,
		'order_pay_wait' => 2,
		'order_pending' => 7,
		'order_pre_end' => 6,
		'order_processing' => 8,
		'order_status_max' => 50,
		'tax_rule_priority' => 'product_id,product_class_id,pref_id,country_id',
		'transport' => $mailInfo['mail']['transport'],
		'port' => $mailInfo['mail']['port'],
		'doctrine_cache'=> $clearCache['doctrine_cache']
);

$objPaygent = new PaygentB2BModule($app);
$objPaygent->init();

$MdlOrderRepo = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');

$MdlPaymentRepo = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
$MdlPaymentRepo->setConfig($app['config']['MdlPaygent']['const']);
// 銀行NET用パラメータの取得
$arrPaymentDB = $MdlPaymentRepo->getPaymentDB();

$pluginService = $app['eccube.plugin.service.plugin'];
// 共通データの取得
$arrRequest = $pluginService->sfGetPaygentShare("091", '0', $arrPaymentDB[0]);

// パラメータにプラグインのバージョンを追加
$arrRequest+= array('eccube_plugin_version'=>$config['version']);

// エラーメール送信ID
$arrErrMailIds = array();
// 今回受信した payment_notice_id
$arrCurrentPaymentNoticeIds = array();

line($app);
logTrace($app, "PAYGENT BATCH START!");
line($app);

// 前回実行した payment_notice_id を取得
line($app);
logTrace($app, "Get the previous payment_notice_ids...");
$arrPreviousNoticeIds = getPaymentNoticeIds();
foreach ($arrPreviousNoticeIds as $id) {
	$pluginService->gfPrintLog($app, $id, PAYGENT_LOG_PATH);
	echo $id;
	ln($app);
}
line($app);

/*
 * 前回実行した payment_notice_id の最大値を取得
 * キャッシュファイルが空の場合は DB より取得
 *
 * キャッシュファイルが空で既存の受注データが大量に存在する場合,
 * 余計な処理を避けるため
 */
$max_payment_notice_id = 0;
if (!empty($arrPreviousNoticeIds)) {
	$max_payment_notice_id = (int) max($arrPreviousNoticeIds);
	logTrace($app, "Max payment_notice_id by $max_payment_notice_id");
} else {
	$max_payment_notice_id = $MdlOrderRepo->getMemo10ByPaymentMethod($app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE']);
	if (!is_null($max_payment_notice_id)) {
		$max_payment_notice_id = reset($max_payment_notice_id);
		logTrace($app, "Max payment_notice_id by $max_payment_notice_id[1]");
	} else {
		logTrace($app, "Max payment_notice_id by $max_payment_notice_id[1]");
	}
}

/*
 * payment_notice_id を指定せずに送信
 * success_code = 1 を受け取るまで再帰的に実行
 */
$result = requestPaygent($objPaygent, $arrRequest, array(), $app);
// ここで result = 1 を受け取っても, 後続の処理を実行する

// 今回実行時に, 空となった payment_notice_id を検索
logTrace($app, "Find lost payment_notice_ids...");
$arrNoticeIds = getLostPaymentNoticeIds($max_payment_notice_id,
		$arrCurrentPaymentNoticeIds);
foreach ($arrNoticeIds as $id) {
	$pluginService->gfPrintLog($app, $id, PAYGENT_LOG_PATH);
	echo $id;
	ln($app);
}

// payment_notice_id のキャッシュに追加
logTrace($app, "added payment_notice_ids by current ids...");
foreach ($arrCurrentPaymentNoticeIds as $id) {
	$pluginService->gfPrintLog($app, $id, PAYGENT_LOG_PATH);
	echo $id;
	ln($app);
	addPaymentNoticeId($id);
}

/*
 * 連番でない payment_notice_id を指定して送信
 */
if (!empty($arrNoticeIds)) {
	$result = requestPaygent($objPaygent, $arrRequest, $arrNoticeIds, $app);
}

// エラーメールを送信する場合は送信
if (!empty($arrErrMailIds)) {
	sendErrorMail($max_payment_notice_id, $arrErrMailIds, $app);
}

if ($result == 0) {
	line($app);
	logTrace($app, "PAYGENT BATCH FINISHED Successful!");
	line($app);
}

exit((int) $result);

/**
 * ペイジェントサーバーに電文を送信してステータスの変更処理を行う.
 *
 * $arrNoticeIds にて payment_notice_id を指定して実行した場合, payment_notice_id
 * をペイジェントサーバーに送信する. $arrNoticeIds に保持する payment_notice_id が
 * 無くなるまで再帰的に実行する.
 *
 * $arrNoticeIds を指定しない場合は, ペイジェントサーバーより success_code = 1 が
 * 返却されるまで, 再帰的に実行する.
 *
 * ペイジェントサーバーより, success_code = 0 が返却された場合は, 受注ステータス
 * を更新する.
 * success_code = 2 が返却された場合は, 差分照会エラー通知メールを送信する.
 *
 * @param PaygentB2BModule $objPaygent ペイジェントB2Bモジュールクラス
 * @param array $arrRequest ペイジェントサーバーに送信するリクエスト
 * @param array $arrNoticeIds payment_notice_id の配列
 * @global array $arrErrMailIds 差分照会エラー通知メールを送信する payment_notice_id の配列
 * @global array $arrCurrentPaymentNoticeIds 現在のバッチで取得した payment_notice_id の配列
 * @return integer 正常終了時は 0, 異常終了時は 1 を返す
 *
 */
function requestPaygent(&$objPaygent, $arrRequest, $arrNoticeIds = array(), $app) {
	global $arrErrMailIds;
	global $arrCurrentPaymentNoticeIds;

	/*
	 * payment_notice_id が指定されている場合は, 最初のIDを付与
	 */
	if (!empty($arrNoticeIds)) {
		$arrRequest['payment_notice_id'] = array_shift($arrNoticeIds);
	}

	line($app);
	logTrace($app, "BEGEN PAYGENT REQUEST!");
	line($app);

	// 電文の送付
	foreach($arrRequest as $key => $val) {
		logTrace($app, "$key => $val");
		$objPaygent->reqPut($key, $val);
	}
	$objPaygent->post();

	line($app);
	logTrace($app, "END PAYGENT REQUEST!");
	line($app);

	line($app);
	logTrace($app, "RETURN PAYGENT RESPONSE!");
	line($app);

	// レスポンスの取得
	while($objPaygent->hasResNext()) {
		// データが存在する限り、取得
		foreach ($objPaygent->resNext() as $key => $val) {
			$arrResponse[$key] = $val;
			$convertedKey = mb_convert_encoding($key, "UTF-8", "SJIS-win");
			$convertedVal = mb_convert_encoding($val, "UTF-8", "SJIS-win");
			logTrace($app, "$convertedKey => $convertedVal");
		}
	}

	line($app);
	logTrace($app, "END PAYGENT RESPONSE!");
	line($app);

	// 処理結果 0=正常終了, 1=異常終了
	$result = $objPaygent->getResultStatus();
	// payment_notice_id を空にする
	$arrRequest['payment_notice_id'] = '';

	/*
	 * 処理結果 = 1 の場合は異常終了
	 */
	if ($result == 1) {
		line($app);
		logTrace($app, "PAYGENT BATCH FAILURE!!");
		logTrace($app, "Result by $result");
		if (isset($arrResponse['response_code'])) {
			logTrace($app, "response_code by " . $arrResponse['response_code']);
		} else {
			logTrace($app, "response_code by null");
		}

		if (isset($arrResponse['response_detail'])) {
			logTrace($app, "response_detail by " . $arrResponse['response_detail']);
		} else {
			logTrace($app, "response_detail by null");
		}

		line($app);
		return (int) $result;
	}

	/*
	 * 返却データなし(success_code = 1) 又は payment_notice_id が無くなるまで
	 * 再帰的に実行する.
	 */
	switch ($arrResponse['success_code']) {

		// success_code = 1 の場合は終了. $arrNoticeIds を指定している場合は再帰する
		case 1:
			if (!empty($arrNoticeIds)) {
				$result = requestPaygent($objPaygent, $arrRequest, $arrNoticeIds, $app);
			}
			break;

			// success_code = 2 の場合はエラー通知の payment_notice_id を追加
		case 2:
			$arrErrMailIds[] = $arrResponse['payment_notice_id'];
			// ここでは break しない
			logTrace($app, "[Notice] success_code = 2 added Notice Mail by "
					. $arrResponse['payment_notice_id']);

		case 0:
			// 決済通知IDをキャッシュに追加
			logTrace($app, "added payment_notice_id by ". $arrResponse['payment_notice_id']);
			$arrCurrentPaymentNoticeIds[] = $arrResponse['payment_notice_id'];

			// 入金ステータスを更新する
			$ret = $app['eccube.plugin.mdl_paygent.repository.mdl_plugin']->getSubData($app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE']);

			if (isset($ret)) {
				$arrConfig = unserialize($ret);
			}
			$app['eccube.plugin.service.plugin']->sfUpdatePaygentOrder($arrResponse, $arrConfig, $app);

			$result = requestPaygent($objPaygent, $arrRequest, $arrNoticeIds, $app);
			break;
		default:
	}
	return (int) $result;
}

/**
 * 罫線を出力する.
 */
function line($app) {
	$log = "-----------------------------------------------------------";
	$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, PAYGENT_LOG_PATH);
	echo $log;
	ln($app);
}

/**
 * 改行(LF)を出力する.
 */
function ln($app) {
	$log = "\n";
	if (defined(PHP_EOL)) {
		$log = PHP_EOL;
	}
	$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, PAYGENT_LOG_PATH);
	echo $log;
}

/**
 * ログのプレフィクスを出力する.
 */
function logPrefix($app) {
	$log = "[";
	$log .= date("Y-m-d H:i:s");
	$log .= "] ";
	$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, PAYGENT_LOG_PATH);
	echo $log;
}

/**
 * トレースログを出力する.
 */
function logTrace($app, $log) {
	logPrefix($app);
	$app['eccube.plugin.service.plugin']->gfPrintLog($app, $log, PAYGENT_LOG_PATH);
	echo $log;
	ln($app);
}

/**
 * キャッシュにある payment_notice_id を配列で取得する.
 */
function getPaymentNoticeIds() {
	$contents = file_get_contents(PAYMENT_NOTICE_IDS_CACHE);
	if ($contents === false) {
		return array();
	} else {
		$result = unserialize($contents);
		$result = is_array($result) ? $result : array();
		sort($result, SORT_NUMERIC);
		return $result;
	}
}

/**
 * payment_notice_id のキャッシュをクリアする.
 */
function clearPaymentNoticeIds() {
	$fp = fopen(PAYMENT_NOTICE_IDS_CACHE, 'r+b');
	if ($fp !== false) {
		ftruncate($fp, 0);
		fclose($fp);
	}
}

/**
 * キャッシュの payment_notice_id を引数のIDの配列で置換する.
 */
function replacePaymentNoticeIds(&$arrResultNoticeIds) {
	clearPaymentNoticeIds();
	if (!empty($arrResultNoticeIds)) {
		foreach ($arrResultNoticeIds as $val) {
			addPaymentNoticeId($val);
		}
	}
}

/**
 * payment_notice_id をキャッシュに追加する.
 */
function addPaymentNoticeId($payment_notice_id) {
	$ids = getPaymentNoticeIds();
	$ids[] = $payment_notice_id;
	$fp = fopen(PAYMENT_NOTICE_IDS_CACHE, 'w+');
	if ($fp !== false) {
		fwrite($fp, serialize($ids));
		fclose($fp);
	}
}

/**
 * payment_notice_id の配列から, 連番ではない, 空になった payment_notice_id
 * を取得します.
 *
 * @param integer $min_payment_notice_id 前回のバッチ実行で取得した
 *   payment_notice_id の最大値. この関数では連番の開始値として扱う.
 * @param array $arrNoticeIds 今回のバッチ実行で取得した payment_notice_id の配列
 */
function getLostPaymentNoticeIds($min_payment_notice_id, &$arrNoticeIds) {
	$results = array();

	if (empty($arrNoticeIds)) {
		return $results;
	}
	sort($arrNoticeIds, SORT_NUMERIC);

	$min = (int) $min_payment_notice_id;
	$max = (int) max($arrNoticeIds);

	// 連番を走査し, 見つからなければ結果に追加
	for ($i = $min; $i < $max; $i++) {
		if ($i == $min_payment_notice_id) {
			continue;
		}
		if (!in_array($i, $arrNoticeIds)) {
			$results[] = $i;
		}
	}
	return $results;
}

/**
 * 入金検知バッチエラーメールを送信する.
 *
 * 前回実行時の payment_notice_id の最大値 + 1 から
 * success_code = 2 を受け取った payment_notice_id の最大値 - 1 までが,
 * パージ対象の payment_notice_id とする.
 */
function sendErrorMail($max_payment_notice_id, $arrErrMailIds, $app) {
	if (empty($arrErrMailIds)) {
		return;
	}

	sort($arrErrMailIds, SORT_NUMERIC);

	$from = $max_payment_notice_id + 1;
	// success_code = 2 を受け取った payment_notice_id の最大値 - 1
	$to = max($arrErrMailIds) - 1;

	$total = $to - $from + 1;

	$loader = new \Twig_Loader_Filesystem(__DIR__.'/../View');

	$twig = new \Twig_Environment($loader, array(
			'cache' => __DIR__.'/../../../cache/twig',
	));

	$template = $twig->loadTemplate('paygent_batch_error_mail.twig');

	$body = $template->render(array(
			'id_from' => $from,
			'id_to' => $to,
			'id_total' => $total,
	));

	$MdlOrderPayment = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
	$objSiteInfo = $MdlOrderPayment->getShopInfo();

	$transport = new \Swift_SmtpTransport();
	$transport::newInstance($app['config']['transport'], $app['config']['port']);

	// Create the Mailer using your created Transport
	$mailer = \Swift_Mailer::newInstance($transport);

	$message = \Swift_Message::newInstance()
	->setSubject('[' . $objSiteInfo[0]['shop_name'] . '] ' . "ペイジェント決済入金検知バッチエラー")
	->setFrom(array($objSiteInfo[0]['email04'] => $objSiteInfo[0]['shop_name']))
	->setTo(array($objSiteInfo[0]['email04']))
	->setBody($body);

	$mailer->send($message);

	logTrace($app, "Notice Error Mail by payment_notice_id on $from => $to...");
}
?>
