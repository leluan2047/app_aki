<?php
namespace Plugin\KrAkiCustomizer\Event;

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\Validator\Constraints as Assert;
use Plugin\KrAkiCustomizer\Common\Constants;
use Plugin\KrAkiCustomizer\Common\Utils;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\ResultSetMapping;

class KrAkiCustomizerFrontEvent {

  /** @var \Eccube\Application $app */
  private $app;

  public function __construct(Application $app) {
    $this -> app = $app;
  }

  /**
   * , Formの拡張
   * 商品詳細表示時の処理
   * 「宅配レンタル」、「来店着付け」を保持する用のフォームを追加
   */
  public function onFrontProductDetailInitialize(EventArgs $event) {
    /** custom01 */
    $builder = $event -> getArgument('builder');
    $settings = $this->app["kr_aki_customizer.settings"];
    // 来店着付け OR 宅配レンタルの隠し項目
    $builder -> add(
      Constants::PLG_ORDER_TYPE_NAME,
      'hidden',
      array(
        'required' => true,
        'label'  => false,
        'mapped' => true,
        'attr' => array('value' => 'visit'),
        'constraints' => array(
          new Assert\NotBlank())));

    /** custom03 フォーム再構築 */
    // 着用日
    $builder -> add(
      Constants::PLG_WEAR_DATE_NAME,
      'text',
      array(
        'required' => true,
        'label' => "着用日を選択して下さい",
        'mapped' => true,
        'read_only' => true,
        'constraints' => array(
          new Assert\NotBlank(),
          new Assert\Date())));

    // 用途
    $builder -> add(
      Constants::PLG_PURPOSE_NAME,
      'choice',
      array(
          'required' => true,
          'label' => "用途：",
          'mapped' => true,
          'choices' => Utils::getPurpose($settings),
          'constraints' => array(
            new Assert\NotBlank())));

    // 身長
    $builder -> add(
      Constants::PLG_BODY_HEIGHT_NAME,
      'choice',
      array(
        'required' => true,
        'label' => "身長：",
        'mapped' => true,
        'choices' => Utils::getBodyHeight($settings),
        'constraints' => array(
          new Assert\NotBlank())));

    // 足のサイズ
    $builder -> add(
      Constants::PLG_FOOT_SIZE_NAME,
      'choice',
      array(
        'required' => true, 
        'label' => "足のサイズ：",
        'mapped' => true,
        'choices' => Utils::getFootSize($settings),
        'constraints' => array(
          new Assert\NotBlank())));

    // 年代
    $builder -> add(
      Constants::PLG_DECADE,
      'choice',
      array(
        'required' => true, 
        'label' => "年代：",
        'mapped' => true,
        'choices' => Utils::getDecade($settings),
        'constraints' => array(
          new Assert\NotBlank())));

    // 体型
    $builder -> add(
      Constants::PLG_BODY_TYPE_NAME,
      'choice',
      array(
        'required' => false,
        'label' => "体型：",
        'mapped' => true,
        'choices' => Utils::getBodyType($settings),
        'expanded' => true, 'multiple' => true));

    // 安心パック
    $builder -> add(
      Constants::PLG_SECURE_PACK_NAME,
      'choice',
      array(
        'required' => false,
        'label' => "安心パック",
        'mapped' => true,
        'choices' => Utils::getSecurePack($settings),
        'expanded' => true, 'multiple' => true));

    /**
     * 来店着付けの場合フォームの項目追加
     */
    if ("visit" == $event -> getRequest() -> request -> get("plg_order_type")) {
        // フォト
        $builder -> add(
          Constants::PLG_NEED_PHOTO,
          'choice',
          array(
            'required' => false,
            'label'  => "フォト",
            'mapped' => true,
            'choices' => Utils::getPhotoPlan($settings)
          )
        );
        // ヘアメイク有り無し
        $builder -> add(
          Constants::PLG_NEED_HAIR_MAKE,
          'choice',
          array(
            'required' => true,
            'label'  => "ヘア・メイクのご希望の有無",
            'mapped' => true,
            'choices' => Utils::getHairMake($settings),
            'constraints' => array(
              new Assert\NotBlank())));

        // 来店希望店舗
        $builder -> add(
          Constants::PLG_VISIT_STORE_NAME,
          'choice',
          array(
            'required' => false,
            'label'  => "着付けご来店希望店舗",
            'mapped' => true,
            'choices' => Utils::getVisitStore($settings)
          )
        );

        // 来店日
/* 20170601 非表示
        $builder -> add(
          Constants::PLG_DATE_VISIT_NAME,
          'text',
          array(
            'required' => true,
            'label' => "ご来店日",
            'mapped' => true,
            'read_only' => true,
            'constraints' => array(
              new Assert\NotBlank(),
              new Assert\Date())));
*/

        // ご出発予定時間
        $builder -> add(
          Constants::PLG_TIME_DEPARTURE_NAME,
          'choice',
          array(
            'required' => true,
            'label' => "ご出発予定時間",
            'mapped' => true,
            'choices' => Utils::getTimeDeparture($settings),
            'constraints' => array(
              new Assert\NotBlank(),
              // new Assert\Time()
            )));
    }
  }

