<?php
namespace Plugin\KrAkiCustomizer\Entity;

class OrderDetail extends \Eccube\Entity\AbstractEntity {

  private $order_detail_id;
  private $order_id;
  private $product_id;
  private $product_class_id;
  private $product_name;
  private $product_code;
  private $class_name1;
  private $class_name2;
  private $class_category_name1;
  private $class_category_name2;
  private $price;
  private $quantity;
  private $tax_rate;
  private $tax_rule;


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
  public function getProductClassId() {
    return $this -> product_class_id;
  }
  public function setProductClassId($product_class_id) {
     $this -> product_class_id = $product_class_id;
     return $this;
  }
}