<?php

/*
 * Copyright(c) 2015 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Controller;

use Eccube\Application;
use Plugin\MdlPaygent\Form\Type\ConfigType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

/**
 * Controller to handle module setting screen
 */
class ConfigController {

    private $app;

    /**
     * Edit config
     *
     * @param Application $app
     * @param Request $request
     * @param type $id
     * @return type
     */
    public function edit(Application $app, Request $request) {
        $this->app = $app;
        $objMdl = $app['eccube.plugin.service.plugin'];
        $objUtil = $app['eccube.plugin.service.payment'];
        $tpl_subtitle = $objMdl->getName();
        $errors = array(
        		'err' => null,
        		'hash_key' => null,
        		'payment_detail' => null,
        		'claim_kana' => null,
        		'claim_kanji' => null,
        		'free_memo' => null,
        		'merchant_name' => null,
        		'link_free_memo' => null,
        );

        $objMdl->install();

        // Get module code from dtb_plugin
        $self = Yaml::parse(__DIR__ . '/../config.yml');
        $Plugin = $this->app['eccube.repository.plugin']->findOneBy(array('code' => $self['code']));

        if (is_null($Plugin)) {
            $error = "不正なページ移動です。";
            $error_title = 'エラー';
            return $this->app['view']->render('error.twig', array('error_message' => $error, 'error_title'=> $error_title));
        }

        $subData = $objMdl->getSubData();

        // get array payment
        $Payments = $objUtil->getPaymentMethod();

        $connectType = null;

        if (!empty($subData)) {
        	$connectType = $subData['settlement_division'];
        }
        if(!empty($_POST['connect_type']))
        {
        	$connectType = $_POST['connect_type'];
        }

        // Get config form
        $configFrom = new ConfigType($this->app, $subData, $connectType);
        // Create form
        $form = $this->app['form.factory']->createBuilder($configFrom)->getForm();

        // In case click button Register/この内容で登録する
        if ('POST' === $this->app['request']->getMethod()) {
            $form->handleRequest($request);
            // 全角文字チェック
            $arrWideCharParams = array(
            		'payment_detail' => "店舗名（カナ）",
            		'claim_kanji' => "店舗名",
            		'claim_kana' => "店舗名（カナ）",
            		'free_memo' => "自由メモ欄",
            		'merchant_name' => "店舗名",
            		'link_free_memo' => "自由メモ欄",
            );

            $formData = $form->getData();

            foreach ($arrWideCharParams as $key => $val) {
            	$value = $formData[$key];
            	if (!empty($value)) {
            		if (preg_match('/\s/', $value)) {
            			$errors[$key] = "※ " . $val . "に半角スペース・改行は入力できません。";
            		}
            	}
            }

            if ($form->isValid()) {
				if ($connectType != "2") {
					if (isset($formData['hash_key']) && strlen($formData['hash_key']) > 0) {
						if(!file_exists(__DIR__ . '/Util/PaygentHash.php')) {
							$errors['hash_key'] = "※ ペイジェント提供のハッシュ生成プログラムを設置してください。";
						}
						if (!function_exists("hash") && !function_exists("mhash")) {
							$errors['hash_key'] = "※ hash関数かmhash関数の利用が必須です。";
						}
					}
				}

                if ($connectType != "1") {
                    $path = __DIR__ . "/../jp/co/ks/merchanttool/connectmodule/";
                    if(!file_exists($path)) {
                        $errors['err'] = "※ ペイジェント提供モジュールを設置してください。<br /> " . $path;
                    // 接続テストを実行
                    } else {
                        // マーチャントID
                        $arrParam['merchant_id'] = $formData['merchant_id'];
                        // 接続ID
                        $arrParam['connect_id'] = $formData['connect_id'];
                        // 接続パスワード
                        $arrParam['connect_password'] = $formData['connect_password'];
                        // 実行
                        if(!$objMdl->sfPaygentTest($arrParam)) {
                            $errors['err'] = "※ 接続試験に失敗しました。<br />";
                            if (isset($arrParam['result_message']) && $arrParam['result_message']) {
                                $errors['err'] .= nl2br($arrParam['result_message']);
                            }
                        }
                    }
                }
                // Incase exists error
				if ($this->existsError($errors)) {
					return $this->app['view']->render('MdlPaygent/View/Admin/mdl_config.twig', array(
			                    'form' => $form->createView(),
			                    'tpl_subtitle' => $tpl_subtitle,
								'errors' => $errors,
			        ));
				} else {
	                $this->app['orm.em']->getConnection()->beginTransaction();

	                $this->setPaymentDB($formData, $Payments);

	                $this->app['orm.em']->getConnection()->commit();

	                $app->addSuccess('admin.register.complete', 'admin');
	                return $this->app->redirect($this->app['url_generator']->generate('plugin_MdlPaygent_config'));
				}
            }
        }
        return $this->app['view']->render('MdlPaygent/View/Admin/mdl_config.twig', array(
                    'form' => $form->createView(),
                    'tpl_subtitle' => $tpl_subtitle,
        			'errors' => $errors,
        ));
    }

