<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Service;

use Eccube;

/**
 * 決済モジュール用 汎用関数クラス
 */
class PaymentService
{

    private $app;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
    }

    /**
     * 決済モジュールで利用出来る決済方式の名前一覧を取得する
     *
     * @return array 支払方法
     */
    function getPaymentTypeNames()
    {
        $payments =  array(
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CREDIT'] => "クレジット",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CONVENI_NUM'] => "コンビニ(番号方式)",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_ATM'] => "ATM決済",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK'] => "銀行ネット",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER'] => "携帯キャリア",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_VIRTUAL_ACCOUNT'] => "仮想口座",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT'] => "後払い",
        );
        return $payments;
    }

    function getPaymentMethod()
    {
    	$payments =  array(
    			$this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CREDIT'] => "ペイジェント クレジット",
    			$this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CONVENI_NUM'] => "ペイジェント コンビニ(番号方式)",
    			$this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_ATM'] => "ペイジェント ATM決済",
    			$this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK'] => "ペイジェント 銀行ネット",
    			$this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER'] => "ペイジェント 携帯キャリア",
    			$this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_VIRTUAL_ACCOUNT'] => "ペイジェント 銀行振込",
    			$this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT'] => "ペイジェント 後払い（コンビニ・銀行）",
    	);
    	return $payments;
    }

    /**
     * 決済モジュールで利用出来る決済方式の名前一覧を取得する
     *
     * @return array 支払方法
     */
    function getPaymentDivisions()
    {
        return $paymentDivisions =  array(
            $this->app['config']['MdlPaygent']['const']['PAY_PAYMENT_LUMP_SUM'] => "一括払い",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_INSTALL'] => "分割払い",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_REVOLVING_CREDIT'] => "リボ払い",
            $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BONUS_LUMP_SUM'] => "ボーナス一括払い",
        );
    }

    /**
     * 決済モジュールで利用出来る決済方式の名前一覧を取得する
     *
     * @return array 支払方法
     */
    function getCareerDivisions()
    {
        return $paymentDivisions =  array(
            $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_DOCOMO'] => "ドコモケータイ払い",
            $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_AU'] => "auかんたん決済",
            $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_SOFTBANK'] => "ソフトバンクまとめて支払い",
        );
    }

    /**
     Method will set status of Payment
     */
    function getStatusCheckPayment() {
    	$credit = "0";
    	$convenienceStore = "0";
    	$atm = "0";
    	$bankNet = "0";
    	$mobileCarries = "0";
    	$eMoney = "0";
    	$virtualAcc = "0";
    	$tokenPay = "0";
    	if(!empty($_POST['credit']))
    	{
    		$credit = $_POST['credit'];
    	}
    	if(!empty($_POST['tokenPay']))
    	{
    		$tokenPay = $_POST['tokenPay'];
    	}
    	if(!empty($_POST['convenienceStore']))
    	{
    		$convenienceStore = $_POST['convenienceStore'];
    	}
    	if(!empty($_POST['atm']))
    	{
    		$atm = $_POST['atm'];
    	}
    	if(!empty($_POST['bankNet']))
    	{
    		$bankNet = $_POST['bankNet'];
    	}
    	if(!empty($_POST['mobileCarries']))
    	{
    		$mobileCarries = $_POST['mobileCarries'];
    	}
    	if(!empty($_POST['eMoney']))
    	{
    		$eMoney = $_POST['eMoney'];
    	}
    	if(!empty($_POST['virtualAcc']))
    	{
    		$virtualAcc = $_POST['virtualAcc'];
    	}

    	return $arrPaymentStatus = array(
    			"credit" => $credit,
    			"convenienceStore" => $convenienceStore,
    			"atm" => $atm,
    			"bankNet" => $bankNet,
    			"mobileCarries" => $mobileCarries,
    			"eMoney" => $eMoney,
    			"virtualAcc" => $virtualAcc,
    			"tokenPay" => $tokenPay,
    	);
    }

    /**
     * 文字列から指定バイト数を切り出す。
     *
     * @param string $value
     * @param integer $len
     * @return string 結果
     */
    function subString($value, $len)
    {
        $value = mb_convert_encoding($value, "SJIS", "UTF-8");
        for ($i = 1; $i <= mb_strlen($value); $i++) {
            $tmp = mb_substr($value, 0, $i);
            if (strlen($tmp) <= $len) {
                $ret = mb_convert_encoding($tmp, "UTF-8", "SJIS");
            } else {
                break;
            }
        }
        return $ret;
    }

    function printLog($msg)
    {
        if (is_array($msg) || is_object($msg)) {
            $msg = print_r($msg, true);
        }
        $objMdl =& PG_MULPAY_Ex::getInstance();
        $objMdl->printLog($msg);
    }

    function getPaymentTypeConfig($payment_id)
    {
    	$PaymentExtension = $this->getPaymentInfo($payment_id);
    	if ($PaymentExtension === false) {
    		return false;
    	}
    	$Payment = $PaymentExtension->getMdlPaymentMethod();
    	$memo05 = $Payment->getMemo05();
    	if (!empty($memo05)) {
    		$arrTemp = unserialize($memo05);
    		if ($arrTemp !== false) {
    			$PaymentExtension->setArrPaymentConfig($arrTemp);
    		}
    	}
    	return $PaymentExtension;
    }

    function getPaymentInfo($paymentId)
    {
    	$Payment = $this->app['orm.em']->getRepository('Plugin\MdlPaygent\Entity\MdlPaymentMethod')
    	->findOneBy(array('id' => $paymentId));
    	if (empty($Payment)) {
    		return false;
    	}
    	$PaymentExtension = new \Plugin\MdlPaygent\Entity\PaymentExtension();
    	$PaymentExtension->setMdlPaymentMethod($Payment);

    	// 決済モジュールの対象決済であるかの判断と内部識別コードの設定を同時に行う。
    	$arrPaymentCode = $this->getPaymentTypeCodes();
    	$PaymentExtension->setPaymentCode($arrPaymentCode[$Payment->getMemo03()]);
    	return $PaymentExtension;
    }

    /**
     * 決済モジュールで利用出来る決済方式の内部名一覧を取得する
     *
     * @return array 支払方法コード
     */
    function getPaymentTypeCodes()
    {
    	$constMdlPG = $this->app['config']['MdlPaygent']['const'];
    	return array(

    			$constMdlPG['PAY_PAYGENT_CREDIT'] => $constMdlPG['PAY_PAYGENT_CREDIT'],
    			$constMdlPG['PAY_PAYGENT_CONVENI_NUM'] => $constMdlPG['PAY_PAYGENT_CONVENI_NUM'],
    			$constMdlPG['PAY_PAYGENT_ATM'] => $constMdlPG['PAY_PAYGENT_ATM'],
    			$constMdlPG['PAY_PAYGENT_BANK'] => $constMdlPG['PAY_PAYGENT_BANK'],
    			$constMdlPG['PAY_PAYGENT_CAREER'] => $constMdlPG['PAY_PAYGENT_CAREER'],
    			$constMdlPG['PAY_PAYGENT_VIRTUAL_ACCOUNT'] => $constMdlPG['PAY_PAYGENT_VIRTUAL_ACCOUNT'],
    			$constMdlPG['PAY_PAYGENT_LATER_PAYMENT'] => $constMdlPG['PAY_PAYGENT_LATER_PAYMENT'],
    			$constMdlPG['PAY_PAYGENT_LINK'] => $constMdlPG['PAY_PAYGENT_LINK'],
    	);
    }

    // 受注時の初期ステータス
    function getInitStatus(){
    	return $arrInitStatus = array(
    			"020" => $this->app['config']['order_new'],            // クレジットは新規受付
    			"010" => $this->app['config']['order_pay_wait'],          // ATM決済は入金待ち
    			"030" => $this->app['config']['order_pay_wait'], // コンビニ(番号方式)は入金待ち
    			"040" => $this->app['config']['order_pay_wait'], // コンビニ(払込票方式)は入金待ち
    			"060" => $this->app['config']['order_pay_wait'],        // 銀行は入金待ち
    			"100" => $this->app['config']['order_new'],         // キャリア決済は新規受付
    			"150" => $this->app['config']['order_new'],          // 電子マネー決済は新規受付
    			"160" => $this->app['config']['order_new'],     // Yahoo!ウォレット決済は新規受付
    			"link" => $this->app['config']['order_new'],              // リンクは新規受付
    			"070" => $this->app['config']['order_pay_wait'], // 仮想口座決済は入金待ち
    			"220" => $this->app['config']['order_new'],      // 後払い決済は新規受付
    	);
    }

    // コンビニの種類
    function getConvenience(){
    	return $arrConvenience = array(
    			"" => 'ご選択ください',
    			"00C016" => 'セイコーマート',
    			"00C002" => 'ローソン',
    			"00C004" => 'ミニストップ',
    			"00C005" => 'ファミリーマート',
    			"00C006" => 'サンクス',
    			"00C007" => 'サークルK',
    			"00C014" => 'デイリーヤマザキ',
    			"00C001" => 'セブンイレブン'
    	);
    }

    function getDispKind(){
    	return $arrDispKind = array(
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_AUTH_CANCEL'] => 'オーソリキャンセル',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT'] => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_REVICE'] => '売上変更',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_REVICE_PROCESSING'] => '売上変更処理中',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_COMMIT_CANCEL'] => '売上キャンセル',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT'] => 'オーソリ変更',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT_PROCESSING'] => 'オーソリ変更処理中',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT'] => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_CANCEL'] => '取消',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_REVICE'] => '売上変更',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_EMONEY_COMMIT_CANCEL'] => '取消',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_EMONEY_COMMIT_REVICE'] => '売上変更',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_YAHOOWALLET_COMMIT'] => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_YAHOOWALLET_COMMIT_CANCEL'] => '取消',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_YAHOOWALLET_COMMIT_REVICE'] => '金額変更',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_NG'] => '審査NG',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_RESERVE'] => '審査保留',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT'] => '審査OK(印字データ取得前)',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED'] => '審査OK',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_CANCEL'] => 'オーソリキャンセル',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_EXPIRE'] => '審査OK',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN'] => '売上処理中',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_SALES_RESERVE'] => '売上保留',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR'] => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR_SALES_CANCEL_INVALIDITY'] => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_SALES_CANCEL'] => '売上キャンセル',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_CANCEL'] => '取消',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_CLEAR'] => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_REDUCTION'] => 'オーソリ変更',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_BILL_REISSUE'] => '請求書再発行',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_PRINT'] => '請求書印字データ出力',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'] . '_01' => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'] . '_02' => '売上(不足）',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'] . '_06' => '売上',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'] . '_07' => '売上（不足）',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'] . '_08' => '売上（過多）',
    			$this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'] . '_99' => '取消',
    	);
    }

    function getArrCarriersCompanyCode() {
	    return $arrCarriersCompanyCode = array(
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_DEFAULT'] => '選択してください',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_SAGAWA'] => '佐川急便',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_YAMATO'] => 'ヤマト運輸',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_SEINO'] => '西濃運輸',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_REGISTERED'] => '郵便書留',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_YUPACK'] => 'ゆうパック',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_FUKUTSU'] => '福山通運',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_SPECIFY_TIME'] => '配達時間指定郵便',
		    $this->app['config']['MdlPaygent']['const']['CARRIERS_COMPANY_CODE_ECOHAI'] => 'エコ配',
		);
    }
    function getArrClientReasonCode() {
	    return $arrClientReasonCode = array(
    		$this->app['config']['MdlPaygent']['const']['CLIENT_REASON_CODE_DEFAULT'] => '選択してください',
    		$this->app['config']['MdlPaygent']['const']['CLIENT_REASON_CODE_BILL_LOSS'] => '請求書紛失',
    		$this->app['config']['MdlPaygent']['const']['CLIENT_REASON_CODE_BILL_NO_DELIVERY'] => '請求書未達',
    		$this->app['config']['MdlPaygent']['const']['CLIENT_REASON_CODE_MOVE'] => '転居',
    		$this->app['config']['MdlPaygent']['const']['CLIENT_REASON_CODE_OTHER'] => 'その他',
	    );
    }

    function getPaymentClass(){
    	// クレジット分割回数
    	return $arrPaymentClass = array(
    			'10' => '一括払い',
    			'61-2' => '分割2回払い',
    			'61-3' => '分割3回払い',
    			'61-6' => '分割6回払い',
    			'61-10' => '分割10回払い',
    			'61-15' => '分割15回払い',
    			'61-20' => '分割20回払い',
    			'80' => 'リボ払い',
    			'23' => 'ボーナス一括払い'
    	);
    }

    // トークン接続先
    function getTokenEnv() {
    	return array(
    			'0' => '試験環境',
    			'1' => '本番環境'
    	);
    }

    function getPaymentForAdminOrder(){
    	return $arrPaymentMethod = array(
    			'MDL_PAYGENT_CODE' => $this->app ['config'] ['MdlPaygent'] ['const'] ['MDL_PAYGENT_CODE'],
    			'PAYGENT_CREDIT' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CREDIT'],
    			'PAYGENT_CAREER_D' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CAREER_D'],
    			'PAYGENT_CAREER_A' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CAREER_A'],
    			'PAYGENT_CAREER_S' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CAREER_S'],
    			'PAYGENT_CARD_COMMIT' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CARD_COMMIT'],
    			'PAYGENT_LATER_PAYMENT' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_LATER_PAYMENT'],
    			'PAYGENT_LATER_PAYMENT_ST_AUTHORIZED' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED'],
    			'PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN'],
    			'PAYGENT_LATER_PAYMENT_ST_SALES_RESERVE' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_LATER_PAYMENT_ST_SALES_RESERVE'],
    			'PAYGENT_LATER_PAYMENT_ST_CLEAR' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_LATER_PAYMENT_ST_CLEAR'],
    			'PAYGENT_EMONEY_W' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_EMONEY_W'],
    			'PAYGENT_CARD_COMMIT_REVICE' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CARD_COMMIT_REVICE'],
    			'PAYGENT_CAREER_COMMIT' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CAREER_COMMIT'],
    			'PAYGENT_CAREER_COMMIT_REVICE' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_CAREER_COMMIT_REVICE'],
    			'PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT' => $this->app ['config'] ['MdlPaygent'] ['const'] ['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT'],
    			'INVOICE_SEND_TYPE_INCLUDE' => $this->app ['config'] ['MdlPaygent'] ['const'] ['INVOICE_SEND_TYPE_INCLUDE'],
    	);
    }

    // 請求書の同梱
    function getInvoiceIncludeOption(){
    	return $arrInvoiceIncludeOption = array(
    			'1' => '請求書を商品に同梱して配送する'
    	);
    }

    // 後払い決済 オーソリ変更 請求書送付方法
    function getInvoiceSendTypeOption() {
    	return array(
    			$this->app ['config'] ['MdlPaygent'] ['const'] ['OPTION_INVOICE_SEND_TYPE_SEPARATE'] => '別送',
    			$this->app ['config'] ['MdlPaygent'] ['const'] ['OPTION_INVOICE_SEND_TYPE_INCLUDE'] => '同梱',
    	);
    }

    function getNumberingType() {
    	return array(
    			$this->app ['config'] ['MdlPaygent'] ['const'] ['NUMBERING_TYPE_CYCLE'] => '回転付番のみ',
    			$this->app ['config'] ['MdlPaygent'] ['const'] ['NUMBERING_TYPE_FIX'] => '固定・回転付番併用',
    	);
    }
    /**
     * 関数名：getInvoiceSendType
     * 処理内容：請求書送付方法が同梱か別送かを判定する
     * 戻り値：判定結果
     */
    function getInvoiceSendType($orderId) {
    	// 後払い決済用パラメータの取得
    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	$payPaygentLaterPayment = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT'];
    	$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentLaterPayment);
    	$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);

    	//「決済モジュール設定画面で同梱が設定されている場合」かつ「注文者と配送先が同じ場合」
    	if ($arrOtherParam['invoice_include'] && $this->isSameOrderShip($orderId)) {
    		return $this->app['config']['MdlPaygent']['const']['OPTION_INVOICE_SEND_TYPE_INCLUDE'];
    	} else {
    		return $this->app['config']['MdlPaygent']['const']['INVOICE_SEND_TYPE_SEPARATE'];
    	}
    }

    /**
     * 関数名：isSameOrderShip
     * 処理内容：注文者と配送先が同じかどうかを返す
     * 戻り値：結果
     */
    function isSameOrderShip($orderId) {
    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	// 受注情報
    	$arrOrder = $MdlPaymentRepo->getCurrentOrder($orderId);
    	$arrOrder = reset($arrOrder);

    	// 配送情報
    	$arrShippings = $MdlPaymentRepo->getCurrentShipping($orderId);
    	$arrShippings = reset($arrShippings);

    	//配送先側の照合項目を定義
    	$arrCompareParam = array(
    			"name01",
    			"name02",
    			"kana01",
    			"kana02",
    			"zip01",
    			"zip02",
    			"pref",
    			"addr01",
    			"addr02",
    			"tel01",
    			"tel02",
    			"tel03",
    	);

    	$isSame = true;

    	foreach ($arrCompareParam AS $compareParam) {
    		if ($arrOrder[$compareParam] != $arrShippings[$compareParam]) {
    			$isSame = false;
    			break;
    		}
    	}
    	return $isSame;
    }

    /**
     * 関数名：clearShipParam
     * 処理内容：配送先関連のパラメータをクリア
     * 戻り値：クリア後の配列
     */
    function clearShipParam($arrParam) {

    	$arrClearParam = array(
    			"ship_name_kanji",
    			"ship_name_kana",
    			"ship_zip_code",
    			"ship_address",
    			"ship_tel"
    	);

    	foreach ($arrClearParam AS $clearParam) {
    		$arrParam[$clearParam] = "";
    	}

    	return $arrParam;
    }
}
