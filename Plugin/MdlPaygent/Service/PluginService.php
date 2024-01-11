<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Service;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModule;
use Eccube\Application;
use Eccube\Entity\OrderDetail;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Entity\MailHistory;
use Eccube\Common\Constant;

/**
 * 決済モジュール基本クラス
 */
class PluginService
{
    private $app;

    private $pluginCode;

    public function __construct(\Eccube\Application $app)
    {
        $this->app = $app;
        $this->init();
    }

    /** サブデータを保持する変数 */
    var $subData = null;

    /** モジュール情報 */
    var $pluginInfo = array(
        'paymentName' => 'ペイジェント決済プラグイン',
        'pluginName' => 'ペイジェント決済プラグイン',
        'pluginCode' => 'MdlPaygent',
        'mdlPluginVersion' => '1.0',
    );

    /**
     * Enter description here...
     *
     * @var unknown_type
     */
    var $updateFile = array();

    var $installSubData = array(
    		'settlement_division' => 2,
    		'merchant_id'=> null,
    		'connect_id' => null,
    		'connect_password' => null,
    		'payment' => array(),
    		'payment_division' => array(),
    		'security_code' => 0,
    		'credit_3d' => 0,
    		'stock_card' => 0,
    		'token_pay' => 0,
    		'token_env' => 0,
    		'token_key' => null,
    		'conveni_limit_date_num' => "15",
    		'atm_limit_date' => "30",
    		'payment_detail' => null,
    		'asp_payment_term' => "7",
    		'claim_kanji' => null,
    		'claim_kana' => null,
    		'copy_right' => null,
    		'free_memo' => null,
    		'career_division' => array(),
    		'result_get_type' => 0,
    		'exam_result_notification_type' => 0,
    		'link_url' => null,
    		'hash_key' => null,
    		'card_class' => 0,
    		'card_conf' => 0,
    		'link_payment_term' => "5",
    		'merchant_name' => null,
    		'link_copy_right' => null,
    		'link_free_memo' => null,
    		'invoice_include' => null,
    		'numbering_type' => 0,
    		'virtual_account_limit_date' => null,
    );

    /**
     * コンストラクタ
     *
     * @return void
     */
    function PluginService()
    {

    }

    /**
     * 初期化処理.
     */
    function init()
    {
        foreach ($this->pluginInfo as $k => $v) {
            $this->$k = $v;
        }
    }

    /**
     * 終了処理.
     */
    function destroy()
    {
    }

    /**
     * モジュール表示用名称を取得する
     *
     * @return string
     */
    function getName()
    {
        return $this->pluginName;
    }

    /**
     * 支払い方法名(決済モジュールの場合のみ)
     *
     * @return string
     */
    function getPaymentName()
    {
        return $this->paymentName;
    }

    /**
     * モジュールコードを取得する
     *
     * @param boolean $toLower trueの場合は小文字へ変換する.デフォルトはfalse.
     * @return string
     */
    function getCode($toLower = false)
    {
        $pluginCode = $this->pluginCode;
        return $pluginCode;
    }

    /**
     * モジュールバージョンを取得する
     *
     * @return string
     */
    function getVersion()
    {
        return $this->mdlPluginVersion;
    }

    /**
     * インストール処理
     *
     * @param boolean $force true時、上書き登録を行う
     */
    function install($force = false)
    {
        $subData = $this->getSubData();
        if (is_null($subData) || $force) {
        	foreach ($this->installSubData as $key => $item) {
        		$this->registerSubdata($this->installSubData[$key], $key);
        	}
        }
    }

    /**
     * サブデータを取得する.
     *
     * @return mixed|null
     */
    function getSubData($key = null)
    {
        if (isset($this->subData)) {
            if (is_null($key)) {
                return $this->subData;
            } else {
                return $this->subData[$key];
            }
        }

        $pluginCode = $this->getCode(true);
        $ret = $this->app['orm.em']->getRepository('Plugin\MdlPaygent\Entity\MdlPlugin')
            ->getSubData($pluginCode);

        if (isset($ret)) {
            $this->subData = unserialize($ret);
            if (is_null($key)) {
                return $this->subData;
            } else {
                return $this->subData[$key];
            }
        }
        return null;
    }

    /**
     * サブデータをDBへ登録する
     * $keyがnullの時は全データを上書きする
     *
     * @param mixed $data
     * @param string $key
     */
    function registerSubData($data, $key = null)
    {
        $subData = $this->getSubData();

        if (is_null($key)) {
            $subData = $data;
        } else {
            $subData[$key] = $data;
        }
        $subDataSer = serialize($subData);

        $pluginCode = $this->getCode(true);
        $MdlPlugin = $this->app['orm.em']->getRepository('Plugin\MdlPaygent\Entity\MdlPlugin')
            ->findOneBy(array('code' => $pluginCode));
        if (!is_null($MdlPlugin)) {
            $MdlPlugin->setSubData($subDataSer);
            $this->app['orm.em']->persist($MdlPlugin);
            $this->app['orm.em']->flush();
        }

        $this->subData = $subData;
    }

    /**
     * 関数名：sfPaygentTest
     * 処理内容：接続テスト
     * 戻り値：取得結果
     */
    function sfPaygentTest(&$arrParam) {
        // 接続モジュールのインスタンス取得 (コンストラクタ)と初期化
        $objPaygent = new PaygentB2BModule($this->app);
        $objPaygent->init();

        $paygentRef = $this->app['config']['MdlPaygent']['const']['PAYGENT_REF'];
        // 共通データの取得
        $arrSend = $this->sfGetPaygentShare($paygentRef, '0', $arrParam);
        $arrSend['payment_notice_id'] = '0';

        // 電文の送付
        foreach($arrSend as $key => $val) {
            $objPaygent->reqPut($key, $val);
        }
        $objPaygent->post();

        // 処理結果取得（共通）
        $resultStatus = $objPaygent->getResultStatus(); # 処理結果 0=正常終了, 1=異常終了

        if($resultStatus === "0") {
            return true;
        } else {
            $arrParam['result_message'] = '';
            if (method_exists($objPaygent, 'getResultMessage')) {
                $arrParam['result_message'] = mb_convert_encoding($objPaygent->getResultMessage(), $this->app['config']['char_code'], 'Shift_JIS');

                $pathLog = $this->app['config']['root_dir'].$this->app['config']['MdlPaygent']['const']['PAYGENT_LOG_PATH'];
               	$this->gfPrintLog($this->app, $arrParam['result_message'], $pathLog);
            }
            return false;
        }
    }

    /**
     * 関数名：sfGetPaygentShare
     * 処理内容：ペイジェント情報送信の共通処理
     * 戻り値：取得結果
     */
    function sfGetPaygentShare($telegram_kind, $order_id, $arrParam, $payment_id = "") {
        /** 共通電文 **/
        // マーチャントID
        $arrSend['merchant_id'] = $arrParam['merchant_id'];
        // 接続ID
        $arrSend['connect_id'] = $arrParam['connect_id'];
        // 接続パスワード
        $arrSend['connect_password'] = $arrParam['connect_password'];
        // 電文種別ID
        $arrSend['telegram_kind'] = $telegram_kind;
        // 電文バージョン
        $arrSend['telegram_version'] = $this->app['config']['MdlPaygent']['const']['TELEGRAM_VERSION'];
        // マーチャント取引ID
        $arrSend['trading_id'] = $order_id;
        // 決済ID
        if (strlen($payment_id) > 0) $arrSend['payment_id'] = $payment_id;
        // EC-CUBEからの電文であることを示す。
        $arrSend['partner'] = 'lockon';
        // EC-CUBE本体のバージョン
        $arrSend['eccube_version'] = Constant::VERSION;
        // 決済プラグインのバージョン
        // 差分照会バッチの場合はなぜか設定ファイルのパスが取れないためバッチ本体側でパラメータを追加する
        if ($telegram_kind != $this->app['config']['MdlPaygent']['const']['PAYGENT_REF']) {
            $arrSend['eccube_plugin_version'] = $this->getPluginVersion();
        }

        return $arrSend;
    }

    /**
     * 関数名：sfPaygentResponseCard
     * 処理内容：応答を処理する
     * 戻り値：取得結果
     */
    function sfPaygentResponseCard($telegram_kind, $objPaygent, $customer_id) {

    	// 処理結果取得（共通）
    	$resultStatus = $objPaygent->getResultStatus(); # 処理結果 0=正常終了, 1=異常終了
    	$responseCode = $objPaygent->getResponseCode(); # 異常終了時、レスポンスコードが取得できる
    	$responseDetail = $objPaygent->getResponseDetail(); # 異常終了時、レスポンス詳細が取得できる
    	$responseDetail = mb_convert_encoding($responseDetail, $this->app['config']['char_code'], "Shift-JIS");

    	// 異常終了
    	if ($resultStatus == 1) {
    		$arrResOther['result'] = $resultStatus;
    		$arrResOther['code'] = $responseCode;
    		$arrResOther['detail'] = $responseDetail;
    		foreach($arrResOther as $key => $val) {
    		}
    		$arrRes[0]['result'] = $resultStatus;

    		// 正常終了
    	} else {
    		// レスポンスの取得
    		$arrRes[0]['result'] = $resultStatus;
    		while($objPaygent->hasResNext()) {
    			// データが存在する限り取得
    			$arrRes[] = $objPaygent->resNext(); # 要求結果取得
    		}
    		$num_card = (isset($arrRes[1]['num_of_cards'])) ? $arrRes[1]['num_of_cards'] : "0";
    		switch($telegram_kind) {
    			// カード情報設定
    			case $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_STOCK_SET']:
    				if ($num_card > 0) {
    					$arrPaymentCard = $this->app ['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\Customer');
    					$stockGetCustomer = $arrPaymentCard->findOneBy(array('id'=>$customer_id));
    					if (!empty($stockGetCustomer)) {
    						$stockGetCustomer->setPaygentCard(1);
    						$this->app['orm.em']->persist($stockGetCustomer);
    					}
    					$this->app['orm.em']->flush();
    				}
    				break;
    				// カード情報削除
    			case $this->app['config']['MdlPaygent']['const']['PAYGENT_CARD_STOCK_DEL']:
    				if ($num_card <= 0) {
    					$arrPaymentCard = $this->app ['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\Customer');
    					$stockGetCustomer = $arrPaymentCard->findOneBy(array('id'=>$customer_id));
    					if (!empty($stockGetCustomer)) {
    						$stockGetCustomer->setPaygentCard(0);
    						$this->app['orm.em']->persist($stockGetCustomer);
    					}
    					$this->app['orm.em']->flush();
    				}
    				break;
    			default:
    				break;
    		}
    	}

    	// 結果とメッセージを返却
    	if (preg_match('/^[P|E]/', $responseCode) <= 0) {
    		$arrRes[0]['response'] = "<br />".$responseDetail. "（". $responseCode. "）";
    	} else {
    		$arrRes[0]['response'] = "（". $responseCode. "）";
    	}
    	return $arrRes;
    }

