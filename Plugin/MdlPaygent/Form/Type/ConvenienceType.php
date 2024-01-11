<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ConvenienceType extends AbstractType {
	private $app;
	private $subData;
	private $arrConve;
	
	public function __construct(\Eccube\Application $app, $subData = null, $arrConve = null)
	{
		$this->app = $app;
		$this->subData = $subData;
		$this->arrConve = $arrConve;
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
		if (empty($this->subData)) {
			$this->subData = $this->initValue();
		}
		$builder
                ->add('cvs_company_id', 'choice', array(
                		'choices' => $this->arrConve,
                		'data' => $this->subData['cvs_company_id'],
                 		'constraints' => array(
                 				new Assert\NotBlank(array('message' => '※ コンビニが入力されていません。')),
                		),
                ))
                ->add('customer_family_name', 'text', array(
                		'attr' => array(                				
                				'maxlength' => '10',  
                				'placeholder' => '姓',
                		),
                		'data' => $this->subData['customer_family_name'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者姓が入力されていません。')),
                				new Assert\Length(array('min' => 0, 'max' => 10, 'maxMessage' => "※利用者姓は10字以下で入力してください。")),
                		),
                ))
                ->add('customer_name', 'text', array(
                		'attr' => array(
                				'maxlength' => '10',                				
                				'placeholder' => '名',
                		),
                		'data' => $this->subData['customer_name'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者名が入力されていません。')),
                				new Assert\Length(array('min' => 0, 'max' => 10 , 'maxMessage' => "※ 利用者名は10字以下で入力してください")),
                		),
                ))
                ->add('customer_family_name_kana', 'text', array(
                		'attr' => array(
                				'maxlength' => '14',  
                				'placeholder' => 'セイ',
                		),
                		'data' => $this->subData['customer_family_name_kana'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者姓カナが入力されていません。')),
                				new Assert\Regex(array(
                						'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
                						'message' => '※利用者姓カナはカタカナで入力してください。'
                				)),
                				new Assert\Length(array('min' => 0, 'max' => 14,  'maxMessage' => "※利用者姓カナは14字以下で入力してください。")),
                		),
                ))
                ->add('customer_name_kana', 'text', array(
                		'attr' => array(
                				'maxlength' => '14',
                				'placeholder' => 'メイ',
                		),
                		'data' => $this->subData['customer_name_kana'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ 利用者名カナが入力されていません。')),
                				new Assert\Regex(array(
                						'pattern' => "/^[ァ-ヶｦ-ﾟー]+$/u",
                						'message' => '※利用者名カナはカタカナで入力してください。'
                				)),
                				new Assert\Length(array('min' => 0, 'max' => 14, 'maxMessage' => "※ 利用者名カナは14字以下で入力してください。")),
                		),
                ))
                ->add('customer_tel', 'text', array(
                		'attr' => array(
                				'maxlength' => '11',                				
                		),
                		'data' => $this->subData['customer_tel'],
                		'constraints' => array(
                				new Assert\NotBlank(array('message' => '※ お電話番号が入力されていません。')),
                				new Assert\Regex(array('pattern' => "/[^0-9]/", 'match' => false, 'message' => '※ お電話番号は数字で入力してください。')),
                				new Assert\Length(array('min' => 0, 'max' => 11, 'maxMessage' => "※ お電話番号は11字以下で入力してください。")),
                		),
                ))                
				; 
	}	
	
	/**
	 * Get getName
	 */
	public function getName()
	{
		return 'convenience_store';
	}
	
	public function initValue() {
		return array(
				'customer_family_name' => null,
				'customer_name' => null,
				'customer_family_name_kana' => null,
				'customer_name_kana' => null,
				'customer_tel' => null,
		);
	}
	
}
