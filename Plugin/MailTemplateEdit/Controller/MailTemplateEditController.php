<?php
/* ActiveFusions 2015/11/09 16:10 */

namespace Plugin\MailTemplateEdit\Controller;

use Plugin\MailTemplateEdit\Form\Type\MailTemplateEditType;
use Eccube\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception as HttpException;

class MailTemplateEditController{
	private $main_title;
	private $sub_title;

	public function __construct(){
	}

	public function index(Application $app){
		$repos = $app['eccube.plugin.mailtemplateedit.repository.mailtemplateedit'];
		$form = $app->form()->getForm();
		$MailTemplates = $repos->findAll();

		return $app->render('MailTemplateEdit/View/admin/index.twig', array(
			'form'   				=> $form->createView(),
			'MailTemplates' 		=> $MailTemplates,
		));
	}

	public function edit(Application $app, Request $request, $id = null){
		$repos = $app['eccube.plugin.mailtemplateedit.repository.mailtemplateedit'];
		$TargetMailTemplate = new \Plugin\MailTemplateEdit\Entity\MailTemplateEdit();
		if($id) {
			$TargetMailTemplate = $repos->find($id);
			if(!$TargetMailTemplate) {
				throw new NotFoundHttpException();
			}
		}

		$form = $app['form.factory']->createBuilder('mailadd', $TargetMailTemplate)->getForm();

		if('POST' === $request->getMethod()) {
			$form->handleRequest($request);
			if($form->isValid()) {
				$status = $repos->save($TargetMailTemplate);

				if($status) {
					$app->addSuccess('admin.mailtemplateedit.save.complete', 'admin');
					return $app->redirect($app->url('admin_mailtemplateedit'));
				} else {
					$app->addError('admin.mailtemplateedit.save.error', 'admin');
				}
			}
		}

		return $app->render('MailTemplateEdit/View/admin/mailadd.twig', array(
			'form'   		=> $form->createView(),
			'TargetMailTemplate' 	=> $TargetMailTemplate,
		));

	}

	public function delete(Application $app, Request $request, $id){
		$repos = $app['eccube.plugin.mailtemplateedit.repository.mailtemplateedit'];
		$TargetMailTemplate = $repos->find($id);
		if(!$TargetMailTemplate) {
			throw new NotFoundHttpException();
		}

		$form = $app['form.factory']->createNamedBuilder('admin_mailtemplateedit', 'form', null, array('allow_extra_fields' => true,))->getForm();
		$status = false;
		if('DELETE' === $request->getMethod()) {
			$form->handleRequest($request);
			$status = $repos->delete($TargetMailTemplate);
		}
		if($status === true) {
			$app->addSuccess('admin.mailtemplateedit.delete.complete', 'admin');
		} else {
			$app->addError('admin.mailtemplateedit.delete.error', 'admin');
		}

		return $app->redirect($app->url('admin_mailtemplateedit'));
	}

}
