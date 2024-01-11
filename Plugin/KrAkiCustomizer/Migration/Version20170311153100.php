<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170311153100 extends AbstractMigration {

    public function up(Schema $schema) {
      $this->createTableOrderDetailAdditionalInfo($schema);
    }

    public function down(Schema $schema) {
      $schema->dropTable('plg_order_detail_additional_info');
    }

    protected function createTableOrderDetailAdditionalInfo(Schema $schema) {
      $table = $schema -> createTable("plg_order_detail_additional_info");
      $table->addColumn('order_detail_additional_info_id', 'integer', array('autoincrement' => true, 'notnull' => false, 'length' => 11));
      $table->addColumn('order_id',         'integer',   array('notnull' => true, 'length' => 11));
      $table->addColumn('order_detail_id',  'integer',   array('notnull' => true, 'length' => 11));
      $table->addColumn('product_class_id', 'integer',   array('notnull' => true, 'length' => 11));
      $table->addColumn('order_type',       'string',    array('notnull' => true, 'length' => 10));
      $table->addColumn('wear_date',        'date',      array('notnull' => true));
      $table->addColumn('purpose',          'string',    array('notnull' => true, 'length'  => 255));
      $table->addColumn('body_height',      'string',    array('notnull' => true, 'length'  => 10));
      $table->addColumn('foot_size',        'string',    array('notnull' => true, 'length'  => 10));
      $table->addColumn('body_type',        'string',    array('notnull' => false, 'length' => 255));
      $table->addColumn('secure_pack',      'string',    array('notnull' => false, 'length' => 255));
      $table->addColumn('need_photo',       'string',    array('notnull' => false, 'length' => 255));
      $table->addColumn('need_hair_make',   'string',    array('notnull' => false, 'length' => 255));
      $table->addColumn('date_visit',       'date',      array('notnull' => false));
      $table->addColumn('time_departure',   'string',    array('notnull' => false));
      $table->addColumn('visit_store',      'string',    array('notnull' => false));
      $table->addColumn('actual_price',     'decimal',   array('notnull' => false, 'precision' => 10, 'scale' => 2));
      $table -> addColumn('before_use_days',  'integer',    array('notnull' => false, 'length' => 11));
      $table -> addColumn('after_use_days',   'integer',    array('notnull' => false, 'length' => 11));

      $table->setPrimaryKey(array('order_detail_additional_info_id'));
    }
}