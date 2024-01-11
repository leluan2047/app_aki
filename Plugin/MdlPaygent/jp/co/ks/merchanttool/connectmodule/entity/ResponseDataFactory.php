<?php
/**
 * PAYGENT B2B MODULE
 * ResponseDataFactory.php
 *
 * Copyright (C) 2007 by PAYGENT Co., Ltd.
 * All rights reserved.
 */

namespace Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\entity;

use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\system\PaygentB2BModuleResources;
use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\entity\ReferenceResponseDataImpl;
use Plugin\MdlPaygent\jp\co\ks\merchanttool\connectmodule\entity\FilePaymentResponseDataImpl;

/**
 * 応答電文処理用オブジェクト作成クラス
 *
 * @version $Revision: 15878 $
 * @author $Author: orimoto $
 */
class ResponseDataFactory {
	private $app;

	public function __construct(\Eccube\Application $app)
	{
		$this->app = $app;
	}

	/**
	 * ResponseData を作成
	 *
	 * @param kind
	 * @return ResponseData
	 */
	public function create($kind) {
		$resData = null;
		$masterFile = null;

		$masterFile = PaygentB2BModuleResources::getInstance($this->app);

		// Create ResponseData
		if ($this->app['config']['MdlPaygent']['const']['PaygentB2BModule__TELEGRAM_KIND_FILE_PAYMENT_RES'] == $kind) {
			// ファイル決済結果照会の場合
			$resData = new FilePaymentResponseDataImpl($this->app);
		} elseif ($masterFile->isTelegramKindRef($kind)) {
			// 照会の場合
			$resData = new ReferenceResponseDataImpl($this->app);
		} else {
			// 照会以外の場合
			$resData = new PaymentResponseDataImpl($this->app);
		}

		return $resData;
	}

}

?>