<?php
namespace Plugin\KrAkiCustomizer\Entity;

class Order extends \Eccube\Entity\AbstractEntity {

  private $order_id;


  public function getOrderId() {
    return $this -> order_id;
  }
  public function setOrderId($order_id) {
     $this -> order_id = $order_id;
     return $this;
  }
}