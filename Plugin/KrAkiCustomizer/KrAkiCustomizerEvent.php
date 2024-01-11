<?php
namespace Plugin\KrAkiCustomizer;

use Eccube\Application;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Symfony\Component\Validator\Constraints as Assert;

class KrAkiCustomizerEvent {

  /** @var \Eccube\Application $app */
  private $app;

  const BACKEND_EVENT_KEY = "kr_aki_customizer.event.backend_event";
  const FRONT_EVENT_KEY = 'kr_aki_customizer.event.front_event';

  public function __construct(Application $app) {
      $this -> app = $app;
  }

  ############### フロントイベント
  /**
   * 購入確認画面
   */
  public function onFrontShoppingIndexInitialize(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontShoppingIndexInitialize($event);
  }

  /**
   * 購入確認画面（支払い方法選択時）
   */
  public function onFrontShoppingPaymentInitialize(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontShoppingPaymentInitialize($event);
  }
  /**
   * 購入確認画面（エラーで返ってきたときの）表示時
   */
  public function onFrontShoppingConfirmInitialize(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontShoppingConfirmInitialize($event);
  }
 public function onFrontShoppingCompleteInitialize(EventArgs $event) {
   $this -> app[self::FRONT_EVENT_KEY] -> onFrontShoppingCompleteInitialize($event);
 }
  
  /**
   * , Formの拡張
   * 商品詳細表示時の処理
   * 「宅配レンタル」、「来店着付け」を保持する用のフォームを追加
   */
  public function onFrontProductDetailInitialize(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontProductDetailInitialize($event);
  }

  /**
   * custom01
   * カート投入後の処理
   * セッションに付与情報を保持する
   */
  public function onFrontProductDetailComplete(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontProductDetailComplete($event);
  }

  /**
   * カートから商品削除時。
   * オーダータイプも削除する。
   */
  public function onFrontCartRemoveComplete(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontCartRemoveComplete($event);
  }

  /**
   * カートから商品数量減時。
   * 対象商品が０の場合オーダータイプも削除する。
   */
  public function onFrontCartDownComplete(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontCartDownComplete($event);
  }

  /**
   * 商品詳細ページのテンプレートに表示するパラメーターの編集
   */
  public function onRenderProductDetail(TemplateEvent $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onRenderProductDetail($event);
  }
  /**
   * 購入確認画面でテンプレートに表示する不要情報のパラメーターを差し込み。
   */
   public function onRenderShoppingIndex(TemplateEvent $event) {
     $this -> app[self::FRONT_EVENT_KEY] -> onRenderShoppingIndex($event);
   }

   public function onControllerShoppingConfirmBefore($event) {
     $this -> app[self::FRONT_EVENT_KEY] -> onControllerShoppingConfirmBefore($event);
   }
  /**
   * 購入処理完了後オーダー付加情報を登録します。
   */
  public function onFrontShoppingConfirmProcessing(EventArgs $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontShoppingConfirmProcessing($event);
  }

  public function onEccubeEventFrontResponse($event) {}

  public function onFrontProductIndexInitialize($event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontProductIndexInitialize($event);
  }
  public function onFrontProductIndexSearch($event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onFrontProductIndexSearch($event);
  }

  ################ バックエンドイベント
  /**
   * 商品詳細、登録画面初期表示。
   * フォームを追加します。
   */
  public function onAdminProductEditInitialize($event) {
    $this -> app[self::BACKEND_EVENT_KEY] -> onAdminProductEditInitialize($event);
  }

  /**
   * 商品詳細、登録、編集、セーブ処理
   */
  public function onAdminProductEditComplete(EventArgs $event) {
    $this -> app[self::BACKEND_EVENT_KEY] -> onAdminProductEditComplete($event);
  }

  public function onAdminOrderEditIndexInitialize(EventArgs $event) {
    $this -> app[self::BACKEND_EVENT_KEY] -> onAdminOrderEditIndexInitialize($event);
  }
  public function onRenderAdminOrderEdit(TemplateEvent $event) {
    $this -> app[self::BACKEND_EVENT_KEY] -> onRenderAdminOrderEdit($event);
  }
  public function onAdminOrderEditIndexComplete(EventArgs $event) {
    $this -> app[self::BACKEND_EVENT_KEY] -> onAdminOrderEditIndexComplete($event);
  }
  public function onRenderCartIndex(TemplateEvent $event) {
    $this -> app[self::FRONT_EVENT_KEY] -> onRenderCartIndex($event);
  }
}