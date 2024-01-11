<?php
namespace Plugin\KrAkiCustomizer\Common;

class Utils {
  
  public static function getOrderType($settings) {
    return $settings["form"]["order_type"];
  }
  public static function getPurpose($settings) {
    $ret = array();
    $purpose = $settings["form"]["purpose"];
    foreach($purpose as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }

  public static function getFootSize($settings) {
    $ret = array();
    $foot_size = $settings["form"]["foot_size"];
    foreach($foot_size as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }

  public static function getBodyHeight($settings) {
    $ret = array();
    $body_height = $settings["form"]["body_height"];
    foreach($body_height as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }
  
  public static function getBodyType($settings) {
    $ret = array();
    $body_type = $settings["form"]["body_type"];
    foreach($body_type as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }
  
  public static function getHairMake($settings) {
    $ret = array();
    $hair_make = $settings["form"]["hair_make"];
    foreach($hair_make as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }

  public static function getTimeDeparture($settings) {
    $ret = array();
    $time_departure = $settings["form"]["time_departure"];
    foreach($time_departure as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }

  public static function getVisitStore($settings) {
    $ret = array();
    $visit_store = $settings["form"]["visit_store"];
    foreach($visit_store as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }

  public static function getSecurePack($settings) {
    $ret = array();
    $secure_pack = $settings["form"]["secure_pack"];
    foreach($secure_pack as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }
  
  public static function getPhotoPlan($settings) {
    $ret = array();
    $photo_plan = $settings["form"]["photo_plan"];
    foreach($photo_plan as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }
  
  public static function getPayMethod($settings) {
    return $settings["form"]["pay_method"];
  }
  
  public static function getDecade($settings) {
    $ret = array();
    $decade = $settings["form"]["decade"];
    foreach($decade as $value) {
      $ret[$value] = $value;
    }
    return $ret;
  }
}