<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161101093835 extends AbstractMigration
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addColumn($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {  
        $this->dropColumn($schema);
    }

    protected function addColumn(Schema $schema) {
    	if ($schema->hasTable('dtb_customer')) {
			$this->addSql("alter table dtb_customer add virtual_account_bank_code text");
			$this->addSql("alter table dtb_customer add virtual_account_branch_code text");
			$this->addSql("alter table dtb_customer add virtual_account_number text");
    	}
		
    	if ($schema->hasTable('dtb_mdl_order_payment')) {
    		$this->addSql("alter table dtb_mdl_order_payment add invoice_send_type int2");
    	}
    }

    protected function dropColumn(Schema $schema) {
    	if ($schema->hasTable('dtb_customer')) {
    		$this->addSql("alter table dtb_customer drop column virtual_account_bank_code");
    		$this->addSql("alter table dtb_customer drop column virtual_account_branch_code");
    		$this->addSql("alter table dtb_customer drop column virtual_account_number");
    	}
    	if ($schema->hasTable('dtb_mdl_order_payment')) {
    		$this->addSql("alter table dtb_mdl_order_payment drop column invoice_send_type");
    	}
    }
}
