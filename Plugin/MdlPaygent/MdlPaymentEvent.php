<?php

namespace Plugin\MdlPaygent;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Event\TemplateEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Eccube;
require_once "Service/htmlDOM.php";

class MdlPaymentEvent
{
    /**
     * @var \Eccube\Application
     */
    private $app;

    /**
     * CategoryContentEvent constructor.
     *
     * @param $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * when open page shopping, change button name
     *
     * @param TemplateEvent $event
     */
    public function onRenderShoppingBefore(FilterResponseEvent $event)
    {
        $nonMember = $this->app['session']->get('eccube.front.shopping.nonmember');
        if ($this->app->isGranted('ROLE_USER') || !is_null($nonMember)) {
            $Order = $this->app['eccube.repository.order']->findOneBy(array('pre_order_id' => $this->app['eccube.service.cart']->getPreOrderId()));
            if (!is_null($Order)) {
                $Payment = $Order->getPayment();
                $PaymentConfig = null;
                if (!is_null($Payment)) {
                    $PaymentConfig = $this->app['eccube.plugin.mdl_paygent.repository.mdl_payment_method']->find($Payment->getId());
                }
				if (!is_null($PaymentConfig)) {
                    // Get request
                    $request = $event->getRequest();
                    // Get response
                    $response = $event->getResponse();
                    // Proccess html
                    $html = $this->getHtmlShoppingConfirm($request, $response, $Payment);
                    // Set content for response
                    $response->setContent($html);
                    $event->setResponse($response);
				}
            }
        }
    }

    public function onControllerShoppingConfirmBefore($event = null) {

    	$nonMember = $this->app['session']->get('eccube.front.shopping.nonmember');
    	if ($this->app->isGranted('ROLE_USER') || !is_null($nonMember)) {
    	    if (is_null($this->app['eccube.service.cart']->getPreOrderId())) {
    	        $this->showErrorPage();
    	    }
    		$Order = $this->app['eccube.repository.order']->findOneBy(array('pre_order_id' => $this->app['eccube.service.cart']->getPreOrderId()));
    		if (is_null($Order) || is_null($Order->getPayment())) {
    		    $this->showErrorPage();
    		}
    		$paymentId = $Order->getPayment()->getId();
    		$this->app['session']->set('payment_id', $paymentId);

    		$listOldVersion = array('3.0.1', '3.0.2', '3.0.3', '3.0.4');
    		if (in_array(Constant::VERSION, $listOldVersion)) {
    			$form = $this->app['form.factory']->createBuilder('shopping')->getForm();
    			$deliveries = $this->findDeliveriesFromOrderDetails($this->app, $Order->getOrderDetails());
    			// 配送業社の設定
    			$shippings = $Order->getShippings();
    			$delivery = $shippings[0]->getDelivery();
    			// Formのカスタマイズ
    			$this->setFormDelivery($form, $deliveries, $delivery);           // 配送業社の設定
    			$this->setFormDeliveryDate($form, $Order, $this->app);           // お届け日の設定
    			$this->setFormDeliveryTime($form, $delivery);                    // お届け時間の設定
    			$this->setFormPayment($form, $delivery, $Order->getPayment());   // 支払い方法選択
    		} else {
    			$form = $this->app['eccube.service.shopping']->getShippingForm($Order);
    		}
    		if ('POST' === $this->app['request']->getMethod()) {
    			$form->handleRequest($this->app['request']);
    			if ($form->isValid()) {
    				$formData = $form->getData();
    				$mdlPaymentMethod = $this->app['eccube.plugin.mdl_paygent.repository.mdl_payment_method']->find($formData['payment']->getId());
    				if (!is_null($mdlPaymentMethod)) {
    					// 受注情報、配送情報を更新（決済処理中として更新する）
    					$this->app['eccube.service.order']->setOrderUpdate($this->app['orm.em'], $Order, $formData);
    					$Order->setOrderStatus($this->app['eccube.repository.order_status']->find($this->app['config']['order_processing']));
    					$this->app['orm.em']->persist($Order);
    					$this->app['orm.em']->flush();

                        // plugin統合対応
                        $this->systemService = $this->app ['eccube.plugin.service.system'];
                        $response = $this->systemService->procExit($this->app->url('mdl_paygent'), $this->app);
                        $event->setResponse($response);
                        return;
    				}
    			}
    		}
    	}
    }

