<?php
/* ActiveFusions 2015/11/09 16:16 */

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version201511091600 extends AbstractMigration{

	/**
	* @param Schema $schema
	**/
	public function up(Schema $schema){
		// this up() migration is auto-generated, please modify it to your needs
		$this->createPlgMailTemplatePlugin($schema);
	}

	/**
	* @param Schema $schema
	**/
	public function down(Schema $schema){
		$app = new \Eccube\Application();

		// this down() migration is auto-generated, please modify it to your needs
		$schema->dropTable('plg_mailtemplate_plugin');
	}

	public function postUp(Schema $schema){
		$app = new \Eccube\Application();
		$app->boot();
		$pluginCode = 'MailTemplateEdit';
		$pluginName = 'メールテンプレート機能拡張プラグイン';
		$datetime = date('Y-m-d H:i:s');
		$insert = "INSERT INTO plg_mailtemplate_plugin(plugin_code, plugin_name, create_date, update_date) VALUES ('$pluginCode', '$pluginName', '$datetime', '$datetime');";
		$this->connection->executeUpdate($insert);
	}

	/* プラグイン情報管理テーブルの生成 */
	protected function createPlgMailTemplatePlugin(Schema $schema){
		$table = $schema->createTable("plg_mailtemplate_plugin");
		$table->addColumn('plugin_id', 'integer', array('autoincrement' => true,));
		$table->addColumn('plugin_code', 'text', array('notnull' => true,));
		$table->addColumn('plugin_name', 'text', array('notnull' => true,));
		$table->addColumn('sub_data', 'text', array('notnull' => false,));
		$table->addColumn('auto_update_flg', 'smallint', array('notnull' => true, 'unsigned' => false, 'default' => 0,));
		$table->addColumn('del_flg', 'smallint', array('notnull' => true, 'unsigned' => false, 'default' => 0,));
		$table->addColumn('create_date', 'datetime', array('notnull' => true, 'unsigned' => false,));
		$table->addColumn('update_date', 'datetime', array('notnull' => true, 'unsigned' => false,));
		$table->setPrimaryKey(array('plugin_id'));
	}

	function getMailTemplateCode(){
		$config = \Eccube\Application::alias('config');
		return "";
	}
}
