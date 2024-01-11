<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20160328104230 extends AbstractMigration
{

    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->createDtbMdlPlugin($schema);
        $this->createDtbMdlOrderPayment($schema);
        $this->createDtbMdlPaymentMethod($schema);
        $this->addColumn($schema);
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // dtb_paymentは受注データと紐付いているため削除しない
        $this->deleteFromDtbPayment();
        $this->deletePageLayout();
        $this->dropColumn($schema);
        // this down() migration is auto-generated, please modify it to your needs

        $schema->dropTable('dtb_mdl_plugin');
        $schema->dropTable('dtb_mdl_payment_method');
        $schema->dropTable('dtb_mdl_order_payment');

    }

    protected function addColumn(Schema $schema) {
    	if ($schema->hasTable('dtb_customer')) {
			$this->addSql("alter table dtb_customer add paygent_card int2");
    	}
    }

    protected function dropColumn(Schema $schema) {
    	if ($schema->hasTable('dtb_customer')) {
    		$this->addSql("alter table dtb_customer drop column paygent_card");
    	}
    }

    protected function createDtbMdlPlugin(Schema $schema)
    {
        $table = $schema->createTable("dtb_mdl_plugin");
        $table->addColumn('plugin_id', 'integer', array(
            'autoincrement' => true,
        ));

        $table->addColumn('plugin_code', 'text', array(
            'notnull' => true,
        ));

        $table->addColumn('plugin_name', 'text', array(
            'notnull' => true,
        ));

        $table->addColumn('sub_data', 'text', array(
            'notnull' => false,
        ));

        $table->addColumn('auto_update_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));

        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->setPrimaryKey(array('plugin_id'));
    }

    protected function createDtbMdlPaymentMethod(Schema $schema)
    {
        $table = $schema->createTable("dtb_mdl_payment_method");

        //id
        $table->addColumn('payment_id', 'integer', array(
            	'notnull' => true,
        		'default' => null,
        ));

        // method
        $table->addColumn('payment_method', 'text', array(
            	'notnull' => true,
        		'default' => null,
        ));

        // delete flg
        $table->addColumn('del_flg', 'smallint', array(
            'notnull' => true,
            'unsigned' => false,
            'default' => 0,
        ));

        // create date
        $table->addColumn('create_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        // update date
        $table->addColumn('update_date', 'datetime', array(
            'notnull' => true,
            'unsigned' => false,
        ));

        $table->addColumn('memo01', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo02', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo03', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo04', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo05', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo06', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo07', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo08', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo09', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        $table->addColumn('memo10', 'text', array(
            	'notnull' => false,
        		'default' => null,
        ));
        // plugin_code
        $table->addColumn('plugin_code', 'text', array(
            'notnull' => false,
        ));

        $table->setPrimaryKey(array('payment_id'));
    }

    protected function createDtbMdlOrderPayment(Schema $schema)
    {
    	$table = $schema->createTable("dtb_mdl_order_payment");
    	$table->addColumn('order_id', 'integer', array(
    			'notnull' => true,
    	));
    	$table->addColumn('memo01', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo02', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo03', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo04', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo05', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo06', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo07', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo08', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo09', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('memo10', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('quick_flg', 'integer', array(
    			'notnull' => false,
    			'default' => null,
    	));
    	$table->addColumn('quick_memo', 'text', array(
    			'notnull' => false,
    			'default' => null,
    	));

    	$table->setPrimaryKey(array('order_id'));
    }

    public function postUp(Schema $schema)
    {

        $app = new \Eccube\Application();
        $app->initDoctrine();
        $app->boot();

        $pluginCode = 'MdlPaygent';
        $pluginName = 'MDLマルチペイメントサービス決済';
        $datetime = date('Y-m-d H:i:s');
        $insert = "INSERT INTO dtb_mdl_plugin(
                            plugin_code, plugin_name, create_date, update_date)
                    VALUES ('$pluginCode', '$pluginName', '$datetime', '$datetime'
                            );";
        $this->connection->executeUpdate($insert);
    }

    protected function deleteFromDtbPayment()
    {
        $select = "SELECT p.payment_id FROM dtb_mdl_payment_method as mdl
                JOIN dtb_payment as p ON mdl.payment_id = p.payment_id
                WHERE mdl.plugin_code =  'MdlPaygent'";

        $paymentIds = $this->connection->executeQuery($select)->fetchAll();
        $ids = array();

        foreach ($paymentIds as $item){
            $ids[]=$item['payment_id'];
        }

        if (!empty($ids)){
            $param = implode(",", $ids);
            $update = "UPDATE dtb_payment SET del_flg = 1 WHERE payment_id in ($param)";
            $this->connection->executeUpdate($update);
        }

    }

    protected function deletePagelayout()
    {
    	$sql_delete = " DELETE FROM dtb_page_layout WHERE url = 'mdl_paygent'";
    	$this->connection->executeUpdate($sql_delete);

    }


    function getMdlPaymentCode()
    {
        $config = \Eccube\Application::alias('config');

        return $paymentCodes;
    }

    /**
     * create a Page layout entry in dtb_page_layout
     *
     * @param    Eccube\Application     $app
     * @param    string                 $url
     * @param    int                    $deviceId
     * @param    string                 $name
     */
    protected function createPagelayout($app, $url, $deviceId, $name)
    {
        $deviceTypeRepo = $app['orm.em']->getRepository('Eccube\Entity\Master\DeviceType');
        $pageLayoutRepo = $app['orm.em']->getRepository('Eccube\Entity\PageLayout');
        $listOldVersion = array('3.0.1', '3.0.2', '3.0.3', '3.0.4', '3.0.5','3.0.6');
        in_array(Constant::VERSION, $listOldVersion) ? $pageLayoutRepo->setApp($app) : $pageLayoutRepo->setApplication($app);
        $deviceType = $deviceTypeRepo->find($deviceId);
        $pageLayout = $pageLayoutRepo->findOneBy(array('url' => $url));
        if (is_null($pageLayout)) {
            $pageLayout = $pageLayoutRepo->newPageLayout($deviceType);
        }
        $pageLayout->setCreateDate(new \DateTime());
        $pageLayout->setUpdateDate(new \DateTime());
        $pageLayout->setName($name);
        $pageLayout->setUrl($url);
        $pageLayout->setMetaRobots('noindex');
        $pageLayout->setEditFlg('2');
        $app['orm.em']->persist($pageLayout);
        $app['orm.em']->flush();
    }
}