    /**
     * Register pay layout
     */
    public function registPaylayout() {
    	$url = "mdl_paygent";
    	$DeviceType = $this->app['eccube.repository.master.device_type']->find(10);
    	$PageLayout = $this->app['eccube.repository.page_layout']->findOneBy(array('url' => $url));
    	if (is_null($PageLayout)) {
    		$PageLayout = $this->app['eccube.repository.page_layout']->newPageLayout($DeviceType);
    	}

    	$PageLayout->setName('商品購入/MDLペイメント決済画面');
    	$PageLayout->setUrl($url);
    	$PageLayout->setMetaRobots('noindex');
    	$PageLayout->setEditFlg('2');
    	$this->app['orm.em']->persist($PageLayout);

    	$this->app['orm.em']->flush();
    }

    /**
     * Check exists error
     */
    function existsError($errors)
    {
    	foreach ($errors as $key => $val) {
			if (!is_null($val)) {
				return true;
			}
    	}
    	return false;
    }
    /**
     * 支払方法の更新処理
    */
    public function setPaymentDB($formData, $Payments) {
    	// Init object
    	$objMdl = $this->app['eccube.plugin.service.plugin'];
    	$settlement_division = $formData['settlement_division'];
    	// Get value of settlement from file config.yml
    	$settlementModule = $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MODULE'];
    	$settlementLink = $this->app['config']['MdlPaygent']['const']['SETTLEMENT_LINK'];
		// Prepare data to save to memo01, memo02, memo04
    	$saveData = array(
    			"merchant_id"=>$formData['merchant_id'],
    			"connect_id"=>$formData['connect_id'],
    			"connect_password"=>$formData['connect_password'],
    	);

    	// Delete all payment method at dtb dtb_mdl_payment_method
    	$MdlPaymentRepo = $this->app['eccube.plugin.mdl_paygent.repository.mdl_payment_method'];
    	$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
    	// Get all payment Id of this module
    	$listId = $MdlPaymentRepo->getPaymentByCode(true, $this->app);

    	$formData = $this->resetSubData($formData, $settlement_division);
    	$formData['merchant_name'] = mb_convert_kana($formData['merchant_name'], "KVA");
    	$formData['claim_kanji'] = mb_convert_kana($formData['claim_kanji'], "KVA");
    	$formData['free_memo'] = mb_convert_kana($formData['free_memo'], "KVA");
    	// Regist subdata
    	$objMdl->registerSubData($formData);

    	if ($settlement_division == $settlementModule && count($formData['payment']) > 0) {
    		// チェックされた決済を登録
    		$installedPayment = array();
			// Loop all payment has checked
    		foreach ($formData['payment'] as $paymentTypeId) {
    			//インストールされていなければ新規作成
				$arrSubData = $this->getSubDataForMemo5($paymentTypeId, $formData, $settlement_division);
    			$id = $this->savePayment($paymentTypeId, $Payments);
    			$this->saveMdlPayment($id, $paymentTypeId, $Payments, $saveData, $arrSubData);

    			$installedPayment[] = $id;
    		}

    		// チェックされていない決済を削除
    		$this->updateDelFlag($listId, $installedPayment);
    	} else if ($settlement_division != $settlementModule) {
    		// In case settlement_division is リンク型/SETTLEMENT_LINK
    		if ($settlement_division == $settlementLink) {
    			$saveData['connect_id'] = null;
    			$saveData['connect_password'] = null;
    		}

    		$paymentTypeId = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LINK'];
    		$Payments = array($paymentTypeId=>"PAYGENT決済");
    		$arrSubData = $this->getSubDataForMemo5($paymentTypeId, $formData, $settlement_division);

    		$id = $this->savePayment($paymentTypeId, $Payments);
    		$this->saveMdlPayment($id, $paymentTypeId, $Payments, $saveData, $arrSubData);
    		$installedPayment[] = $id;

    		// チェックされていない決済を削除
    		$this->updateDelFlag($listId, $installedPayment);
    	}
    	// dtb_page_lauoutにも登録
    	$this->registPaylayout();
    }

