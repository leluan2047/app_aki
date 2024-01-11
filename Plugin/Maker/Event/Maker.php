<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Maker\Event;

use Doctrine\ORM\EntityRepository;
use Eccube\Entity\Product;
use Eccube\Event\EventArgs;
use Eccube\Common\Constant;
use Eccube\Event\TemplateEvent;
use Plugin\Maker\Entity\ProductMaker;
use Plugin\Maker\Repository\ProductMakerRepository;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Maker.
 * New event on version EC-CUBE version >= 3.0.9 (new hook point).
 */
class Maker extends CommonEvent
{
    /**
     * New event function on version >= 3.0.9 (new hook point)
     * Add/Edit product trigger.
     *
     * @param EventArgs $event
     */
    public function onAdminProductEditInitialize(EventArgs $event)
    {
        log_info('Event: product maker hook into the product render start.');
        /**
         * @var FormBuilder $builder
         */
        $builder = $event->getArgument('builder');

        // Remove old extension
        $builder->remove('maker')
            ->remove('maker_url');

        // Add new extension
        $builder
            ->add('plg_maker', 'entity', array(
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
            ->add('plg_maker_url', 'text', array(
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

        /**
         * @var Product $Product
         */
        $Product = $event->getArgument('Product');
        $id = $Product->getId();

        /**
         * @var ProductMaker $ProductMaker
         */
        $ProductMaker = null;

        if ($id) {
            /**
             * @var ProductMakerRepository $repository
             */
            $repository = $this->app['eccube.plugin.maker.repository.product_maker'];
            $ProductMaker = $repository->find($id);
        }

        if (!$ProductMaker) {
            log_info('Event: Product maker not found!', array('Product id' => $id));

            return;
        }

        $builder->get('plg_maker')->setData($ProductMaker->getMaker());
        $builder->get('plg_maker_url')->setData($ProductMaker->getMakerUrl());
        log_info('Event: product maker hook into the product render end.');
    }

    /**
     * New Event:function on version >= 3.0.9 (new hook point)
     * Save event.
     *
     * @param EventArgs $eventArgs
     */
    public function onAdminProductEditComplete(EventArgs $eventArgs)
    {
        log_info('Event: product maker hook into the product management complete start.');
        /**
         * @var Form $form
         */
        $form = $eventArgs->getArgument('form');

        /**
         * @var Product $Product
         */
        $Product = $eventArgs->getArgument('Product');

        /**
         * @var ProductMakerRepository $repository
         */
        $repository = $this->app['eccube.plugin.maker.repository.product_maker'];
        /**
         * @var ProductMaker $ProductMaker
         */
        $ProductMaker = $repository->find($Product);
        if (!$ProductMaker) {
            $ProductMaker = new ProductMaker();
        }

        $maker = $form->get('plg_maker')->getData();
        if (!$maker) {
            if ($ProductMaker->getId()) {
                log_info('Event: product maker removed', array('Product maker id' => $ProductMaker->getId()));
                $this->app['orm.em']->remove($ProductMaker);
                $this->app['orm.em']->flush($ProductMaker);
            }

            return;
        }

        $makerUrl = $form->get('plg_maker_url')->getData();

        $ProductMaker
            ->setId($Product->getId())
            ->setMakerUrl($makerUrl)
            ->setDelFlg(Constant::DISABLED)
            ->setMaker($maker);
        /**
         * @var EntityRepository $this->app['orm.em']
         */
        $this->app['orm.em']->persist($ProductMaker);
        $this->app['orm.em']->flush($ProductMaker);
        log_info('Event: product maker save success!', array('Product id' => $ProductMaker->getId()));

        log_info('Event: product maker hook into the product management complete end.');
    }

    /**
     * New event function on version >= 3.0.9 (new hook point)
     * Product detail render (front).
     *
     * @param TemplateEvent $event
     */
    public function onRenderProductDetail(TemplateEvent $event)
    {
        log_info('Event: product maker hook into the product detail start.');

        $parameters = $event->getParameters();
        /**
         * @var Product $Product
         */
        $Product = $parameters['Product'];

        if (!$Product) {
            return;
        }

        /**
         * @var ProductMakerRepository $repository
         */
        $repository = $this->app['eccube.plugin.maker.repository.product_maker'];
        /**
         * @var ProductMaker $ProductMaker
         */
        $ProductMaker = $repository->find($Product);
        if (!$ProductMaker) {
            log_info('Event: product maker not found.', array('Product id' => $Product->getId()));

            return;
        }

        $Maker = $ProductMaker->getMaker();

        if (!$Maker) {
            log_info('Event: maker not found.', array('Product maker id' => $ProductMaker->getId()));
            // 商品メーカーマスタにデータが存在しないまたは削除されていれば無視する
            return;
        }

        /**
         * @var \Twig_Environment $twig
         */
        $twig = $this->app['twig'];

        $twigAppend = $twig->getLoader()->getSource('Maker/Resource/template/default/maker.twig');

        /**
         * @var string $twigSource twig template.
         */
        $twigSource = $event->getSource();

        $twigSource = $this->renderPosition($twigSource, $twigAppend, $this->makerTag);

        $event->setSource($twigSource);

        $parameters['maker_name'] = $ProductMaker->getMaker()->getName();
        $parameters['maker_url'] = $ProductMaker->getMakerUrl();
        $event->setParameters($parameters);
        log_info('Event: product maker render success.', array('Product id' => $ProductMaker->getId()));
        log_info('Event: product maker hook into the product detail end.');
    }
}
