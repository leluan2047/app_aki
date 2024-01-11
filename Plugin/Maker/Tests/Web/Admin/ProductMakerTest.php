<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Maker\Tests\Web\Admin;

use Faker\Generator;
use Plugin\Maker\Entity\ProductMaker;
use Plugin\Maker\Tests\Web\MakerWebCommon;
use Symfony\Component\HttpKernel\Client;

/**
 * Class ProductMakerTest.
 */
class ProductMakerTest extends MakerWebCommon
{
    const MAKER = 'plg_maker';
    const MAKER_URL = 'plg_maker_url';

    /**
     * @var int
     */
    protected $productId;

    /**
     * Set up function.
     */
    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows(array('plg_product_maker', 'plg_maker'));
    }

    /**
     * Test render
     */
    public function testProductNewRender()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_product_product_new'));
        $this->assertContains('メーカー', $crawler->filter('body .container-fluid')->html());
    }

    /**
     * Test new
     */
    public function testProductNewWithoutMaker()
    {
        $crawler = $this->client->request('GET', $this->app->url('admin_product_product_new'));
        $this->assertContains('メーカー', $crawler->filter('body .container-fluid')->html());
    }

    /**
     * Test new
     */
    public function testProductNewWithMakerWithoutMakerSelect()
    {
        $Maker = $this->createMaker();

        $crawler = $this->client->request('GET', $this->app->url('admin_product_product_new'));
        $this->assertContains($Maker->getName(), $crawler->filter('body .container-fluid')->html());
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerURLWithoutMakerSelect()
    {
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = $faker->url;

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

        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('登録が完了しました。', $crawler->filter('.alert')->html());

        // Check layout
        $this->assertNotContains($formData[self::MAKER_URL], $crawler->filter('body .container-fluid')->html());

        // Check database
        $arrProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->findAll();

        $this->actual = count($arrProductMaker);
        $this->expected = 0;
        $this->verify();
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerInvalid()
    {
        $Maker = $this->createMaker();
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId() + 1;
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client $client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->app->url('admin_product_product_new'),
            array('admin_product' => $formData)
        );

        // Check message
        $this->assertContains('登録できませんでした。', $crawler->filter('.alert')->html());
        $this->assertContains('有効な値ではありません。', $crawler->filter('.errormsg')->html());

        // Check database
        $arrProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->findAll();

        $this->actual = count($arrProductMaker);
        $this->expected = 0;
        $this->verify();
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerWithoutMakerUrl()
    {
        $Maker = $this->createMaker();

        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = '';

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

        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('登録が完了しました。', $crawler->filter('.alert')->html());

        // Check database
        /**
         * @var ProductMaker $ProductMaker
         */
        $ProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->find($productId);

        $this->actual = $ProductMaker->getMaker()->getId();
        $this->expected = $formData[self::MAKER];
        $this->verify();
    }

    /**
     * Test render
     */
    public function testProductNewWithAddMakerAndMakerUrlInValid()
    {
        $Maker = $this->createMaker();

        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->word;

        /**
         * @var Client $client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->app->url('admin_product_product_new'),
            array('admin_product' => $formData)
        );

        // Check message
        $this->assertContains('登録できませんでした。', $crawler->filter('.alert')->html());
        $this->assertContains('有効なURLではありません。', $crawler->filter('.errormsg')->html());

        // Check database
        $arrProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->findAll();

        $this->actual = count($arrProductMaker);
        $this->expected = 0;
        $this->verify();
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerAndMakerUrlSuccess()
    {
        $Maker = $this->createMaker();

        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->url;

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

        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('登録が完了しました。', $crawler->filter('.alert')->html());

        // Check database
        /**
         * @var ProductMaker $ProductMaker
         */
        $ProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->find($productId);

        $this->actual = array($ProductMaker->getMaker()->getId(), $ProductMaker->getMakerUrl());
        $this->expected = array($formData[self::MAKER], $formData[self::MAKER_URL]);
        $this->verify();
    }

    /**
     * Test render
     */
    public function testProductEditRender()
    {
        $Product = $this->createProduct();

        $crawler = $this->client->request('GET', $this->app->url('admin_product_product_edit', array('id' => $Product->getId())));
        $this->assertContains('メーカー', $crawler->filter('body .container-fluid')->html());
    }

    /**
     * Test render
     */
    public function testProductEditWithMaker()
    {
        $Product = $this->createProduct();
        $Maker = $this->createMaker();

        $crawler = $this->client->request('GET', $this->app->url('admin_product_product_edit', array('id' => $Product->getId())));
        $this->assertContains($Maker->getName(), $crawler->filter('body .container-fluid')->html());
    }

    /**
     * Test new
     */
    public function testProductEditWithAddMakerURLWithoutMakerSelect()
    {
        $this->createMaker();

        // New product
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client $client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->app->url('admin_product_product_edit', array('id' => $productId)),
            array('admin_product' => $formData)
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('登録が完了しました。', $crawler->filter('.alert')->html());

        // Check database
        $arrProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->findAll();

        $this->actual = count($arrProductMaker);
        $this->expected = 0;
        $this->verify();
    }

    /**
     * Test Edit
     */
    public function testProductEditWithAddMakerInvalid()
    {
        $this->createMaker();

        // New product
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = 99999;
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client $client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->app->url('admin_product_product_edit', array('id' => $productId)),
            array('admin_product' => $formData)
        );

        // Check message
        $this->assertContains('登録できませんでした。', $crawler->filter('.alert')->html());
        $this->assertContains('有効な値ではありません。', $crawler->filter('.errormsg')->html());

        // Check database
        $arrProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->findAll();

        $this->actual = count($arrProductMaker);
        $this->expected = 0;
        $this->verify();
    }

    /**
     * Test Edit
     */
    public function testProductEditWithAddMakerWithoutMakerUrl()
    {
        $Maker = $this->createMaker();

        // New product
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = '';

        /**
         * @var Client $client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->app->url('admin_product_product_edit', array('id' => $productId)),
            array('admin_product' => $formData)
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('登録が完了しました。', $crawler->filter('.alert')->html());

        // Check database
        $ProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->find($productId);

        $this->actual = array($ProductMaker->getMaker()->getId(), $ProductMaker->getMakerUrl());
        $this->expected = array($Maker->getId(), $formData[self::MAKER_URL]);
        $this->verify();
    }

    /**
     * Test render
     */
    public function testProductEditWithAddMakerAndMakerUrlInValid()
    {
        $Maker = $this->createMaker();

        // New product
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->word; // invalid

        $crawler = $client->request(
            'POST',
            $this->app->url('admin_product_product_edit', array('id' => $productId)),
            array('admin_product' => $formData)
        );

        // Check message
        $this->assertContains('登録できませんでした。', $crawler->filter('.alert')->html());
        $this->assertContains('有効なURLではありません。', $crawler->filter('.errormsg')->html());

        // Check database
        $arrProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->findAll();

        $this->actual = count($arrProductMaker);
        $this->expected = 0;
        $this->verify();
    }

    /**
     * Test Edit
     */
    public function testProductEditWithAddMakerAndMakerUrlSuccess()
    {
        $Maker = $this->createMaker();
        // New product
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client $client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->app->url('admin_product_product_edit', array('id' => $productId)),
            array('admin_product' => $formData)
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('登録が完了しました。', $crawler->filter('.alert')->html());

        // Check database
        $ProductMaker = $this->app['eccube.plugin.maker.repository.product_maker']->find($productId);

        $this->actual = array($ProductMaker->getMaker()->getId(), $ProductMaker->getMakerUrl());
        $this->expected = array($Maker->getId(), $formData[self::MAKER_URL]);
        $this->verify();
    }
}
