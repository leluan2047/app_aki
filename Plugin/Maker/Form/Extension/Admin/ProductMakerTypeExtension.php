<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Maker\Form\Extension\Admin;

use Doctrine\ORM\EntityRepository;
use Eccube\Application;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class ProductMakerTypeExtension.
 */
class ProductMakerTypeExtension extends AbstractTypeExtension
{
    private $app;

    /**
     * ProductMakerTypeExtension constructor.
     *
     * @param Application $app
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('maker', 'entity', array(
                'label' => 'メーカー',
                'class' => 'Plugin\Maker\Entity\Maker',
                'query_builder' => function (EntityRepository $repository) {
                    return $repository->createQueryBuilder('m')->orderBy('m.rank', 'DESC');
                },
                'property' => 'name',
                'required' => false,
                'empty_value' => '',
                'mapped' => false,
            ))
            ->add('maker_url', 'text', array(
                'label' => 'URL',
                'required' => false,
                'constraints' => array(
                    new Assert\Url(),
                ),
                'mapped' => false,
                'attr' => array(
                    'placeholder' => $this->app->trans('admin.plugin.maker.placeholder.url'),
                ),
            ));
    }

    /**
     * @return string
     */
    public function getExtendedType()
    {
        return 'admin_product';
    }
}
