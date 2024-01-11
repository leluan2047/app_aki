<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170321113600 extends AbstractMigration {

    public function up(Schema $schema) {
      $this->createTableProductUseDays($schema);
    }

    public function down(Schema $schema) {
      $schema->dropTable('plg_product_use_days');
    }

    protected function createTableProductUseDays(Schema $schema) {
      $table = $schema -> createTable("plg_product_use_days");
      $table -> addColumn('product_use_days_id', 'integer', array('autoincrement' => true, 'notnull' => false, 'length' => 11));
      $table -> addColumn('product_id',          'integer', array('notnull' => true, 'length' => 11));
      $table -> addColumn('before_use_days',     'integer', array('notnull' => true, 'length' => 2));
      $table -> addColumn('after_use_days',      'integer', array('notnull' => true, 'length' => 2));
      $table -> setPrimaryKey(array('product_use_days_id'));
    }

}