  /**
   * custom01
   * カート投入後の処理
   * セッションに付与情報を保持する
   */
  public function onFrontProductDetailComplete(EventArgs $event) {
    $session = $this -> app["session"];
    $orderDetailAdditionalInfoList = [];
    $form = $event -> getArgument("form");
    $data = $form -> getData();
    $productClassId = $data["product_class_id"];

    if ($session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
      foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
        if ($orderDetailAdditionalInfo -> getProductClassId() == $productClassId) {
          // すでにセッションに同一商品が入っている場合何もしない
          // 多分そういったことは無い
          return;
        }
      }
    }

    $orderDetailAdditionalInfo = new \Plugin\KrAkiCustomizer\Entity\OrderDetailAdditionalInfo();
    $orderDetailAdditionalInfo -> setProductClassId($productClassId);
    $orderDetailAdditionalInfo -> setOrderType($data[Constants::PLG_ORDER_TYPE_NAME]);
    $orderDetailAdditionalInfo -> setPurpose($data[Constants::PLG_PURPOSE_NAME]);
    $orderDetailAdditionalInfo -> setBodyHeight($data[Constants::PLG_BODY_HEIGHT_NAME]);
    $orderDetailAdditionalInfo -> setFootSize($data[Constants::PLG_FOOT_SIZE_NAME]);
    $orderDetailAdditionalInfo -> setDecade($data[Constants::PLG_DECADE]);
    $orderDetailAdditionalInfo -> setWearDate($data[Constants::PLG_WEAR_DATE_NAME]);
    $orderDetailAdditionalInfo -> setBodyType($data[Constants::PLG_BODY_TYPE_NAME]);
    $orderDetailAdditionalInfo -> setSecurePack($data[Constants::PLG_SECURE_PACK_NAME]);
    if ($data[Constants::PLG_ORDER_TYPE_NAME] == "visit") {
      $orderDetailAdditionalInfo -> setNeedHairMake($data[Constants::PLG_NEED_HAIR_MAKE]);
/* 20170601 非表示
      $orderDetailAdditionalInfo -> setDateVisit($data[Constants::PLG_DATE_VISIT_NAME]);
*/
      $orderDetailAdditionalInfo -> setTimeDeparture($data[Constants::PLG_TIME_DEPARTURE_NAME]);
      $orderDetailAdditionalInfo -> setVisitStore($data[Constants::PLG_VISIT_STORE_NAME]);
      $orderDetailAdditionalInfo -> setNeedPhoto($data[Constants::PLG_NEED_PHOTO]);
    }

    $orderDetailAdditionalInfoList[] = $orderDetailAdditionalInfo;

    $session -> set(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST, $orderDetailAdditionalInfoList);
    
