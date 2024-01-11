<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;

class CreditType extends AbstractType {

    private $app;
    private $subData;
    private $arrPaymentClass;
    private $stock;
    private $token_pay;
    private $security_code;

    public function __construct(\Eccube\Application $app, $subData = null, $stock = null,$token_pay = null, $security_code = null) {
        $this->app = $app;
        $this->subData = $subData;
        $this->arrPaymentClass = $subData['arrPaymentClass'];
        $this->stock = $stock;
        $this->security_code = $security_code;
        $this->token_pay = $token_pay;
    }

    /**
     * Build payment type form
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return type
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {
    	$builder->add ( 'mode', 'hidden')
    	->add ( 'deletecard', 'hidden')
    	->add ( 'stockFlag', 'hidden')
    	->add ( 'card_token_stock', 'hidden')
    	->add ( 'card_token', 'hidden')
    	->add ( 'cardSeq', 'radio');

    	if((isset($this->stock) && $this->stock == 1) || (isset($this->token_pay) && $this->token_pay == 1)){
    		$builder = $this->getFormStockChecked($builder, $this->stock);
    	}else{
    		$builder = $this->getFromStockNotChecked($builder);
    	}
	}
	// When stock not check
	public function getFromStockNotChecked(FormBuilderInterface $builder) {
		$year = $this->getZeroYear ( date ( 'Y' ), date ( 'Y' ) + 15 );
		$month = $this->getZeroMonth ();
		$this->app ['request']->request->all ();
		$Order = $this->app ['eccube.repository.order']->findOneBy ( array (
				'pre_order_id' => $this->app ['eccube.service.cart']->getPreOrderId ()
		) );
		if (is_null ( $Order ))
			return;

		$objUtil = $this->app ['eccube.plugin.service.payment'];
		$Payment = $Order->getPayment ();
		if (! is_null ( $Payment )) {
			$paymentInfo = $objUtil->getPaymentTypeConfig ( $Payment->getId () )->getArrPaymentConfig ();
		}
		$builder->add ( 'card_no01', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6',
				),
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ カード番号1が入力されていません。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 4,
								'maxMessage' => "※ カード番号1は4字以下で入力してください。"
						) )
				)
		) )->add ( 'card_no02', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6'
				),
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ カード番号2が入力されていません。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 4,
								'maxMessage' => "※ カード番号2は4字以下で入力してください。"
						) )
				)
		) )->add ( 'card_no03', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6'
				),
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ カード番号3が入力されていません。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 4,
								'maxMessage' => "※ カード番号3は4字以下で入力してください。"
						) )
				)
		) )->add ( 'card_no04', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6'
				),
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ カード番号4が入力されていません。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 4,
								'maxMessage' => "※ カード番号4は4字以下で入力してください。"
						) )
				)
		) );
		if ($this->security_code == 1) {
			$builder->add ( 'security_code', 'text', array (
					'label' => 'セキュリティコード',
					'attr' => array (
							'class' => 'lockon_card_row',
							'maxlength' => '4',
							'autocomplete' => 'off'
					),
					'required' => false,
					'constraints' => array (
							new Assert\NotBlank ( array (
									'message' => '※ セキュリティコードが入力されていません。'
							) ),
							new Assert\Length ( array (
									'min' => 3,
									'max' => 4,
									'minMessage' => "※ セキュリティコードが3桁～4桁の範囲ではありません。",
									'maxMessage' => "※ セキュリティコードが3桁～4桁の範囲ではありません。"
							) ),
							new Assert\Regex ( array (
									'pattern' => "/^[0-9]+$/",
									'match' => true,
									'message' => '※ セキュリティコードに数字以外の文字が含まれています。'
							) )
					)
			) );
		}
		$builder->add ( 'card_name01', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'maxlength' => '25',
						'placeholder' =>'名',
				),
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ 名が入力されていません。'
						) ),
						new Assert\Regex ( array (
								'pattern' => "/[^a-zA-Z\d\s]/",
								'match' => false,
								'message' => '※ カード名義人名:名は英数字で入力してください。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 25,
								'minMessage' => "※ カード名義人名:名は25字以下で入力してください。",
								'maxMessage' => "※ カード名義人名:名は25字以下で入力してください。"
						) )
				)
		) )->add ( 'card_name02', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'maxlength' => '24',
						'placeholder' =>'姓',
				),
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ 姓が入力されていません。'
						) ),
						new Assert\Regex ( array (
								'pattern' => "/[^a-zA-Z\d\s]/",
								'match' => false,
								'message' => '※ カード名義人名:姓は英数字で入力してください。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 24,
								'minMessage' => "※ カード名義人名:名は24字以下で入力してください。",
								'maxMessage' => "※ カード名義人名:姓は24字以下で入力してください。"
						) )
				)
		) )->add ( 'stock', 'checkbox', array (
				'required' => false,
				'label' => '登録カードを利用する'
		) )->add ( 'stock_new', 'checkbox', array (
				'required' => false,
				'label' => '登録する'
		) )->add ( 'card_month', 'choice', array (
				'required' => false,
				'choices' => $month,
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ カード期限月が入力されていません。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 2,
								'minMessage' => "※ カード有効期限月は2字以下で入力してください。",
								'maxMessage' => "※ カード有効期限月は2字以下で入力してください。"
						) ),
						new Assert\Regex ( array (
								'pattern' => "/^[0-9]+$/",
								'match' => true,
								'message' => '※ カード有効期限月は数字で入力してください。'
						) )
				)
		) )->add ( 'card_year', 'choice', array (
				'required' => false,
				'choices' => $year,
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ カード期限年が入力されていません。'
						) ),
						new Assert\Length ( array (
								'min' => 0,
								'max' => 2,
								'minMessage' => "※ カード有効期限年は2字以下で入力してください。",
								'maxMessage' => "※ カード有効期限年は2字以下で入力してください。"
						) ),
						new Assert\Regex ( array (
								'pattern' => "/^[0-9]+$/",
								'match' => true,
								'message' => '※ カード有効期限年は数字で入力してください。'
						) )
				)
		) )->add ( 'payment_class', 'choice', array (
				'choices' => $this->arrPaymentClass,
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ 支払い方法が入力されていません。'
						) )
				)
		) )->addEventListener ( FormEvents::POST_BIND, function ($event) use ($builder) {
			$form = $event->getForm ();
			$expire_month = $form ['card_month']->getData ();
			$expire_year = $form ['card_year']->getData ();
			if (! empty ( $expire_month ) && ! empty ( $expire_year )) {
				if (strtotime ( '-1 month' ) > strtotime ( '20' . $expire_year . '/' . $expire_month . '/1' )) {
					$form ['card_year']->addError ( new FormError ( "※ 有効期限が過ぎたカードは利用出来ません。" ) );
				}
			}
		} );
	}


	//when stock checked
	public function getFormStockChecked(FormBuilderInterface $builder, $stock = null) {
		$year = $this->getZeroYear ( date ( 'Y' ), date ( 'Y' ) + 15 );
		$month = $this->getZeroMonth ();
		$this->app ['request']->request->all ();
		$Order = $this->app ['eccube.repository.order']->findOneBy ( array (
				'pre_order_id' => $this->app ['eccube.service.cart']->getPreOrderId ()
		) );
		if (is_null ( $Order ))
			return;

		$objUtil = $this->app ['eccube.plugin.service.payment'];
		$Payment = $Order->getPayment ();
		if (! is_null ( $Payment )) {
			$paymentInfo = $objUtil->getPaymentTypeConfig ( $Payment->getId () )->getArrPaymentConfig ();
		}

		$builder->add ( 'card_no01', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6'
				),
		) )->add ( 'card_no02', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6'
				),
		) )->add ( 'card_no03', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6'
				),
		) )->add ( 'card_no04', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'minlength' => '0',
						'maxlength' => '4',
						'autocomplete' => 'off',
						'size' => '6'
				),
		) );

		if ($this->security_code == 1 && $stock == 1) {
			$builder->add ( 'security_code', 'text', array (
					'label' => 'セキュリティコード',
					'attr' => array (
							'class' => 'lockon_card_row',
							'maxlength' => '4',
							'autocomplete' => 'off'
					),
					'required' => false,
					'constraints' => array (
							new Assert\NotBlank ( array (
									'message' => '※ セキュリティコードが入力されていません。'
							) ),
							new Assert\Length ( array (
									'min' => 3,
									'max' => 4,
									'minMessage' => "※ セキュリティコードが3桁～4桁の範囲ではありません。",
									'maxMessage' => "※ セキュリティコードが3桁～4桁の範囲ではありません。"
							) ),
							new Assert\Regex ( array (
									'pattern' => "/^[0-9]+$/",
									'match' => true,
									'message' => '※ セキュリティコードに数字以外の文字が含まれています。'
							) )
					)
			) );
		} else {
			$builder->add ( 'security_code', 'text', array (
					'label' => 'セキュリティコード',
					'attr' => array (
							'class' => 'lockon_card_row',
							'maxlength' => '4',
							'autocomplete' => 'off'
					),
					'required' => false,
					));
		}
		$builder->add ( 'card_name01', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'maxlength' => '25'
				),
		) )->add ( 'card_name02', 'text', array (
				'required' => false,
				'attr' => array (
						'class' => 'lockon_card_row',
						'maxlength' => '24'
				),
		) )->add ( 'stock', 'checkbox', array (
				'required' => false,
				'label' => '登録カードを利用する'
		) )->add ( 'stock_new', 'checkbox', array (
				'required' => false,
				'label' => '登録する'
		) )->add ( 'card_month', 'choice', array (
				'required' => false,
				'choices' => $month,
		) )->add ( 'card_year', 'choice', array (
				'required' => false,
				'choices' => $year,
		) )->add ( 'payment_class', 'choice', array (
				'choices' => $this->arrPaymentClass,
				'constraints' => array (
						new Assert\NotBlank ( array (
								'message' => '※ 支払い方法が入力されていません。'
						) )
				)
		) );
    }

    /**
     * {@inheritdoc}
     */
    public function getName() {
        return '';
    }

    /**
     * Get zero month
     *
     * @return type
     */
    public function getZeroMonth() {
        $month_array = array();
        for ($i = 1; $i <= 12; $i++) {
            $val = sprintf('%02d', $i);
            $month_array[$val] = $val;
        }

        return $month_array;
    }

    /**
     * Get zero year
     *
     * @param type $star_year
     * @param type $end_year
     * @param type $year
     * @return type
     */
    public function getZeroYear($star_year, $end_year, $year = '') {
        if ($year)
            $this->setStartYear($year);

        $year = $star_year;
        if (!$year)
            $year = DATE('Y');

        $end_year = $end_year;
        if (!$end_year)
            $end_year = (DATE('Y') + 3);

        $year_array = array();

        for ($i = $year; $i <= $end_year; $i++) {
            $key = substr($i, -2);
            $year_array[$key] = $key;
        }

        return $year_array;
    }

}
