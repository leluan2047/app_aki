<?php
namespace Plugin\KrAkiCustomizer\Event;

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\KrAkiCustomizer\Common\Constants;
use Plugin\KrAkiCustomizer\Common\Utils;
use Plugin\KrAkiCustomizer\Entity\OrderDetailAdditionalInfo;

class KrAkiCustomizerBackendEvent {

  private $app;

  public function __construct(Application $app) {
      $this->app = $app;
  }

  public function onAdminProductEditInitialize(EventArgs $event) {
    $builder = $event -> getArgument('builder');
    $builder -> add(
      Constants::PLG_BEFORE_USE_DAYS,
      'text',
      array(
        'required' => true,
        'label' => "前利用日数",
        'mapped' => false,
        'constraints' => array(
          new Assert\NotBlank(),
          new Assert\Regex("/\d/")
        )
      )
    );

    $builder -> add(
      Constants::PLG_AFTER_USE_DAYS,
      'text',
      array(
        'required' => true,
        'label' => "後利用日数",
        'mapped' => false,
        'constraints' => array(
          new Assert\NotBlank(),
          new Assert\Regex("/\d/")
        )
      )
    );

    // 設定値を取得
    $product   = $event -> getArgument('Product');
    $productId = $product -> getId();
    $useDays = $this -> app['kr_aki_customizer.repository.product_use_days'] -> findOneBy(array("product_id" => $productId));
    if ($useDays) {
      $builder -> get(Constants::PLG_BEFORE_USE_DAYS) -> setData($useDays -> getBeforeUseDays());
      $builder -> get(Constants::PLG_AFTER_USE_DAYS) -> setData($useDays -> getAfterUseDays());
    } else {
      // 初期値を設定
      $builder -> get(Constants::PLG_BEFORE_USE_DAYS) -> setData(Constants::DEFAULT_BEFORE_USE_DAYS);
      $builder -> get(Constants::PLG_AFTER_USE_DAYS) -> setData(Constants::DEFAULT_AFTER_USE_DAYS);
    }
  }

  public function onAdminProductEditComplete(EventArgs $event) {
    $form = $event->getArgument('form');
    $before = $form[Constants::PLG_BEFORE_USE_DAYS]->getData();
    $after  = $form[Constants::PLG_AFTER_USE_DAYS]->getData();

    // 設定値を取得
    $product   = $event -> getArgument('Product');
    $productId = $product -> getId();
    $useDays = $this -> app['kr_aki_customizer.repository.product_use_days'] -> findOneBy(array("product_id" => $productId));

    if (!$useDays) {
      $useDays = new \Plugin\KrAkiCustomizer\Entity\ProductUseDays();
      $useDays -> setProductId($productId);
    }
    if ($useDays -> getBeforeUseDays() != $before || $useDays -> getAfterUseDays() != $after) {
      $useDays -> setBeforeUseDays($before);
      $useDays -> setAfterUseDays($after);
      $em = $this -> app['orm.em'];
      $em -> persist($useDays);
      $em -> flush();
    }
  }

  public function onAdminOrderEditIndexInitialize(EventArgs $event) {
    // Handle Show Order type
    $TargetOrder = $event -> getArgument('TargetOrder');
    $order_detail_additional_info_list = $this -> app['kr_aki_customizer.repository.order_detail_additional_info'] -> findBy([
      'order_id' => $TargetOrder->getId()
    ]);
    if ($order_detail_additional_info_list && count($order_detail_additional_info_list)) {
      $TargetOrder->orderType = $order_detail_additional_info_list[0]->getOrderType();
    } else {
      $TargetOrder->orderType = null;
    }
  }

