<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\PlgExpandProductColumns\Form\Type\Admin;

use Plugin\PlgExpandProductColumns\Form\EventListener\PlgExpandProductColumnsValueSubscriber;
use \Symfony\Component\Form\AbstractType;
use \Symfony\Component\Form\Extension\Core\Type;
use \Symfony\Component\Form\FormBuilderInterface;
use \Symfony\Component\Validator\Constraints as Assert;
use \Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Silex\Application as BaseApplication;

/**
 * http://docs.symfony.gr.jp/symfony2/book/forms.html#book-forms-type-reference
 *
 * Class PlgExpandProductColumnsType
 * @package Plugin\PlgExpandProductColumns\Form\Type\Admin
 */
class PlgExpandProductColumnsValueType extends AbstractType
{
    private $app;

    public function __construct(BaseApplication $app)
    {
        $this->app = $app;
    }
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $subscriber = new PlgExpandProductColumnsValueSubscriber($builder->getFormFactory(), $this->app);
        $builder->addEventSubscriber($subscriber);

    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue'
        ));

    }

    public function getName()
    {
        return 'admin_plg_expand_product_columns_value';
    }

}
