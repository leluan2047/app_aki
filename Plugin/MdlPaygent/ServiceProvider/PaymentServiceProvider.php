<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */
namespace Plugin\MdlPaygent\ServiceProvider;

use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class PaymentServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app)
    {
        // Setting
        $app->match('/' . $app["config"]["admin_route"] . '/plugin/mdl_paygent/config', '\\Plugin\\MdlPaygent\\Controller\\ConfigController::edit')->bind('plugin_MdlPaygent_config');
        $app->match('/' . $app["config"]["admin_route"] . '/plugin/mdl_paygent/paygent_order_commit', '\\Plugin\\MdlPaygent\\Controller\\OrderController::index')->bind('paygent_order_commit');

        $app->match('/mdl_paygent/difference_notice', '\\Plugin\\MdlPaygent\\Batch\\PaygentDifferenceNotice::index')->bind('paygent_difference_notice');
        // route 1
        $app->match('/shopping/mdl_paygent', '\\Plugin\\MdlPaygent\\Controller\\PaygentController::index')->bind('mdl_paygent');
        $app->match('/shopping/mdl_paygent/back', '\\Plugin\\MdlPaygent\\Controller\\PaygentController::goBack')->bind('mdl_shopping_payment_back');

        $app['eccube.plugin.service.payment'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\PaymentService($app);
        });

        $app['eccube.plugin.service.plugin'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\PluginService($app);
        });
        // plugin“‡‘Î‰ž
        $app['eccube.plugin.service.system'] = $app->share(function () use ($app) {
            return new \Plugin\MdlPaygent\Service\SystemService($app);
        });

        $app['eccube.plugin.service.banknet'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\PaymentBankNetService($app);
        });

        $app['eccube.plugin.service.convenience'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\ConvenienceService($app);
        });

		$app['eccube.plugin.service.later'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\PaymentLaterService($app);
        });

		$app['eccube.plugin.service.career'] = $app->share(function () use ($app) {
			return new \Plugin\MdlPaygent\Service\PaymentCareerService($app);
		});

		$app['eccube.plugin.service.paygent'] = $app->share(function () use ($app) {
			return new \Plugin\MdlPaygent\Service\PaygentSettlementService($app);
		});

        $app['eccube.plugin.mdl_paygent.repository.mdl_plugin'] = $app->share(function () use ($app) {
        	return $app['orm.em']->getRepository('Plugin\MdlPaygent\Entity\MdlPlugin');
        });

        // get payment method Repository
        $app['eccube.plugin.mdl_paygent.repository.mdl_payment_method'] = $app->share(function () use ($app) {
                return $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlPaymentMethod');
        });

        	// get order method Repository
        $app['eccube.plugin.mdl_paygent.repository.mdl_order_payment'] = $app->share(function () use ($app) {
        	return $app['orm.em']->getRepository('\Plugin\MdlPaygent\Entity\MdlOrderPayment');
        });

        $app['eccube.plugin.service.atm'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\PaymentATMService($app);
        });

        $app['eccube.plugin.service.order'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\AdminOrderService($app);
        });

        $app['eccube.plugin.service.credit'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\PaymentCreditService($app);
        });
        
        $app['eccube.plugin.service.virtual.account'] = $app->share(function () use ($app) {
        	return new \Plugin\MdlPaygent\Service\PaymentVirtualAccountService($app);
        });

        // Form type
        $app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
        	$types[] = new \Plugin\MdlPaygent\Form\Type\ConfigType($app);
        	return $types;
        }));
    }

    public function boot(BaseApplication $app)
    {
    }
}
?>