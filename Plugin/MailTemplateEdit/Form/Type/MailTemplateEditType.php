<?php
/* ActiveFusions 2015/11/10 11:36 */

namespace Plugin\MailTemplateEdit\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class MailTemplateEditType extends AbstractType
{

	private $app;

	public function __construct(\Eccube\Application $app){
		$this->app = $app;
	}

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', 'text', array(
                'label' => 'テンプレート',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
                /*'mapped' => false,*/
            ))
            ->add('subject', 'text', array(
                'label' => '件名',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('header', 'textarea', array(
                'label' => 'ヘッダー',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->add('footer', 'textarea', array(
                'label' => 'フッター',
                'required' => true,
                'constraints' => array(
                    new Assert\NotBlank(),
                ),
            ))
            ->addEventSubscriber(new \Eccube\Event\FormEventSubscriber());
        ;

    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'mailadd';
    }
}
