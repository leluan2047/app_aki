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

namespace Plugin\PlgExpandProductColumns\Form\Extension\Admin;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Silex\Application as BaseApplication;


class PlgExpandProductColumnsValueExtension extends AbstractTypeExtension
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('admin_plg_expand_product_columns_value', 'collection', array(
                'label' => '拡張項目',
                'type' => 'admin_plg_expand_product_columns_value',
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => false,
                'mapped' => false
            ));

    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
    }

    public function getExtendedType()
    {
        return 'admin_product';
    }

}
