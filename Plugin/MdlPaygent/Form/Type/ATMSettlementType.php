<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ATMSettlementType extends AbstractType {
	private $app;
	private $subData;

	public function __construct(\Eccube\Application $app, $subData = null)
	{
		$this->app = $app;
		$this->subData = $subData;
	}

	/**
	 * Build result convenience type form
	 *
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 * @return type
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder
                ->add('customer_family_name', 'text', array(
                		'required' => false,
                		'attr' => array(
                				'maxlength' => '6',
                				'placeholder' =>'姓',
                		),
                		'data' => $this->subData['customer_family_name'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者姓が入力されていません。')),
                				new Assert\Length(array('min' => 0, 'max' => 6, 'maxMessage' => "※※ 利用者姓は6字以下で入力してください。")),
                		),
                ))
                ->add('customer_name', 'text', array(
                		'required' => false,
                		'attr' => array(
                				'maxlength' => '6',
                				'placeholder' =>'名',
                		),
                		'data' => $this->subData['customer_name'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者名が入力されていません。')),
                				new Assert\Length(array('min' => 0, 'max' => 6 , 'maxMessage' => "※ 利用者名は6字以下で入力してください。")),
                		),
                ))
                ->add('customer_family_name_kana', 'text', array(
                		'required' => false,
                		'attr' => array(
                				'maxlength' => '12',
                				'placeholder' =>'セイ',

                		),
                		'data' => $this->subData['customer_family_name_kana'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者姓カナが入力されていません。')),
                				new Assert\Regex(array(
                						'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
                						'message' => '※ 利用者姓カナはカタカナで入力してください。'
                				)),
                				new Assert\Length(array('min' => 0, 'max' => 12,  'maxMessage' => "※ 利用者姓カナは12字以下で入力してください。")),
                		),
                ))
                ->add('customer_name_kana', 'text', array(
                		'required' => false,
                		'attr' => array(
                				'maxlength' => '12',
                				'placeholder' =>'メイ',
                		),
                		'data' => $this->subData['customer_name_kana'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者名カナが入力されていません。')),
                				new Assert\Regex(array(
                						'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
                						'message' => '※ 利用者名カナはカタカナで入力してください。'
                				)),
                				new Assert\Length(array('min' => 0, 'max' => 12, 'maxMessage' => "※ 利用者名カナは12字以下で入力してください。")),
                		),
                ))
				;
	}

	public function getName()
	{
		return 'ATM_settlement';
	}
}