    // FIXME 安心パック
    $settings = $this->app["kr_aki_customizer.settings"];
    if (!empty($data[Constants::PLG_SECURE_PACK_NAME])) {
      $secure_pack_product_code = $settings["secure_pack"]["product_code"];
      $secure_pack = $this->app['eccube.repository.product_class']->findOneBy(array(
          'code' => $secure_pack_product_code
      ));
      $this -> app['eccube.service.cart'] -> addProduct($secure_pack -> getId(), 1) -> save();
    }
  }

  /**
   * 来店着付けの場合の内金処理
   */
  private function doDepositProcess($session, $productClassId) {

    if (!$session -> has("cart")) {
      return;
    }

    // セッションの修正
    $cart = $session -> get("cart");

    foreach ($cart["CartItems"] as $cartItem) {
      if ($cartItem -> getClassId() != $productClassId) {
        continue;
      }
      if ($cartItem -> getPrice() > Constants::DEPOSIT_PRICE_WITHOUT_TAX) {
        $cartItem -> setPrice(Constants::DEPOSIT_PRICE_WITHOUT_TAX);
      }
      $cart["CartItems"] = $cartItem;
    }
    $session -> set("cart", $cart);
  }

  public function updateOrder() {

    $session = $this -> app["session"];
    if (!$session -> has("cart") || !$session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      return;
    }
    $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);

    $targetProductClassIdList = array();
    foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
      if ($orderDetailAdditionalInfo -> getOrderType() == "visit") {
        $targetProductClassIdList[] = $orderDetailAdditionalInfo -> getProductClassId();
      }
    }

    if (empty($targetProductClassIdList)) {
      return;
    }

    $cart = $session -> get("cart");
    $preOrderId  = $cart["pre_order_id"];
    $Order = $this->app['eccube.repository.order']->findOneBy(array(
        'pre_order_id' => $preOrderId,
        'OrderStatus' => $this->app['config']['order_processing'],
    ));
    $em = $this -> app['orm.em'];
    $details = $Order -> getOrderDetails();


    $consist = false;
    
    $settings = $this->app["kr_aki_customizer.settings"];
    $deposit_code = $settings["deposit"]["product_code"];

    foreach ($details as $detail) {
      if ($detail -> getProductClass() -> getCode() == $deposit_code) {
        continue;
      }
      $detail -> setPrice(0);
      $em -> persist($detail);
      $em -> flush();
      $em->getConnection()->commit();
      $consist = true;
    }
    if (!$consist) {
      return;
    }
    $orderService = $this -> app["eccube.service.order"];
    $shoppingService = $this -> app["eccube.service.shopping"];
    $subTotal = $orderService -> getSubTotal($Order);
    $tax = $orderService->getTotalTax($Order);
    $Order->setDeliveryFeeTotal($shoppingService->getShippingDeliveryFeeTotal($Order->getShippings()));
    $Order->setSubTotal($subTotal);
    $Order->setTax($tax);
    $shoppingService->calculatePrice($Order);

    $em = $this -> app['orm.em'];
    $em -> persist($Order);
    $em -> flush();
    $em->getConnection()->commit();
    return $Order;
  }

  /**
   * 購入確認画面
   */
  public function onFrontShoppingIndexInitialize(EventArgs $event) {
    // // XXX ペイジェントの絡みでやむなくsrc/Eccubeに以下の処理を移行しないといけなくなったのでコメントアウト
    // /** custom04 */
    // $builder = $event -> getArgument('builder');
    // 
    // $session = $this -> app["session"];
    // $orderType = "";
    // if ($session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
    //   $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
    //   $orderType = $orderDetailAdditionalInfoList[0] -> getOrderType();
    // }
    // 
    // // ※来店着付けで商品単価（最大値）が2万円以上の場合、以下のラジオボタンを表示する（択一）
    // if ($orderType == "visit") {
    //   $settings = $this->app["kr_aki_customizer.settings"];
    //   $threshold = $settings["deposit"]["threshold"];
    //   $cart = $session -> get("cart");
    //   $depositable = false;
    //   foreach ($cart["CartItems"] as $cartItem) {
    //     if ($cartItem -> getPrice() > intval($threshold)) {
    //       $depositable = true;
    //       break;
    //     }
    //   }
    //   if ($depositable) {
    //     $settings = $this->app["kr_aki_customizer.settings"];
    //     // 内金 or 前払い
    //     $builder -> add(
    //       Constants::PLG_PAY_METHOD,
    //       'choice',
    //       array(
    //         'required' => true,
    //         'label'  => "お支払い方法",
    //         'choices' => Utils::getPayMethod($settings),
    //         'constraints' => array(
    //           new Assert\NotBlank())));
    //   }
    //   // $Order = $event -> getArgument('Order');
    //   // $Order = $this -> updateOrder();
    //   // $builder -> remove("shippings");
    // }
  }

  /**
   * 購入確認画面
   */
  public function onFrontShoppingConfirmInitialize(EventArgs $event) {
    $this -> onFrontShoppingIndexInitialize($event);
  }

  /**
   *
   */
  public function onFrontShoppingPaymentInitialize(EventArgs $event) {
    $this -> onFrontShoppingIndexInitialize($event);
  }

  /**
   * カートから商品削除時。
   * オーダータイプも削除する。
   */
  public function onFrontCartRemoveComplete(EventArgs $event) {
    $productClassId = $event -> getArgument("productClassId");
    $this -> removeOrderType($productClassId);
  }

  /**
   * カートから商品数量減時。
   * 対象商品が０の場合オーダータイプも削除する。
   */
  public function onFrontCartDownComplete(EventArgs $event) {
    $session = $this -> app["session"];
    $productClassId = $event -> getArgument("productClassId");
    if ($session -> has("cart")) {
      $cart = $session -> get("cart");
      foreach ($cart["CartItems"] as $cartItem) {
        if ($cartItem -> getClassId() == $productClassId) {
          return;
        }
      }
    }
    $this -> removeOrderType($productClassId);
  }

  /**
   * セッションから引数の$productClassIdのオーダータイプを削除します。
   */
  private function removeOrderType($productClassId) {
    $session = $this -> app["session"];
    if ($session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      $cart = $session -> get("cart");
      $secure_pack = $this->app["kr_aki_customizer.settings"]["secure_pack"]["product_code"];
      $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
      $recreate = [];
      foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
        if ($orderDetailAdditionalInfo -> getProductClassId() != $productClassId) {
          $recreate[] = $orderDetailAdditionalInfo;
        } else if (!empty($orderDetailAdditionalInfo -> getSecurePack()) && $session -> has("cart")) {
          foreach ($cart -> getCartItems() as $cartItem) {
            $code = $cartItem -> getObject() -> getCode();
            if ($code === $secure_pack) {
              $secure_pack_class_id = $cartItem -> getClassId();
              $this -> app['eccube.service.cart'] -> downProductQuantity($secure_pack_class_id)->save();
            }
          }
        }
      }
      $session -> set(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST, $recreate);
    }
  }

  public function onRenderProductDetail(TemplateEvent $event) {
    $parameters = $event -> getParameters();
    $product = $parameters["Product"];

    $productClassId = $product -> getProductClasses()[0] -> getId();

    // 利用不可能な日を出す
    $productId = $product -> getId();

    $useDays = $this -> app['kr_aki_customizer.repository.product_use_days'] -> findOneBy(array("product_id" => $productId));

    $beforeUseDays = Constants::DEFAULT_BEFORE_USE_DAYS;
    $afterUseDays = Constants::DEFAULT_AFTER_USE_DAYS;
    if ($useDays) {
      $beforeUseDays = $useDays -> getBeforeUseDays();
      $afterUseDays = $useDays -> getAfterUseDays();
    }
    $today = date("Y-m-d", strtotime("-10 days"));

    $app = $this -> app;

    $em = $app['orm.em'];

    $query =
      $em -> createQueryBuilder()
        -> select("odai")
        -> from("\\Plugin\\KrAkiCustomizer\\Entity\\OrderDetailAdditionalInfo", "odai")
        -> innerJoin('\\Eccube\\Entity\\Order', 'odr')
        -> where('odai.product_class_id = :product_class_id and odai.wear_date >= :wear_date')
        -> andWhere('odai.order_id = odr.id')
        -> andWhere('odr.OrderStatus <> 3')
        -> setParameter('product_class_id', $productClassId)
        -> setParameter('wear_date', $today)
        -> getQuery();

    $orderDetailAdditionalInfoList = $query -> getResult();
    $not_available_dates = array();
    foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
      if (!empty($orderDetailAdditionalInfo -> getBeforeUseDays())) {
        $beforeUseDays = $orderDetailAdditionalInfo -> getBeforeUseDays();
      }
      if (!empty($orderDetailAdditionalInfo -> getAfterUseDays())) {
        $afterUseDays = $orderDetailAdditionalInfo -> getAfterUseDays();
      }
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

    /*
    sort($not_available_dates);
    $add_dates = [];
    for ($j = 0; $j < count($not_available_dates); $j++) {
      $current  = $not_available_dates[$j];
      for ($i = $beforeUseDays; $i > 0; $i--) {
        $tmpDate = strtotime($current . " - {$i} day");
        $add_dates[] = date("Y-m-d", $tmpDate);
      }
      for ($i = 1; $i <= $afterUseDays; $i++) {
        $tmpDate = strtotime($current . " + {$i} day");
        $add_dates[] = date("Y-m-d", $tmpDate);
      }
    }
    $not_available_dates = $not_available_dates + $add_dates;
    */
    $not_available_dates = array_unique($not_available_dates);
    sort($not_available_dates);
    $parameters += ["not_available_dates" => $not_available_dates];

    // 当月を出す
    $time = time();
    $today = date("d", $time);
    $year  = date("Y", $time);
    $month = date("m", $time);
    $end_of_month = date("t", $time);
    $start_week   = date('w', strtotime("$year/$month/1"));

    $next_month_time       = strtotime("$year/$month/1 + 1 month");
    $next_month_year       = date("Y", $next_month_time);
    $next_month            = date("m", $next_month_time);
    $next_end_of_month     = date("t", $next_month_time);
    $next_month_start_week = date('w', $next_month_time);

    $parameters += ["year" => intval($year)];
    $parameters += ["current_month" => intval($month)];
    $parameters += ["today" => intval($today)];
    $parameters += ["current_end_of_month" => intval($end_of_month)];
    $parameters += ["start_week" => intval($start_week)];
    
    $parameters += ["next_month_year" => intval($next_month_year)];
    $parameters += ["next_month" => intval($next_month)];
    $parameters += ["next_end_of_month" => intval($next_end_of_month)];
    $parameters += ["next_month_start_week" => intval($next_month_start_week)];

    $parameters += ["product_id" => $productId];

    /**
     * すでにカートに商品が入っている場合、同じ用途以外選択できない。
     */
    $session = $this -> app["session"];
    $parameters += ["order_type" => ""];
    if ($session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
      foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
        $parameters["order_type"] = $orderDetailAdditionalInfo -> getOrderType();
        break;
      }
    }

    /**
     * "日付検索から来た場合「ご利用日選択 」、「着付けご来店希望店舗 」に検索した日付をデフォルトで設定しておく。（日付は変更可能）"
     */
    $searchDate = "";
    if ($session -> has(Constants::SEARCH_DATE)) {
      $searchDate = $session -> get(Constants::SEARCH_DATE);
    }
    $parameters += [Constants::SEARCH_DATE => $searchDate];
    $settings = $this->app["kr_aki_customizer.settings"];
    $form_settings = [
      "order_type"    => Utils::getOrderType($settings),
      "purpose"       => Utils::getPurpose($settings),
      "foot_size"     => Utils::getFootSize($settings),
      "body_height"   => Utils::getBodyHeight($settings),
      "body_type"     => Utils::getBodyType($settings),
      "hair_make"     => Utils::getHairMake($settings),
      "time_departure" => Utils::getTimeDeparture($settings),
      "visit_store"   => Utils::getVisitStore($settings),
      "secure_pack"   => Utils::getSecurePack($settings),
      "photo_plan"    => Utils::getPhotoPlan($settings),
      "decade"    => Utils::getDecade($settings)
    ];
    $parameters += [Constants::FORM_SETTINGS => $form_settings];
    $event -> setParameters($parameters);
  }

  /**
   * 購入確認画面でテンプレートに表示する不要情報のパラメーターを差し込み。
   */
  public function onRenderShoppingIndex(TemplateEvent $event) {
    $parameters = $event -> getParameters();
    $session = $this -> app["session"];
    $orderType = "";
    if ($session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
      $orderType = $orderDetailAdditionalInfoList[0] -> getOrderType();
      $parameters += ['orderType' => $orderType];
      $parameters += ['OrderDetailAdditionalInfoList' => $orderDetailAdditionalInfoList];
    }

    $settings    = $this->app["kr_aki_customizer.settings"];
    $parameters += ["secure_pack_code" => $settings["secure_pack"]["product_code"]];
    $parameters += ["secure_pack_price" => intval($settings["secure_pack"]["price"])];
    $parameters += ["deposit_price" => intval($settings["deposit"]["price"])];
    $form_settings = ["pay_method" => Utils::getPayMethod($settings)];
    $parameters += [Constants::FORM_SETTINGS => $form_settings];
    
    if ($orderType == "visit") {
      // 20000以上の商品がある場合、内金支払いが出来る
      $parameters["depositable"] = false;
      $threshold = $settings["deposit"]["threshold"];
      $cart = $session -> get("cart");
      foreach ($cart["CartItems"] as $cartItem) {
        if ($cartItem -> getPrice() > intval($threshold)) {
          $parameters["depositable"] = true;
          break;
        }
      }
    }
    $event -> setParameters($parameters);
    
  }

  public function onControllerShoppingConfirmBefore($event) {
    
    // 内金処理
    $pay_method = $event -> getRequest() -> request -> get("shopping")["plg_pay_method"];
    $settings = $this -> app["kr_aki_customizer.settings"];
    $session = $this -> app["session"];
    $cart = $session -> get("cart");
    $preOrderId  = $cart["pre_order_id"];
    $Order = $this->app['eccube.repository.order']->findOneBy(array(
        'pre_order_id' => $preOrderId,
        'OrderStatus' => $this->app['config']['order_processing'],
    ));
    $session -> set("KrAkiCustomizer.orderId", $Order["id"]);
    
    //Add point parameters to mail template
    $Customer = $Order->getCustomer();
    if(!empty($Customer)){
        $usePoint = $this->app['eccube.plugin.point.repository.point']->getLatestPreUsePoint($Order);
        $usePoint = abs($usePoint);
        $calculator = $this->app['eccube.plugin.point.calculate.helper.factory'];
        $calculator->setUsePoint($usePoint); 
        $calculator->addEntity('Order', $Order);
        $calculator->addEntity('Customer', $Customer);
        $addPoint = $calculator->getAddPointByOrder();
        if (is_null($addPoint)) {
                $addPoint = 0;
        }
        $currentPoint = $calculator->getPoint();
        if (is_null($currentPoint)) {
                $currentPoint = 0;
        }
        
        $Order->hasPoint = true;
        $Order->usePoint = $usePoint;
        $Order->addPoint = $addPoint;
        $Order->currentPoint = $currentPoint;
    } else {
        $Order->hasPoint = false; 
    }
    
    //Add orderType parameter to mail template
    $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
    $orderType = $orderDetailAdditionalInfoList[0] -> getOrderType();
    $Order->orderDetailInfo = $orderDetailAdditionalInfoList;
    $Order->orderType = $orderType;
    
    $em = $this -> app['orm.em'];
    $em -> persist($Order);
    $em -> flush();
    $em->getConnection()->commit();
    
    if ("prepay" == $pay_method) {
      // 全額前払
    } else if ("deposit" == $pay_method) {
      //Check deposit is saved in DB
      $OrderDetailDeposit = $this->app['kr_aki_customizer.repository.order_detail']->findOneBy(array(
        'order_id' => $Order["id"],
        'product_code' => 'deposit',
      ));  
      if(!$OrderDetailDeposit){
        // 内金処理

        // 内金取得
        $deposit_code = $settings["deposit"]["product_code"];
        $ProductClass = $this->app['eccube.repository.product_class']->findOneBy(array(
            'code' => $deposit_code
        ));
        $Product = $ProductClass -> getProduct();
        // 受注明細情報を作成
        $OrderDetail = new \Eccube\Entity\OrderDetail();
        $TaxRule = $this->app['eccube.repository.tax_rule']->getByRule($Product, $ProductClass);
        $OrderDetail -> setProduct($Product)
            ->setProductClass($ProductClass)
            ->setProductName($Product->getName())
            ->setProductCode($ProductClass->getCode())
            ->setPrice($ProductClass->getPrice02())
            ->setQuantity(1)
            ->setTaxRule($TaxRule->getId())
            ->setTaxRate($TaxRule->getTaxRate());

        $ClassCategory1 = $ProductClass->getClassCategory1();
        if (!is_null($ClassCategory1)) {
            $OrderDetail->setClasscategoryName1($ClassCategory1->getName());
            $OrderDetail->setClassName1($ClassCategory1->getClassName()->getName());
        }
        $ClassCategory2 = $ProductClass->getClassCategory2();
        if (!is_null($ClassCategory2)) {
            $OrderDetail->setClasscategoryName2($ClassCategory2->getName());
            $OrderDetail->setClassName2($ClassCategory2->getClassName()->getName());
        }
        $em = $this -> app['orm.em'];
        $em -> persist($OrderDetail);
        $OrderDetail->setOrder($Order);
        $Order -> addOrderDetail($OrderDetail);
        $em -> persist($Order);
        $em -> flush();
        $this -> updateOrder();
        $em->getConnection()->commit();
      }
    }
  }
  
  public function onFrontShoppingCompleteInitialize(EventArgs $event) {
    $em = $this -> app['orm.em'];
    $em -> getConnection() -> beginTransaction();
    $session = $this -> app["session"];
    if (!$session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      return;
    }
    $orderId = $session -> get("KrAkiCustomizer.orderId");
    $order = $this -> app['eccube.repository.order'] -> find($orderId);
    // $order = $this -> app['eccube.service.shopping'] -> getOrder($this -> app['config']['order_processing']);
    $orderDetails = $order -> getOrderDetails();
    $form = $this -> app['eccube.service.shopping'] -> getShippingForm($order);
    $data = $form -> getData();

    $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
    $orderType = $orderDetailAdditionalInfoList[0] -> getOrderType();

    foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
      foreach ($orderDetails as $orderDetail) {
        $detailProductClassId = $orderDetail -> getProductClass() -> getId();
        if ($detailProductClassId == $orderDetailAdditionalInfo -> getProductClassId()) {
          $orderDetailAdditionalInfo -> setOrderDetailId($orderDetail -> getId());
          $orderDetailAdditionalInfo -> setOrderId($order -> getId());
          $orderDetailAdditionalInfo -> setProductClassId(intval($orderDetailAdditionalInfo -> getProductClassId()));
          $bodyType = $orderDetailAdditionalInfo -> getBodyType();
          if (!empty($bodyType)) {
            $orderDetailAdditionalInfo -> setBodyType(json_encode($bodyType));
          } else {
            $orderDetailAdditionalInfo -> setBodyType(null);
          }
          $secure_pack = $orderDetailAdditionalInfo -> getSecurePack();
          if (!empty($secure_pack)) {
            $orderDetailAdditionalInfo -> setSecurePack($secure_pack[0]);
          } else {
            $orderDetailAdditionalInfo -> setSecurePack(null); 
          }
          $orderDetailAdditionalInfo -> setActualPrice($orderDetail -> getProductClass() -> getPrice02());
        
          $productId = $orderDetail['product_id'];
          $beforeUseDays = Constants::DEFAULT_BEFORE_USE_DAYS;
          $afterUseDays = Constants::DEFAULT_AFTER_USE_DAYS;
          $useDays = $this->app['kr_aki_customizer.repository.product_use_days']->findOneBy(array("product_id" => $productId));
          if ($useDays) {
              $beforeUseDays = $useDays['before_use_days'];
              $afterUseDays = $useDays['after_use_days'];
          }
          $orderDetailAdditionalInfo -> setBeforeUseDays($beforeUseDays);
          $orderDetailAdditionalInfo -> setAfterUseDays($afterUseDays);

          $em   -> persist($orderDetailAdditionalInfo);
          $this -> removeOrderType($detailProductClassId);
          break;
        }
      }
    }
    $em -> flush();
    $em -> getConnection() -> commit();
    
    
    foreach ($orderDetailAdditionalInfoList as $orderDetailAdditionalInfo) {
      $bodyType = $orderDetailAdditionalInfo -> getBodyType();
      if (!empty($bodyType)) {
        $orderDetailAdditionalInfo -> setBodyType(json_decode($bodyType));
      }
    }
    // 店舗あてのメール
    //$this -> sendOrderMailToOwner($order, $orderDetailAdditionalInfoList);
  }
  /**
   * 購入処理完了後オーダー付加情報を登録します。
   * 店舗宛にメールを送信します。
   */
  public function onFrontShoppingConfirmProcessing(EventArgs $event) {
  }
  /**
   * Send order mail.
   *
   * @param \Eccube\Entity\Order $Order 受注情報
   * @return string
   */
  public function sendOrderMailToOwner(\Eccube\Entity\Order $Order, $orderDetailAdditionalInfoList) {
        //Add point parameters to mail template
        $Customer = $Order->getCustomer();
        if(!empty($Customer)){
            $usePoint = $this->app['eccube.plugin.point.repository.point']->getLatestPreUsePoint($Order);
            $usePoint = abs($usePoint);
            $calculator = $this->app['eccube.plugin.point.calculate.helper.factory'];
            $calculator->setUsePoint($usePoint); 
            $calculator->addEntity('Order', $Order);
            $calculator->addEntity('Customer', $Customer);
            $addPoint = $calculator->getAddPointByOrder();
            if (is_null($addPoint)) {
                    $addPoint = 0;
            } else if($addPoint > 100){
                    $addPoint = $addPoint-100;
            }
            $currentPoint = $calculator->getPoint();
            if (is_null($currentPoint)) {
                    $currentPoint = 0;
            }

            $Order->hasPoint = true;
            $Order->usePoint = $usePoint;
            $Order->addPoint = $addPoint;
            $Order->currentPoint = $currentPoint;
        } else {
            $Order->hasPoint = false; 
        }
        
      //Add orderType parameter to mail template
      $orderType = $orderDetailAdditionalInfoList[0] -> getOrderType();
      $Order->orderDetailInfo = $orderDetailAdditionalInfoList;
      $Order->orderType = $orderType;
      //change mail template
      if(!empty($orderType) && $orderType == 'deliv')
                $templateId = 100;
      else $templateId = 1;
    
      $MailTemplate = $this -> app['eccube.repository.mail_template'] -> find($templateId);
      
      $settings = $this->app["kr_aki_customizer.settings"];
      $deposit_code = $settings["deposit"]["product_code"];
      $body = $this -> app -> renderView($MailTemplate->getFileName(), array(
          'header' => $MailTemplate->getHeader(),
          'footer' => $MailTemplate->getFooter(),
          'Order' => $Order,
          'orderDetailAdditionalInfoList' => $orderDetailAdditionalInfoList,
          'settings' => $settings,
      ));
      
      $baseInfo = $this -> app['eccube.repository.base_info'] -> get();
      $message = \Swift_Message::newInstance()
          ->setSubject('[' . $baseInfo -> getShopName() . '] ' . $MailTemplate -> getSubject())
          ->setFrom(array($baseInfo -> getEmail01() => $baseInfo -> getShopName()))
          ->setTo(array($baseInfo -> getEmail01()))
          ->setBcc($baseInfo -> getEmail01())
          ->setReplyTo($baseInfo -> getEmail03())
          ->setReturnPath($baseInfo -> getEmail04())
          ->setBody($body);

      $count = $this->app->mail($message);

      return $message;

  }
  public function onRenderCartIndex(TemplateEvent $event) {

    $session = $this -> app["session"];
    
    $parameters = $event -> getParameters();
    if ($session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      $parameters += [Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST => $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)];
    }
    $orderType = $this -> getOrderTypeForSession();
    $parameters += ['orderType' => $orderType];

    if ($orderType == "visit") {
      $actual_price = $this -> getActualPrice();
      $parameters += ['actual_price' => $actual_price];
    }
    
    $settings = $this->app["kr_aki_customizer.settings"];
    $parameters += ["secure_pack_code" => $settings["secure_pack"]["product_code"]];
    $parameters += ["secure_pack_price" => intval($settings["secure_pack"]["price"])];

    $event -> setParameters($parameters);
  }
  
  private function getOrderTypeForSession() {
    $session = $this -> app["session"];
    $orderType = "";
    if ($session -> has(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST)) {
      $orderDetailAdditionalInfoList = $session -> get(Constants::ORDER_DETAIL_ADDITIONAL_INFO_LIST);
      if (!empty($orderDetailAdditionalInfoList)) {
        $orderType = $orderDetailAdditionalInfoList[0] -> getOrderType();
      }
    }
    return $orderType;
  }
  
  private function getActualPrice() {
    $session = $this -> app["session"];
    $cart = $session -> get("cart");
    $actual_price = 0;
    foreach ($cart -> getCartItems() as $cartItem) {
      $productClass = $cartItem -> getObject();
      $actual_price += $productClass -> getPrice02();
    }
    return $actual_price;
  }

  /**
   *
   */
  public function onFrontProductIndexInitialize(EventArgs $event) {  
      $builder = $event -> getArgument('builder');
      // 来店着付け OR 宅配レンタルの隠し項目
      // use `visit` as default use_date, This is not good solution, because use_date is date type So it case error in highler version mysql
      $useDate =  isset($_GET['date']) && \DateTime::createFromFormat("Y-m-d", $_GET['date'])
          ? $_GET['date']
          : 'visit'; // visit is old code, 
      $builder -> add(
        Constants::DATE_NAME,
        'text',
        array(
          'required' => false,
          'label'  => false,
          'mapped' => true,
          'attr' => array('value' => $useDate),
          'constraints' => array(
            new Assert\NotBlank())));
  }

  public function onFrontProductIndexSearch(EventArgs $event) {
    $em = $this -> app['orm.em'];
    $qb = $event -> getArgument('qb');
    $settings = $this->app["kr_aki_customizer.settings"];
    $deposit_code = $settings["deposit"]["product_code"];
    $secure_pack_code = $settings["secure_pack"]["product_code"];
    // 安心パックと内金を検索結果から省く
    $qb -> andWhere("pc.code <> '{$deposit_code}'");
    $qb -> andWhere("pc.code <> '{$secure_pack_code}'");

    $session = $this -> app["session"];

    $searchData = $event -> getArgument('searchData');
    
    if( !empty($_GET['orderby'])){
        if($_GET['orderby'] == $this->app['config']['product_order_newer']){
           $qb->innerJoin('p.ProductClasses', 'pc'); 
        }
    } else {
        $qb->innerJoin('p.ProductClasses', 'pc');
    }
     
    //name場合：商品名・品番で検索 
    if( !empty($_GET['name']) ){
      $qb->orWhere('pc.code LIKE :product_code')
         ->setParameter('product_code', '%'.$_GET['name'].'%');
    }
    
    //複数カテゴリ指定用に追加
    $dem = 0;
    $cat_list = array();
    if( !empty($_GET['category_id_0']) ){
        array_push($cat_list, $_GET['category_id_0']);
    }
    if( !empty($_GET['category_id_1']) ){
        array_push($cat_list, $_GET['category_id_1']);
    }
    if( !empty($_GET['category_id_2']) ){
        array_push($cat_list, $_GET['category_id_2']);
    }
    if( !empty($_GET['category_id_3']) ){
        array_push($cat_list, $_GET['category_id_3']);
    }
    if( !empty($_GET['category_id_4']) ){
        array_push($cat_list, $_GET['category_id_4']);
    }
    foreach ($cat_list as $cat_arr){
        $str = '';
        $arr = array();
        foreach($cat_arr as $cat){
                if(is_numeric($cat)){
                   $arr[] =    $cat;
                }
        }
        if(!empty($arr)){
                $dem++;
                $qb->innerJoin('p.ProductCategories', 'pct'.$dem);
                $str .= 'pct'.$dem.'.Category IN ('.implode(',', $arr). ')';
                $qb->andWhere($str);    
        }
        
    }

    //メーカーIDの検索を追加
    if( !empty($_GET['maker_id']) ){
      $qb->innerJoin('\\Plugin\\Maker\\Entity\\ProductMaker', 'promkr')
         ->andWhere('p.id = promkr.id')
         ->andWhere($qb->expr()->in('promkr.Maker', ':maker_id'))->setParameter('maker_id', $_GET['maker_id']);
    }
    $useDate = null;


    // Ignore Limited Product by Date Ranges
    if (isset($_GET['date']) && \DateTime::createFromFormat("Y-m-d", $_GET['date'])) {
        $useDate = $_GET['date'];
        $rsm = new ResultSetMapping();
        $rsm -> addScalarResult('product_id', 'product_id');
        $query = $this->app['orm.em']->createNativeQuery($this->getProductUsesDateSQL($useDate), $rsm);
        $results = $query->getResult();
        if (!empty($results)) {
            $productUseDates = array_map(function($item) {
              return $item['product_id'];
            }, $results);

            $qb->andWhere($qb->expr()->notIn('p.id', ':usedayProductIds'))
              ->setParameter('usedayProductIds', $productUseDates);
        }
    }
  }

  private function getProductUsesDateSQL($useDate) {
    $beforeUseDays = Constants::DEFAULT_BEFORE_USE_DAYS;
    $afterUseDays = Constants::DEFAULT_AFTER_USE_DAYS;

    return <<<EOF
    SELECT 
        dtb_order_detail.product_id
    FROM plg_order_detail_additional_info
    JOIN dtb_order_detail ON dtb_order_detail.order_detail_id = plg_order_detail_additional_info.order_detail_id
    JOIN dtb_order ON dtb_order.order_id = dtb_order_detail.order_id
    LEFT JOIN plg_product_use_days ON plg_product_use_days.product_id = dtb_order_detail.product_id
    WHERE
    wear_date BETWEEN IF(
        plg_order_detail_additional_info.before_use_days,
        DATE_SUB(
            "{$useDate}",
            INTERVAL
            IF(wear_date >= "{$useDate}",
                plg_order_detail_additional_info.before_use_days,
                plg_order_detail_additional_info.after_use_days
            ) DAY
        ),
        DATE_SUB(
            "{$useDate}",
            INTERVAL {$beforeUseDays} DAY
        )
    )
    AND
    IF(
        plg_order_detail_additional_info.after_use_days,
        DATE_ADD(
            "{$useDate}",
            INTERVAL 
            IF(wear_date > "{$useDate}",
                plg_order_detail_additional_info.before_use_days,
                plg_order_detail_additional_info.after_use_days
            ) DAY
        ),
        DATE_ADD(
            "{$useDate}",
            INTERVAL {$afterUseDays} DAY
        )
    )
EOF;
  }
}
