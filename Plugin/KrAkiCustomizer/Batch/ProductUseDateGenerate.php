<?php 

namespace Plugin\KrAkiCustomizer\Batch;

use Plugin\KrAkiCustomizer\Common\Constants;
use Plugin\KrAkiCustomizer\Common\Utils;
use Plugin\KrAkiCustomizer\Entity\ProductUseDate;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Doctrine\ORM\Query\ResultSetMapping;

class ProductUseDateGenerate extends \Knp\Command\Command {

  protected function configure() {
    $this
        ->setName('product:usedate')
        ->setDescription('日付検索用テーブル更新')
        ->addArgument('name', InputArgument::OPTIONAL, '日付検索用テーブル更新');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    
      $app = $this -> getSilexApplication();
      $em = $app['orm.em'];
      $em -> getConnection() -> beginTransaction();
      try {
        $this -> transaction($app, $em);
        $em -> getConnection() -> commit();
      } catch (\Exception $e) {
        $em -> getConnection() -> rollback();
        throw $e;
      }
      
  }

  private function getSQL() {
    return <<<EOF
    SELECT
      odai.order_detail_additional_info_id,
    	pc.product_id,
    	odai.wear_date,
      odai.before_use_days as odr_before_use_days,
      odai.after_use_days as odr_after_use_days,
    	pud.before_use_days,
    	pud.after_use_days
    FROM
    	plg_order_detail_additional_info odai
    inner join
      dtb_order o
    on
      odai.order_id = o.order_id
    inner join
    	dtb_product_class pc
    on
    	odai.product_class_id = pc.product_class_id
    left outer join
    	plg_product_use_days pud
    on
    	pc.product_id = pud.product_id
    where
      odai.wear_date >= date_sub(CURRENT_DATE(), interval 10 day)
      and o.status <> 3
EOF;
  }

  protected function transaction($app, $em) {
    // delete
    $use_date_repo = $app['kr_aki_customizer.repository.product_use_date'];
    $em -> createQuery("delete from Plugin\\KrAkiCustomizer\\Entity\\ProductUseDate") -> execute();

    $use_days_repo = $app['kr_aki_customizer.repository.product_use_days'];
    $detail_repo = $app['kr_aki_customizer.repository.order_detail_additional_info'];

    $rsm = new ResultSetMapping();
    $rsm -> addScalarResult('order_detail_additional_info_id', 'order_detail_additional_info_id');
    $rsm -> addScalarResult('odr_before_use_days', 'odr_before_use_days');
    $rsm -> addScalarResult('odr_after_use_days', 'odr_after_use_days');
    $rsm -> addScalarResult('product_id', 'product_id');
    $rsm -> addScalarResult('wear_date', 'wear_date');
    $rsm -> addScalarResult('before_use_days', 'before_use_days');
    $rsm -> addScalarResult('after_use_days', 'after_use_days');

    $query = $em -> createNativeQuery($this -> getSQL(), $rsm);
    $results = $query -> getResult();
    if (empty($results)) {
      return;
    }
    foreach($results as $res) {
      $before_use_days = empty($res["odr_before_use_days"]) ? $res["before_use_days"] : $res["odr_before_use_days"];
      if (empty($before_use_days)) {
        $before_use_days = Constants::DEFAULT_BEFORE_USE_DAYS;
      }
      $before_use_days = intval($before_use_days);
      $after_use_days = empty($res["odr_after_use_days"]) ? $res["after_use_days"] : $res["odr_after_use_days"];
      if (empty($after_use_days)) {
        $after_use_days = Constants::DEFAULT_AFTER_USE_DAYS;
      }
      echo $res["order_detail_additional_info_id"] . "\n";
      $after_use_days = intval($after_use_days);

      $this -> regist($em, $res);
      
      for ($i = 1; $i <= $after_use_days; $i++) {
        $this -> regist($em, $res, $i);
      }
      for ($i = 1; $i <= $before_use_days; $i++) {
        $this -> regist($em, $res, $i * -1);
      }
    }
  }
  private function regist($em, $res, $i = 0) {
    $entity = new ProductUseDate();
    $entity -> setProductId($res["product_id"]);
    $wear_date = new \DateTime($res["wear_date"]);
    if ($i > 0) {
      $wear_date -> add(new \DateInterval('P' . $i . 'D'));
    } else if ($i < 0) {
      $wear_date -> sub(new \DateInterval('P' . ($i * -1) . 'D'));
    }
    $entity -> setUseDate($wear_date);
    $em -> persist($entity);
    $em -> flush();
  }
}
