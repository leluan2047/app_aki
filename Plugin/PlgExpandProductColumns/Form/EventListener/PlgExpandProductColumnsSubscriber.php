<?php
namespace Plugin\PlgExpandProductColumns\Form\EventListener;

use Eccube\Common\Constant;
use Eccube\Entity\Csv;
use Eccube\Entity\Master\CsvType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Silex\Application as BaseApplication;


class PlgExpandProductColumnsSubscriber implements EventSubscriberInterface
{

    private $factory;
    private $app;

    public function __construct(FormFactoryInterface $factory, BaseApplication $app)
    {
        $this->factory = $factory;
        $this->app = $app;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::SUBMIT => 'submit'
        );
    }

    public function submit(FormEvent $event)
    {
        $em = $this->app['orm.em'];
        /** @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns $data */
        $data = $event->getData();

        /**
         * column_idが指定されてない = 新規追加
         * 新規追加時はCSVテーブルにも新規追加する
         */
        $em->getConnection()->beginTransaction();
        if (is_null($data->getColumnId())) {
            $count = count($this->app['eccube.repository.csv']->findAll());
            $CsvType = $this->app['eccube.repository.master.csv_type']->find(CsvType::CSV_TYPE_PRODUCT);
            $csv_id = false;
            $csv = new Csv();
            $csv->setEntityName('Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns');
            $csv->setFieldName('dummy');
            $csv->setDispName($data->getColumnName());
            $csv->setRank(intval($count) + 1);
            $csv->setEnableFlg(Constant::DISABLED);
            $csv->setCsvType($CsvType);
            try {
                $em->persist($csv);
                $em->flush();
                $em->getConnection()->commit();
                $csv_id = $csv->getId();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
            }
        } 
        /**
         * 更新時はCSV情報も更新する
         */
        else {
            /**
             * @var $entity \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns
             */
            $entity = $em->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns')
                ->findOneBy(array(
                    'columnId' => $data->getColumnId()
                ));
            $csv_id = $entity->getCsvId();
            $csv = $this->app['eccube.repository.csv']->findOneBy(array(
                'id' => $csv_id
            ));
            /**
             * @var $csv \Eccube\Entity\Csv
             */
            $csv->setDispName($data->getColumnName());
            try {
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
            }
        }
        /**
         * カラム情報を保存
         */
        $saved_id = $this->app['eccube.plugin.repository.plg_expand_product_columns']
            ->save(
                $data->getColumnName(),
                $data->getColumnType(),
                $csv_id,
                $data->getColumnSetting(),
                $data->getColumnId()
            );

        if ($saved_id !== false) {
            try {
                $em->getConnection()->beginTransaction();
                $csv->setFieldName('PlgExpandProductColumns_' . $saved_id);
                $em->flush();
                $em->getConnection()->commit();
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
            }
            $this->app->addSuccess('admin.register.complete', 'admin');
            $this->app['PlgExpandProductColumns-REDIRECT'] = $this->app->url('plugin_PlgExpandProductColumns_config_edit', array('id' => $saved_id));
        } else {
            $this->app->addError('admin.register.failed', 'admin');
        }
    }
}