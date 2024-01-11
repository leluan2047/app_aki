<?php
namespace Plugin\KrAkiCustomizer\ServiceProvider;

use Eccube\Application;
use Silex\Application as BaseApplication;
use Silex\ServiceProviderInterface;
use Plugin\KrAkiCustomizer\Event\KrAkiCustomizerBackendEvent;
use Plugin\KrAkiCustomizer\Event\KrAkiCustomizerFrontEvent;
use Plugin\KrAkiCustomizer\Batch\ProductUseDateGenerate;
use Symfony\Component\Yaml\Parser;

class KrAkiCustomizerServiceProvider implements ServiceProviderInterface
{
    public function register(BaseApplication $app) {

      // kraki.yml 登録
      $yaml = new Parser();
      $value = $yaml -> parse(file_get_contents(__DIR__ . "/../kraki.yml"));
      $app["kr_aki_customizer.settings"] = $value["settings"];

      $app['kr_aki_customizer.repository.order_detail_additional_info'] = $app -> share(function () use ($app) {
          return $app['orm.em']->getRepository('Plugin\KrAkiCustomizer\Entity\OrderDetailAdditionalInfo');
      });
      
      $app['kr_aki_customizer.repository.order_detail'] = $app -> share(function () use ($app) {
          return $app['orm.em']->getRepository('Plugin\KrAkiCustomizer\Entity\OrderDetail');
      });
      
      $app['kr_aki_customizer.repository.order'] = $app -> share(function () use ($app) {
          return $app['orm.em']->getRepository('Plugin\KrAkiCustomizer\Entity\Order');
      });

      $app['kr_aki_customizer.repository.product_use_days'] = $app -> share(function () use ($app) {
          return $app['orm.em']->getRepository('Plugin\KrAkiCustomizer\Entity\ProductUseDays');
      });
      
      $app['kr_aki_customizer.repository.product_use_date'] = $app -> share(function () use ($app) {
          return $app['orm.em']->getRepository('Plugin\KrAkiCustomizer\Entity\ProductUseDate');
      });

      $app['kr_aki_customizer.event.backend_event'] = $app->share(function () use ($app) {
          return new KrAkiCustomizerBackendEvent($app);
      });
      
      $app['kr_aki_customizer.event.front_event'] = $app->share(function () use ($app) {
          return new KrAkiCustomizerFrontEvent($app);
      });

      // Batch専用
      if (isset($app["console"])) {
        $app['console'] -> add(new ProductUseDateGenerate());
      }



      // Routing 登録
      $app->match('/api/KrAkiCustomizer/v1/calendar/{productId}','\Plugin\KrAkiCustomizer\Controller\v1\ApiController::calendar') -> bind('calendar') -> assert('productId', '\d+');
    }

    public function boot(BaseApplication $app) {
    }
}