    /**
     * Insert or update payment that user selected
     * @param integer $paymentTypeId
     * @param array $Payments
     * @return payment_id
     */
    public function savePayment($paymentTypeId, $Payments) {

    	//Get MdlPaymentMethod of this paymentTypeId without considering del_flg
    	$Payment = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod')->getPaymentByType($paymentTypeId, true, $this->app);

    	// If no such data exists, create a new one
    	if (is_null($Payment)) {
    		$Payment = $this->app['orm.em']->getRepository('\Eccube\Entity\Payment')->findOrCreate(0);
    	}

    	//Get payment from dtb_payment by id, with option to including or excluding deleted record
    	$PaymentMethods = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod')->getAllPaymentMethods($Payment->getId(), true, $this->app);
    	if (!is_null($PaymentMethods)) {
    		if ($PaymentMethods->getDelFlg() == 1) {
    			$PaymentMethods->setUpdateDate(new \DateTime());
    			$PaymentMethods->setDelFlg(0);
    		}
    		if ($Payment->getDelFlg() == 1) {
    			$Payment->setUpdateDate(new \DateTime());
    			$Payment->setDelFlg(0);
    		}

    		return $Payment->getId();
    	}

    	// If data exists, update some info, but keep value in memo05
    	$Payment->setMethod($Payments[$paymentTypeId]);

    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CREDIT']) {
    		$Payment->setChargeFlg(2);
    	} else {
    		$Payment->setChargeFlg(1);
    	}

    	$Payment->setFixFlg(1);

    	$Payment->setUpdateDate(new \DateTime());
    	$Payment->setCreateDate(new \DateTime());
    	$Payment->setDelFlg(0);
    	$Payment->setCharge(0);

