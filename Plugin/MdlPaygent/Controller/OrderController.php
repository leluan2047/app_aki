<?php

/*
 * Copyright(c) 2015 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\Controller;

use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Eccube;

/**
 * Controller to handle module setting screen
 */
class OrderController {

	private $app;

	public function index(\Eccube\Application $app, Request $request) {
		$this->app = $app;
		//service
		$orderService = $app['eccube.plugin.service.order'];

		if(!empty($_GET['arrOrderId']))
		{
			$OrderId = $_GET['arrOrderId'];
		}
		if(!empty($_GET['mode']))
		{
			$mode = $_GET['mode'];
		}
		//lfOutputTitle
		$this->lfOutputTitle($mode);

		$arrOrderId = explode("--", $OrderId);

		list($success_cnt, $fail_cnt) = $orderService -> lfPaygentAllOrder($arrOrderId);

		$this->lfOutputResult($success_cnt, $fail_cnt);

		return $this->app['view']->render('MdlPaygent/View/Admin/paygent_order_commit.twig');
	}

	public function edit(\Eccube\Application $app, Request $request, $data = null) {
		$this->app = $app;
		//service
		$orderService = $app['eccube.plugin.service.order'];
		if(isset($data)){
			$arrData = explode("-", $data);
		}
		$orderId = $arrData[0];
		$paygent_type = $arrData[1];
		$this->paygent_return = $orderService->sfPaygentOrder($paygent_type, $order_id, '', '', $this->getPaygentRequst());
	}

	function getPaygentRequst() {
		$arrRequest = array();
		switch($_POST['paygent_type']) {
			case 'later_payment_reduction':
				$arrRequest['invoice_send_type'] = $_REQUEST['invoice_send_type'];
				break;
			case 'later_payment_clear':
				$arrRequest['delivery_company_code'] = $_REQUEST['carriers_company_code'];
				$arrRequest['delivery_slip_no'] = $_REQUEST['delivery_slip_number'];
				break;
			case 'later_payment_bill_reissue':
				$arrRequest['reason_code'] = $_REQUEST['client_reason_code'];
				break;
		}
		return $arrRequest;
	}

	function lfOutputResult($success_cnt, $fail_cnt) {
		// 結果表示
		$output = "<br />\n■ 処理結果<br />\n";
		$output .= $success_cnt. "件が成功しました。<br />\n";
		$output .= $fail_cnt. "件が失敗しました。<br />\n";
		$output .= "<br />\n";
		$output .= "メイン画面のリロードを行い、受注一覧を更新してください。<br />\n";
		echo $output;
	}

	// タイトル出力
	function lfOutputTitle($mode) {
		// タイトル出力
		if ($mode == 'paygent_commit') $output = "■ 一括売上<br />\n";
		elseif ($mode == 'paygent_cancel') $output = "■ 一括取消<br />\n";
		echo $output;
	}

}