<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
class ConfigType extends AbstractType
{
    private $app;
    private $subData;
    private $connectType;

    public function __construct(\Eccube\Application $app, $subData = null, $connectType = null)
    {
        $this->app = $app;
        $this->subData = $subData;
        $this->connectType = $connectType;
    }

    /**
     * Build config type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $objUtil = $this->app['eccube.plugin.service.payment'];
        if (empty($this->subData)) {
            $this->subData = $this->initValue();
        }
        $arrPayments = $objUtil->getPaymentTypeNames();

        $arrPaymentDivisions = $objUtil->getPaymentDivisions();

        $arrCareerDivisions = $objUtil->getCareerDivisions();

        $arrPaymentStatus = $objUtil->getStatusCheckPayment();

        if (isset($this->connectType) && $this->connectType == "1") {
        	$builder = $this->getFormLinkType($builder, $arrPayments, $arrPaymentDivisions, $arrCareerDivisions, $objUtil);
        } else if (isset($this->connectType) && $this->connectType == "3") {
			$builder = $this->getFormMixedType($builder, $arrPayments, $arrPaymentDivisions, $arrCareerDivisions, $objUtil);
        } else {
        	$builder = $this->getFormModuleType($builder, $arrPayments, $arrPaymentDivisions, $arrCareerDivisions, $arrPaymentStatus, $objUtil);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'config';
    }

    public function initValue()
    {
    	return array(
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
    }

    public function getFormLinkType(FormBuilderInterface $builder, $arrPayments,
    		$arrPaymentDivisions, $arrCareerDivisions, $objUtil)
    {
    	$builder
        	->add('settlement_division', 'choice', array(
        			'label' => 'システム種別',
        			'choices' => array(2 => 'モジュール型', 1 => 'リンク型', 3 => '混合型'),
        			'expanded' => true,
        			'data' => $this->subData['settlement_division'],
        			'constraints' => array(
        					new Assert\NotBlank(),
        			),
        	));
        	$builder->add('merchant_id', 'text', array(
        			'label' => 'マーチャントID',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '9',
        			),
        			'constraints' => array(
        					new Assert\NotBlank(array('message' => '※ マーチャントIDが入力されていません。')),
        					new Assert\Regex(array(
        							'pattern' => "/^[0-9]+$/",
        							'message' => '※ マーチャントIDは数字で入力してください。'
        					)),
        			),
        			'data' => $this->subData['merchant_id'],
        	));
        	$builder->add('connect_id', 'text', array(
        			'label' => '接続ID',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '32',
        			),
        			'data' => $this->subData['connect_id'],
        	));
        	$builder->add('connect_password', 'password', array(
        			'label' => '接続パスワード',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '32',
        			),
        			'data' => $this->subData['connect_password'],
        	));
        	$builder->add('payment', 'choice', array(
        			'label' => '利用決済',
        			'choices' => $arrPayments,
        			'expanded' => true,
        			'multiple' => true,
        			'data' => $this->subData['payment'],
        	));
        	$builder->add('payment_division', 'choice', array(
        			'label' => '支払回数',
        			'choices' => $arrPaymentDivisions,
        			'expanded' => true,
        			'multiple' => true,
        			'data' => $this->subData['payment_division'],
        	));
        	$builder->add('security_code', 'choice', array(
        			'label' => 'セキュリティコード',
        			'choices' => array(1 => '要', 0 => '不要'),
        			'expanded' => true,
        			'data' => $this->subData['security_code'],
        	));
        	$builder->add('credit_3d', 'choice', array(
        			'label' => '3Dセキュア',
        			'choices' => array(1 => '要', 0 => '不要'),
        			'expanded' => true,
        			'data' => $this->subData['credit_3d'],
        	));
        	$builder->add('stock_card', 'choice', array(
        			'label' => 'カード情報お預かり機能',
        			'choices' => array(1 => '要', 0 => '不要'),
        			'expanded' => true,
        			'data' => $this->subData['stock_card'],
        	));
        	$builder->add('token_pay', 'choice', array(
        			'label' => 'トークン決済',
        			'choices' => array(1 => '要', 0 => '不要'),
        			'expanded' => true,
        			'data' => $this->subData['token_pay'],
        	));
        	$builder->add('token_env', 'choice', array(
        			'label' => 'トークン接続先',
        			'choices' => $objUtil->getTokenEnv(),
        			'expanded' => true,
        			'data' => $this->subData['token_env'],
        	));
        	$builder->add('token_key', 'text', array(
        			'label' => 'トークン生成鍵',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '100',
        			),
        			'data' => $this->subData['token_key'],
        	));
        	$builder->add('conveni_limit_date_num', 'text', array(
        			'label' => '支払期限日',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '2',
        					'style' => 'width:50px',
        			),
        			'data' => $this->subData['conveni_limit_date_num'],
        	));
        	$builder->add('atm_limit_date', 'text', array(
        			'label' => '支払期限日',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '2',
        					'style' => 'width:50px',
        			),
        			'data' => $this->subData['atm_limit_date'],
        	));
        	$builder->add('payment_detail', 'text', array(
        			'label' => '店舗名（カナ）',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '12',
        			),
        			'data' => $this->subData['payment_detail'],
        	));
        	$builder->add('asp_payment_term', 'text', array(
        			'label' => '支払期限日',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '2',
        					'style' => 'width:50px',
        			),
        			'data' => $this->subData['asp_payment_term'],
        	));
        	$builder->add('claim_kanji', 'text', array(
        			'label' => '店舗名（全角）',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '12',
        			),
        			'data' => $this->subData['claim_kanji'],
        	));
        	$builder->add('claim_kana', 'text', array(
        			'label' => '店舗名（カナ）',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '12',
        			),
        			'data' => $this->subData['claim_kana'],
        	));
        	$builder->add('copy_right', 'text', array(
        			'label' => 'コピーライト(半角英数)',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '32',
        			),
        			'data' => $this->subData['copy_right'],
        	));
        	$builder->add('free_memo', 'text', array(
        			'label' => '自由メモ欄(全角)',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '128',
        			),
        			'data' => $this->subData['free_memo'],
        	));
        	$builder->add('career_division', 'choice', array(
        			'label' => '利用決済',
        			'choices' => $arrCareerDivisions,
        			'expanded' => true,
        			'multiple' => true,
        			'data' => $this->subData['career_division'],
        	));
        	$builder->add('numbering_type', 'choice', array(
        			'label' => '結果取得区分',
        			'choices' => $objUtil->getNumberingType(),
        			'expanded' => true,
        			'data' => $this->subData['numbering_type'],
        	));
        	$builder->add('virtual_account_limit_date', 'text', array(
        			'label' => '支払期限日',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '3',
        					'style' => 'width:55px',
        			),
        			'data' => $this->subData['virtual_account_limit_date'],
        	));
        	$builder->add('result_get_type', 'choice', array(
        			'label' => '結果取得区分',
        			'choices' => array(0 => '審査結果を待つ', 1 => '審査結果を後で取得する'),
        			'expanded' => true,
        			'data' => $this->subData['result_get_type'],
        	));
        	$builder->add('exam_result_notification_type', 'choice', array(
        			'label' => '審査結果通知メール',
        			'choices' => array(0 => '自動で送信する', 1 => '自動で送信しない'),
        			'expanded' => true,
        			'data' => $this->subData['exam_result_notification_type'],
        	));
        	$builder->add('invoice_include', 'choice', array(
        			'label' => '請求書の同梱',
        			'choices' => $objUtil->getInvoiceIncludeOption(),
        			'expanded' => true,
        			'multiple' => true,
        			'data' => $this->subData['invoice_include'],
        	));
        	$builder->add('link_url', 'text', array(
        			'label' => 'リンクタイプリクエスト先URL',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => $this->app['config']['url_len'],
        			),
        			'data' => $this->subData['link_url'],
        			'constraints' => array(
        					new Assert\NotBlank(array('message' => '※ リクエスト先URLが入力されていません。')),
        					new Assert\Url(array('message' => '※ リクエスト先URLを正しく入力してください。')),
        			),
        	));
        	$builder->add('hash_key', 'text', array(
        			'label' => 'ハッシュ値生成キー',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '84',
        			),
        			'data' => $this->subData['hash_key'],
        	));
        	$builder->add('card_class', 'choice', array(
        			'label' => 'カード支払区分',
        			'choices' => array(0 => '1回払いのみ', 1 => '全て', 2 => 'ボーナス一括以外全て'),
        			'expanded' => true,
        			'data' => $this->subData['card_class'],
        	));
        	$builder->add('card_conf', 'choice', array(
        			'label' => 'カード確認番号',
        			'choices' => array(1 => '要', 0 => '不要'),
        			'expanded' => true,
        			'data' => $this->subData['card_conf'],
        	));
        	$builder->add('link_payment_term', 'text', array(
        			'label' => '支払期限日',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '2',
        					'style' => 'width:50px',
        			),
        			'constraints' => array(
        					new Assert\NotBlank(array('message' => '※ 支払期限日は2～60日で設定してください。')),
        					new Assert\GreaterThanOrEqual(array(
        							'value'=>'2',
        							'message' => '※ 支払期限日は2～60日で設定してください。',
        					)),
        					new Assert\LessThanOrEqual(array(
        							'value'=>'60',
        							'message' => '※ 支払期限日は2～60日で設定してください。',
        					)),
        			),
        			'data' => $this->subData['link_payment_term'],
        	));
        	$builder->add('merchant_name', 'text', array(
        			'label' => '店舗名(全角)',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '32',
        			),
        			'data' => $this->subData['merchant_name'],
        	));
        	$builder->add('link_copy_right', 'text', array(
        			'label' => 'コピーライト(半角英数)',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '128',
        			),
        			'data' => $this->subData['link_copy_right'],
        			'constraints' => array(
        					new Assert\Regex(array(
        							'pattern' => "/^[a-zA-Z0-9]+[ \t\r\n\v\f]*\(?[A-z0-9]*\)?[\w\d\s.\\\]*$/",
        							'message' => '※ 決済ページ用コピーライトは英数字で入力してください。'
        					)),
        			),
        	));
        	$builder->add('link_free_memo', 'text', array(
        			'label' => '自由メモ欄(全角)',
        			'attr' => array(
        					'class' => 'lockon_card_row',
        					'maxlength' => '128',
        			),
        			'data' => $this->subData['link_free_memo'],
        	));
        	return $builder;
    }

    public function getFormMixedType(FormBuilderInterface $builder, $arrPayments,
    		$arrPaymentDivisions, $arrCareerDivisions, $objUtil)
    {
    	$builder
    	->add('settlement_division', 'choice', array(
    			'label' => 'システム種別',
    			'choices' => array(2 => 'モジュール型', 1 => 'リンク型', 3 => '混合型'),
    			'expanded' => true,
    			'data' => $this->subData['settlement_division'],
    			'constraints' => array(
    					new Assert\NotBlank(),
    			),
    	));
    	$builder->add('merchant_id', 'text', array(
    			'label' => 'マーチャントID',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '9',
    			),
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ マーチャントIDが入力されていません。')),
    					new Assert\Regex(array(
    							'pattern' => "/^[0-9]+$/",
    							'message' => '※ マーチャントIDは数字で入力してください。'
    					)),
    			),
    			'data' => $this->subData['merchant_id'],
    	));
    	$builder->add('connect_id', 'text', array(
    			'label' => '接続ID',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '32',
    			),
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ 接続IDが入力されていません。')),
    					new Assert\Regex(array(
    							'pattern' => "/^[A-z0-9]+$/",
    							'message' => '※ 接続IDは英数字で入力してください。'
    					)),
    			),
    			'data' => $this->subData['connect_id'],
    	));
    	$builder->add('connect_password', 'password', array(
    			'label' => '接続パスワード',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '32',
    			),
    			'data' => $this->subData['connect_password'],
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ 接続パスワードが入力されていません。')),
    					new Assert\Regex(array(
    							'pattern' => "/^[A-z0-9]+$/",
    							'message' => '※ 接続パスワードは英数字で入力してください。'
    					)),
    			),
    	));
    	$builder->add('payment', 'choice', array(
    			'label' => '利用決済',
    			'choices' => $arrPayments,
    			'expanded' => true,
    			'multiple' => true,
    			'data' => $this->subData['payment'],
    	));
    	$builder->add('payment_division', 'choice', array(
    			'label' => '支払回数',
    			'choices' => $arrPaymentDivisions,
    			'expanded' => true,
    			'multiple' => true,
    			'data' => $this->subData['payment_division'],
    	));
    	$builder->add('security_code', 'choice', array(
    			'label' => 'セキュリティコード',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['security_code'],
    	));
    	$builder->add('credit_3d', 'choice', array(
    			'label' => '3Dセキュア',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['credit_3d'],
    	));
    	$builder->add('stock_card', 'choice', array(
    			'label' => 'カード情報お預かり機能',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['stock_card'],
    	));
    	$builder->add('token_pay', 'choice', array(
    			'label' => 'トークン決済',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['token_pay'],
    	));
    	$builder->add('token_env', 'choice', array(
    			'label' => 'トークン接続先',
    			'choices' => $objUtil->getTokenEnv(),
    			'expanded' => true,
    			'data' => $this->subData['token_env'],
    	));
    	$builder->add('token_key', 'text', array(
    			'label' => 'トークン生成鍵',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '100',
    			),
    			'data' => $this->subData['token_key'],
    	));
    	$builder->add('conveni_limit_date_num', 'text', array(
    			'label' => '支払期限日',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '2',
    					'style' => 'width:50px',

    			),
    			'data' => $this->subData['conveni_limit_date_num'],
    	));
    	$builder->add('atm_limit_date', 'text', array(
    			'label' => '支払期限日',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '2',
    					'style' => 'width:50px',
    			),
    			'data' => $this->subData['atm_limit_date'],
    	));
    	$builder->add('payment_detail', 'text', array(
    			'label' => '店舗名（カナ）',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '12',
    			),
    			'data' => $this->subData['payment_detail'],
    	));
    	$builder->add('asp_payment_term', 'text', array(
    			'label' => '支払期限日',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '2',
    					'style' => 'width:50px',
    			),
    			'data' => $this->subData['asp_payment_term'],
    	));
    	$builder->add('claim_kanji', 'text', array(
    			'label' => '店舗名（全角）',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '12',
    			),
    			'data' => $this->subData['claim_kanji'],
    	));
    	$builder->add('claim_kana', 'text', array(
    			'label' => '店舗名（カナ）',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '12',
    			),
    			'data' => $this->subData['claim_kana'],
    	));
    	$builder->add('copy_right', 'text', array(
    			'label' => 'コピーライト(半角英数)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '32',
    			),
    			'data' => $this->subData['copy_right'],
    	));
    	$builder->add('free_memo', 'text', array(
    			'label' => '自由メモ欄(全角)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '128',
    			),
    			'data' => $this->subData['free_memo'],
    	));
    	$builder->add('career_division', 'choice', array(
    			'label' => '利用決済',
    			'choices' => $arrCareerDivisions,
    			'expanded' => true,
    			'multiple' => true,
    			'data' => $this->subData['career_division'],
    	));
    	$builder->add('numbering_type', 'choice', array(
    			'label' => '結果取得区分',
    			'choices' => $objUtil->getNumberingType(),
    			'expanded' => true,
    			'data' => $this->subData['numbering_type'],
    	));
    	$builder->add('virtual_account_limit_date', 'text', array(
    			'label' => '支払期限日',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '3',
    					'style' => 'width:55px',
    			),
    			'data' => $this->subData['virtual_account_limit_date'],
    	));
    	$builder->add('result_get_type', 'choice', array(
    			'label' => '結果取得区分',
    			'choices' => array(0 => '審査結果を待つ', 1 => '審査結果を後で取得する'),
    			'expanded' => true,
    			'data' => $this->subData['result_get_type'],
    	));
    	$builder->add('exam_result_notification_type', 'choice', array(
    			'label' => '審査結果通知メール',
    			'choices' => array(0 => '自動で送信する', 1 => '自動で送信しない'),
    			'expanded' => true,
    			'data' => $this->subData['exam_result_notification_type'],
    	));
    	$builder->add('invoice_include', 'choice', array(
    			'label' => '請求書の同梱',
    			'choices' => $objUtil->getInvoiceIncludeOption(),
    			'expanded' => true,
    			'multiple' => true,
    			'data' => $this->subData['invoice_include'],
    	));
    	$builder->add('link_url', 'text', array(
    			'label' => 'リンクタイプリクエスト先URL',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => $this->app['config']['url_len'],
    			),
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ リクエスト先URLが入力されていません。')),
    					new Assert\Url(array('message' => '※ リクエスト先URLを正しく入力してください。')),
    			),
    			'data' => $this->subData['link_url'],
    	));
    	$builder->add('hash_key', 'text', array(
    			'label' => 'ハッシュ値生成キー',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '84',
    			),
    			'data' => $this->subData['hash_key'],
    	));
    	$builder->add('card_class', 'choice', array(
    			'label' => 'カード支払区分',
    			'choices' => array(0 => '1回払いのみ', 1 => '全て', 2 => 'ボーナス一括以外全て'),
    			'expanded' => true,
    			'data' => $this->subData['card_class'],
    	));
    	$builder->add('card_conf', 'choice', array(
    			'label' => 'カード確認番号',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['card_conf'],
    	));
    	$builder->add('link_payment_term', 'text', array(
    			'label' => '支払期限日',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '2',
    					'style' => 'width:50px',
    			),
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ 支払期限日は2～60日で設定してください。')),
    					new Assert\GreaterThanOrEqual(array(
    							'value'=>'2',
    							'message' => '※ 支払期限日は2～60日で設定してください。',
    					)),
    					new Assert\LessThanOrEqual(array(
    							'value'=>'60',
    							'message' => '※ 支払期限日は2～60日で設定してください。',
    					)),
    			),
    			'data' => $this->subData['link_payment_term'],
    	));
    	$builder->add('merchant_name', 'text', array(
    			'label' => '店舗名(全角)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '32',
    			),
    			'data' => $this->subData['merchant_name'],
    	));
    	$builder->add('link_copy_right', 'text', array(
    			'label' => 'コピーライト(半角英数)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '128',
    			),
    			'data' => $this->subData['link_copy_right'],
    			'constraints' => array(
    					new Assert\Regex(array(
    							'pattern' => "/^[a-zA-Z0-9]+[ \t\r\n\v\f]*\(?[A-z0-9]*\)?[\w\d\s.\\\]*$/",
    							'message' => '※ 決済ページ用コピーライトは英数字で入力してください。'
    					)),
    			),
    	));
    	$builder->add('link_free_memo', 'text', array(
    			'label' => '自由メモ欄(全角)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '128',
    			),
    			'data' => $this->subData['link_free_memo'],
    	));
    	return $builder;
    }

    public function getFormModuleType(FormBuilderInterface $builder, $arrPayments,
    		$arrPaymentDivisions, $arrCareerDivisions, $arrPaymentStatus, $objUtil)
    {
    	$builder
    	->add('settlement_division', 'choice', array(
    			'label' => 'システム種別',
    			'choices' => array(2 => 'モジュール型', 1 => 'リンク型', 3 => '混合型'),
    			'expanded' => true,
    			'data' => $this->subData['settlement_division'],
    			'constraints' => array(
    					new Assert\NotBlank(),
    			),
    	));
    	$builder->add('merchant_id', 'text', array(
    			'label' => 'マーチャントID',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '9',
    			),
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ マーチャントIDが入力されていません。')),
    					new Assert\Regex(array(
    							'pattern' => "/^[0-9]+$/",
    							'message' => '※ マーチャントIDは数字で入力してください。'
    					)),
    			),
    			'data' => $this->subData['merchant_id'],
    	));
    	$builder->add('connect_id', 'text', array(
    			'label' => '接続ID',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '32',
    			),
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ 接続IDが入力されていません。')),
    					new Assert\Regex(array(
    							'pattern' => "/^[A-z0-9]+$/",
    							'message' => '※ 接続IDは英数字で入力してください。'
    					)),
    			),
    			'data' => $this->subData['connect_id'],
    	));
    	$builder->add('connect_password', 'password', array(
    			'label' => '接続パスワード',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '32',
    			),
    			'data' => $this->subData['connect_password'],
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ 接続パスワードが入力されていません。')),
    					new Assert\Regex(array(
    							'pattern' => "/^[A-z0-9]+$/",
    							'message' => '※ 接続パスワードは英数字で入力してください。'
    					)),
    			),
    	));
    	$builder->add('payment', 'choice', array(
    			'label' => '利用決済',
    			'choices' => $arrPayments,
    			'expanded' => true,
    			'multiple' => true,
    			'data' => $this->subData['payment'],
    			'constraints' => array(
    					new Assert\NotBlank(array('message' => '※ 利用決済が入力されていません。')),
    			),
    	));
    	if (isset($arrPaymentStatus["credit"]) && $arrPaymentStatus["credit"] == "1") {
    		$builder->add('payment_division', 'choice', array(
    				'label' => '支払回数',
    				'choices' => $arrPaymentDivisions,
    				'expanded' => true,
    				'multiple' => true,
    				'data' => $this->subData['payment_division'],
    				'constraints' => array(
    						new Assert\NotBlank(array('message' => '※ 支払回数が入力されていません。')),
    				),
    		));
    	} else {
	    	$builder->add('payment_division', 'choice', array(
	    			'label' => '支払回数',
	    			'choices' => $arrPaymentDivisions,
	    			'expanded' => true,
	    			'multiple' => true,
	    			'data' => $this->subData['payment_division'],
	    	));
    	}
    	$builder->add('security_code', 'choice', array(
    			'label' => 'セキュリティコード',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['security_code'],
    	));
    	$builder->add('credit_3d', 'choice', array(
    			'label' => '3Dセキュア',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['credit_3d'],
    	));
    	$builder->add('stock_card', 'choice', array(
    			'label' => 'カード情報お預かり機能',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['stock_card'],
    	));
    	$builder->add('token_pay', 'choice', array(
    			'label' => 'トークン決済',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['token_pay'],
    			'invalid_message' => "※ トークン決済は数字で入力してください。",
    	));
    	$builder->add('token_env', 'choice', array(
    			'label' => 'トークン接続先',
    			'choices' => $objUtil->getTokenEnv(),
    			'expanded' => true,
    			'data' => $this->subData['token_env'],
    			'invalid_message' => "※ トークン接続先は数字で入力してください。",
    	));
    	if (isset($arrPaymentStatus["tokenPay"]) && $arrPaymentStatus["tokenPay"] == "1") {
    		$builder->add('token_key', 'text', array(
    			'label' => 'トークン生成鍵',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '100',
    			),
    			'data' => $this->subData['token_key'],
    			'constraints' => array(
    						new Assert\Length(array(
					            'max'        => 100,
					            'maxMessage' => '※ トークン生成鍵は100字以下で入力してください。',
					        )),
    						new Assert\NotBlank(array('message' => '※ トークン生成鍵が入力されていません。')),
	    					new Assert\Regex(array(
	    							'pattern' => "/^[[:graph:][:space:]]+$/i",
	    							'message' => '※ トークン生成鍵は英数記号で入力してください。'
	    					)),
    			)
    		));
    	} else {
    		$builder->add('token_key', 'text', array(
    				'label' => 'トークン生成鍵',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '100',
    				),
    				'data' => $this->subData['token_key'],
    				'constraints' => array(
    						new Assert\Length(array(
    								'max'        => 100,
    								'maxMessage' => '※ トークン生成鍵は100字以下で入力してください。',
    						)),
    						new Assert\Regex(array(
    								'pattern' => "/^[[:graph:][:space:]]+$/i",
    								'message' => '※ トークン生成鍵は英数記号で入力してください。'
    						)),
    				)
    		));
    	}

    	if (isset($arrPaymentStatus["convenienceStore"]) && $arrPaymentStatus["convenienceStore"] == "1") {
    		$builder->add('conveni_limit_date_num', 'text', array(
    				'label' => '支払期限日',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '2',
    						'style' => 'width:50px',
    				),
    				'data' => $this->subData['conveni_limit_date_num'],
    				'constraints' => array(
    						new Assert\NotBlank(array('message' => '※ 支払期限日は1～60日で設定してください。')),
    						new Assert\GreaterThanOrEqual(array(
    								'value'=>'1',
    								'message' => '※ 支払期限日は1～60日で設定してください。',
    						)),
    						new Assert\LessThanOrEqual(array(
    								'value'=>'60',
    								'message' => '※ 支払期限日は1～60日で設定してください。',
    						)),
    				),
    		));
    	} else {
	    	$builder->add('conveni_limit_date_num', 'text', array(
	    			'label' => '支払期限日',
	    			'attr' => array(
	    					'class' => 'lockon_card_row',
	    					'maxlength' => '2',
	    					'style' => 'width:50px',
	    			),
	    			'data' => $this->subData['conveni_limit_date_num'],
	    	));
    	}
    	if (isset($arrPaymentStatus["atm"]) && $arrPaymentStatus["atm"] == "1") {
	    	$builder->add('atm_limit_date', 'text', array(
	    			'label' => '支払期限日',
	    			'attr' => array(
	    					'class' => 'lockon_card_row',
	    					'maxlength' => '2',
	    					'style' => 'width:50px',
	    			),
	    			'data' => $this->subData['atm_limit_date'],
	    			'constraints' => array(
	    					new Assert\NotBlank(array('message' => '※ 支払期限日は0～60日で設定してください。')),
	    					new Assert\GreaterThanOrEqual(array(
	    							'value'=>'0',
	    							'message' => '※ 支払期限日は0～60日で設定してください。',
	    					)),
	    					new Assert\LessThanOrEqual(array(
	    							'value'=>'60',
	    							'message' => '※ 支払期限日は0～60日で設定してください。',
	    					)),
	    			),
	    	));
	    	$builder->add('payment_detail', 'text', array(
	    			'label' => '店舗名（カナ）',
	    			'attr' => array(
	    					'class' => 'lockon_card_row',
	    					'maxlength' => '12',
	    			),
	    			'constraints' => array(
	    					new Assert\NotBlank(array('message' => '※ 店舗名(カナ)が入力されていません。')),
	    					new Assert\Regex(array(
	    							'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
	    							'message' => '※ 店舗名（カナ）はカタカナで入力してください。'
	    					)),
	    			),
	    			'data' => $this->subData['payment_detail'],
	    	));
    	} else {
    		$builder->add('atm_limit_date', 'text', array(
    				'label' => '支払期限日',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '2',
    						'style' => 'width:50px',
    				),
    				'data' => $this->subData['atm_limit_date'],
    		));
    		$builder->add('payment_detail', 'text', array(
    				'label' => '店舗名（カナ）',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '12',
    				),
    				'data' => $this->subData['payment_detail'],
    		));
    	}
    	if (isset($arrPaymentStatus["bankNet"]) && $arrPaymentStatus["bankNet"] == "1") {
    		$builder->add('asp_payment_term', 'text', array(
    				'label' => '支払期限日',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '2',
    						'style' => 'width:50px',
    				),
    				'data' => $this->subData['asp_payment_term'],
    				'constraints' => array(
    						new Assert\NotBlank(array('message' => '※ 支払期限日は1～99日で設定してください。')),
    						new Assert\GreaterThanOrEqual(array(
    								'value'=>'1',
    								'message' => '※ 支払期限日は1～99日で設定してください。',
    						)),
    						new Assert\LessThanOrEqual(array(
    								'value'=>'99',
    								'message' => '※ 支払期限日は1～99日で設定してください。',
    						)),
    				),
    		));
    		$builder->add('claim_kanji', 'text', array(
    				'label' => '店舗名（全角）',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '12',
    				),
    				'data' => $this->subData['claim_kanji'],
    				'constraints' => array(
    						new Assert\NotBlank(array('message' => '※ 店舗名が入力されていません。')),
    				),
    		));
    		$builder->add('claim_kana', 'text', array(
    				'label' => '店舗名（カナ）',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '12',
    				),
    				'constraints' => array(
    						new Assert\NotBlank(array('message' => '※ 店舗名(カナ)が入力されていません。')),
    						new Assert\Regex(array(
    								'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
    								'message' => '※ 店舗名（カナ）はカタカナで入力してください。'
    						)),
    				),
    				'data' => $this->subData['claim_kana'],
    		));
    	} else {
    		$builder->add('asp_payment_term', 'text', array(
    				'label' => '支払期限日',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '2',
    						'style' => 'width:50px',
    				),
    				'data' => $this->subData['asp_payment_term'],
    		));
    		$builder->add('claim_kanji', 'text', array(
    				'label' => '店舗名（全角）',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '12',
    				),
    				'data' => $this->subData['claim_kanji'],
    		));
    		$builder->add('claim_kana', 'text', array(
    				'label' => '店舗名（カナ）',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '12',
    				),
    				'data' => $this->subData['claim_kana'],
    		));
    	}
		$builder->add('copy_right', 'text', array(
				'label' => 'コピーライト(半角英数)',
				'attr' => array(
						'class' => 'lockon_card_row',
						'maxlength' => '32',
				),
				'data' => $this->subData['copy_right'],
				'constraints' => array(
						new Assert\Regex(array(
								'pattern' => "/^[a-zA-Z0-9]+[ \t\r\n\v\f]*\(?[A-z0-9]*\)?[\w\d\s.\\\]*$/",
								'message' => '※ 決済ページ用コピーライトは英数字で入力してください。'
						)),
				),
		));
    	$builder->add('free_memo', 'text', array(
    			'label' => '自由メモ欄(全角)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '128',
    			),
    			'data' => $this->subData['free_memo'],
    	));
    	if (isset($arrPaymentStatus["mobileCarries"]) && $arrPaymentStatus["mobileCarries"] == "1") {
    		$builder->add('career_division', 'choice', array(
    				'label' => '利用決済',
    				'choices' => $arrCareerDivisions,
    				'expanded' => true,
    				'multiple' => true,
    				'data' => $this->subData['career_division'],
    				'constraints' => array(
    						new Assert\NotBlank(array('message' => '※ 利用決済が入力されていません。')),
    				),
    		));
    	} else {
    		$builder->add('career_division', 'choice', array(
    				'label' => '利用決済',
    				'choices' => $arrCareerDivisions,
    				'expanded' => true,
    				'multiple' => true,
    				'data' => $this->subData['career_division'],
    		));
    	}

    	$builder->add('numbering_type', 'choice', array(
    			'label' => '結果取得区分',
    			'choices' => $objUtil->getNumberingType(),
    			'expanded' => true,
    			'data' => $this->subData['numbering_type'],
    	));
    	if (isset($arrPaymentStatus["virtualAcc"]) && $arrPaymentStatus["virtualAcc"] == "1") {
    		$builder->add('virtual_account_limit_date', 'number', array(
    				'label' => '支払期限日',
    				'attr' => array(
    						'class' => 'lockon_card_row',
    						'maxlength' => '3',
    						'style' => 'width:55px',
    				),
    				'data' => $this->subData['virtual_account_limit_date'],
    				'invalid_message' => "※ 支払期限日は数字で入力してください。",
    				'constraints' => array(
    						new Assert\NotBlank(array('message' => '※ 支払期限日は0～364日で設定してください。')),
    						new Assert\GreaterThanOrEqual(array(
    								'value'=>'0',
    								'message' => '※ 支払期限日は0～364日で設定してください。',
    						)),
    						new Assert\LessThanOrEqual(array(
    								'value'=>'364',
    								'message' => '※ 支払期限日は0～364日で設定してください。',
    						)),
    				),
    		));
    	} else {
	    	$builder->add('virtual_account_limit_date', 'text', array(
	    			'label' => '支払期限日',
	    			'attr' => array(
	    					'class' => 'lockon_card_row',
	    					'maxlength' => '3',
	    					'style' => 'width:55px',
	    			),
	    			'data' => $this->subData['virtual_account_limit_date'],
	    	));
    	}

    	$builder->add('result_get_type', 'choice', array(
    			'label' => '結果取得区分',
    			'choices' => array(0 => '審査結果を待つ', 1 => '審査結果を後で取得する'),
    			'expanded' => true,
    			'data' => $this->subData['result_get_type'],
    	));
    	$builder->add('exam_result_notification_type', 'choice', array(
    			'label' => '審査結果通知メール',
    			'choices' => array(0 => '自動で送信する', 1 => '自動で送信しない'),
    			'expanded' => true,
    			'data' => $this->subData['exam_result_notification_type'],
    	));
    	$builder->add('invoice_include', 'choice', array(
    			'label' => '請求書の同梱',
    			'choices' => $objUtil->getInvoiceIncludeOption(),
    			'expanded' => true,
    			'multiple' => true,
    			'data' => $this->subData['invoice_include'],
    	));
    	$builder->add('link_url', 'text', array(
    			'label' => 'リンクタイプリクエスト先URL',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => $this->app['config']['url_len'],
    			),
    			'data' => $this->subData['link_url'],
    	));
    	$builder->add('hash_key', 'text', array(
    			'label' => 'ハッシュ値生成キー',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '84',
    			),
    			'data' => $this->subData['hash_key'],
    	));
    	$builder->add('card_class', 'choice', array(
    			'label' => 'カード支払区分',
    			'choices' => array(0 => '1回払いのみ', 1 => '全て', 2 => 'ボーナス一括以外全て'),
    			'expanded' => true,
    			'data' => $this->subData['card_class'],
    	));
    	$builder->add('card_conf', 'choice', array(
    			'label' => 'カード確認番号',
    			'choices' => array(1 => '要', 0 => '不要'),
    			'expanded' => true,
    			'data' => $this->subData['card_conf'],
    	));
    	$builder->add('link_payment_term', 'text', array(
    			'label' => '支払期限日',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '2',
    					'style' => 'width:50px',
    			),
    			'data' => $this->subData['link_payment_term'],
    	));
    	$builder->add('merchant_name', 'text', array(
    			'label' => '店舗名(全角)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '32',
    			),
    			'data' => $this->subData['merchant_name'],
    	));
    	$builder->add('link_copy_right', 'text', array(
    			'label' => 'コピーライト(半角英数)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '128',
    			),
    			'data' => $this->subData['link_copy_right'],
    	));
    	$builder->add('link_free_memo', 'text', array(
    			'label' => '自由メモ欄(全角)',
    			'attr' => array(
    					'class' => 'lockon_card_row',
    					'maxlength' => '128',
    			),
    			'data' => $this->subData['link_free_memo'],
    	));

    	return $builder;
    }
}
