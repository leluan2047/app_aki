<?php
/* ActiveFusions 2015/10/05 9:44 */

namespace Plugin\MailTemplateEdit\ServiceProvider;

use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;

class MailTemplateEditServiceProvider implements ServiceProviderInterface{

	public function register(BaseApplication $app){

		// メールテンプレート追加用リポジトリ
		$app['eccube.plugin.mailtemplateedit.repository.mailtemplateedit'] = $app->share(function () use ($app) {
			return $app['orm.em']->getRepository('Plugin\MailTemplateEdit\Entity\MailTemplateEdit');
		});

		// 一覧
		$app->match('/' . $app["config"]["admin_route"] . '/setting/mailadd', '\\Plugin\\MailTemplateEdit\\Controller\\MailTemplateEditController::index')->bind('admin_mailtemplateedit');

		// 登録
		$app->match('/' . $app["config"]["admin_route"] . '/setting/mailadd/new', '\\Plugin\\MailTemplateEdit\\Controller\\MailTemplateEditController::edit')->bind('admin_mailtemplateedit_new');

		// 修正
		$app->match('/' . $app["config"]["admin_route"] . '/setting/mailadd/edit/{id}', '\\Plugin\\MailTemplateEdit\\Controller\\MailTemplateEditController::edit')
			->value('id', null)->assert('id', '\d+|')
			->bind('admin_mailtemplateedit_edit');

		// 削除
		$app->match('/' . $app["config"]["admin_route"] . '/setting/mailadd/{id}/delete', '\\Plugin\\MailTemplateEdit\\Controller\\MailTemplateEditController::delete')
			->value('id', null)->assert('id', '\d+|')
			->bind('admin_mailtemplateedit_delete');

		// 型登録
		$app['form.types'] = $app->share($app->extend('form.types', function ($types) use ($app) {
			$types[] = new \Plugin\MailTemplateEdit\Form\Type\MailTemplateEditType($app);
			return $types;
		}));

		// メッセージ登録
		$app['translator'] = $app->share($app->extend('translator', function ($translator, \Silex\Application $app) {
			$translator->addLoader('yaml', new \Symfony\Component\Translation\Loader\YamlFileLoader());
			$file = __DIR__ . '/../Resource/locale/message.' . $app['locale'] . '.yml';
			if(file_exists($file)) {
				$translator->addResource('yaml', $file, $app['locale']);
			}
			return $translator;
		}));

		// メニュー登録
		$app['config'] = $app->share($app->extend('config', function ($config) {
			$addNavi['id'] = "admin_mailtemplateedit";
			$addNavi['name'] = "メールテンプレート管理";
			$addNavi['url'] = "admin_mailtemplateedit";
			$nav = $config['nav'];
			foreach ($nav as $key => $val) {
				if("setting" == $val["id"]) {
					$nav[$key]['child'][] = $addNavi;
				}
			}
			$config['nav'] = $nav;
			return $config;
		}));
	}

	public function boot(BaseApplication $app){

	}
}
