<?php
namespace Plugin\KrAkiCustomizer\Entity;

class OrderDetailAdditionalInfo extends \Eccube\Entity\AbstractEntity {

  private $order_detail_additional_info_id;
  private $order_id;
  private $order_detail_id;
  private $order_type;
  private $product_class_id;
  private $purpose;
  private $body_height;
  private $foot_size;
  private $decade;
  private $wear_date;
  private $body_type;

  private $secure_pack;

  private $need_hair_make;
/* 20170601 非表示
  private $date_visit;
*/
  private $time_departure;
  private $visit_store;
  private $actual_price;
  private $need_photo;
  private $before_use_days;
  private $after_use_days;

  public function getOrderDetailAdditionalInfoId() {
      return $this -> order_detail_additional_info_id;
  }

  public function setOrderDetailAdditionalInfoId($order_detail_additional_info_id) {
      $this -> order_detail_additional_info_id = $order_detail_additional_info_id;
      return $this;
  }
  public function getOrderId() {
    return $this -> order_id;
  }
  public function setOrderId($order_id) {
     $this -> order_id = $order_id;
     return $this;
  }
  public function getOrderDetailId() {
    return $this -> order_detail_id;
  }
  public function setOrderDetailId($order_detail_id) {
     $this -> order_detail_id = $order_detail_id;
     return $this;
  }
  public function getOrderType() {
    return $this -> order_type;
  }
  public function setOrderType($order_type) {
     $this -> order_type = $order_type;
     return $this;
  }
  public function getProductClassId() {
    return $this -> product_class_id;
  }
  public function setProductClassId($product_class_id) {
    $this -> product_class_id = $product_class_id;
    return $this;
  }
  public function getPurpose() {
    return $this -> purpose;
  }
  public function setPurpose($purpose) {
    $this -> purpose = $purpose;
    return $this;
  }
  public function getBodyHeight() {
    return $this -> body_height;
  }
  public function setBodyHeight($body_height) {
    $this -> body_height = $body_height;
    return $this;
  }
  public function getFootSize() {
    return $this -> foot_size;
  }
  public function setFootSize($foot_size) {
    $this -> foot_size = $foot_size;
    return $this;
  }
  public function getDecade() {
    return $this -> decade;
  }
  public function setDecade($decade) {
    $this -> decade = $decade;
    return $this;
  }
  public function getWearDate() {
    return $this -> wear_date;
  }
  public function setWearDate($wear_date) {
     $this -> wear_date = $wear_date;
     return $this;
  }
  public function getBodyType() {
    return $this -> body_type;
  }
  public function setBodyType($body_type) {
     $this -> body_type = $body_type;
     return $this;
  }


  public function getSecurePack() {
    return $this -> secure_pack;
  }

  public function setSecurePack($secure_pack) {
    $this -> secure_pack = $secure_pack;
    return $this;
  }
  public function getNeedHairMake() {
    return $this -> need_hair_make;
  }
  public function setNeedHairMake($need_hair_make) {
    $this -> need_hair_make = $need_hair_make;
    return $this;
  }
/* 20170601 非表示
  public function getDateVisit() {
    return $this -> date_visit;
  }
*/
  public function setDateVisit($date_visit) {
    $this -> date_visit = $date_visit;
    return $this;
  }

  public function getTimeDeparture() {
    return $this -> time_departure;
  }
  public function setTimeDeparture($time_departure) {
    $this -> time_departure = $time_departure;
    return $this;
  }
  public function getVisitStore() {
    return $this -> visit_store;
  }
  public function setVisitStore($visit_store) {
    $this -> visit_store = $visit_store;
    return $this;
  }
  public function setActualPrice($actual_price) {
    $this -> actual_price = $actual_price;
    return $this;
  }
  public function getActualPrice() {
    return $this -> actual_price;
  }
  public function getNeedPhoto() {
    return $this -> need_photo;
  }
  public function setNeedPhoto($need_photo) {
    $this -> need_photo = $need_photo;
    return $this;
  }
  public function getBeforeUseDays() {
    return $this -> before_use_days;
  }
  public function setBeforeUseDays($before_use_days) {
    $this -> before_use_days = $before_use_days;
    return $this;
  }
  public function getAfterUseDays() {
    return $this -> after_use_days;
  }
  public function setAfterUseDays($after_use_days) {
    $this -> after_use_days = $after_use_days;
    return $this;
  }
}