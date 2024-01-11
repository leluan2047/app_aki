<?php

namespace Plugin\KrAkiCustomizer\Controller\V1;

use Eccube\Application;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Plugin\KrAkiCustomizer\Common\Constants;
use Plugin\KrAkiCustomizer\Common\Utils;

class ApiController {
  
	public function calendar(Application $app, Request $request, $productId) {
    $product = $app['eccube.repository.product'] -> get($productId);
    
    $productClassId = $product -> getProductClasses()[0] -> getId();

    // 利用不可能な日を出す
    $useDays       = $app['kr_aki_customizer.repository.product_use_days'] -> findOneBy(array("product_id" => $productId));
    $beforeUseDays = Constants::DEFAULT_BEFORE_USE_DAYS;
    $afterUseDays  = Constants::DEFAULT_AFTER_USE_DAYS;

    if ($useDays) {
      $beforeUseDays = $useDays -> getBeforeUseDays();
      $afterUseDays = $useDays -> getAfterUseDays();
    }

    $today = date("Y-m-d");

    $em = $app['orm.em'];

    $query =
      $em -> createQueryBuilder()
        -> select("odai")
        -> from("\\Plugin\\KrAkiCustomizer\\Entity\\OrderDetailAdditionalInfo", "odai")
        -> where('odai.product_class_id = :product_class_id and odai.wear_date >= :wear_date')
        -> setParameter('product_class_id', $productClassId)
        -> setParameter('wear_date', $today)
        -> getQuery();

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
    $parameters = ["not_available_dates" => $not_available_dates];
    
    $start_year  = $request -> query -> get('start_year');
    if (empty($start_year)) {
      $start_year = date("Y", time());
    }
    $start_month = $request -> query -> get('start_month');
    if (empty($start_month)) {
      $start_month = date("m", time());
    }

    // 当月を出す
    $year  = $start_year;
    $month = $start_month;
    $today = date("Y-m-d");
    $current_month_time = strtotime("$year/$month/1");
    $end_of_month = date("t", $current_month_time);
    $start_week   = date('w', $current_month_time);

    $next_month_time       = strtotime("$year/$month/1 + 1 month");
    $next_month_year       = date("Y", $next_month_time);
    $next_month            = date("m", $next_month_time);
    $next_end_of_month     = date("t", $next_month_time);
    $next_month_start_week = date('w', $next_month_time);
    
    $parameters += ["today" => $today];
    $parameters += ["year" => intval($year)];
    $parameters += ["current_month" => intval($month)];
    $parameters += ["current_end_of_month" => intval($end_of_month)];
    $parameters += ["start_week" => intval($start_week)];

    $parameters += ["next_month_year" => intval($next_month_year)];
    $parameters += ["next_month" => intval($next_month)];
    $parameters += ["next_end_of_month" => intval($next_end_of_month)];
    $parameters += ["next_month_start_week" => intval($next_month_start_week)];

    $response = new Response(json_encode($parameters), 200);
    $response -> headers -> set('Content-Type', 'application/json');
    return $response;
	}

}