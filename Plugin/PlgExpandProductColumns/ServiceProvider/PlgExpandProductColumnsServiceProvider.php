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

namespace Plugin\PlgExpandProductColumns\ServiceProvider;

use Eccube\Application;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
class PlgExpandProductColumnsServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        if (!defined('EX_TYPE_TEXT')) {
            define('EX_TYPE_TEXT', 1);
            define('EX_TYPE_TEXTAREA', 2);
            define('EX_TYPE_IMAGE', 3);
            define('EX_TYPE_SELECT', 4);
            define('EX_TYPE_CHECKBOX', 5);
            define('EX_TYPE_RADIO', 6);
        }

        $app['PlgExpandProductColumns-TYPE_MAP'] = array(
            EX_TYPE_TEXT => '1行テキスト',
            EX_TYPE_TEXTAREA => 'テキストエリア',
            EX_TYPE_IMAGE => '画像',
            EX_TYPE_SELECT => 'セレクトボックス',
            EX_TYPE_CHECKBOX => 'チェックボックス',
            EX_TYPE_RADIO => 'ラジオボタン'
        );

        $cd = 'PlgExpandProductColumns';

        // -- ページ拡張 --
        // 拡張項目管理画面
        $app->match("/{$app['config']['admin_route']}/plugin/{$cd}/config", '\Plugin\PlgExpandProductColumns\Controller\ConfigController::init')
            ->bind("plugin_{$cd}_config");
        $app->match("/{$app['config']['admin_route']}/plugin/{$cd}/config/new", '\Plugin\PlgExpandProductColumns\Controller\ConfigController::edit')
            ->bind("plugin_{$cd}_config_new");
        $app->match("/{$app['config']['admin_route']}/plugin/{$cd}/config/{id}/edit", '\Plugin\PlgExpandProductColumns\Controller\ConfigController::edit')
            ->assert('id', '\d+')->bind("plugin_{$cd}_config_edit");
        $app->match("/{$app['config']['admin_route']}/plugin/{$cd}/config/{id}/delete", '\Plugin\PlgExpandProductColumns\Controller\ConfigController::delete')
            ->assert('id', '\d+')->bind("plugin_{$cd}_config_delete");
        // CSV importページへのルーティングを上書き
        $app->match("/{$app['config']['admin_route']}/plugin/{$cd}/config/csv", '\Plugin\PlgExpandProductColumns\Controller\ConfigController::csvProduct')->bind('admin_product_csv_import');
        $app->match("/{$app['config']['admin_route']}/plugin/{$cd}/config/csv_template/{type}", '\Plugin\PlgExpandProductColumns\Controller\ConfigController::csvTemplate')->bind('admin_product_csv_template');
        // 商品画像登録処理の上書き
        $app->post("/{$app['config']['admin_route']}/plugin/{$cd}/product/image/add", '\Plugin\PlgExpandProductColumns\Controller\ConfigController::addImage')->bind('admin_product_image_add');



        // -- Repositoy --
        $app['eccube.plugin.repository.plg_expand_product_columns_value'] = function () use ($app) {
            return $app['orm.em']->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue');
        };
        $app['eccube.plugin.repository.plg_expand_product_columns'] = function () use ($app) {
            return $app['orm.em']->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns');
        };

        // -- Formの定義 --
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new \Plugin\PlgExpandProductColumns\Form\Type\Admin\PlgExpandProductColumnsValueType($app);
            $types[] = new \Plugin\PlgExpandProductColumns\Form\Type\Admin\PlgExpandProductColumnsType($app);
            return $types;
        }));
        $app['form.type.extensions'] = $app->share($app->extend('form.type.extensions', function ($extensions) use ($app) {
            $extensions[] = new \Plugin\PlgExpandProductColumns\Form\Extension\Admin\PlgExpandProductColumnsValueExtension();
            return $extensions;
        }));


        // -- CSV Export ---
        $app['eccube.service.csv.export'] = $app->share(function () use ($app) {
            $csvService = new \Plugin\PlgExpandProductColumns\Service\PlgCsvExportService(
                $app['eccube.plugin.repository.plg_expand_product_columns'],
                $app['eccube.plugin.repository.plg_expand_product_columns_value']
            );
            $csvService->setEntityManager($app['orm.em']);
            $csvService->setConfig($app['config']);
            $csvService->setCsvRepository($app['eccube.repository.csv']);
            $csvService->setCsvTypeRepository($app['eccube.repository.master.csv_type']);
            $csvService->setOrderRepository($app['eccube.repository.order']);
            $csvService->setCustomerRepository($app['eccube.repository.customer']);
            $csvService->setProductRepository($app['eccube.repository.product']);

            return $csvService;
        });

    }

    public function boot(BaseApplication $app)
    {
    }
}