  public function onRenderAdminOrderEdit(TemplateEvent $event) {
    $parameters = $event -> getParameters();
    $orderId = $parameters["id"];
    $order_detail_additional_info_list = $this -> app['kr_aki_customizer.repository.order_detail_additional_info'] -> findBy(array("order_id" => $orderId));
    foreach ($order_detail_additional_info_list as $order_detail_additional_info) {
      if (empty($order_detail_additional_info -> getBodyType())) {
        $order_detail_additional_info -> setBodyType([]);
      } else {
        $order_detail_additional_info -> setBodyType(json_decode($order_detail_additional_info -> getBodyType()));
      }
    }
    
    $settings = $this -> app["kr_aki_customizer.settings"];

    $parameters += ["order_detail_additional_info_list" => $order_detail_additional_info_list];
    $parameters += ["body_height_list" => Utils::getBodyHeight($settings)];
    $parameters += ["foot_size_list" => Utils::getFootSize($settings)];
    $parameters += ["decade_list" => Utils::getDecade($settings)];
    $parameters += ["body_type_list" => Utils::getBodyType($settings)];
    $parameters += ["purpose_list" => Utils::getPurpose($settings)];
    $parameters += ["secure_pack_list" => Utils::getSecurePack($settings)];
    $parameters += ["photo_plan_list" => Utils::getPhotoPlan($settings)];
    $parameters += ["hair_make_list" => Utils::getHairMake($settings)];
    $parameters += ["visit_store_list" => Utils::getVisitStore($settings)];
    $parameters += ["time_departure_list" => Utils::getTimeDeparture($settings)];

    $order = $parameters["Order"];
    $order_details = $order -> getOrderDetails();
    $not_available_dates_list = array();
    $deposit_code = $settings["deposit"]["product_code"];
    $deposit_price = intval($settings["deposit"]["price"]);
    $secure_pack_price = intval($settings["secure_pack"]["price"]);

    $is_deposit = false;

    $actual_price = 0;
    foreach ($order_details as $order_detail) {
      $productId = $order_detail -> getProduct() -> getId();
      $productClassId = $order_detail -> getProductClass() -> getId();
      if ($deposit_code == $order_detail -> getProductClass() -> getCode()) {
        $is_deposit = true;
      } else {
        $actual_price += $order_detail -> getProductClass() -> getPrice02() * $order_detail -> getQuantity();
        $actual_price += $order_detail -> getProductClass() -> getPrice02() * $order_detail -> getQuantity() * $order_detail -> getTaxRate() / 100; //tax plus
      }
      
      //add new order only
      if (strpos($_SERVER['REQUEST_URI'], 'new') !== false) {
        $not_available_dates = $this -> getNotAvailableDates($productId, $productClassId);
        $not_available_dates_list[$productId] = $not_available_dates;
      }
      //END: Add new order only
      
      if (empty($order_detail_additional_info_list)) {
        continue;
      }
      foreach ($order_detail_additional_info_list as $order_detail_additional_info) {
        if ($order_detail_additional_info -> getProductClassId() == $productClassId) {
          $aid = $order_detail_additional_info -> getOrderDetailAdditionalInfoId();
          $not_available_dates = $this -> getNotAvailableDates($productId, $productClassId, $aid);
          $not_available_dates_list[$order_detail -> getId()] = $not_available_dates;
        }
      }
    }
    
    $parameters += ["is_deposit" => $is_deposit];
    $parameters += ["actual_price" => $actual_price];
    $parameters += ["pay_in_store" => $actual_price - $deposit_price];
    $parameters += ["deposit_price" => $deposit_price];
    $parameters += ["not_available_dates_list" => $not_available_dates_list];
    ///////////

    $event -> setParameters($parameters);
  }

