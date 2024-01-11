<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Maker\ServiceProvider;

use Plugin\Maker\Event\Maker;
use Plugin\Maker\Event\MakerLegacy;
use Plugin\Maker\Form\Extension\Admin\ProductMakerTypeExtension;
use Plugin\Maker\Form\Type\MakerType;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Eccube\Common\Constant;

// include log functions (for 3.0.0 - 3.0.11)
require_once(__DIR__.'/../log.php');

/**
 * Class MakerServiceProvider.
 */
class MakerServiceProvider implements ServiceProviderInterface
{
    /**
     * @param BaseApplication $app
     */
    public function register(BaseApplication $app)
    {
        // 管理画面定義
        $admin = $app['controllers_factory'];
        // 強制SSL
        if ($app['config']['force_ssl'] == Constant::ENABLED) {
            $admin->requireHttps();
        }

        // メーカーテーブル用リポジトリ
        $app['eccube.plugin.maker.repository.maker'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Maker\Entity\Maker');
        });

        $app['eccube.plugin.maker.repository.product_maker'] = $app->share(function () use ($app) {
            return $app['orm.em']->getRepository('Plugin\Maker\Entity\ProductMaker');
        });

        // Maker event
        $app['eccube.plugin.maker.event.maker'] = $app->share(function () use ($app) {
            return new Maker($app);
        });

        // Maker legacy event
        $app['eccube.plugin.maker.event.maker_legacy'] = $app->share(function () use ($app) {
            return new MakerLegacy($app);
        });

        // 一覧・登録・修正
        $admin->match('/plugin/maker/{id}', '\\Plugin\\Maker\\Controller\\MakerController::index')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_plugin_maker_index');

        // 削除
        $admin->delete('/plugin/maker/{id}/delete', '\\Plugin\\Maker\\Controller\\MakerController::delete')
            ->value('id', null)->assert('id', '\d+|')
            ->bind('admin_plugin_maker_delete');

        $admin->post('/plugin/maker/rank/move', '\\Plugin\\Maker\\Controller\\MakerController::moveRank')
            ->bind('admin_plugin_maker_move_rank');

        $app->mount('/'.trim($app['config']['admin_route'], '/').'/', $admin);

        // 型登録
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
            $types[] = new MakerType($app);

            return $types;
        }));

        // Form Extension
        $app['form.type.extensions'] = $app->share($app->extend('form.type.extensions', function ($extensions) use ($app) {
            $extensions[] = new ProductMakerTypeExtension($app);

            return $extensions;
        }));

        // メッセージ登録
        $file = __DIR__.'/../Resource/locale/message.'.$app['locale'].'.yml';
        $app['translator']->addResource('yaml', $file, $app['locale']);

        // メニュー登録
        $app['config'] = $app->share($app->extend('config', function ($config) {
            $addNavi['id'] = 'maker';
            $addNavi['name'] = 'メーカー管理';
            $addNavi['url'] = 'admin_plugin_maker_index';

            $nav = $config['nav'];
            foreach ($nav as $key => $val) {
                if ('product' == $val['id']) {
                    $nav[$key]['child'][] = $addNavi;
                }
            }

            $config['nav'] = $nav;

            return $config;
        }));

        // initialize logger (for 3.0.0 - 3.0.8)
        if (!method_exists('Eccube\Application', 'getInstance')) {
            eccube_log_init($app);
        }
    }

    /**
     * @param BaseApplication $app
     */
    public function boot(BaseApplication $app)
    {
    }
}
