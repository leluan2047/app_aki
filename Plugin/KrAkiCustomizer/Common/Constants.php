<?php 
namespace Plugin\KrAkiCustomizer\Common;

class Constants {

  // 商品詳細
  const PLG_ORDER_TYPE_NAME  = "plg_order_type";
  const PLG_PURPOSE_NAME     = "plg_purpose";
  const PLG_BODY_HEIGHT_NAME = "plg_body_height";
  const PLG_FOOT_SIZE_NAME   = "plg_foot_size";
  const PLG_WEAR_DATE_NAME   = "plg_wear_date";
  const PLG_BODY_TYPE_NAME   = "plg_body_type";
  const PLG_SECURE_PACK_NAME = "plg_secure_pack";
  const PLG_DECADE           = "plg_decade";

  const FORM_SETTINGS = "form_settings";

  // 商品一覧
  const DATE_NAME = "date";

  // 購入確認画面
  const PLG_NEED_HAIR_MAKE      = "plg_need_hair_make";
/* 20170601 非表示
  const PLG_DATE_VISIT_NAME     = "plg_date_visit";
*/
  const PLG_TIME_DEPARTURE_NAME = "plg_time_departure";
  const PLG_VISIT_STORE_NAME    = "plg_visit_store";
  const PLG_NEED_PHOTO          = "plg_need_photo";
  const PLG_PAY_METHOD          = "plg_pay_method";

  // セッションキー
  const ORDER_ADDITIONAL_INFO_LIST        = "order_additional_info_list";
  const ORDER_DETAIL_ADDITIONAL_INFO_LIST = "order_detail_additional_info_list";
  const SEARCH_DATE = "search_date";

  // 内金
  const ORDER_TOTAL_PRICE = "order_total_price";
  const ORDER_TOTAL_PRICE_WITHOUT_TAX = "order_total_price_without_tax";
  const CART_ORIGINAL = "cart_original";
  const CART_ITEMS_ORIGINAL = "cart_items_original";

  // 内金(税込み価格)
  const DEPOSIT_PRICE = 10800;

  // 内金(税抜価格)
  const DEPOSIT_PRICE_WITHOUT_TAX = 10000;

  // バックオフィス
  const PLG_BEFORE_USE_DAYS = "plg_before_use_days";
  const PLG_AFTER_USE_DAYS  = "plg_after_use_days";

  // 利用日前後デフォルト値
  const DEFAULT_BEFORE_USE_DAYS = 3;
  const DEFAULT_AFTER_USE_DAYS  = 3;

  //Register
  const ALLOW_REGISTER_A_DAY = 2;

}