    	$this->app['orm.em']->persist($Payment);
    	$this->app['orm.em']->flush();
    	return $Payment->getId();
    }


    /**
     * Insert or update dtb_mdl_payment_method
     * @param integer $id
     * @param integer $paymentId
     * @param array $Payments
     */
    public function saveMdlPayment($id, $paymentTypeId, $Payments, $saveData, $arrSubData) {
    	$objMdl = $this->app['eccube.plugin.service.plugin'];
    	$pluginCode = $objMdl->getCode(true);

    	$MdlPayment = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod')->getMdlPayment('id', $id, true, $this->app);

    	if (is_null($MdlPayment)) {
    		// Create new payment
    		$MdlPayment = $this->app['eccube.plugin.mdl_paygent.repository.mdl_payment_method']->findOrCreate(0);
    	}
    	$MdlPayment->setId($id);

    	$MdlPayment->setMethod($Payments[$paymentTypeId]);
    	$MdlPayment->setDelFlg(0);
    	$MdlPayment->setUpdateDate(new \DateTime());
    	$MdlPayment->setCreateDate(new \DateTime());
    	$MdlPayment->setMemo01($saveData['merchant_id']);
    	$MdlPayment->setMemo02($saveData['connect_id']);
    	$MdlPayment->setMemo03($paymentTypeId);
    	$MdlPayment->setMemo04($saveData['connect_password']);
    	$MdlPayment->setMemo05(serialize($arrSubData));
    	$MdlPayment->setCode($pluginCode);
    	$this->app['orm.em']->persist($MdlPayment);
    	$this->app['orm.em']->flush();
    }

    /**
     *
     * Method will get all data depend on $paymentTypeId
     * @param unknown $paymentTypeId payment id
     * @param unknown $formData Form data
     * @return $arrParam[] array contain data of payment
     */
    function getSubDataForMemo5($paymentTypeId, $formData, $settlement_division) {
    	$arrParam = array();
    	if ($settlement_division == $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MODULE']) {
	    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CREDIT']) {
	    		$arrParam['payment_division'] = $formData['payment_division'];
	    		$arrParam['security_code'] = $formData['security_code'];
	    		$arrParam['credit_3d'] = $formData['credit_3d'];
	    		$arrParam['stock_card'] = $formData['stock_card'];
	    		$arrParam['token_pay'] = $formData['token_pay'];
	    		$arrParam['token_env'] = $formData['token_env'];
	    		$arrParam['token_key'] = $formData['token_key'];
	    	}

	    	// コンビニ登録(番号方式)
	    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CONVENI_NUM']) {
	    		$arrParam['payment_limit_date'] = $formData['conveni_limit_date_num'];
	    	}
	    	// ATM決済登録
	    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_ATM']) {
	    		$arrParam['payment_detail'] = $formData['payment_detail'];
	    		$arrParam['payment_limit_date'] = $formData['atm_limit_date'];
	    	}

	    	// 銀行NET登録
	    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_BANK']) {
	    		$arrParam['claim_kana'] = $formData['claim_kana'];
	    		$arrParam['claim_kanji'] = $formData['claim_kanji'];
	    		$arrParam['asp_payment_term'] = $formData['asp_payment_term'];
	    		$arrParam['copy_right'] = $formData['copy_right'];
	    		$arrParam['free_memo'] = $formData['free_memo'];
	    	}

	    	// 携帯キャリア決済
	    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER']) {
	    		$arrParam['career_division'] = $formData['career_division'];
	    	}

	    	// 仮想口座決済
	    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_VIRTUAL_ACCOUNT']) {
	    		$arrParam['numbering_type'] = $formData['numbering_type'];
	    		$arrParam['payment_limit_date'] = $formData['virtual_account_limit_date'];
	    	}

	    	// 後払い決済
	    	if ($paymentTypeId == $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_LATER_PAYMENT']) {
	    		$arrParam['result_get_type'] = $formData['result_get_type'];
	    		$arrParam['exam_result_notification_type'] = $formData['exam_result_notification_type'];
	    		$arrParam['invoice_include'] = $formData['invoice_include'];
	    	}
    	} else if ($settlement_division != $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MODULE']){
    		$arrParam['link_url'] = $formData['link_url'];
    		$arrParam['hash_key'] = $formData['hash_key'];
    		$arrParam['payment_term_day'] = $formData['link_payment_term'];
    		$arrParam['merchant_name'] = $formData['merchant_name'];
    		$arrParam['free_memo'] = $formData['link_free_memo'];
    		$arrParam['copy_right'] = $formData['link_copy_right'];
    		$arrParam['payment_class'] = $formData['card_class'];
    		$arrParam['use_card_conf_number'] = $formData['card_conf'];
    	}
    	return $arrParam;
    }

    /**
     *
     * @param unknown $formData
     */
    function resetSubData($formData, $settlement_division)
    {
		if ($settlement_division == $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MODULE']) {
			$formData['link_url'] = null;
			$formData['hash_key'] = null;
			$formData['card_class'] = 0;
			$formData['card_conf'] = 0;
			$formData['link_payment_term'] = "5";
			$formData['merchant_name'] = null;
			$formData['link_copy_right'] = null;
			$formData['link_free_memo'] = null;
		} else if ($settlement_division == $this->app['config']['MdlPaygent']['const']['SETTLEMENT_LINK']) {
			$formData['connect_id'] = null;
			$formData['connect_password'] = null;
			$formData['payment'] = array();
			$formData['payment_division'] = array();
			$formData['security_code'] = 0;
			$formData['credit_3d'] = 0;
			$formData['stock_card'] = 0;
			$formData['conveni_limit_date_num'] = "15";
			$formData['atm_limit_date'] = "30";
			$formData['payment_detail'] = null;
			$formData['asp_payment_term'] = "7";
			$formData['claim_kanji'] = null;
			$formData['claim_kana'] = null;
			$formData['copy_right'] = null;
			$formData['free_memo'] = null;
			$formData['career_division'] = array();
			$formData['emoney_division'] = array();
			$formData['numbering_type'] = 0;
			$formData['virtual_account_limit_date'] = "30";
			$formData['result_get_type'] = 0;
			$formData['exam_result_notification_type'] = 0;
			$formData['token_pay'] = 0;
			$formData['token_env'] = 0;
			$formData['token_key'] = null;
			$formData['invoice_include'] = null;
			$formData['numbering_type'] = 0;
			$formData['virtual_account_limit_date'] = null;

		} else if ($settlement_division == $this->app['config']['MdlPaygent']['const']['SETTLEMENT_MIX']) {
			$formData['payment'] = array();
			$formData['payment_division'] = array();
			$formData['security_code'] = 0;
			$formData['credit_3d'] = 0;
			$formData['stock_card'] = 0;
			$formData['conveni_limit_date_num'] = "15";
			$formData['atm_limit_date'] = "30";
			$formData['payment_detail'] = null;
			$formData['asp_payment_term'] = "7";
			$formData['claim_kanji'] = null;
			$formData['claim_kana'] = null;
			$formData['copy_right'] = null;
			$formData['free_memo'] = null;
			$formData['career_division'] = array();
			$formData['emoney_division'] = array();
			$formData['numbering_type'] = 0;
			$formData['virtual_account_limit_date'] = "30";
			$formData['result_get_type'] = 0;
			$formData['exam_result_notification_type'] = 0;
			$formData['token_pay'] = 0;
			$formData['token_env'] = 0;
			$formData['token_key'] = null;
			$formData['invoice_include'] = null;
			$formData['numbering_type'] = 0;
			$formData['virtual_account_limit_date'] = null;
		}
		return $formData;
    }


	function updateDelFlag($listId, $installedPayment)
	{
		// チェックされていない決済を削除
		if (!empty($listId)) {
			foreach ((array) $listId as $paymentId) {
				if (!in_array($paymentId["id"], $installedPayment)) {
					$removeMdlPaymentMethod = $this->app['eccube.plugin.mdl_paygent.repository.mdl_payment_method']->find($paymentId["id"]);
					if (!empty($removeMdlPaymentMethod)) {
						$removeMdlPaymentMethod->setDelFlg(1);
						$this->app['orm.em']->persist($removeMdlPaymentMethod);
					}
					$removePayment = $this->app['eccube.repository.payment']->find($paymentId["id"]);
					if (!empty($removePayment)) {
						$removePayment->setDelFlg(1);
						$this->app['orm.em']->persist($removePayment);
					}
					$this->app['orm.em']->flush();
				}
			}
		}
	}
}
