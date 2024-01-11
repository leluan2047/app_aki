<?php
namespace Plugin\KrAkiCustomizer\Entity;

class ProductUseDate extends \Eccube\Entity\AbstractEntity {
  private $product_use_date_id;
  private $product_id;
  private $use_date;

  public function getProductUseDateId() {
    return $this -> product_use_date_id;
  }

  public function getProductId() {
    return $this -> product_id;
  }

  public function getUseDate() {
    return $this -> use_date;
  }
  public function setProductUseDateId($product_use_date_id) {
    $this -> product_use_date_id = $product_use_date_id;
    return $this;
  }

  public function setProductId($product_id) {
    $this -> product_id = $product_id;
    return $this;
  }
  
  public function setUseDate($use_date) {
    $this -> use_date = $use_date;
    return $this;
  }
}