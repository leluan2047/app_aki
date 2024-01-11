<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\Maker\Tests\Web;

use Eccube\Common\Constant;
use Eccube\Entity\Product;
use Faker\Generator;
use Plugin\Maker\Entity\Maker;
use Plugin\Maker\Entity\ProductMaker;
use Symfony\Component\HttpKernel\Client;

/**
 * Class ProductDetailTest
 * Hook point test
 */
class ProductDetailTest extends MakerWebCommon
{
    /**
     * @var Maker $Maker
     */
    private $Maker;

    /**
     * @var ProductMaker $ProductMaker
     */
    private $ProductMaker;

    /**
     * Set up function.
     */
    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows(array('plg_product_maker', 'plg_maker'));
        $this->Maker = $this->createMaker();

        $this->ProductMaker = $this->createProductMaker($this->Maker);
    }

    /**
     * Product detail render test maker
     */
    public function testProductDetailWhenHasMakerButUnRegister()
    {
        $productId = $this->ProductMaker->getId();
        $this->app['orm.em']->remove($this->ProductMaker);
        $this->app['orm.em']->flush($this->ProductMaker);
        $crawler = $this->client->request('GET', $this->app->url('product_detail', array('id' => $productId)));
        $html = $crawler->filter('.item_detail')->html();
        $this->assertNotContains('メーカーコード', $html);
        $this->assertNotContains('メーカーURL', $html);
    }

    /**
     * Product detail render test maker
     */
    public function testProductDetailWhenRegisterMakerWithoutMakerUrl()
    {
        $productId = $this->ProductMaker->getId();
        $this->ProductMaker->setMakerUrl('');
        $this->app['orm.em']->persist($this->ProductMaker);
        $this->app['orm.em']->flush($this->ProductMaker);

        $crawler = $this->client->request('GET', $this->app->url('product_detail', array('id' => $productId)));

        $html = $crawler->filter('.item_detail')->html();
        $this->assertContains($this->ProductMaker->getMaker()->getName(), $html);
        $this->assertNotContains('メーカーURL', $html);
    }

    /**
     * Product detail render test maker
     */
    public function testProductDetailWhenRegisterMakerAndMakerUrl()
    {
        $productId = $this->ProductMaker->getId();

        $crawler = $this->client->request('GET', $this->app->url('product_detail', array('id' => $productId)));

        $html = $crawler->filter('.item_detail')->html();
        $this->assertContains($this->ProductMaker->getMaker()->getName(), $html);
        $this->assertContains($this->ProductMaker->getMakerUrl(), $html);
    }

    /**
     * Create maker
     *
     * @param int $rank
     *
     * @return Maker
     */
    protected function createMaker($rank = null)
    {
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();

        if (!$rank) {
            $rank = $faker->randomNumber(3);
        }

        $Maker = new Maker();
        $Maker->setName($faker->word);
        $Maker->setRank($rank);
        $Maker->setDelFlg(Constant::DISABLED);

        $this->app['orm.em']->persist($Maker);
        $this->app['orm.em']->flush($Maker);

        return $Maker;
    }

    /**
     * Create maker
     *
     * @param Maker   $Maker
     * @param Product $Product
     *
     * @return ProductMaker
     */
    protected function createProductMaker(Maker $Maker, $Product = null)
    {
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();

        if (!$Product) {
            // New product
            /**
             * @var Generator $faker
             */
            $faker = $this->getFaker();
            $formData = $this->createFormData();
            $formData['plg_maker'] = '';
            $formData['plg_maker_url'] = '';

            /**
             * @var Client $client
             */
            $client = $this->client;
            $client->request(
                'POST',
                $this->app->url('admin_product_product_new'),
                array('admin_product' => $formData)
            );

            $this->assertTrue($client->getResponse()->isRedirection());

            $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
            $productId = $arrTmp[count($arrTmp)-2];

            $client->followRedirect();
            $Product = $this->app['eccube.repository.product']->find($productId);
        }

        $ProductMaker = new ProductMaker();
        $ProductMaker->setMaker($Maker);
        $ProductMaker->setMakerUrl($faker->url);
        $ProductMaker->setDelFlg(Constant::DISABLED);
        $ProductMaker->setId($Product->getId());

        $this->app['orm.em']->persist($ProductMaker);
        $this->app['orm.em']->flush($ProductMaker);

        return $ProductMaker;
    }
}
