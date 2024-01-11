<?php
namespace Plugin\PlgExpandProductColumns\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Exception\CartException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConfigController
{
    private $title;

    public function __construct()
    {
        $this->title = '';
    }

    /**
     *
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function init(Application $app, Request $request)
    {
        $repository = $app['eccube.plugin.repository.plg_expand_product_columns'];
        $entities = $repository->findAll();
        $ex_columns = array();
        $type_map = $app['PlgExpandProductColumns-TYPE_MAP'];
        foreach ($entities as $entity) {
            $column['columnId'] = $entity->getColumnId();
            $column['columnName'] = $entity->getColumnName();
            $column['columnType'] = $type_map[$entity->getColumnType()];
            $ex_columns[] = $column;
        }

        return $app->render('PlgExpandProductColumns/Resource/template/Admin/index.twig', array(
            'title' => $this->title,
            'ex_columns' => $ex_columns
        ));
    }

    public function edit(Application $app, Request $request, $id = null)
    {
        if (is_null($id)) {
            $ExColumns = new \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns();
        } else {
            /* @var $ExColumns \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns */
            $ExColumns = $app['eccube.plugin.repository.plg_expand_product_columns']
                ->findOneByColumnId($id);
        }


        $form = $app['form.factory']
            ->createBuilder('admin_plg_expand_product_columns')
            ->getForm();

        $form->setData($ExColumns);
        $form->handleRequest($request);

        // 新規登録の場合は編集画面に飛ばす
        if (isset($app['PlgExpandProductColumns-REDIRECT'])) {
            $url = $app['PlgExpandProductColumns-REDIRECT'];
            unset($app['PlgExpandProductColumns-REDIRECT']);
            if (is_null($id)) {
                return $app->redirect($url);
            }
        }

        return $app->render(
            'PlgExpandProductColumns/Resource/template/Admin/edit.twig',
            array(
                'form' => $form->createView(),
                'is_edit' => is_null($id) ? 0 : 1
            )
        );
    }

    public function delete(Application $app, Request $request, $id = null)
    {
        if (!is_null($id)) {
            $res = $app['eccube.plugin.repository.plg_expand_product_columns']->delete($id);
            if ($res) {
                $app->addSuccess('admin.delete.complete', 'admin');
            } else {
                $app->addError('admin.delete.failed', 'admin');
            }
        } else {
            $app->addError('admin.delete.failed', 'admin');
        }

        return $app->redirect($app->url('plugin_PlgExpandProductColumns_config'));

    }


    public function csvProduct(Application $app, Request $request)
    {
        $csvController = new PlgExpandProductColumnsCsvImportController();
        return $csvController->csvProduct($app, $request);
    }

    public function csvTemplate(Application $app, Request $request, $type)
    {
        $csvController = new PlgExpandProductColumnsCsvImportController();
        return $csvController->csvTemplate($app, $request, $type);
    }

    public function addImage(Application $app, Request $request)
    {
        $csvController = new PlgExpandProductColumnsProductController();
        return $csvController->addImage($app, $request);
    }
}
