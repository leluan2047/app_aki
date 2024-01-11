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
use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Faker\Generator;
use Plugin\Maker\Entity\Maker;
use Plugin\Maker\Util\Version;

/**
 * Class MakerWebTestCase
 * @package Plugin\Maker\Tests\Web
 */
class MakerWebCommon extends AbstractAdminWebTestCase
{
    /**
     * Create product form to submit.
     *
     * @return array
     */
    protected function createFormData()
    {
        /**
         * @var Generator $faker
         */
        $faker = $this->getFaker();
        $form = array(
            'class' => array(
                'product_type' => 1,
                'price01' => $faker->randomNumber(5),
                'price02' => $faker->randomNumber(5),
                'stock' => $faker->randomNumber(3),
                'stock_unlimited' => 0,
                'code' => $faker->word,
                'sale_limit' => null,
                'delivery_date' => '',
            ),
            'name' => $faker->word,
            'product_image' => null,
            'description_detail' => $faker->text,
            'description_list' => $faker->paragraph,
            'Category' => null,
            'Tag' => 1,
            'search_word' => $faker->word,
            'free_area' => $faker->text,
            'Status' => 1,
            'note' => $faker->text,
            'tags' => null,
            'images' => null,
            'add_images' => null,
            'delete_images' => null,
            '_token' => 'dummy',
        );
        if (Version::isSupport('3.0.9', '==')) {
            unset($form['Tag']);
            $form['tag'] = $faker->word;
        }

        return $form;
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
}
