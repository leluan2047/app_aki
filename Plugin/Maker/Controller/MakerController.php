<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Maker\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Application;
use Eccube\Controller\AbstractController;
use Plugin\Maker\Entity\Maker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class MakerController.
 */
class MakerController extends AbstractController
{
    /**
     * List, add, edit maker.
     *
     * @param Application $app
     * @param Request     $request
     * @param null        $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function index(Application $app, Request $request, $id = null)
    {
        $repos = $app['eccube.plugin.maker.repository.maker'];

        $TargetMaker = new Maker();

        if ($id) {
            $TargetMaker = $repos->find($id);
            if (!$TargetMaker) {
                log_error('The Maker not found!', array('Maker id' => $id));
                throw new NotFoundHttpException();
            }
        }

        $form = $app['form.factory']
            ->createBuilder('admin_maker', $TargetMaker)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('Maker add/edit start.');
            $status = $repos->save($TargetMaker);

            if ($status) {
                log_info('Maker add/edit success', array('Maker id' => $TargetMaker->getId()));
                $app->addSuccess('admin.plugin.maker.save.complete', 'admin');

                return $app->redirect($app->url('admin_plugin_maker_index'));
            } else {
                log_info('Maker add/edit fail!', array('Maker id' => $TargetMaker->getId()));
                $app->addError('admin.plugin.maker.save.error', 'admin');
            }
        }

        /**
         * @var ArrayCollection $arrMaker
         */
        $arrMaker = $app['eccube.plugin.maker.repository.maker']->findBy(array(), array('rank' => 'DESC'));

        return $app->render('Maker/Resource/template/admin/maker.twig', array(
            'form' => $form->createView(),
            'arrMaker' => $arrMaker,
            'TargetMaker' => $TargetMaker,
        ));
    }

    /**
     * Delete Maker.
     *
     * @param Application $app
     * @param Request     $request
     * @param int         $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function delete(Application $app, Request $request, $id = null)
    {
        // Valid token
        $this->isTokenValid($app);

        // Check request
        if (!'POST' === $request->getMethod()) {
            log_error('Delete with bad method!');
            throw new BadRequestHttpException();
        }

        // Id valid
        if (!$id) {
            log_info('The maker not found!', array('Maker id' => $id));
            $app->addError('admin.plugin.maker.not_found', 'admin');

            return $app->redirect($app->url('admin_plugin_maker_index'));
        }

        $repos = $app['eccube.plugin.maker.repository.maker'];

        $TargetMaker = $repos->find($id);

        if (!$TargetMaker) {
            log_error('The maker not found!', array('Maker id' => $id));
            throw new NotFoundHttpException();
        }

        $status = $repos->delete($TargetMaker);

        if ($status === true) {
            log_info('The maker delete success!', array('Maker id' => $id));
            $app->addSuccess('admin.plugin.maker.delete.complete', 'admin');
        } else {
            log_info('The maker delete fail!', array('Maker id' => $id));
            $app->addError('admin.plugin.maker.delete.error', 'admin');
        }

        return $app->redirect($app->url('admin_plugin_maker_index'));
    }

    /**
     * Move rank with ajax.
     *
     * @param Application $app
     * @param Request     $request
     *
     * @return bool
     */
    public function moveRank(Application $app, Request $request)
    {
        if ($request->isXmlHttpRequest()) {
            $arrRank = $request->request->all();
            $arrMoved = $app['eccube.plugin.maker.repository.maker']->moveMakerRank($arrRank);
            log_info('Maker move rank', $arrMoved);
        }

        return true;
    }
}
