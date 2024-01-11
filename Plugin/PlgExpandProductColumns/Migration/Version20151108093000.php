<?php
/*
* This file is part of EC-CUBE
*
* Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
* http://www.lockon.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20151108093000 extends AbstractMigration
{

    public function up(Schema $schema)
    {
        $this->createPlgExpandProductColumnsTable($schema);
        $this->createPlgExpandProductColumnsValueTable($schema);
    }

    public function down(Schema $schema)
    {
        $schema->dropTable('plg_expand_product_columns');
        $schema->dropTable('plg_expand_product_columns_value');
    }

    protected function createPlgExpandProductColumnsTable(Schema $schema)
    {
        $table = $schema->createTable("plg_expand_product_columns");
        $table->addColumn('column_id', 'integer', array('autoincrement' => true));
        $table->addColumn('column_name', 'text', array('notnull' => false));
        $table->addColumn('column_type', 'smallint', array('notnull' => false));
        $table->addColumn('column_setting', 'text', array('notnull' => false));
        $table->addColumn('csv_id', 'integer', array('notnull' => false));
        $table->setPrimaryKey(array('column_id'));
    }

    protected function createPlgExpandProductColumnsValueTable(Schema $schema)
    {
        $table = $schema->createTable("plg_expand_product_columns_value");
        $table->addColumn('product_id', 'integer');
        $table->addColumn('column_id', 'integer');
        $table->addColumn('value', 'text', array('notnull' => false));
        $table->setPrimaryKey(array('product_id', 'column_id'));
    }

}