  function getNotAvailableDates($productId, $productClassId, $aid = null) {

    // 利用不可能な日を出す


    $useDays = $this -> app['kr_aki_customizer.repository.product_use_days'] -> findOneBy(array("product_id" => $productId));

    $beforeUseDays = Constants::DEFAULT_BEFORE_USE_DAYS;
    $afterUseDays = Constants::DEFAULT_AFTER_USE_DAYS;
    if ($useDays) {
      $beforeUseDays = $useDays -> getBeforeUseDays();
      $afterUseDays = $useDays -> getAfterUseDays();
    }
    $today = date("Y-m-d");

    $app = $this -> app;

    $em = $app['orm.em'];

    $where = 'odai.product_class_id = :product_class_id and odai.wear_date >= :wear_date';
    if (!empty($aid)) {
      // 自身のIDを除く
      $where .= "  and odai.order_detail_additional_info_id <> :order_detail_additional_info_id";
    }
    $query =
      $em -> createQueryBuilder()
        -> select("odai")
        -> from("\\Plugin\\KrAkiCustomizer\\Entity\\OrderDetailAdditionalInfo", "odai")
        -> where($where)
        -> setParameter('product_class_id', $productClassId)
        -> setParameter('wear_date', $today);
    if (!empty($aid)) {
      $query -> setParameter('order_detail_additional_info_id', $aid);
    }

    $query = $query -> getQuery();
    $orderDetailAdditionalInfoList = $query -> getResult();

    $not_available_dates = array();
    foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
      $wearDate = $orderDetailAdditionalInfo -> getWearDate();
      $not_available_dates[] = $wearDate;
      for ($i = $beforeUseDays; $i > 0; $i--) {
        $tmpDate = strtotime($wearDate . " - {$i} day");
        $not_available_dates[] = date("Y-m-d", $tmpDate);
      }
      for ($i = 1; $i <= $afterUseDays; $i++) {
        $tmpDate = strtotime($wearDate . " + {$i} day");
        $not_available_dates[] = date("Y-m-d", $tmpDate);
      }
    }
    $not_available_dates = array_unique($not_available_dates);
    sort($not_available_dates);
    return $not_available_dates;
  }


  public function onAdminOrderEditIndexComplete(EventArgs $event) {
    $orderDetailAdditionalInfoList = $_POST["OrderDetailAdditionalInfo"];
    if (empty($orderDetailAdditionalInfoList)) {
      return;
    }
    foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
      if(!empty($orderDetailAdditionalInfo["order_detail_additional_info_id"])){
        $id = $orderDetailAdditionalInfo["order_detail_additional_info_id"];
        $entity = $this->app['kr_aki_customizer.repository.order_detail_additional_info']->findOneBy(array(
            'order_detail_additional_info_id' => $id
        ));   
        if (empty($entity)) {
                continue;
        }
        $order_id = $orderDetailAdditionalInfo["order_id"];
        $order_detail_id = $orderDetailAdditionalInfo["order_detail_id"];
        $product_class_id = $orderDetailAdditionalInfo["product_class_id"];
      } else {
        //INSERT NEW ADDITIONAL INFO TO DATABASE
        $order = $this -> app['kr_aki_customizer.repository.order']->findOneBy(array(), array('order_id' => 'DESC'));
        if (empty($order)) {
                continue;
        }
        $order_detail = $this -> app['kr_aki_customizer.repository.order_detail']->findOneBy(
                array(
                    'order_id' => $order['order_id'],
                    'product_id' => $orderDetailAdditionalInfo['product_id'],
                )
        );
        if (empty($order_detail)) {
                continue;
        }
        $order_id = $order_detail['order_id'];
        $order_detail_id = $order_detail['order_detail_id'];
        $product_class_id = $order_detail["product_class_id"];
        $entity = new \Plugin\KrAkiCustomizer\Entity\OrderDetailAdditionalInfo();
      }
      $secure_pack = !empty($orderDetailAdditionalInfo["secure_pack"]) ? $orderDetailAdditionalInfo["secure_pack"] : NULL;
      
      $entity -> setOrderId($order_id);
      $entity -> setOrderDetailId($order_detail_id);
      $entity -> setProductClassId($product_class_id);
      $entity -> setOrderType($orderDetailAdditionalInfo["order_type"]);
      $entity -> setBodyHeight($orderDetailAdditionalInfo["body_height"]);
      $entity -> setFootSize($orderDetailAdditionalInfo["foot_size"]);
      $entity -> setDecade($orderDetailAdditionalInfo["decade"]);
      $entity -> setWearDate($orderDetailAdditionalInfo["wear_date"]);
      $entity -> setPurpose($orderDetailAdditionalInfo["purpose"]);

      
      $entity -> setSecurePack($secure_pack);
      $entity -> setNeedPhoto($orderDetailAdditionalInfo["need_photo"]);
      $entity -> setNeedHairMake($orderDetailAdditionalInfo["need_hair_make"]);
      $entity -> setVisitStore($orderDetailAdditionalInfo["visit_store"]);
/* 20170601 非表示
      $entity -> setDateVisit($orderDetailAdditionalInfo["date_visit"]);
*/
      $entity -> setTimeDeparture($orderDetailAdditionalInfo["time_departure"]);

      if (!empty($orderDetailAdditionalInfo["body_type"])) {
        $entity -> setBodyType(json_encode($orderDetailAdditionalInfo["body_type"]));
      } else {
        $entity -> setBodyType(null);
      }
      $beforeUseDays = !empty($orderDetailAdditionalInfo["before_use_days"]) ? $orderDetailAdditionalInfo["before_use_days"] : null;
      $afterUseDays = !empty($orderDetailAdditionalInfo["after_use_days"]) ? $orderDetailAdditionalInfo["after_use_days"] : null;
      if (!is_numeric($beforeUseDays) || ((int) $beforeUseDays) < 0) {
        $beforeUseDays = null;
      }
      if (!is_numeric($afterUseDays) || ((int) $afterUseDays) < 0) {
        $afterUseDays = null;
      }
      $entity -> setBeforeUseDays($beforeUseDays);
      $entity -> setAfterUseDays($afterUseDays);
      $em = $this -> app['orm.em'];
      $em -> persist($entity);
      $em -> flush();
    }
  }
}