    /**
     * Filter and add rename button submit in shopping confirm page
     * @param Request $request
     * @param Response $response
     * @param type $Payment
     * @return html
     */
    private function getHtmlShoppingConfirm(Request $request, Response $response, $Payment){
        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);
        $newMethod = $Payment->getMethod() . 'で支払う';
        try {
            $listOldVersion = array('3.0.1', '3.0.2', '3.0.3', '3.0.4');

            if (in_array(Constant::VERSION, $listOldVersion)) {
                $oldMethod = $crawler->filter('.btn.btn-primary.btn-block')->html();
            }else{
                $oldMethod = $crawler->filter('#order-button')->html();
            }

            $html = str_replace($oldMethod, $newMethod, $html);
        } catch (\InvalidArgumentException $e) {
        	$logFilePath = __DIR__ . "/../../log/paygent_cube.log";
        	if (!file_exists($logFilePath)) {
        		touch($logFilePath);
        	}

            $logStart = '******* Exception when change content button shopping start *******';
            $logContent = $e->getTraceAsString();
           	$logEnd = '******* Exception when change content button shopping end *******';

            $this->app['eccube.plugin.service.plugin']->gfPrintLog($this->app, $logStart, $logFilePath);
            $this->app['eccube.plugin.service.plugin']->gfPrintLog($this->app, $logContent, $logFilePath);
            $this->app['eccube.plugin.service.plugin']->gfPrintLog($this->app, $logEnd, $logFilePath);
        }
        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }

	public static function getHtml(Crawler $crawler){
        $html = '';
        foreach ($crawler as $domElement) {
            $domElement->ownerDocument->formatOutput = true;
            $html .= $domElement->ownerDocument->saveHTML();
        }
        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }

	/**
     * Generate shopping complete
     * @param FilterResponseEvent $event
     * @return type
     */
    public function onRenderShoppingCompleteBefore(FilterResponseEvent $event) {
        $nonMember = $this->app['session']->get('eccube.front.shopping.nonmember');
        if ($this->app->isGranted('ROLE_USER') || !is_null($nonMember)) {
            // Get request
            $request = $event->getRequest();
            // Get response
            $response = $event->getResponse();
            // Find dom and add extension template
            $html = $this->getHTMLShoppingComplete($request, $response);
            // Set content for response
            $response->setContent($html);
            $event->setResponse($response);
        }
    }
    /**
     * Find and add extension template to response.
     * @param FilterResponseEvent $event
     * @return type
     */
    public function getHTMLShoppingComplete(Request $request, Response $response){
    	$app = $this->app;

        $crawler = new Crawler($response->getContent());
        $html = $this->getHtml($crawler);

        try{
        	if (!is_null($this->app['session']->get("payment_id"))) {
        		$paymentId = $this->app['session']->get('payment_id');

	        	$insert = $this->getInsertHtmlToComplete($paymentId);
	            $oldHtml = $crawler->filter('#deliveradd_input > div > div > h2')->html();
	            $newHtml = $oldHtml . $insert;
	            $html = str_replace($oldHtml, $newHtml, $html);

	            //remove session payment_id
	            $this->app['session']->remove('payment_id');
        	}
        } catch (\InvalidArgumentException $e) {
        }
        return html_entity_decode($html, ENT_NOQUOTES, 'UTF-8');
    }

    public function getInsertHtmlToComplete($paymentId){
    	//get Payment method
    	$paymentService = $this->app['eccube.plugin.service.payment'];
    	$PaymentExtension = $paymentService->getPaymentTypeConfig($paymentId);
    	if ($PaymentExtension != false) {
	    	$paymentCode = $PaymentExtension->getPaymentCode();

	    	switch ($paymentCode) {
	    		case $this->app ['config'] ['MdlPaygent'] ['const'] ['PAY_PAYGENT_ATM'] :
	    			$data = $this->app['session']->get('dataReturn');
	    			$data['payment_limit_date'] = date("Y年m月d日", strtotime($data['payment_limit_date']));

	    			$orderId = null;
	    			if ($this->app['session']->has('orderIdATM')){

	    				$orderId = $this->app['session']->get('orderIdATM');

	    				$this->app['session']->remove('orderIdATM');

	    				$arrData = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->getMemo02FromMdlOrderPayment($orderId);
	    				$arrOther = $arrData[0]['memo02'];

	    				if (!is_null($arrOther)) {
	    					$arrOther = unserialize($arrData[0]['memo02']);
	    					foreach($arrOther as $key => $val){
	    						// URLの場合にはリンクつきで表示させる
	    						if (preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $val["value"])) {
	    							$arrOther[$key]["value"] = "<a href=".$val["value"]." target='_blank'>" . $val["value"] ."</a>";
	    						}
	    					}
	    				}

	    				$this->app['session']->remove('dataReturn');
	    				return $this->app->renderView ( 'MdlPaygent/View/atm_settlement_complete.twig', array (
	    						'arrOther' => $arrOther
	    				));
	    			}
	    			break;
	    		case $this->app ['config'] ['MdlPaygent'] ['const'] ['PAY_PAYGENT_CONVENI_NUM'] :
	    			$conveniService = $this->app['eccube.plugin.service.convenience'];
	    			$orderId = $this->app['session']->get('orderIdConvi');
	    			$shopInfo = $this->app['session']->get('shopInfo');

	    			if ($this->app['session']->has('orderIdConvi')){
	    				$this->app['session']->remove('orderIdConvi');
	    			}

	    			if ($this->app['session']->has('shopInfo')){
	    				$this->app['session']->remove('shopInfo');
	    			}
	    			$arrData = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->getMemo02FromMdlOrderPayment($orderId);
	    			$arrOther = $arrData[0]['memo02'];

					if (!is_null($arrOther)) {
						$arrOther = unserialize($arrData[0]['memo02']);
						foreach($arrOther as $key => $val){
							// URLの場合にはリンクつきで表示させる
							if (preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $val["value"])) {
								$arrOther[$key]["value"] = "<a href=".$val["value"]." target='_blank'>" . $val["value"] ."</a>";
							}
						}
					}
	    			return $this->app['view']->render('MdlPaygent/View/convenience_complete.twig', array(
	    					'arrOther' => $arrOther,
	    					'shop_name' => $shopInfo[0]['shop_name'],
	    					'email' => $shopInfo[0]['email01'],
	    					'tel' => $shopInfo[0]['tel01']."-".$shopInfo[0]['tel02']."-".$shopInfo[0]['tel03'],
	    			));
	    			break;
	    		case $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT']:

	    			$orderId = null;
	    			if ($this->app['session']->has('orderIdLater')) {
	    				$orderId = $this->app['session']->get('orderIdLater');

	    				$this->app['session']->remove('orderIdLater');

	    				$arrData = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->getMemo02FromMdlOrderPayment($orderId);
	    				$arrOther = $arrData[0]['memo02'];

	    				if (!is_null($arrOther)) {
	    					$arrOther = unserialize($arrData[0]['memo02']);
	    					return $this->app->renderView('MdlPaygent/View/later_complete.twig', array (
	    							'arrOther' => $arrOther,
	    					));
	    				}

	    			}
	    			break;

    			case $this->app ['config'] ['MdlPaygent'] ['const'] ['PAY_PAYGENT_VIRTUAL_ACCOUNT'] :

    				if ($this->app['session']->has('orderIdVA')){
    					$orderId = $this->app['session']->get('orderIdVA');
    					$this->app['session']->remove('orderIdVA');

    					$arrData = $this->app['eccube.plugin.mdl_paygent.repository.mdl_order_payment']->getMemo02FromMdlOrderPayment($orderId);
    					$arrOther = $arrData[0]['memo02'];

    					if (!is_null($arrOther)) {
    						$arrOther = unserialize($arrData[0]['memo02']);
    						foreach($arrOther as $key => $val){
    							// URLの場合にはリンクつきで表示させる
    							if (preg_match('/^(https?|ftp)(:\/\/[-_.!~*\'()a-zA-Z0-9;\/?:\@&=+\$,%#]+)$/', $val["value"])) {
    								$arrOther[$key]["value"] = "<a href=".$val["value"]." target='_blank'>" . $val["value"] ."</a>";
    							}
    						}
    					}
    					return $this->app->renderView ( 'MdlPaygent/View/virtual_account_complete.twig', array (
    							'arrOther' => $arrOther
    					));
    				}
    				break;
	    	}
    	}
    }

    public function onRenderAdminOrderBefore(FilterResponseEvent $event){
    		// Get request
    		$request = $event->getRequest();
    		// Get response
    		$response = $event->getResponse();
    		// Find dom and add extension template
    		$html = $this->getHTMLAdminOrderBefore($request, $response);
    		// Set content for response
    		$response->setContent($html);
    		$event->setResponse($response);
    }

    public function onRenderAdminOrderEditBefore(FilterResponseEvent $event){
    	// Get request
    	$request = $event->getRequest();
    	// Get response
    	$response = $event->getResponse();
    	// Find dom and add extension template
    	$html = $this->getHTMLAdminOrderEditBefore($request, $response);
    	// Set content for response
    	$response->setContent($html);
    	$event->setResponse($response);
    }

    public function getHTMLAdminOrderBefore(Request $request, Response $response){
    	$crawler = new Crawler($response->getContent());
    	$html = $this->getHtml($crawler);
    	try{
    	$insertHeader = $this->app->renderView('MdlPaygent/View/Admin/order_extent_header.twig');
    	$oldHtmlHeader = $crawler->filter('#result_list_main__header_id')->html();

    	$newHtmlHeader = $oldHtmlHeader . $insertHeader;

    	$html = str_replace ( $oldHtmlHeader, $newHtmlHeader, $html );
    	$crawler2 = new Crawler($html);
    	$oldHtmlHeader2 = $crawler2->filter('#result_list_main__list_body > table > tbody')->html();

    	$html1 = str_get_html($oldHtmlHeader2);

    	foreach($html1->find('td[!class]') as $element){
    		foreach ($element->find('a') as $el){
	    		$orderId = $el->innertext;
	    		//
	    		$a = "";
	    		$paymentService = $this->app['eccube.plugin.service.payment'];
	    		$arrDispKind = $paymentService->getDispKind();
	    		$paymentMethod = $paymentService -> getPaymentForAdminOrder();

	    		$arrPaymentDB = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');

	    		$OrderObj = $arrPaymentDB->getOrderByIdMethod($orderId);
	    		if (!is_null($OrderObj) && count($OrderObj)>0) {
	    			$a = $OrderObj[0];
	    		}
	    		$insertValue = $this->app->renderView('MdlPaygent/View/Admin/order_extent.twig',array(
	    				'orderId' => $orderId,
	    				'OrderObj'=> $a,
	    				'arrDispKind' => $arrDispKind,
	    				'paymentMethod' => $paymentMethod,
	    		));

	    		$oldHtmlValue = $crawler->filter('#result_list_main__id--'.$orderId)->html();
	    		$newHtmlValue = $oldHtmlValue . $insertValue;
	    		$html = str_replace($oldHtmlValue, $newHtmlValue, $html);
    		}
    	}
    	}catch ( \InvalidArgumentException $e ) {
		}
		return html_entity_decode ( $html, ENT_NOQUOTES, 'UTF-8' );
	}


	public function getHTMLAdminOrderEditBefore(Request $request, Response $response){
		$crawler = new Crawler($response->getContent());
		$html = $this->getHtml($crawler);
		$paygent_return="";
		$message = "(未処理)";
		$arrCarriersCompanyCode = array();
		$arrClientReasonCode = array();
		$arrError = array();
		$arrOrderPaygent = array();
		try{
			//Get arrOrderPaygent
			$strPathInfo = $_SERVER['REQUEST_URI'];
			$arrData = explode('/',$strPathInfo);
			$i = count($arrData) - 2;
				$orderId= $arrData[$i];
			//get payment method
			$paymentService = $this->app['eccube.plugin.service.payment'];
			$arrDispKind = $paymentService->getDispKind();
			$paymentMethod = $paymentService -> getPaymentForAdminOrder();
			$arrInvoiceSendType = $paymentService -> getInvoiceSendTypeOption();

			//getArrCarriersCompanyCode
			$arrCarriersCompanyCode = $paymentService->getArrCarriersCompanyCode();
			$arrClientReasonCode = $paymentService->getArrClientReasonCode();

			$type = "";
			$arrPaymentDB = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
			$OrderObj = $arrPaymentDB->getOrderByIdMethod($orderId);
			if (!is_null($OrderObj) && count($OrderObj)>0) {
				$arrOrderPaygent = $OrderObj[0];
				if($arrOrderPaygent->getMemo08() == $paymentMethod['PAYGENT_CREDIT']){
					$type = "カード";
				}else if ($arrOrderPaygent->getMemo08() == $paymentMethod['PAYGENT_CAREER_D']
						|| $arrOrderPaygent->getMemo08() == $paymentMethod['PAYGENT_CAREER_A']
						|| $arrOrderPaygent->getMemo08() == $paymentMethod['PAYGENT_CAREER_S']){
							$type = "携帯キャリア";
				}else if ($arrOrderPaygent->getMemo08() == $paymentMethod['PAYGENT_LATER_PAYMENT']){
					$type = "後払い";
				}
			}
			//get payment_return
			if ('POST' === $this->app ['request']->getMethod ()) {
            	$orderService = $this->app['eccube.plugin.service.order'];
            	$paygent_return = "";
            	switch($this->getMode()) {
            		case 'paygent_order':
            			$arrError = $orderService->checkError($_POST['paygent_type']);
            			if(isset($arrError[0])){
            				break;
            			}
            			$paygent_return = $orderService->sfPaygentOrder($_POST['paygent_type'], $orderId, '', '', $this->getPaygentRequst());
            			break;
            	}
        	}
			//set message
			if ($paygent_return != "" && (!isset($paygent_return['revice_price_error']) || $paygent_return['revice_price_error'] == "")){
				if($paygent_return['return'] === true){
					$message = $arrDispKind[$paygent_return['kind']]."に成功しました。";
				}else if (isset($paygent_return['response'])){
					$message = $arrDispKind[$paygent_return['kind']]."に失敗しました。".$paygent_return['response'];
				}else{
					$message = $arrDispKind[$paygent_return['kind']]."に失敗しました。";
				}
			}else if(isset($arrOrderPaygent['memo09']) && $arrOrderPaygent['memo09'] != ""){
				$message = $arrDispKind[$arrOrderPaygent['memo09']];
			}
			$insertHeader = "";
			if (!is_null($OrderObj) && count($OrderObj)>0) {
				$insertHeader = $this->app->renderView('MdlPaygent/View/Admin/paygent_order.twig',array(
		    		'arrOrderPaygent'=> $arrOrderPaygent,
					'paymentMethod'=> $paymentMethod,
					'type'=> $type,
					'orderId'=> $orderId,
					'message' => $message,
					'arrCarriersCompanyCode'=>$arrCarriersCompanyCode,
					'arrClientReasonCode' => $arrClientReasonCode,
					'arrInvoiceSendType' => $arrInvoiceSendType,
					'invoiceSendType' =>$arrOrderPaygent->getInvoiceSendType(),
					'arrError' => $arrError,
		    	));
			}
			$oldHtmlHeader = $crawler->filter('.col_inner')->html();

			$newHtmlHeader = $oldHtmlHeader . $insertHeader;

			$html = str_replace($oldHtmlHeader, $newHtmlHeader, $html);

		}catch ( \InvalidArgumentException $e ) {
		}
		return html_entity_decode ( $html, ENT_NOQUOTES, 'UTF-8' );
	}

	function processOrderEdit($order_id){
		$arrError = array();
		$orderService = $this->app['eccube.plugin.service.order'];
		$paygent_return = "";
		switch($this->getMode()) {
			case 'paygent_order':
				$arrError = $orderService->checkError($_POST['paygent_type']);
				if(0 < count($arrError)){
					return $arrError;
				}
				$paygent_return = $orderService->sfPaygentOrder($_POST['paygent_type'], $order_id, '', '', $this->getPaygentRequst());
			break;
		}
		return $paygent_return;
	}

	public function getMode()
	{
		$pattern = '/^[a-zA-Z0-9_]+$/';
		$mode = null;
		if (isset($_REQUEST['modal']) && preg_match($pattern, $_REQUEST['modal'])) {
			$mode =  $_REQUEST['modal'];
		}
		return $mode;
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

	function showErrorPage() {
	    $error_title = 'エラー';
	    $error_message = "不正なページ移動です。";
	    echo $this->app['view']->render('error.twig', array('error_message' => $error_message, 'error_title'=> $error_title));
	    exit;
	}

}
