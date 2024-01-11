<?php
namespace Plugin\KrAkiCustomizer\Entity;

class ProductUseDays extends \Eccube\Entity\AbstractEntity {
  private $product_use_days_id;
  private $product_id;
  private $after_use_days;
  private $before_use_days;

  public function getProductUseDaysId() {
    return $this -> product_use_days_id;
  }

  public function getProductId() {
    return $this -> product_id;
  }
  
  public function getAfterUseDays() {
    return $this -> after_use_days;
  }

  public function getBeforeUseDays() {
    return $this -> before_use_days;
  }
  
  public function setProductUseDaysId($product_use_days_id) {
    $this -> product_use_days_id = $product_use_days_id;
    return $this;
  }

  public function setProductId($product_id) {
    $this -> product_id = $product_id;
    return $this;
  }

  public function setAfterUseDays($after_use_days) {
    $this -> after_use_days = $after_use_days;
    return $this;
  }

  public function setBeforeUseDays($before_use_days) {
    $this -> before_use_days = $before_use_days;
    return $this;
  }
}