    /**
     * 関数名：sfPaygentResponse
     * 処理内容：応答を処理する
     * 戻り値：取得結果
     */
    function sfPaygentResponse($telegram_kind, $objPaygent, $orderId, $arrInput, $arrData=null, $arrDataMemo=null) {
    	if ($arrDataMemo == null) {
    		$arrDataMemo = $this->resetMemo();
    	}
    	$arrConvenience = $this->app['eccube.plugin.service.payment']->getConvenience();

    	// 処理結果取得（共通）
    	$resultStatus = $objPaygent->getResultStatus(); # 処理結果 0=正常終了, 1=異常終了
    	$responseCode = $objPaygent->getResponseCode(); # 異常終了時、レスポンスコードが取得できる
    	$responseDetail = $objPaygent->getResponseDetail(); # 異常終了時、レスポンス詳細が取得できる
    	$responseDetail = mb_convert_encoding($responseDetail, $this->app['config']['char_code'], "Shift-JIS");

    	// 取得した値をログに保存する。
    	if ($resultStatus == 1) {
    		$arrResOther['result'] = $resultStatus;
    		$arrResOther['code'] = $responseCode;
    		$arrResOther['detail'] = $responseDetail;
    		foreach($arrResOther as $key => $val) {
    			$pathLog = $this->app['config']['root_dir'].$this->app['config']['MdlPaygent']['const']['PAYGENT_LOG_PATH'];
    			$this->app['eccube.plugin.service.plugin']->gfPrintLog($this->app, $key."->".$val, $pathLog);
    		}
    	}
    	// レスポンスの取得
    	while($objPaygent->hasResNext()) {
    		# データが存在する限り、取得
    		$arrRes[] = $objPaygent->resNext(); # 要求結果取得
    	}

    	if (!isset($arrRes)) {
    		$arrRes[] = null;
    	}
    	// 決済毎に異なる処理
    	switch($telegram_kind) {
    		// クレジット決済の場合
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_CREDIT']:
    			// 空の配列を格納しておく
    			$arrVal["memo02"] = serialize(array());
    			break;

    			// コンビニ決済（番号方式）の場合
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_CONVENI_NUM']:
    			// お支払可能なコンビニ
    			$cvsLine = "";

    			// 応答情報.利用可能コンビニ企業CD を文字列に変換し、"," で結合する
    			$arrCvs = split("-", $arrRes[0]['usable_cvs_company_id']);
    			foreach ($arrCvs as $val) {
    				if ($cvsLine !== "") {
    					$cvsLine .= ",";
    				}
    				$cvsLine .= $arrConvenience[$val];
    			}

    			// 決済ベンダ受付番号
    			$receiptNumName = "";
    			// 特記事項
    			$confirmMemo = "";

    			$CODE_SEVENELEVEN = $this->app['config']['MdlPaygent']['const']['CODE_SEVENELEVEN'];
    			$CODE_LOWSON = $this->app['config']['MdlPaygent']['const']['CODE_LOWSON'];
    			$CODE_MINISTOP = $this->app['config']['MdlPaygent']['const']['CODE_MINISTOP'];
    			$CODE_SEICOMART = $this->app['config']['MdlPaygent']['const']['CODE_SEICOMART'];
    			$CODE_FAMILYMART = $this->app['config']['MdlPaygent']['const']['CODE_FAMILYMART'];
    			$CODE_SEICOMART = $this->app['config']['MdlPaygent']['const']['CODE_SEICOMART'];

    			// 選択されたコンビニ毎の処理
    			switch ($arrInput['cvs_company_id']) {
    				// セブンイレブンの場合
    				case $CODE_SEVENELEVEN:
    					$receiptNumName = "払込票番号";
    					$confirmMemo = "";
    					break;

    					// サンクス、サークルK、デイリーヤマザキの場合
    				case $this->app['config']['MdlPaygent']['const']['CODE_SUNKUS']:
    				case $this->app['config']['MdlPaygent']['const']['CODE_CIRCLEK']:
    				case $this->app['config']['MdlPaygent']['const']['CODE_YAMAZAKI']:
    					if (in_array($CODE_SEICOMART, $arrCvs)) {
    						$receiptNumName = "受付番号";
    					} else {
    						$receiptNumName = "ケータイ／オンライン決済番号";
    						$confirmMemo = $arrConvenience[$CODE_LOWSON] . "、" . $arrConvenience[$CODE_MINISTOP];
    						if (in_array($CODE_FAMILYMART, $arrCvs)) {
    							$confirmMemo .= "、" . $arrConvenience[$CODE_FAMILYMART];
    						}
    						$confirmMemo .= "でのお支払いには下記の確認番号も必要となります";
    					}
    					break;
    					// ローソン、ミニストップの場合
    				case $CODE_LOWSON:
    				case $CODE_MINISTOP:
    					if (in_array($CODE_SEICOMART, $arrCvs)) {
    						$receiptNumName = "受付番号";
    					} else {
    						$receiptNumName = "お客様番号";
    						$confirmMemo = $arrConvenience[$CODE_LOWSON] . "、" . $arrConvenience[$CODE_MINISTOP];
    						if (in_array($CODE_FAMILYMART, $arrCvs)) {
    							$confirmMemo .= "、" . $arrConvenience[$CODE_FAMILYMART];
    						}
    						$confirmMemo .= "でのお支払いには下記の確認番号も必要となります";
    					}
    					break;

    					// ファミリーマートの場合
    				case $CODE_FAMILYMART:
    					if (in_array($CODE_LOWSON, $arrCvs)) {
    						$receiptNumName = "お客様番号";
    						$confirmMemo = $arrConvenience[$CODE_LOWSON] . "、" . $arrConvenience[$CODE_MINISTOP] . "、" . $arrConvenience[$CODE_FAMILYMART] . "でのお支払いには下記の確認番号も必要となります";
    					} else {
    						// 課題No.110 対応 コンビニ接続タイプA の場合
    						$receiptNumName = "収納番号";
    						$confirmMemo = "";
    					}
    					break;

    					// セイコーマートの場合
    				case $CODE_SEICOMART:
    					$receiptNumName = "お客様の受付番号";
    					$confirmMemo = "";
    					break;

    				default:
    					break;
    			}

    			// --- memo02 にパラメータを設定 ------------------------------
    			// タイトル
    			$arrMemo['title'] = $this->sfSetConvMSG("コンビニお支払", true);
    			// 決済ベンダ受付番号
    			$arrMemo['receipt_number'] = $this->sfSetConvMSG($receiptNumName, $arrRes[0]['receipt_number']);
    			// 電話番号
    			if (in_array($CODE_SEICOMART, $arrCvs)) {
    				// イーコンの場合は電話番号を表示
    				$arrMemo['customer_tel'] = $this->sfSetConvMSG("電話番号", $arrInput['customer_tel']);
    			}

    			// 払込票URL（結果URL情報）
    			if ($arrInput['cvs_company_id'] === $CODE_SEVENELEVEN) {
    				// 選択されたコンビニが「セブンイレブン」で、携帯端末でない場合のみ指定
    				$arrMemo['receipt_print_url'] = $this->sfSetConvMSG("払込票URL", $arrRes[0]['receipt_print_url']);
    			}

    			// お支払可能なコンビニ
    			$arrMemo['usable_cvs_company_id'] = $this->sfSetConvMSG("お支払可能なコンビニ", $cvsLine);
    			// お支払期日（支払期限日）
    			$arrMemo['payment_limit_date'] = $this->sfSetConvMSG("お支払期日", date("Y年m月d日", strtotime($arrRes[0]['payment_limit_date'])));
    			$arrMemo['help_url'] = $this->sfSetConvMSG("お支払方法の説明", "http://www.paygent.co.jp/merchant_info/help/shophelp_cvs.html");

    			// 特記事項、確認番号
    			if ($confirmMemo !== "") {
    				$arrMemo['confirm_memo'] = $this->sfSetConvMSG("特記事項", $confirmMemo);
    				$arrMemo['confirm_number'] = $this->sfSetConvMSG("確認番号", "400008");
    			}

    			$arrVal["memo02"] = serialize($arrMemo);

    			break;
    			// ATM決済の場合
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_ATM']:
    			// タイトルを設定する
    			$arrMemo['title'] = $this->sfSetConvMSG("ATMお支払", true);
    			$arrMemo['pay_center_number'] = $this->sfSetConvMSG("収納機関番号", $arrRes[0]['pay_center_number']);
    			$arrMemo['customer_number'] = $this->sfSetConvMSG("お客様番号", $arrRes[0]['customer_number']);
    			$arrMemo['conf_number'] = $this->sfSetConvMSG("確認番号", $arrRes[0]['conf_number']);
    			// 支払期日
    			$arrMemo['payment_limit_date'] = $this->sfSetConvMSG("お支払期日", date("Y年m月d日", strtotime($arrRes[0]['payment_limit_date'])));
    			// ヘルプ画面
    			$arrMemo['help_url'] = $this->sfSetConvMSG("お支払方法の説明", "http://www.paygent.co.jp/merchant_info/help/shophelp_atm.html");
    			// 受注テーブルに保存
    			$arrVal["memo02"] = serialize($arrMemo);
    			break;
    			// 仮想口座決済の場合
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT']:
    			$arrMemo['title'] = $this->sfSetConvMSG("銀行お振込", true);
    			$arrMemo['bank_name'] = $this->sfSetConvMSG("金融機関名",
    					mb_convert_encoding($arrRes[0]['virtual_account_bank_name'], $this->app['config']['char_code'], "Shift-JIS")."(".$arrRes[0]['virtual_account_bank_code'].")");
    			$arrMemo['branch_name'] = $this->sfSetConvMSG("支店名",
    					mb_convert_encoding($arrRes[0]['virtual_account_branch_name'], $this->app['config']['char_code'], "Shift-JIS")."(".$arrRes[0]['virtual_account_branch_code'].")");

    			if ($arrRes[0]['virtual_account_deposit_kind'] == 1) {
    				$deposit_kind = '普通預金';
    			} else if ($arrRes[0]['virtual_account_deposit_kind'] == 2) {
    				$deposit_kind = '当座預金';
    			} else if ($arrRes[0]['virtual_account_deposit_kind'] == 4) {
    				$deposit_kind = '貯蓄預金';
    			} else {
    				$deposit_kind = "";
    			}
    			$arrMemo['deposit_kind'] = $this->sfSetConvMSG("預金種目名", $deposit_kind);
    			$arrMemo['number'] = $this->sfSetConvMSG("口座番号", $arrRes[0]['virtual_account_number']);
    			$arrMemo['expire_date'] = $this->sfSetConvMSG("お支払期日", date("Y年m月d日", strtotime($arrRes[0]['expire_date'])));
    			$arrMemo['blank'] = $this->sfSetConvMSG("", "");
    			$arrMemo['info1'] = $this->sfSetConvMSG("", "お振込先にペイジェントグチと表示されます。");
    			$arrMemo['info2'] = $this->sfSetConvMSG("", "お支払期日までに上記お支払先へお振込みをお願いします。");
    			// 受注テーブルに保存
    			$arrVal["memo02"] = serialize($arrMemo);

    			if ($resultStatus !== "0") {
    				$arrRes[0]['code'] = $responseCode;
    			}
    			break;
    			// 銀行ネットの場合
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_BANK']:
    			$arrMemo['title'] = $this->sfSetConvMSG("銀行ネットお支払", true);
    			$arrMemo['pay_message'] = $this->sfSetConvMSG("お支払について", "支払期限日までに下記URLからお支払を完了してください。\nお支払の手続を途中で中断された場合も、こちらから再手続が可能です。");
    			$arrMemo['pay_url'] = $this->sfSetConvMSG("お支払URL", $arrRes[0]['asp_url']);
    			$arrVal["memo02"] = serialize($arrMemo);
    			break;
    			// キャリア決済の場合
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER']:
    			$arrVal['quick_flg'] = "1";
    			// 空の配列を格納しておく
    			$arrVal["memo02"] = serialize(array());
    			// 支払画面フォームをデコード
    			if (isset($arrRes[0]['redirect_html'])) {
    				$arrRes[0]['redirect_html'] = mb_convert_encoding($arrRes[0]['redirect_html'], $this->app['config']['char_code'], "Shift-JIS");
    			}
    			// 初期ステータスを設定する。
    			$arrInitStatus = $this->app['eccube.plugin.service.payment']->getInitStatus();
    			$arrVal["status"] = $arrInitStatus[$this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER']];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_COMMIT_AUTH']:
    			// 支払画面フォームをデコード
    			if (isset($arrRes[0]['redirect_html'])) {
    				$arrRes[0]['redirect_html'] = mb_convert_encoding($arrRes[0]['redirect_html'], $this->app['config']['char_code'], "Shift-JIS");
    			}
    			// 初期ステータスを設定する。
    			$arrInitStatus = $this->app['eccube.plugin.service.payment']->getInitStatus();
    			$arrVal["status"] = $arrInitStatus[$this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER']];
    			// 空の配列を格納しておく
    			$arrVal["memo02"] = serialize(array());
    			break;
    			// 仮想口座決済の場合
    		case $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT']:
    			if ($resultStatus == 1) {
    				$arrRes[0]['code'] = $responseCode;
    				if ($responseCode === '15007') {
    					$arrMemo['title'] = $this->sfSetConvMSG("後払い決済", true);
    					$arrMemo['info1'] = $this->sfSetConvMSG("", "後払い決済（アトディーネ）の審査が保留になりました。");
    					$arrMemo['info2'] = $this->sfSetConvMSG("", "審査の結果がでしだい改めてご連絡いたします。");
    					$arrMemo['info3'] = $this->sfSetConvMSG("", "もうしばらくお待ちください。");
    					$arrMemo['info4'] = $this->sfSetConvMSG("", "（審査の結果がでるまで1,2日かかります。）");
    					// 受注テーブルに保存
    					$arrVal["memo02"] = serialize($arrMemo);
    				}
    			}
    			break;
    		default:
    			break;
    	}

    	// 受注テーブルに記録する
    	$arrVal["memo01"] = $this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE'];        // 処理結果

    	// memo02は、支払情報を格納
    	$arrVal["memo03"] = $resultStatus;        // 処理結果
    	$arrVal["memo04"] = $responseCode;        // レスポンスコード
    	$arrVal["memo05"] = $responseDetail;    // エラーメッセージ
    	if (isset($arrRes[0]['payment_id'])) {
    		$arrVal["memo06"] = $arrRes[0]['payment_id'];        // 承認番号
    	} else {
    		$arrVal["memo06"] = "";
    	}

    	$arrVal["memo07"] = "";                    // ステータス取得で使用

    	// キャリアの場合はキャリアタイプ(1:docomo,2:au,3:softbank)を$telegram_kindに追記
    	if(isset($arrInput['career_type']) && strlen($arrInput['career_type']) > 0) {
    		$arrVal["memo08"] = $telegram_kind . "_" . $arrInput['career_type'];
    		// 画面入力値(キャリアタイプ)がnull且つ汎用項目8に値があり、その値が"104_1"の場合
    	} else if (!isset($arrInput['career_type']) && isset($arrDataMemo['memo08'])
    			&& $arrDataMemo['memo08'] == $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_AUTH_D']) {
    		$arrVal["memo08"] = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_D'];
    		// 画面入力値(キャリアタイプ)がnull且つ汎用項目8に値があり、その値が"104_2"の場合
    	} else if (!isset($arrInput['career_type']) && isset($arrDataMemo['memo08'])
    			&& $arrDataMemo['memo08'] == $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_AUTH_A']) {
    		$arrVal["memo08"] = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_A'];
    		// 電子マネーの場合は(1:WebMoney)を$telegram_kindに追記
    	} else if (isset($arrInput['emoney_type']) && strlen($arrInput['emoney_type']) > 0) {
    		$arrVal["memo08"] = $telegram_kind . "_" . $arrInput['emoney_type'];
    		// Yahoo!ウォレットの場合は汎用項目8に電文種別をセットする
    	} else if ($telegram_kind == $this->app['config']['MdlPaygent']['const']['PAYGENT_YAHOOWALLET']) {
    		$arrVal["memo08"] = $telegram_kind;
    	} else {
    		$arrVal["memo08"] = "";                // この段階では空にしておく
    	}

    	$arrVal["memo09"] = "";                    // カード、キャリア決済連携で使用
    	$arrVal["memo10"] = "";                    // 再取得用のnotice_idを保存しておく

    	// 受注テーブルの更新
    	$this->registerOrder($orderId, $arrVal);
    	$this->updateStock($orderId, true);
    	// 結果とメッセージを返却
    	$arrRes[0]['result'] = $resultStatus;
    	if (preg_match('/^[P|E]/', $responseCode) <= 0) {
    		$arrRes[0]['response'] = "<br />". $responseDetail. "（". $responseCode. "）";
    	} elseif (strlen($responseCode) > 0) {
    		$arrRes[0]['response'] = "（". $responseCode. "）";
    	} else {
    		$arrRes[0]['response'] = "";
    	}
    	return $arrRes[0];
    }

    /**
     * 受注情報を登録する.
     *
     * 既に受注IDが存在する場合は, 受注情報を更新する.
     * 引数の受注IDが, 空白又は null の場合は, 新しく受注IDを発行して登録する.
     *
     * @param  integer $order_id  受注ID
     * @param  array   $arrParams 受注情報の連想配列
     * @return integer 受注ID
     */
    public function registerOrder($order_id, $arrParams)
    {
    	//Get Order of this orderId without considering del_flg
    	$Order = $this->app['orm.em']->getRepository('\Eccube\Entity\Order')->findOneBy(array('id' => $order_id));

    	// If no such data exists, create a new one
    	if (is_null($Order)) {
    		$Order = $this->app['orm.em']->getRepository('\Eccube\Entity\Order')->findOrCreate(0);
    	}

    	$this->updateMdlOrderPayment($Order->getId(), $arrParams);

		if (isset($arrParams['status'])) {
			if (isset($arrParams['del_flg'])) {
				$this->updateOrderStatus($Order->getId(), $arrParams['status'], $arrParams['del_flg']);
			} else {
				$this->updateOrderStatus($Order->getId(), $arrParams['status']);
			}
		} else {
			$this->updateOrderStatus($Order->getId());
		}

		if (!is_null($Order->getCustomer())) {
			$customerId = $Order->getCustomer()->getId();

			$this->updateDeliveryTime($order_id, $customerId);
		}
    }

    /**
     * 受注.対応状況の更新
     *
     * 必ず呼び出し元でトランザクションブロックを開いておくこと。
     *
     * @param  integer      $orderId     注文番号
     * @param  integer|null $newStatus   対応状況 (null=変更無し)
     * @return void
     */
    public function updateMdlOrderPayment($order_id, $arrParams)
    {
    	$MdlOrder = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->findOneBy(array('id' => $order_id));
    	// If no such data exists, create a new one
    	if (is_null($MdlOrder)) {
    		$MdlOrder = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->findOrCreate(0);
    	}
    	// Insert to dtb_mdl_order_payment
    	$MdlOrder->setId($order_id);

		if (isset($arrParams['memo01'])) {
			$MdlOrder->setMemo01($arrParams['memo01']);
		}
		if (isset($arrParams['memo02'])) {
			$MdlOrder->setMemo02($arrParams['memo02']);
		}
		if (isset($arrParams['memo03'])) {
			$MdlOrder->setMemo03($arrParams['memo03']);
		}
		if (isset($arrParams['memo04'])) {
			$MdlOrder->setMemo04($arrParams['memo04']);
		}
		if (isset($arrParams['memo05'])) {
			$MdlOrder->setMemo05($arrParams['memo05']);
		}
		if (isset($arrParams['memo06'])) {
			$MdlOrder->setMemo06($arrParams['memo06']);
		}
		if (isset($arrParams['memo07'])) {
			$MdlOrder->setMemo07($arrParams['memo07']);
		}
		if (isset($arrParams['memo08'])) {
			$MdlOrder->setMemo08($arrParams['memo08']);
		}
		if (isset($arrParams['memo09'])) {
			$MdlOrder->setMemo09($arrParams['memo09']);
		}
		if (isset($arrParams['memo10'])) {
			$MdlOrder->setMemo10($arrParams['memo10']);
		}
		if (isset($arrParams['quick_flg'])) {
			$MdlOrder->setQuickFlg($arrParams['quick_flg']);
		}
		if (isset($arrParams['quick_memo'])) {
			$MdlOrder->setQuickMemo($arrParams['quick_memo']);
		}
		if (isset($arrParams['invoice_send_type'])) {
			$MdlOrder->setInvoiceSendType($arrParams['invoice_send_type']);
		}

    	$this->app['orm.em']->persist($MdlOrder);
    	$this->app['orm.em']->flush();
    }

    /**
     * 受注.対応状況の更新
     *
     * 必ず呼び出し元でトランザクションブロックを開いておくこと。
     *
     * @param  integer      $orderId     注文番号
     * @param  integer|null $newStatus   対応状況 (null=変更無し)
     * @return void
     */
    public function updateOrderStatus($orderId, $newStatus = null, $delFlg = null, $paymentDate = null)
    {
    	$Order = $this->app['orm.em']->getRepository('\Eccube\Entity\Order')->findOneBy(array('id' => $orderId));

		if (!is_null($newStatus)) {
			$OrderStatus = $this->app['orm.em']->getRepository('\Eccube\Entity\Master\OrderStatus')->findOneBy(array('id' => $newStatus));
			if (!is_null($OrderStatus)) {


				$ORDER_DELIV = $this->app['config']['order_deliv'];
				$ORDER_PRE_END = $this->app['config']['order_pre_end'];
				// 対応状況が発送済みに変更の場合、発送日を更新
				if ($Order->getOrderStatus()->getId() != $ORDER_DELIV && $newStatus == $ORDER_DELIV) {
					$Order->setCommitDate(new \DateTime());
					// 対応状況が入金済みに変更の場合、入金日を更新
				} elseif ($Order->getOrderStatus()->getId() != $ORDER_PRE_END && $newStatus == $ORDER_PRE_END) {
					$Order->setPaymentDate(new \DateTime());
				}

				if (!is_null($delFlg)) {
					$Order->setDelFlg($delFlg);
				}

				$Order->setOrderStatus($OrderStatus);
				$Order->setUpdateDate(new \DateTime());

			}
		}

		if (!is_null($paymentDate)) {
			$Order->setPaymentDate($paymentDate);
		}

		$this->app['orm.em']->persist($Order);
		$this->app['orm.em']->flush();
		if (!is_null($Order->getCustomer())) {
			$this->updateOrderSummary($Order->getCustomer()->getId());
		}
    }

    //受注関連の会員情報を更新
    public function updateOrderSummary($customerId)
    {
    	$orderSummary = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getOrderSummary($customerId, $this->app['config']['order_cancel']);
    	if (isset($orderSummary) && count($orderSummary) > 0) {
    		$Customer = $this->app['orm.em']->getRepository('\Eccube\Entity\Customer')->findOneBy(array('id' => $customerId));
    		$lastBuyDate = new \DateTime($orderSummary[0]['last_buy_date']);
    		$lastBuyDate->format('Y-m-d H:i:s.u');
    		$firstBuyDate = new \DateTime($orderSummary[0]['first_buy_date']);
    		$firstBuyDate->format('Y-m-d H:i:s.u');
    		$Customer->setBuyTotal($orderSummary[0]['buy_total']);
    		$Customer->setBuyTimes($orderSummary[0]['buy_times']);
    		$Customer->setLastBuyDate($lastBuyDate);
    		$Customer->setFirstBuyDate($firstBuyDate);
    		$this->app['orm.em']->persist($Customer);
    		$this->app['orm.em']->flush();
    	}
    }

    /**
     * 受注の名称列を更新する
     *
     * @param integer $order_id   更新対象の注文番号
     * @static
     */
    public function updateDeliveryTime($orderId, $customerId)
    {
    	$Shipping = $this->app['orm.em']->getRepository('\Eccube\Entity\Shipping')->findOneBy(array('Order' => $orderId));
    	if (!is_null($Shipping)) {
    		if (!is_null($Shipping->getDeliveryTime())) {
    			$timeId = $Shipping->getDeliveryTime()->getId();
    			$deliveryTime = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getDeliveryTime($orderId, $customerId, $timeId);
    			if (isset($deliveryTime) && count($deliveryTime) > 0) {
    				$Shipping->setShippingDeliveryTime($deliveryTime[0]['delivery_time']);
    				$this->app['orm.em']->persist($Shipping);
    				$this->app['orm.em']->flush();
    			}
    		}
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

    /**
     * ログを出力.
     *
     * @param string $msg
     * @param mixed $data
     */
    function printLog($msg, $date = null)
    {
    	$path = $this->app['config']['root_dir'] . "/app/log/" . $this->getCode(true) . '_' . date('Ymd') . '.log';

    	$text = '';
    	if (is_array($msg)) {
    		$text = print_r($msg, true);
    	} else {
    		$text = $msg;
    	}
    	CommonUtil::printLog($this->app, $text, $path);
    }

    /**
     * ログの出力を行う
     *
     * エラー・警告は trigger_error() を経由して利用すること。(補足の出力は例外。)
     * @param string $message
     * @param string $path
     * @param bool $verbose 冗長な出力を行うか
     */
    public function gfPrintLog($app, $message, $path = '', $verbose = false)
    {
    	// 日付の取得
    	$today = date('Y/m/d H:i:s');
    	// 出力パスの作成
    	if (strlen($path) === 0) {
    		$path = self::isAdminFunction() ?: $app['config']['MdlPaygent']['const']['log_realfile'];
    	}

    	if (empty($_SERVER['REMOTE_ADDR'])) {
    		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    	}

    	$msg = $today . '[{' . $_SERVER['SCRIPT_NAME'] . '}]' . $message;
    	$msg .= 'from {' . $_SERVER['REMOTE_ADDR'] . '}\n';
    	if ($verbose) {
    		if (self::isFrontFunction()) {
    			$msg .= 'customer_id = ' . $_SESSION['customer']['customer_id'] . "\n";
    		}
    		if (self::isAdminFunction()) {
    			$msg .= 'login_id = ' . $_SESSION['login_id'] . '(' . $_SESSION['authority'] . ')' . '[' . session_id() . ']' . "\n";
    		}
    		$msg .= self::toStringBacktrace(self::getDebugBacktrace());
    	}

    	error_log($msg, 3, $path);

    	// ログテーション
    	self::gfLogRotation($app['config']['MdlPaygent']['const']['max_log_quantity'], $app['config']['MdlPaygent']['const']['max_log_size'], $path);
    }

    /**
     * ログローテーション機能
     *
     * XXX この類のローテーションは通常 0 開始だが、本実装は 1 開始である。
     * この中でログ出力は行なわないこと。(無限ループの懸念あり)
     * @param  integer $max_log 最大ファイル数
     * @param  integer $max_size 最大サイズ
     * @param  string $path ファイルパス
     * @return void
     */
    public function gfLogRotation($max_log, $max_size, $path)
    {
    	// ファイルが存在しない場合、終了
    	if (!file_exists($path)) return;

    	// ファイルが最大サイズを超えていない場合、終了
    	if (filesize($path) <= $max_size) return;

    	// Windows 版 PHP への対策として明示的に事前削除
    	$path_max = "$path.$max_log";
    	if (file_exists($path_max)) {
    		$res = unlink($path_max);
    		// 削除に失敗時した場合、ログローテーションは見送り
    		if (!$res) return;
    	}

    	// アーカイブのインクリメント
    	for ($i = $max_log; $i >= 2; $i--) {
    		$path_old = "$path." . ($i - 1);
    		$path_new = "$path.$i";
    		if (file_exists($path_old)) {
    			rename($path_old, $path_new);
    		}
    	}

    	// 現在ファイルのアーカイブ
    	rename($path, "$path.1");
    }

    /**
     * 管理機能かを判定
     *
     * @return bool 管理機能か
     */
    public function isAdminFunction()
    {
    	return defined('ADMIN_FUNCTION') && ADMIN_FUNCTION === true;
    }

    /**
     * フロント機能かを判定
     *
     * @return bool フロント機能か
     */
    public static function isFrontFunction()
    {
    	return defined('FRONT_FUNCTION') && FRONT_FUNCTION === true;
    }

    /**
     * インストール機能かを判定
     *
     * @return bool インストール機能か
     */
    public static function isInstallFunction()
    {
    	return defined('INSTALL_FUNCTION') && INSTALL_FUNCTION === true;
    }

   /**
     * バックトレースをテキスト形式で出力する
     *
     * 現状スタックトレースの形で出力している。
     * @param  array $arrBacktrace バックトレース
     * @return string テキストで表現したバックトレース
     */
    public static function toStringBacktrace($arrBacktrace)
    {
        $string = '';

        foreach (array_reverse($arrBacktrace) as $backtrace) {
            if (!empty($backtrace['class'])) {
                if (strlen($backtrace['class']) >= 1) {
                    $func = $backtrace['class'] . $backtrace['type'] . $backtrace['function'];
                } else {
                    $func = $backtrace['function'];
                }
            }

            if (!empty($backtrace['file'])) {
                $string .= $backtrace['file'] . '(' . $backtrace['line'] . '): ' . $func . "\n";
            }
        }

        return $string;
    }

    /**
     * デバッグ情報として必要な範囲のバックトレースを取得する
     *
     * エラーハンドリングに関わる情報を切り捨てる。
     */
    public static function getDebugBacktrace($arrBacktrace = null)
    {
    	if (is_null($arrBacktrace)) {
    		$arrBacktrace = debug_backtrace(false);
    	}
    	$arrReturn = array();
    	foreach (array_reverse($arrBacktrace) as $arrLine) {
    		// 言語レベルの致命的エラー時。発生元の情報はトレースできない。(エラーハンドリング処理のみがトレースされる)
    		// 実質的に何も返さない(空配列を返す)意図。
    		if (!empty($arrLine['file'])) {
    			if (strlen($arrLine['file']) === 0
    					&& ($arrLine['class'] === 'Helper_HandleError' || $arrLine['class'] === 'Helper_HandleError_Ex')
    					&& ($arrLine['function'] === 'handle_error' || $arrLine['function'] === 'handle_warning')
    					) {
    						break 1;
    					}
    		}

    		$arrReturn[] = $arrLine;

    		// エラーハンドリング処理に引き渡した以降の情報は通常不要なので含めない。
    		if (!isset($arrLine['class']) && $arrLine['function'] === 'trigger_error') {
    			break 1;
    		}

    		if (!empty($arrLine['class'])) {
    			if (($arrLine['class'] === 'Helper_HandleError' || $arrLine['class'] === 'Helper_HandleError_Ex')
    					&& ($arrLine['function'] === 'handle_error' || $arrLine['function'] === 'handle_warning')
    					) {
    						break 1;
    					}
    					if (($arrLine['class'] === 'Utils' || $arrLine['class'] === 'Utils_Ex')
    							&& $arrLine['function'] === 'sfDispException'
    							) {
    								break 1;
    							}
    							if (($arrLine['class'] === 'GC_Utils' || $arrLine['class'] === 'GC_Utils_Ex')
    									&& ($arrLine['function'] === 'gfDebugLog' || $arrLine['function'] === 'gfPrintLog')
    									) {
    										break 1;
    									}
    		}
    	}

    	return array_reverse($arrReturn);
    }

    public function sendData($arrRet, $payment_total, $orderId, $telegram_kind, $sqlVal = array()) {
    	if($arrRet['result'] === "0") {

    		$arrInitStatus = $this->app['eccube.plugin.service.payment']->getInitStatus();
    		$order_status = $arrInitStatus[$telegram_kind];

    		$sqlVal['memo08'] = $telegram_kind;

    		return $this->orderComplete($orderId, $sqlVal, $order_status);
    	}else {
            return "決済に失敗しました。". $arrRet['response'];
        }
    }

    //insert or update -$order_status = ORDER_NEW <-> ordernew=1 xD
    public function orderComplete($order_id, $sqlval = array(), $order_status = 1, $type = "1") {
    	//get Order by order_id
    	$paygentBank = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK'];
    	$order = $this->app['eccube.repository.order']->findOneBy(array('id' => $order_id));

    	$orderPending = $this->app['config']['order_pending'];
    	// 受注ステータスを「決済処理中」から更新する。
    	if ($order_status != $orderPending) { // iDでは更新しない
    		$this->updateMdlOrderPayment($order_id, $sqlval);
    		$this->updateOrderStatus($order_id, $order_status);
    	} else if (!empty($sqlval)) {
    		$this->registerOrder($order_id, $sqlval);
    	}

    	$arrOrder = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->getMemo02FromMdlOrderPayment($order_id);
    	$arrOther = $arrOrder[0]['memo02'];

    	// 受注完了メールを送信する。
    	$this->sendOrderMail($order, $arrOther);

    	if ($this->app['session']->has("customer-not-login")) {
    		$this->app['session']->remove("customer-not-login");
    	}

    	if ($type != $paygentBank) {
    		// 購入完了ページへリダイレクト
    		return true;
    	}
    }

	/**
	 * 関数名：getPaygentLaterPaymentCustomer
	 * 処理内容：後払い決済電文送信の顧客情報作成処理
	 * 戻り値：取得結果
	 */
	public function getPaygentLaterPaymentCustomer($arrOrder) {

		// 決済金額
		$arrSend['payment_amount'] = $arrOrder[0]['payment_total'];
		// 購入者注文日
		$arrSend['shop_order_date'] = date_format($arrOrder[0]['create_date'], "Ymd");
		// 購入者郵便番号
		$arrSend['customer_zip_code'] = $arrOrder[0]['zip01'] . $arrOrder[0]['zip02'];
		// 購入者住所
		$arrPref =  $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getPref();

		$arrSend['customer_address'] = $arrPref[$arrOrder[0]->getPref()->getId()-1]['name'] . $arrOrder[0]['addr01'] . $arrOrder[0]['addr02'];
		// 購入者メールアドレス
		$arrSend['customer_email'] = $arrOrder[0]['email'];

		return $arrSend;
	}

    /**
     * 関数名：getPaygentLaterPaymentShip
     * 処理内容：後払い決済電文送信の配送先情報作成処理
     * 戻り値：取得結果
     */
    public function getPaygentLaterPaymentShip($arrShippings) {
    	// 配送先郵便番号
    	$arrSend['ship_zip_code'] = $arrShippings['zip01'] . $arrShippings['zip02'];
    	// 配送先住所
    	$shippingName = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getPrefByShippingId($arrShippings['id']);
    	$shippingName = reset($shippingName);

    	$arrSend['ship_address'] = $shippingName['shipping_pref'] . $arrShippings['addr01'] . $arrShippings['addr02'];
    	// 配送先電話番号
    	$arrSend['ship_tel'] = $arrShippings['tel01'] .  "-" . $arrShippings['tel02'] .  "-" . $arrShippings['tel03'];

    	return $arrSend;
    }

    /**
     * 関数名：getPaygentLaterPaymentGoods
     * 処理内容：後払い決済電文送信の明細情報作成処理
     * 戻り値：取得結果
     */
    public function getPaygentLaterPaymentGoods($arrOrder, $arrOrderDetail) {

    	// 明細（商品）
    	foreach ($arrOrderDetail as $key => $orderDetail) {
    		$index = $key;
    		// 明細(商品名）
    		$arrSend['goods[' . $index . ']'] = $orderDetail['product_name'];
    		// 明細(単価）
    		$arrSend['goods_price[' . $index . ']'] = $this->app['eccube.service.tax_rule']->getPriceIncTax($orderDetail['price']);
    		// 明細(数量）
    		$arrSend['goods_amount[' . $index . ']'] = $orderDetail['quantity'];
    	}
    	// 明細（手数料）
    	if ($arrOrder[0]['charge'] != "0") {
    		$index++;
    		// 明細(商品名）
    		$arrSend['goods[' . $index . ']'] = "手数料";
    		// 明細(単価）
    		$arrSend['goods_price[' . $index . ']'] = $arrOrder[0]['charge'];
    		// 明細(数量）
    		$arrSend['goods_amount[' . $index . ']'] = "1";
    	}
    	// 明細（送料）
    	if ($arrOrder[0]['delivery_fee_total'] != "0") {
    		$index++;
    		// 明細(商品名）
    		$arrSend['goods[' . $index . ']'] = "送料";
    		// 明細(単価）
    		$arrSend['goods_price[' . $index . ']'] = $arrOrder[0]['delivery_fee_total'];
    		// 明細(数量）
    		$arrSend['goods_amount[' . $index . ']'] = "1";
    	}
    	return $arrSend;
    }

    /**
     * 関数名：sfGetPaygentLaterPaymentModule
     * 処理内容：後払い決済電文送信の共通処理（モジュール）
     * 戻り値：取得結果
     */
    public function sfGetPaygentLaterPaymentModule($order_id) {
    	// 受注情報
    	$arrOrder = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getOrderByOrderId($order_id);

    	$arrShippings = $this->getShippings($arrOrder[0]['id']);

    	$arrShippings = reset($arrShippings);

    	// 受注詳細
    	$arrOrderDetail = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getOrderDetail($arrOrder[0]['id']);

    	$productList = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getEnableStatus();
    	if (count($productList) > 0) {
	    	foreach ($arrOrderDetail as $key => $orderDetail) {
	    		$index = $key;
	    		$arrOrderDetail[$key]['enable'] = 1;
	    	}
    	} else {
    		foreach ($arrOrderDetail as $key => $orderDetail) {
	    		$index = $key;
	    		$arrOrderDetail[$key]['enable'] = 0;
	    	}
    	}

    	$arrSend = array();
    	// 顧客
    	$arrSend += $this->getPaygentLaterPaymentCustomer($arrOrder);
    	// 配送先
    	$arrSend += $this->getPaygentLaterPaymentShip($arrShippings);
    	// 明細（商品/手数料/ポイント/送料）
    	$arrSend += $this->getPaygentLaterPaymentGoods($arrOrder, $arrOrderDetail);

    	// モジュールとリンクで異なる項目
    	// 購入者氏名（漢字）
    	$arrSend['customer_name_kanji'] = mb_convert_kana($arrOrder[0]['name01'] . $arrOrder[0]['name02'], "KVA");
    	// 購入者氏名（カナ）
    	$arrSend['customer_name_kana'] = $arrOrder[0]['kana01'] . $arrOrder[0]['kana02'];
    	// 購入者電話番号
    	$arrSend['customer_tel'] = $arrOrder[0]['tel01'] . "-" . $arrOrder[0]['tel02'] . "-" . $arrOrder[0]['tel03'];
    	// 配送先氏名（漢字）
    	$arrSend['ship_name_kanji'] = $arrShippings['name01'] . $arrShippings['name02'];
    	// 配送先氏名（カナ）
    	$arrSend['ship_name_kana'] = $arrShippings['kana01'] . $arrShippings['kana02'];

    	return $arrSend;
    }

    /**
     * 配送情報を取得する.
     *
     * @param  integer $order_id  受注ID
     * @return array   配送情報の配列
     */
    public function getShippings($order_id)
    {
    	$arrResults = array();
    	$arrShippings = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getShippingByOrderId($order_id);
    	// shipping_id ごとの配列を生成する
    	foreach ($arrShippings as $shipping) {
    		foreach ($shipping as $key => $val) {
    			$arrResults[$shipping['id']][$key] = $val;
    		}
    	}
    	return $arrResults;
    }

    /**
     * 受注をキャンセルし, カートをロールバックして, 受注一時IDを返す.
     *
     * 受注完了後の受注をキャンセルし, カートの状態を受注前の状態へ戻す.
     * この関数は, 主に, 決済モジュールに遷移した後, 購入確認画面へ戻る場合に使用する.
     *
     * 対応状況を引数 $orderStatus で指定した値に変更する.
     * (デフォルト ORDER_CANCEL)
     * 引数 $is_delete が true の場合は, 受注データを論理削除する.
     * 商品の在庫数, カートの内容は受注前の状態に戻される.
     *
     * @param  integer $order_id    受注ID
     * @param  integer $orderStatus 対応状況
     * @param  boolean $is_delete   受注データを論理削除する場合 true
     * @return string  受注一時ID
     */
    public function rollbackOrder($order_id, $orderStatus = 3, $is_delete = false)
    {
    	$this->cancelOrder($order_id, $orderStatus, $is_delete);

    	$this->app['session']->remove($this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME']);
    }

    /**
     * 受注をキャンセルする.
     *
     * 受注完了後の受注をキャンセルする.
     * この関数は, 主に決済モジュールにて, 受注をキャンセルする場合に使用する.
     *
     * 対応状況を引数 $orderStatus で指定した値に変更する.
     * (デフォルト ORDER_CANCEL)
     * 引数 $is_delete が true の場合は, 受注データを論理削除する.
     * 商品の在庫数は, 受注前の在庫数に戻される.
     *
     * @param  integer $order_id    受注ID
     * @param  integer $orderStatus 対応状況
     * @param  boolean $is_delete   受注データを論理削除する場合 true
     * @return void
     */
    public function cancelOrder($order_id, $orderStatus = 3, $is_delete = false)
    {
    	$arrParams = array();
    	$arrParams['status'] = $orderStatus;
    	if ($is_delete) {
    		$arrParams['del_flg'] = 1;
    	}

    	$this->registerOrder($order_id, $arrParams);

    	$this->updateStock($order_id, false);
    }

    /**
     * Method will update stock when order product or cancel cart
     * @param unknown $orderId
     * @param string $orderSuccess
     */
    public function updateStock($orderId, $orderSuccess = false)
    {
		if ($orderSuccess == false) {
			$this->app['eccube.service.cart']->clear()->save();
		}
		$arrOrderDetail = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->getArrOrderDetail($orderId);
    	foreach ($arrOrderDetail as $arrDetail) {
    		$productClass = $this->app['orm.em']->getRepository('\Eccube\Entity\ProductClass')->findOneBy(array('id' => $arrDetail->getProductClass()->getId()));
    		if ($orderSuccess == true) {
    			$stockNew = $productClass->getStock() - $arrDetail->getQuantity();
    		} else {
    			$stockNew = $productClass->getStock() + $arrDetail->getQuantity();
    			$this->app['eccube.service.cart']->addProduct($arrDetail->getProductClass()->getId(), $arrDetail->getQuantity())->save();
    		}

    		$productClass->setStock($stockNew);
    		$this->app['orm.em']->persist($productClass);
    		$this->app['orm.em']->flush();
    	}
    }

    /**
     * 携帯キャリア決済 電文送信後の処理。
     *
     * @param $arrRet 応答情報
     */
    public function sendData_Career($arrRet, $order_id, $mode = null, $career_type = null) {
    	$paymentService = $this->app['eccube.plugin.service.payment'];
    	if ($arrRet['result'] === "0") {
    		// 処理結果が "0"：正常 の場合
    		if ($mode == 'next') {
    			$paygentCareer = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER'];
    			// 受注ステータス更新
    			$arrInitStatus = $paymentService->getInitStatus();
    			$order_status = $arrInitStatus[$paygentCareer];
    			$sqlVal = array();
    			$this->orderUpdate($order_id, $sqlVal, $order_status, $paygentCareer);
    		}

    		// 画面の設定
    		if (isset($arrRet['redirect_url']) && strlen($arrRet['redirect_url']) > 0) {
    			$this->app['eccube.service.cart']->clear()->save();
    			// 応答情報にリダイレクトURL が含まれる場合
    			// plugin統合対応
                $this->systemService = $this->app ['eccube.plugin.service.system'];
                $response = $this->systemService->procExitResponse($arrRet['redirect_url'], true);
                return $response;

    		} else {
    			$this->app['session']->set('redirectHtml', $arrRet['redirect_html']);
				$this->app['session']->set('flag', false);
    		}

    		if ($this->app['session']->has("customer-not-login")) {
    			$this->app['session']->remove("customer-not-login");
    		}

    	} else {
    		// 処理結果が正常ではない場合
    		return "決済に失敗しました。" . $arrRet['response'];
    	}
    }

    /**
     * 注文情報を更新
     */
    public function orderUpdate($order_id, $sqlval = array(), $order_status = 1, $type = "1") {
    	// 受注ステータスを「決済処理中」から更新する。
    	$orderPending = $this->app['config']['order_pending'];
    	if ($order_status != $orderPending) { // iDでは更新しない
    		$this->updateMdlOrderPayment($order_id, $sqlval);
    		$this->updateOrderStatus($order_id, $order_status);
    	} else if (!empty($sqlval)) {
    		$this->registerOrder($order_id, $sqlval);
    	}
    }

    /**
     * 携帯キャリア決済申込電文（ID=100）を送信する。
     *
     * @param $arrData 受注情報
     * @param $arrInput 入力情報
     * @param $order_id 受注ID
     * @param $transactionid EC-CUBE側のトランザクションID
     * @param $pc_mobile_type PC/Mobile区分
     * @param $open_id OpenID
     * @return 応答情報
     */
    function sfSendPaygentCareer($arrData, $arrInput, $order_id, $transactionid, $pc_mobile_type, $open_id = "") {

    	// 支払方法情報テーブル（dtb_payment）から、携帯キャリア決済に関する情報を取得
    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	$payPaygentCareer = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER'];
    	// 銀行NET用パラメータの取得
    	$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentCareer);

    	// --- パラメータを設定 ------------------------------
    	// 共通ヘッダ
    	$paygentCareer = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER'];
    	$arrSend = $this->sfGetPaygentShare($paygentCareer, $arrData->getId(), $arrPaymentDB[0]);

    	$arrDataMemo = $MdlPaymentRepo->getMemoByOrderId($arrData->getId());
    	if (count($arrDataMemo) <= 0) {
    		$arrDataMemo = $this->resetMemo();
    	}

    	$transactionIdName = $this->app['config']['MdlPaygent']['const']['TRANSACTION_ID_NAME'];
    	$careerMobileTypeDocomo = $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_DOCOMO'];
    	$paygentCareerAuthD = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_AUTH_D'];
    	$careerTypeDocomo = $this->app['config']['MdlPaygent']['const']['CAREER_TYPE_DOCOMO'];
    	$careerMobileTypeAu = $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_AU'];
    	$paygentCareerAuthA = $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_AUTH_A'];
    	$careerTypeAu = $this->app['config']['MdlPaygent']['const']['CAREER_TYPE_AU'];
    	$careerMobileTypeSoftbank = $this->app['config']['MdlPaygent']['const']['CAREER_MOBILE_TYPE_SOFTBANK'];
    	$careerTypeSoftbank = $this->app['config']['MdlPaygent']['const']['CAREER_TYPE_SOFTBANK'];
    	// キャリア種別
    	if ($arrInput['career_type'] == $careerMobileTypeDocomo
    			|| (empty($arrDataMemo['memo04']) && isset($arrDataMemo['memo08']) && $arrDataMemo['memo08'] == $paygentCareerAuthD)) {
    				// キャリア決済選択で「ドコモケータイ払い」が選ばれた場合、
    				// または、ドコモへの認証要求が正常に完了し、リダイレクトで戻って来た場合
    				$arrSend['career_type'] = $careerTypeDocomo;

    			} else if ($arrInput['career_type'] == $careerMobileTypeAu
    					|| (empty($arrDataMemo['memo04']) && isset($arrDataMemo['memo08']) && $arrDataMemo['memo08'] == $paygentCareerAuthA)) {
    						// キャリア決済選択で「auかんたん決済」が選ばれた場合、
    						// または、au への認証要求が正常に完了し、リダイレクトで戻って来た場合
    						$arrSend['career_type'] = $careerTypeAu;

    					} else if ($arrInput['career_type'] == $careerMobileTypeSoftbank) {
    						// キャリア決済選択で「ソフトバンク」が選ばれた場合
    						$arrSend['career_type'] = $careerTypeSoftbank;

    					} else {
    						$arrSend['career_type'] = $arrInput['career_type'];
    					}

    					// 請求金額
    					$arrSend['amount'] = $arrData->getTotal();
    					// オーソリ通知URL
    					$arrSend['return_url'] = $this->app->url('homepage') . "shopping/mdl_paygent?mode=career_auth&order_id=" . $order_id . "&" . $transactionIdName . "=" . $transactionid . '&hash=' . $this->createPaygentHash($order_id, $arrData->getCreateDate()->format('Y-m-d H:i:s'));
    					// キャンセル通知URL
    					$arrSend['cancel_url'] = $this->app->url('homepage') . "shopping/mdl_paygent?mode=career_auth_cancel&order_id=" . $order_id . "&" . $transactionIdName . "=" . $transactionid . '&hash=' . $this->createPaygentHash($order_id, $arrData->getCreateDate()->format('Y-m-d H:i:s'));
    					if ($arrSend['career_type'] == $careerTypeDocomo) {
    						// 他決済用URL
    						$arrSend['other_url'] = $arrSend['cancel_url'];
    					}
    					// PC/Mobile区分
    					$arrSend['pc_mobile_type'] = $pc_mobile_type;
    					// OpenId
    					$arrSend['open_id'] = $open_id;

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
    					$arrRet = $this->sfPaygentResponse($paygentCareer, $p, $order_id, $arrInput, $arrData, $arrDataMemo);

    					return $arrRet;
    }

    /**
     * Method will return array null.
     * @return array null
     */
    public function resetMemo() {
    	return $arrData = array(
    			'memo01' => null,
    			'memo02' => null,
    			'memo03' => null,
    			'memo04' => null,
    			'memo05' => null,
    			'memo06' => null,
    			'memo07' => null,
    			'memo08' => null,
    			'memo09' => null,
    			'memo10' => null,
    	);
    }

    /**
     * KSシステムからの決済情報差分通知、または決済情報差分照会の応答情報を受けて、
     * 受注情報テーブル（dtb_order）の更新を行う。
     *
     * @param $arrRet 応答情報
     * @param $arrConfig モジュール設定情報
     * @param $app array from Batch
     */
    function sfUpdatePaygentOrder($arrRet, $arrConfig, $app) {
    	if ($arrRet['payment_type'] == $this->app['config']['MdlPaygent']['const']['PAYMENT_TYPE_LATER_PAYMENT']) {
    		// 後払い決済
    		$this->updatePaygentOrderLaterPayment($arrRet, $arrConfig, $app);
    	} else {
    		$MdlOrderPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
    		// 後払い決済以外
    		if ($arrRet['trading_id'] != "") {
    			$payment_type = $arrRet['payment_type'];
    			$arrVal['status'] = null;
    			$arrVal['payment_date'] = null;
    			// 決済ステータスごとに処理を分岐
    			switch ($arrRet['payment_status']) {
    				case $this->app['config']['MdlPaygent']['const']['STATUS_PRE_CLEARED']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_PRELIMINARY_PRE_DETECTION']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_COMPLETE_CLEARED']:
    					// "40"：消込済、"43"：速報検知済、"44"：消込完了 の場合

    					// 受注情報テーブル（dtb_order）からデータを取得
    					// 受注状態
    					$order_status = $MdlOrderPaymentRepo->getOneField("os.id", $arrRet['trading_id']);
    					// 申込時の電文種別ID
    					$payment_type = $MdlOrderPaymentRepo->getOneField("p.memo08", $arrRet['trading_id']);

    					if ($order_status['id'] == $this->app['config']['order_pay_wait']
    							|| $payment_type['memo08'] === $this->app['config']['MdlPaygent']['const']['PAYGENT_BANK']
    							|| $payment_type['memo08'] === $this->app['config']['MdlPaygent']['const']['PAYGENT_CAREER_S']
    							|| $payment_type['memo08'] === $this->app['config']['MdlPaygent']['const']['PAYGENT_EMONEY_W']
    					) {
    						// 受注状態が "2"：入金待ち、
    						// または「銀行ネット決済、ソフトバンク、WebMoney」の場合
    						//（※ ソフトバンクについては、同時売上の場合を考慮しての処理）

    						// 受注状態 = "6"：入金済み
    						$arrVal['status'] = $this->app['config']['order_pre_end'];

    						if ($payment_type['memo08'] === $this->app['config']['MdlPaygent']['const']['PAYGENT_BANK']) {
    							// 銀行ネット決済の場合、memo06 = 決済ID
    							$arrVal['memo06'] = $arrRet['payment_id'];
    						}
    					}

    					if ($arrRet['payment_type'] == $this->app['config']['MdlPaygent']['const']['PAYMENT_TYPE_VIRTUAL_ACCOUNT']) {
    						$payment_id = $MdlOrderPaymentRepo->getOneField("p.memo06", $arrRet['trading_id']);
    						if (($arrRet['clear_detail'] == null && ($payment_id == null || $payment_id != $arrRet['payment_id']))
    								|| $arrRet['clear_detail'] == '03'
    								|| $arrRet['clear_detail'] == '04'
    								|| $arrRet['clear_detail'] == '05'
    						) {
    							// 仮想口座決済で入金処理詳細が異常入金のパターンの場合、
    							// エラーメールを送信してDB更新はスキップする。
    							// ここに入らないパターンは通常通り更新する。
    							$this->sendVirtualAccountErrorMail($arrRet, $app);
    							return;
    						}
    						// 更新時にペイジェント状況を設定する
    						$clear_detail = empty($arrRet['clear_detail']) ? "01" : $arrRet['clear_detail'];
    						$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_VIRTUAL_ACCOUNT'] . '_' . $clear_detail;
    					}

    					// 入金日時 = 応答情報.支払日時
    					if ($arrRet['payment_date'] != "") {
    						$arrVal['payment_date'] = date("Y-m-d H:i:s", strtotime($arrRet['payment_date']));
    					}

    					break;

    				case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_OK']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_COMPLETED']:
    					// "20"：オーソリOK、"21"：オーソリ完了 の場合
    					if ($arrRet['payment_type'] == $this->app['config']['MdlPaygent']['const']['PAYMENT_TYPE_CAREER']
    					|| $arrRet['payment_type'] == $this->app['config']['MdlPaygent']['const']['PAYMENT_TYPE_YAHOOWALLET']
    					|| $arrConfig['settlement_division'] == $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MIX']
    					) {
    						// 決済種別CD = "06"：携帯キャリア決済、"12"：Yahoo!ウォレット決済
    						// または、システム種別が "3"：混合型 の場合
    						// 受注状態 = "2"：入金待ち
    						$arrVal['status'] = $this->app['config']['order_pay_wait'];
    					}
    					break;

    				case $this->app['config']['MdlPaygent']['const']['STATUS_NG_AUTHORITY']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_PAYMENT_EXPIRED']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_CANCELED']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORITY_EXPIRED']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_PRE_SALES_CANCELLATION']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_PRELIMINARY_CANCELLATION']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_COMPLETE_CANCELLATION']:
    				case $this->app['config']['MdlPaygent']['const']['STATUS_PAYMENT_INVALIDITY_NO_CLEAR']:
    					// "11"：オーソリNG、"12"：支払期限切、"16"：支払期限切（消込対象外）、"32"：オーソリ取消済、"33"：オーソリ期限切、
    					// "60"：売上取消済、"61"：速報取消済、"62"：取消完了 の場合
    					// 受注状態 = "3"：キャンセル
    					$arrVal['status'] = $this->app['config']['order_cancel'];
    					break;
    			}

    			// memo07 = 決済ステータス
    			$arrVal['memo07'] = $arrRet['payment_status'];
    			// memo10 = 決済通知ID
    			$arrVal['memo10'] = $arrRet['payment_notice_id'];

    			if ($arrRet['payment_type'] == $this->app['config']['MdlPaygent']['const']['PAYMENT_TYPE_BANK']
    					|| $arrConfig['settlement_division'] == $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MIX']
    					) {
    						// 決済種別CD = "05"：銀行ネット決済 または システム種別が "3"：混合型 の場合、
    						// 受注情報テーブル（dtb_order）から、memo06（決済ID）を取得
    						$resultMemo06 = $MdlOrderPaymentRepo->getOneField("p.memo06", $arrRet['trading_id']);

    						if (empty($resultMemo06['memo06'])) {
    							// 決済ID が空文字の場合、memo06 = 決済ID
    							$arrVal['memo06'] = $arrRet['payment_id'];
    							// 決済ID を更新条件（update 文の where 句）に含めないようにする
    							unset($arrRet['payment_id']);
    						}
    					}
    					// 受注情報テーブル（dtb_order）の更新
    					if ($arrRet['payment_id'] != '' && $payment_type != $this->app['config']['MdlPaygent']['const']['PAYGENT_BANK']) {
    						$this->updateMdlOrderPaymentBatch($arrRet['trading_id'], $arrVal, $arrRet['payment_id']);
    						$this->updateOrderStatusBatch($arrRet['trading_id'], $arrVal['status'], null, $arrVal['payment_date'], $arrRet['payment_id']);
    					} else {
    						$this->updateMdlOrderPaymentBatch($arrRet['trading_id'], $arrVal);
    						$this->updateOrderStatusBatch($arrRet['trading_id'], $arrVal['status'], null, $arrVal['payment_date']);
    					}
    		} else if ($arrRet['payment_type'] == $this->app['config']['MdlPaygent']['const']['PAYMENT_TYPE_VIRTUAL_ACCOUNT'] && $arrRet['clear_detail'] == '05') {
	            // $arrRet['clear_detail'] == '05' 消込対象請求なし の場合、取引ID == null がありえる
	            $this->sendVirtualAccountErrorMail($arrRet, $app);
        	}
    	}
    }

    /**
     * KSシステムからの決済情報差分通知、または決済情報差分照会の応答情報を受けて、
     * 受注情報テーブル（dtb_order）の更新を行う。後払い決済用処理。
     *
     * @param $arrRet 応答情報
     * @param $arrConfig モジュール設定情報
     * @param $app array from Batch
     */
    function updatePaygentOrderLaterPayment($arrRet, $arrConfig, $app) {
    	// 受注状態
    	$MdlOrderPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
    	$arrOrder = $MdlOrderPaymentRepo->getArrOrder($arrRet['trading_id']);
    	$arrVal['status'] = null;
    	$arrVal['payment_date'] = null;
    	switch ($arrRet['payment_status']) {
    		case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORIZE_NG']:
    			// 10：オーソリNG
    			$arrVal['status'] = $this->app['config']['order_cancel'];
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_NG'];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORIZED_BEFORE_PRINT']:
    			// 19：オーソリOK(印字データ取得前)
    			if ($arrOrder[0]['memo09'] == $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN']) {
    				// 35 → 19 の変遷は 35 が差分通知対象外なことによる巻き戻りなので処理対象外
    				return;
    			}
    			$arrVal['status'] = $this->app['config']['order_new'];
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED_BEFORE_PRINT'];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORIZED']:
    			// 20：オーソリOK
    			if (isset($arrOrder[0]['memo09']) && $arrOrder[0]['memo09'] == $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR_REQ_FIN']) {
    				// 35 → 20 の変遷は 35 が差分通知対象外なことによる巻き戻りなので処理対象外
    				return;
    			}
    			$arrVal['status'] = $this->app['config']['order_new'];
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED'];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORIZE_CANCEL']:
    			// 32：オーソリ取消済
    			$arrVal['status'] = $this->app['config']['order_cancel'];
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_CANCEL'];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_AUTHORIZE_EXPIRE']:
    			// 33：オーソリ期限切
    			$arrVal['status'] = $this->app['config']['order_cancel'];
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_EXPIRE'];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_SALES_RESERVE']:
    			// 36：売上保留
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_SALES_RESERVE'];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_CLEAR']:
    			// 40：消込済
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR'];
    			// 入金日時 = 応答情報.支払日時
    			if ($arrRet['payment_date'] != "") {
    				$arrVal['payment_date'] = date("Y-m-d H:i:s", strtotime($arrRet['payment_date']));
    			}
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_CLEAR_SALES_CANCEL_INVALIDITY']:
    			// 41：消込済（取消期限切）
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_CLEAR_SALES_CANCEL_INVALIDITY'];
    			break;
    		case $this->app['config']['MdlPaygent']['const']['STATUS_SALES_CANCEL']:
    			// 60：売上取消済
    			$arrVal['status'] = $this->app['config']['order_cancel'];
    			$arrVal['memo09'] = $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_SALES_CANCEL'];
    			break;
    	}

    	// memo07 = 決済ステータス
    	$arrVal['memo07'] = $arrRet['payment_status'];
    	// memo10 = 決済通知ID
    	$arrVal['memo10'] = $arrRet['payment_notice_id'];
    	// memo02 = null;
    	$arrVal['memo02'] = "";
    	// memo05 = null;
    	$arrVal['memo05'] = "";

    	if ($arrConfig['settlement_division'] == $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MIX']) {
    		$resultMemo06 = $MdlOrderPaymentRepo->getOneField("p.memo06", $arrRet['trading_id']);
    		if (empty($resultMemo06['memo06'])) {
    			// 決済ID が空文字の場合、memo06 = 決済ID
    			$arrVal['memo06'] = $arrRet['payment_id'];
    		}
    	}

		$this->updateMdlOrderPaymentBatch($arrRet['trading_id'], $arrVal);
		$this->updateOrderStatusBatch($arrRet['trading_id'], $arrVal['status'], null, $arrVal['payment_date']);

    	if (isset($arrOrder[0]['memo09']) && $arrOrder[0]['memo09'] == $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_RESERVE']
    			&& ($arrVal['memo09'] == $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZE_NG']
    					|| $arrVal['memo09'] == $this->app['config']['MdlPaygent']['const']['PAYGENT_LATER_PAYMENT_ST_AUTHORIZED'])) {

    				$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    				$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);

    				// オーソリ保留からオーソリNG/オーソリOKに変更
    				// モジュール以外ではEC-CUBE上のステータスはオーソリ保留にならないのでこの処理には入らない
    				$MDL_PAYGENT_CODE = $this->app['config']['MdlPaygent']['const']['MDL_PAYGENT_CODE'];
    				$PAY_PAYGENT_LATER_PAYMENT = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT'];

    				$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($PAY_PAYGENT_LATER_PAYMENT);
    				$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);
    				if ($arrOtherParam['exam_result_notification_type'] == "1") {
    					return;
    				}
    				// 審査結果を通知する
    				$arrLaterPaymentStatus = $this->getArrLaterPaymentExampResult();
    				$header = "お待たせいたしました。" . "\r\n"
    						. "以下のご注文の後払い決済（アトディーネ）審査結果がでました。" . "\r\n" . "\r\n"
    								. $arrLaterPaymentStatus[$arrVal['memo09']];

    				$formData['header'] = $header;
    				$formData['footer'] = "";
    				$formData['subject'] = "後払い審査結果のお知らせ";
    				$Order = $this->app['orm.em']->getRepository('\Eccube\Entity\Order')->findOneBy(array('id' => $arrRet['trading_id']));
    				if (!is_null($Order)) {
    					$this->sendBatchOrderMail($Order, $formData, $app);
    				}
    			}
    }

    /**
     * Send batch order mail.
     *
     * @param $Order 受注情報
     * @param $formData 入力内容
     */
    function sendBatchOrderMail(\Eccube\Entity\Order $Order, $formData, $app)
    {
    	$loader = new \Twig_Loader_Filesystem(__DIR__.'/../View');

    	$twig = new \Twig_Environment($loader, array(
    			'cache' => __DIR__.'/../../../cache/twig',
    	));

    	$twig->addExtension(new \Eccube\Twig\Extension\EccubeExtension($app));

    	$template = $twig->loadTemplate('order_paygent.twig');

    	$arrOrder = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->getMemo02FromMdlOrderPayment($Order->getId());

    	$arrOther = null;
    	if (count($arrOrder) > 0) {
    		$arrOther = $arrOrder[0]['memo02'];
    	}

    	$body = $template->render(array(
    			'header' => $formData['header'],
    			'footer' => $formData['footer'],
    			'Order' => $Order,
    			'arrOther' => $arrOther,
    	));

    	$MdlOrderPayment = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
    	$objSiteInfo = $MdlOrderPayment->getShopInfo();

    	$transport = new \Swift_SmtpTransport();
    	$transport::newInstance($app['config']['transport'], $app['config']['port']);

    	// Create the Mailer using your created Transport
    	$mailer = \Swift_Mailer::newInstance($transport);

    	$message = \Swift_Message::newInstance()
    	->setSubject('[' . $objSiteInfo[0]['shop_name'] . '] ' . $formData['subject'])
    	->setFrom(array($objSiteInfo[0]['email03'] => $objSiteInfo[0]['shop_name']))
    	->setTo(array($Order->getEmail() => $Order->getName01().' '.$Order->getName02().' 様'))
    	->setBcc($objSiteInfo[0]['email01'])
    	->setBody($body);

    	$mailer->send($message);
    	$MailTemplate = $this->app['eccube.repository.mail_template']->find(1);
    	$this->saveMailHistory($message, $MailTemplate, $Order);
    }

    /**
     * 関数名：sendVirtualAccountErrorMail
     * 処理内容：異常入金通知メール送信処理
     * 戻り値：なし
     */
    function sendVirtualAccountErrorMail($arrRet, $app) {
    	$loader = new \Twig_Loader_Filesystem(__DIR__.'/../View');

    	$twig = new \Twig_Environment($loader, array(
    			'cache' => __DIR__.'/../../../cache/twig',
    	));

    	$twig->addExtension(new \Eccube\Twig\Extension\EccubeExtension($app));

    	$template = $twig->loadTemplate('paygent_virtual_account_error_mail.twig');
    	$clear_detail = '';
    	if ($arrRet['clear_detail'] == '03') {
    		$clear_detail = '入金額不足';
    	} else if ($arrRet['clear_detail'] == '04') {
    		$clear_detail = '入金額過多';
    	} else if ($arrRet['clear_detail'] == '05') {
    		$clear_detail = '消込対象なし';
    	}

    	$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);

    	$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_VIRTUAL_ACCOUNT']);

    	$body = $template->render(array(
    			'merchant_id' => $arrPaymentDB[0]['merchant_id'],
    			'payment_id' => $arrRet['payment_id'],
    			'trading_id' => $arrRet['trading_id'],
    			'payment_amount' => $arrRet['payment_amount'],
    			'clear_detail' => $clear_detail,
    	));

    	$MdlOrderPayment = $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
    	$objSiteInfo = $MdlOrderPayment->getShopInfo();

    	$transport = new \Swift_SmtpTransport();
    	$transport::newInstance($app['config']['transport'], $app['config']['port']);

    	// Create the Mailer using your created Transport
    	$mailer = \Swift_Mailer::newInstance($transport);

    	$message = \Swift_Message::newInstance()
    	->setSubject("【ペイジェント】異常入金のお知らせ")
    	->setFrom("pg-support@paygent.co.jp")
    	->setTo($objSiteInfo[0]['email04'])
    	->setBody($body);

    	$mailer->send($message);
    }

    // 後払い決済の審査結果
    function getArrLaterPaymentExampResult(){
    	return $arrLaterPaymentStatus = array(
    			"220_20" => '後払い決済の審査が通りました。',
    			"220_11" => '後払い決済の審査が通りませんでした。'
    	);
    }

    /**
     * 受注.対応状況の更新
     *
     * 必ず呼び出し元でトランザクションブロックを開いておくこと。
     *
     * @param  integer      $orderId     注文番号
     * @param  integer|null $newStatus   対応状況 (null=変更無し)
     * @return void
     */
    public function updateMdlOrderPaymentBatch($order_id, $arrParams, $memo06 = null)
    {
    	if (is_null($memo06)) {
    		$MdlOrder = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->findOneBy(array('id' => $order_id));
    	} else {
    		$MdlOrder = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->findOneBy(array('id' => $order_id, 'memo06' => $memo06));
    	}
    	// If no such data exists, create a new one
    	if (!is_null($MdlOrder)) {
	    	// Insert to dtb_mdl_order_payment
	    	$MdlOrder->setId($order_id);

	    	if (isset($arrParams['memo01'])) {
	    		$MdlOrder->setMemo01($arrParams['memo01']);
	    	}
	    	if (isset($arrParams['memo02'])) {
	    		$MdlOrder->setMemo02($arrParams['memo02']);
	    	}
	    	if (isset($arrParams['memo03'])) {
	    		$MdlOrder->setMemo03($arrParams['memo03']);
	    	}
	    	if (isset($arrParams['memo04'])) {
	    		$MdlOrder->setMemo04($arrParams['memo04']);
	    	}
	    	if (isset($arrParams['memo05'])) {
	    		$MdlOrder->setMemo05($arrParams['memo05']);
	    	}
	    	if (isset($arrParams['memo06'])) {
	    		$MdlOrder->setMemo06($arrParams['memo06']);
	    	}
	    	if (isset($arrParams['memo07'])) {
	    		$MdlOrder->setMemo07($arrParams['memo07']);
	    	}
	    	if (isset($arrParams['memo08'])) {
	    		$MdlOrder->setMemo08($arrParams['memo08']);
	    	}
	    	if (isset($arrParams['memo09'])) {
	    		$MdlOrder->setMemo09($arrParams['memo09']);
	    	}
	    	if (isset($arrParams['memo10'])) {
	    		$MdlOrder->setMemo10($arrParams['memo10']);
	    	}
	    	if (isset($arrParams['quick_flg'])) {
	    		$MdlOrder->setQuickFlg($arrParams['quick_flg']);
	    	}
	    	if (isset($arrParams['quick_memo'])) {
	    		$MdlOrder->setQuickMemo($arrParams['quick_memo']);
	    	}
	    	if (isset($arrParams['invoice_send_type'])) {
	    		$MdlOrder->setInvoiceSendType($arrParams['invoice_send_type']);
	    	}

	    	$this->app['orm.em']->persist($MdlOrder);
	    	$this->app['orm.em']->flush();
    	}
    }

    /**
     * 受注.対応状況の更新
     *
     * 必ず呼び出し元でトランザクションブロックを開いておくこと。
     *	In case $memo06 != null will select database to check exists
     * @param  integer      $orderId     注文番号
     * @param  integer|null $newStatus   対応状況 (null=変更無し)
     * @return void
     */
    public function updateOrderStatusBatch($orderId, $newStatus = null, $delFlg = null, $paymentDate = null, $memo06 = null)
    {
    	if (!is_null($memo06)) {
    		$MdlOrder = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment')->findOneBy(array('id' => $orderId, 'memo06' => $memo06));
    		// If no such data exists, create a new one
    		if (!is_null($MdlOrder)) {
    			$this->updateOrderStatusForBatch($orderId, $newStatus, $delFlg, $paymentDate);
    		}
    	} else {
    		$this->updateOrderStatusForBatch($orderId, $newStatus, $delFlg, $paymentDate);
    	}
    }

	/**
	 * Execute update order status
	 * @param unknown $orderId
	 * @param unknown $newStatus
	 * @param unknown $delFlg
	 * @param unknown $paymentDate
	 */
    public function updateOrderStatusForBatch($orderId, $newStatus = null, $delFlg = null, $paymentDate = null) {
    	$Order = $this->app['orm.em']->getRepository('\Eccube\Entity\Order')->findOneBy(array('id' => $orderId));
    	if (!is_null($Order)) {
    		if (!is_null($newStatus)) {
    			$OrderStatus = $this->app['orm.em']->getRepository('\Eccube\Entity\Master\OrderStatus')->findOneBy(array('id' => $newStatus));
    			if (!is_null($OrderStatus)) {


    				$ORDER_DELIV = $this->app['config']['order_deliv'];
    				$ORDER_PRE_END = $this->app['config']['order_pre_end'];
    				// 対応状況が発送済みに変更の場合、発送日を更新
    				if ($Order->getOrderStatus()->getId() != $ORDER_DELIV && $newStatus == $ORDER_DELIV) {
    					$Order->setCommitDate(new \DateTime());
    					// 対応状況が入金済みに変更の場合、入金日を更新
    				} elseif ($Order->getOrderStatus()->getId() != $ORDER_PRE_END && $newStatus == $ORDER_PRE_END) {
    					$Order->setPaymentDate(new \DateTime());
    				}

    				if (!is_null($delFlg)) {
    					$Order->setDelFlg($delFlg);
    				}

    				$Order->setOrderStatus($OrderStatus);
    				$Order->setUpdateDate(new \DateTime());

    			}
    		}

    		if (!is_null($paymentDate)) {
    			$date = new \DateTime();
    			$date::createFromFormat($date::ATOM, $paymentDate);
    			$Order->setPaymentDate($date);
    		}

    		$this->app['orm.em']->persist($Order);
    		$this->app['orm.em']->flush();
    	}
    }

    /**
     * Send order mail.
     *
     * @param $Order 受注情報
     */
    public function sendOrderMail(\Eccube\Entity\Order $Order, $arrOther = null)
    {

    	$BaseInfo = $this->app['eccube.repository.base_info']->get();
        
        //Add point parameters to mail template
        $Customer = $Order->getCustomer();
        if(!empty($Customer)){
            $usePoint = $this->app['eccube.plugin.point.repository.point']->getLatestPreUsePoint($Order);
            $usePoint = abs($usePoint);
            $calculator = $this->app['eccube.plugin.point.calculate.helper.factory'];
            $calculator->setUsePoint($usePoint); 
            $calculator->addEntity('Order', $Order);
            $calculator->addEntity('Customer', $Customer);
            $addPoint = $calculator->getAddPointByOrder();
            if (is_null($addPoint)) {
                    $addPoint = 0;
            } else if($addPoint > 100){
                    $addPoint = $addPoint-100;
            }
            $currentPoint = $calculator->getPoint();
            if (is_null($currentPoint)) {
                    $currentPoint = 0;
            }

            $Order->hasPoint = true;
            $Order->usePoint = $usePoint;
            $Order->addPoint = $addPoint;
            $Order->currentPoint = $currentPoint;
        } else {
            $Order->hasPoint = false; 
        }
        
        $session = $this -> app["session"];
        if (!$session -> has('order_detail_additional_info_list')) {
          return;
        }
        $orderDetailAdditionalInfoList = $session -> get('order_detail_additional_info_list');
        
        //Add orderType parameter to mail template
        $orderType = $orderDetailAdditionalInfoList[0] -> getOrderType();
        $Order->orderDetailInfo = $orderDetailAdditionalInfoList;
        $Order->orderType = $orderType;
        
        //change mail template
        
        
        if(!empty($orderType) && $orderType == 'deliv')
                $templateId = 100;
        else $templateId = 1;

    	$MailTemplate = $this->app['eccube.repository.mail_template']->find($templateId);

    	$loader = new \Twig_Loader_Filesystem(__DIR__.'/../View');

    	$twig = new \Twig_Environment($loader, array(
    			'cache' => __DIR__.'/../../../cache/twig',
    	));

    	$twig->addExtension(new \Eccube\Twig\Extension\EccubeExtension($this->app));

    	$template = $twig->loadTemplate('order.twig');

    	if (!is_null($arrOther)) {
    		$arrOther = unserialize($arrOther);
    	}

    	$body = $template->render(array(
    			'header' => $MailTemplate->getHeader(),
    			'footer' => $MailTemplate->getFooter(),
    			'Order' => $Order,
    			'arrOther' => $arrOther,
    	));

    	$message = \Swift_Message::newInstance()
    	->setSubject('[' . $BaseInfo->getShopName() . '] ' . $MailTemplate->getSubject())
    	->setFrom(array($BaseInfo->getEmail01() => $BaseInfo->getShopName()))
    	->setTo(array($Order->getEmail()))
    	->setBcc($BaseInfo->getEmail01())
    	->setReplyTo($BaseInfo->getEmail03())
    	->setReturnPath($BaseInfo->getEmail04())
    	->setBody($body);

    	$event = new EventArgs(
    			array(
    					'message' => $message,
    					'Order' => $Order,
    					'MailTemplate' => $MailTemplate,
    					'BaseInfo' => $BaseInfo,
    			),
    			null
    			);
    	$this->app['eccube.event.dispatcher']->dispatch(EccubeEvents::MAIL_ORDER, $event);

    	$this->app->mail($message);
    	$this->saveMailHistory($message, $MailTemplate , $Order);
    }

    /**
     * Save mail history.
     *
     * @param $message Object Swift_Message
     * @param $MailTemplate template mail
     * @param $Order 受注情報
     */
    public function saveMailHistory($message, $MailTemplate, $Order) {
    	$MailHistory = new MailHistory();
    	$MailHistory
    	->setSubject($message->getSubject())
    	->setMailBody($message->getBody())
    	->setMailTemplate($MailTemplate)
    	->setSendDate(new \DateTime())
    	->setOrder($Order);
    	$this->app['orm.em']->persist($MailHistory);
    	$this->app['orm.em']->flush($MailHistory);
    }

    /**
     * 関数名：getPluginVersion
     * 処理内容：config.ymlからプラグインのバージョンを取得する
     * 戻り値：プラグインのバージョン
     */
    function getPluginVersion() {

        $service = $this->app['eccube.service.plugin'];
        $pluginDir = $service->calcPluginDir("MdlPaygent");
        $config = $service->readYml($pluginDir.'/config.yml');
        return $config['version'];
    }

    /**
     * セキュリティ対策にペイジェント固有のハッシュを生成
     *
     * @param String $order_id 受注ID
     * @param String $create_date 受注発生時刻
     * @return String ハッシュ文字列
     */
    function createPaygentHash ($order_id, $create_date) {
        $hash = '';
        $arrPayment = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
        $arrPayment->setConfig($this->app['config']['MdlPaygent']['const']);
        $arrPaymentDB = $arrPayment->getPaymentDB();
        $hash = $order_id . $create_date . $arrPaymentDB[0]['connect_id'] . $arrPaymentDB[0]['connect_password'];
        for ($i=0; $i<3; $i++) {
            $hash = hash('sha256', $hash);
        }

        return $hash;
    }
}
