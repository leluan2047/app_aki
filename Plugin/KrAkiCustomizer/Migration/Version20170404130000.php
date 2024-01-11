<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170404130000 extends AbstractMigration {

    const TABLE_NAME = "plg_product_use_date";
    
    public function up(Schema $schema) {
      $this -> createTableProductUseDate($schema);
    }

    public function down(Schema $schema) {
      $schema->dropTable('plg_product_use_date');
    }

    protected function createTableProductuseDate(Schema $schema) {
      $table = $schema -> createTable(self::TABLE_NAME);
      $table -> addColumn('product_use_date_id', 'integer', array('autoincrement' => true, 'notnull' => false, 'length' => 11));
      $table -> addColumn('product_id',         'integer',   array('notnull' => true, 'length' => 11));
      $table -> addColumn('use_date',           'date',      array('notnull' => true));
      $table -> setPrimaryKey(array('product_use_date_id'));
      $table -> addIndex(array("product_id" , "use_date"));
    }
}