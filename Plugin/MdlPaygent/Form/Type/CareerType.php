<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class CareerType extends AbstractType {
	private $app;
	private $subData;

	public function __construct(\Eccube\Application $app, $subData = null)
	{
		$this->app = $app;
		$this->subData = $subData;
	}

	public function getArrCarerr() {
		$MdlPaymentRepo = $this->app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
		$MdlPaymentRepo->setConfig($this->app['config']['MdlPaygent']['const']);
		$payPaygentCareer = $this->app['config']['MdlPaygent']['const']['PAY_PAYGENT_CAREER'];
		// 銀行NET用パラメータの取得

		$arrPaymentDB = $MdlPaymentRepo->getPaymentDB($payPaygentCareer);

		$arrOtherParam = unserialize($arrPaymentDB[0]['other_param']);
		$arrCarrerDivision = $arrOtherParam['career_division'];

		$arrCareer = array(
				'' => 'ご選択ください'
		);
		if (!is_null($arrOtherParam)) {
			foreach ($arrOtherParam['career_division'] as $menthod) {
				if ($menthod != null && $menthod == 1) {
					$arrCareer[1] = 'ドコモケータイ払い';
				}
				if ($menthod != null && $menthod == 2) {
					$arrCareer[2] = 'auかんたん決済';
				}
				if ($menthod != null && $menthod == 3) {
					$arrCareer[3] = 'ソフトバンクまとめて支払い';
				}
			}
		}
		return $arrCareer;
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
		$arrCareer = $this->getArrCarerr();
		$builder
		->add('career_type', 'choice', array(
				'label' => 'キャリア決済選択',
				'choices' => $arrCareer,
				'required' => false,
				'constraints' => array(
						new Assert\NotBlank(array('message' => '※ キャリア決済選択が入力されていません。')),
				),
		));
	}

	public function getName()
	{
		return 'mobile_carrier';
	}
}
