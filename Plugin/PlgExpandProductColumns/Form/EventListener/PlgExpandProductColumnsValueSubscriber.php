<?php
namespace Plugin\PlgExpandProductColumns\Form\EventListener;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Form\FormEvents;
use Silex\Application as BaseApplication;


class PlgExpandProductColumnsValueSubscriber implements EventSubscriberInterface
{
    private $TYPE_MAP;
    private $factory;
    private $app;

    public function __construct(FormFactoryInterface $factory, BaseApplication $app)
    {
        $this->factory = $factory;
        $this->app = $app;
        $this->TYPE_MAP = $app['PlgExpandProductColumns-TYPE_MAP'];
        $this->init();

    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'onPreSubmit',
        );
    }

    private function init()
    {
        if (!isset($this->app['plgExpandProductColumnsValue_temp'])){
            $this->app['plgExpandProductColumnsValue_temp'] = array();
        }

        if (!isset($this->app['plgExpandProductColumnsValue_inserted'])){
            $this->app['plgExpandProductColumnsValue_inserted'] = array();
        }
    }

    /**
     *
     * 保存したい値を一旦保持しておく
     *
     * @param FormEvent $event
     */
    public function onPreSubmit(FormEvent $event)
    {
        $column_repository = $this->app['orm.em']->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns');
        $data_list = $event->getData();
        foreach ($data_list as $key => $data) {
            $temp_data = array();
            $info = explode('-', $key);
            $column_id = $info[1];
            $val_key = 'value-' . $column_id;

            // 商品の新規登録時は product_idが入っていない
            if (count($info) > 2) {
                $product_id = $info[2];
                $temp_data['product_id'] = $product_id;
            }

            /**
             * カラムタイプによって渡す値を整形
             */
            $col_info = $column_repository->findOneBy(array(
                'columnId' => $column_id
            ));

            $go = true;
            switch ($col_info->getColumnType()) {
                case EX_TYPE_TEXT :
                case EX_TYPE_TEXTAREA :
                case EX_TYPE_SELECT :
                case EX_TYPE_RADIO :
                    $save_value = $data;
                break;
                case EX_TYPE_CHECKBOX :
                    $save_value = is_array($data) ? implode(',', $data) : $data;
                    break;
                case EX_TYPE_IMAGE :
                    $save_value = '';
                    if (isset($_POST['ex_product_images'][$val_key])) {
                        $save_value = implode(',', $_POST['ex_product_images'][$val_key]);
                        foreach ($_POST['ex_product_images'][$val_key] as $add_image) {
                            $temp_file = $this->app['config']['image_temp_realdir'] . '/' . $add_image;
                            if (is_file($temp_file)) {
                                // 移動
                                $file = new File($temp_file);
                                $file->move($this->app['config']['image_save_realdir']);
                            }
                        }
                    }
                    if (isset($_POST['ex_product_del_images'][$val_key])) {
                        foreach ($_POST['ex_product_del_images'][$val_key] as $del_image) {
                            $delete_file = $this->app['config']['image_save_realdir'] . '/' . $del_image;
                            if (is_file($delete_file)) {
                                // 削除
                                $fs = new Filesystem();
                                $fs->remove($delete_file);
                            }
                        }
                    }
                    break;
                default :
                    $go = false;
                    break;
            }

            if ($go) {
                $temp_data['column_id'] = $column_id;
                $temp_data['value'] = $save_value;
                $save_data = $this->app['plgExpandProductColumnsValue_temp'];
                $save_data[$column_id] = $temp_data;
                $this->app['plgExpandProductColumnsValue_temp'] = $save_data;
            }
        }

        // 自分で保存するからformには保存させない
        $event->setData(null);
    }

    public function preSetData(FormEvent $event)
    {
        //$this->init();
        /** @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue $data */
        $entity = $event->getData();

        // フォーム作成時に FormBuilder のコンストラクタによって setData() は null の引数で呼ばれます。
        // Doctrine に新規に保存するとき、またはデータを取ってきたときなど
        // 実際のエンティティオブジェクトを操作する際の setData() のみを対象にします。
        // そのため、この if 文は null の条件をスキップさせます。
        if (null === $entity) {
            return;
        }
        
        /** @var \Symfony\Component\Form\Form $form */
        $form = $event->getForm();

        $column_id = $entity->getColumnId();
        /** @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns $ex_column */
        $ex_column = $this->app['eccube.plugin.repository.plg_expand_product_columns']
            ->findOneBy(array('columnId' => $column_id));

        if (is_null($ex_column) && !is_null($column_id)) {
            // もし$column_id(value側)はあるのに、column側にないなら不正データ
            $this->app['eccube.plugin.repository.plg_expand_product_columns_value']
                ->delete($column_id);
            return;
        } else if (is_null($ex_column)) {
            // 空のフォームを作成
            $this->createBlankForm($form);
            return;
        }

        // データの入っているフィールドを作成
        $field_name = 'value-' . $column_id;
        $form->add($field_name, $this->getFieldName($ex_column->getColumnType()),
            $this->getFieldInfo($ex_column->getColumnType(), $ex_column->getColumnName(), $ex_column->getColumnSetting()));

        // 追加済みフィールドを記録
        $inserted_array = $this->app['plgExpandProductColumnsValue_inserted'];
        $inserted_array[] = $column_id;
        $this->app['plgExpandProductColumnsValue_inserted'] = $inserted_array;
        
        // データのカスタム
        switch ($ex_column->getColumnType()) {
            case EX_TYPE_CHECKBOX :
                $val = $entity->getValue();
                if (!empty($val) && is_string($val)) {
                    $entity->setValue(explode(',', $entity->getValue()));
                    $event->setData($entity);
                }
                break;
        }

    }

    /**
     *
     * Event.php addContentOnProductEditメソッドからsetDataする時に
     * 最後の1件を空Entityにしているので、他のフィールドがaddされた後
     * 最後に呼び出される。
     * ここで、データの入っていないフィールドを作成している
     *
     * @param \Symfony\Component\Form\Form $form
     */
    private function createBlankForm(\Symfony\Component\Form\Form $form)
    {
        $ex_columns = $this->app['eccube.plugin.repository.plg_expand_product_columns']->findAll();
        $inserted_array = $this->app['plgExpandProductColumnsValue_inserted'];
        foreach ($ex_columns as $column) {
            // まだ追加されていないフィールドがあれば追加する
            if (!in_array($column->getColumnId(), $inserted_array)) {
                $field_name = 'value-' . $column->getColumnId();
                $form->add($field_name, $this->getFieldName($column->getColumnType()),
                    $this->getFieldInfo($column->getColumnType(), $column->getColumnName(), $column->getColumnSetting()));
            }
        }
        unset($this->app['plgExpandProductColumnsValue_inserted']);
    }

    private function getFieldName($type)
    {
        $result = 'text';
        switch ($type) {
            case EX_TYPE_TEXT :
                $result = 'text';
                break;
            case EX_TYPE_TEXTAREA :
                $result = 'textarea';
                break;
            case EX_TYPE_IMAGE :
                $result = 'file';
                break;
            case EX_TYPE_SELECT :
            case EX_TYPE_CHECKBOX :
            case EX_TYPE_RADIO :
                $result = 'choice';
                break;
        }

        return $result;
    }
    private function getFieldInfo($type, $display_name, $setting)
    {
        $result = array(
            'label' => $display_name,
            'required' => false,
            'mapped' => true
        );
        switch ($type) {
            case EX_TYPE_TEXT :
            case EX_TYPE_TEXTAREA :
                $result = array(
                    'label' => $display_name,
                    'required' => false,
                    'mapped' => true
                );
                break;
            case EX_TYPE_IMAGE :
                $result = array(
                    'label' => $display_name,
                    'multiple' => true,
                    'required' => false,
                    'mapped' => false,
                );
                break;
            case EX_TYPE_SELECT :
                $result = array(
                    'label' => $display_name,
                    'required' => false,
                    'mapped' => true,
                    'choices' => self::settingToArray($setting),
                    'empty_value' => false
                );
                break;
            case EX_TYPE_CHECKBOX :
                $result = array(
                    'label' => $display_name,
                    'required' => false,
                    'expanded' => true,
                    'mapped' => true,
                    'multiple' => true,
                    'choices' => self::settingToArray($setting),
                    'empty_value' => false
                );
                break;
            case EX_TYPE_RADIO :
                $result = array(
                    'label' => $display_name,
                    'required' => false,
                    'mapped' => true,
                    'expanded' => true,
                    'choices' => self::settingToArray($setting),
                    'empty_value' => false
                );
                break;
        }

        return $result;
    }

    static function settingToArray($setting){
        $result = array();
        $setting = preg_replace("/\r\n|\r|\n/", "\n", $setting); // 改行コード統一
        $array = explode("\n", $setting); // とりあえず行に分割
        $array = array_map('trim', $array); // 各行にtrim()をかける
        $array = array_filter($array, 'strlen'); // 文字数が0の行を取り除く
        $array = array_values($array); // これはキーを連番に振りなおしてるだけ

        foreach ($array as $value) {
            $wk_arr = explode(":", $value);
            if (count($wk_arr) === 2) {
                $result[$wk_arr[0]] = $wk_arr[1];
            }
        }

        return $result;
    }
}