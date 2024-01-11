<?php
namespace Plugin\PlgExpandProductColumns\Controller;

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Exception\CartException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;

class PlgExpandProductColumnsProductController extends \Eccube\Controller\ProductController
{
    private $title;

    public function __construct()
    {
        $this->title = '';
        parent::__construct();
    }

    public function edit(Application $app, Request $request, $id = null)
    {
        $has_class = false;
        if (is_null($id)) {
            $Product = new \Eccube\Entity\Product();
            $ProductClass = new \Eccube\Entity\ProductClass();
            $Disp = $app['eccube.repository.master.disp']->find(\Eccube\Entity\Master\Disp::DISPLAY_HIDE);
            $Product
                ->setDelFlg(Constant::DISABLED)
                ->addProductClass($ProductClass)
                ->setStatus($Disp);
            $ProductClass
                ->setDelFlg(Constant::DISABLED)
                ->setStockUnlimited(true)
                ->setProduct($Product);
            $ProductStock = new \Eccube\Entity\ProductStock();
            $ProductClass->setProductStock($ProductStock);
            $ProductStock->setProductClass($ProductClass);
        } else {
            $Product = $app['eccube.repository.product']->find($id);
            if (!$Product) {
                throw new NotFoundHttpException();
            }
            // 規格あり商品か
            $has_class = $Product->hasProductClass();
            if (!$has_class) {
                $ProductClasses = $Product->getProductClasses();
                $ProductClass = $ProductClasses[0];
                $BaseInfo = $app['eccube.repository.base_info']->get();
                if ($BaseInfo->getOptionProductTaxRule() == Constant::ENABLED && $ProductClass->getTaxRule() && !$ProductClass->getTaxRule()->getDelFlg()) {
                    $ProductClass->setTaxRate($ProductClass->getTaxRule()->getTaxRate());
                }
                $ProductStock = $ProductClasses[0]->getProductStock();
            }
        }

        $builder = $app['form.factory']
            ->createBuilder('admin_product', $Product);

        // 規格あり商品の場合、規格関連情報をFormから除外
        if ($has_class) {
            $builder->remove('class');
        }

        $event = new EventArgs(
            array(
                'builder' => $builder,
                'Product' => $Product,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_PRODUCT_EDIT_INITIALIZE, $event);

        $form = $builder->getForm();

        if (!$has_class) {
            $ProductClass->setStockUnlimited((boolean)$ProductClass->getStockUnlimited());
            $form['class']->setData($ProductClass);
        }

        // ファイルの登録
        $images = array();
        $ProductImages = $Product->getProductImage();
        foreach ($ProductImages as $ProductImage) {
            $images[] = $ProductImage->getFileName();
        }
        $form['images']->setData($images);

        $categories = array();
        $ProductCategories = $Product->getProductCategories();
        foreach ($ProductCategories as $ProductCategory) {
            /* @var $ProductCategory \Eccube\Entity\ProductCategory */
            $categories[] = $ProductCategory->getCategory();
        }
        $form['Category']->setData($categories);

        $Tags = array();
        $ProductTags = $Product->getProductTag();
        foreach ($ProductTags as $ProductTag) {
            $Tags[] = $ProductTag->getTag();
        }
        $form['Tag']->setData($Tags);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $Product = $form->getData();

                if (!$has_class) {
                    $ProductClass = $form['class']->getData();

                    // 個別消費税
                    $BaseInfo = $app['eccube.repository.base_info']->get();
                    if ($BaseInfo->getOptionProductTaxRule() == Constant::ENABLED) {
                        if ($ProductClass->getTaxRate()) {
                            if ($ProductClass->getTaxRule() && !$ProductClass->getTaxRule()->getDelFlg()) {
                                $ProductClass->getTaxRule()->setTaxRate($ProductClass->getTaxRate());
                            } else {
                                $taxrule = $app['eccube.repository.tax_rule']->newTaxRule();
                                $taxrule->setTaxRate($ProductClass->getTaxRate());
                                $taxrule->setApplyDate(new \DateTime());
                                $taxrule->setProduct($Product);
                                $taxrule->setProductClass($ProductClass);
                                $ProductClass->setTaxRule($taxrule);
                            }
                        } else {
                            if ($ProductClass->getTaxRule()) {
                                $ProductClass->getTaxRule()->setDelFlg(Constant::ENABLED);
                            }
                        }
                    }
                    $app['orm.em']->persist($ProductClass);

                    // 在庫情報を作成
                    if (!$ProductClass->getStockUnlimited()) {
                        $ProductStock->setStock($ProductClass->getStock());
                    } else {
                        // 在庫無制限時はnullを設定
                        $ProductStock->setStock(null);
                    }
                    $app['orm.em']->persist($ProductStock);
                }

                // カテゴリの登録
                // 一度クリア
                /* @var $Product \Eccube\Entity\Product */
                foreach ($Product->getProductCategories() as $ProductCategory) {
                    $Product->removeProductCategory($ProductCategory);
                    $app['orm.em']->remove($ProductCategory);
                }
                $app['orm.em']->persist($Product);
                $app['orm.em']->flush();

                $count = 1;
                $Categories = $form->get('Category')->getData();
                foreach ($Categories as $Category) {
                    $ProductCategory = new \Eccube\Entity\ProductCategory();
                    $ProductCategory
                        ->setProduct($Product)
                        ->setProductId($Product->getId())
                        ->setCategory($Category)
                        ->setCategoryId($Category->getId())
                        ->setRank($count);
                    $app['orm.em']->persist($ProductCategory);
                    $count++;
                    /* @var $Product \Eccube\Entity\Product */
                    $Product->addProductCategory($ProductCategory);
                }

                // 画像の登録
                $add_images = $form->get('add_images')->getData();
                foreach ($add_images as $add_image) {
                    $ProductImage = new \Eccube\Entity\ProductImage();
                    $ProductImage
                        ->setFileName($add_image)
                        ->setProduct($Product)
                        ->setRank(1);
                    $Product->addProductImage($ProductImage);
                    $app['orm.em']->persist($ProductImage);

                    // 移動
                    $file = new File($app['config']['image_temp_realdir'] . '/' . $add_image);
                    $file->move($app['config']['image_save_realdir']);
                }

                // 画像の削除
                $delete_images = $form->get('delete_images')->getData();
                foreach ($delete_images as $delete_image) {
                    $ProductImage = $app['eccube.repository.product_image']
                        ->findOneBy(array('file_name' => $delete_image));

                    // 追加してすぐに削除した画像は、Entityに追加されない
                    if ($ProductImage instanceof \Eccube\Entity\ProductImage) {
                        $Product->removeProductImage($ProductImage);
                        $app['orm.em']->remove($ProductImage);

                    }
                    $app['orm.em']->persist($Product);

                    // 削除
                    $fs = new Filesystem();
                    $fs->remove($app['config']['image_save_realdir'] . '/' . $delete_image);
                }


                $app['orm.em']->persist($Product);
                $app['orm.em']->flush();


                $ranks = $request->get('rank_images');
                if ($ranks) {
                    foreach ($ranks as $rank) {
                        list($filename, $rank_val) = explode('//', $rank);
                        $ProductImage = $app['eccube.repository.product_image']
                            ->findOneBy(array(
                                'file_name' => $filename,
                                'Product' => $Product,
                            ));
                        $ProductImage->setRank($rank_val);
                        $app['orm.em']->persist($ProductImage);
                    }
                }
                $app['orm.em']->flush();

                // 商品タグの登録
                // 商品タグを一度クリア
                $ProductTags = $Product->getProductTag();
                foreach ($ProductTags as $ProductTag) {
                    $Product->removeProductTag($ProductTag);
                    $app['orm.em']->remove($ProductTag);
                }

                // 商品タグの登録
                $Tags = $form->get('Tag')->getData();
                foreach ($Tags as $Tag) {
                    $ProductTag = new ProductTag();
                    $ProductTag
                        ->setProduct($Product)
                        ->setTag($Tag);
                    $Product->addProductTag($ProductTag);
                    $app['orm.em']->persist($ProductTag);
                }
                $app['orm.em']->flush();

                $event = new EventArgs(
                    array(
                        'form' => $form,
                        'Product' => $Product,
                    ),
                    $request
                );
                $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_PRODUCT_EDIT_COMPLETE, $event);

                $app->addSuccess('admin.register.complete', 'admin');

                return $app->redirect($app->url('admin_product_product_edit', array('id' => $Product->getId())));
            } else {
                $app->addError('admin.register.failed', 'admin');
            }
        }

        // 検索結果の保持
        $builder = $app['form.factory']
            ->createBuilder('admin_search_product');

        $event = new EventArgs(
            array(
                'builder' => $builder,
                'Product' => $Product,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_PRODUCT_EDIT_SEARCH, $event);

        $searchForm = $builder->getForm();

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
        }

        return $app->render('Product/product.twig', array(
            'Product' => $Product,
            'form' => $form->createView(),
            'searchForm' => $searchForm->createView(),
            'has_class' => $has_class,
            'id' => $id
        ));
    }

    /**
     * 画像アップロード時のメソッドをオーバーライド
     *
     * @param Application $app
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     */
    public function addImage(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException();
        }

        $images = $request->files->get('admin_product');

        $files = array();
        if (count($images) > 0) {
            foreach ($images as $img) {
                foreach ($img as $image) {
                    /**
                     * デフォルトの階層
                     */
                    if ($image instanceof UploadedFile) {
                        //ファイルフォーマット検証
                        $mimeType = $image->getMimeType();
                        if (0 !== strpos($mimeType, 'image')) {
                            throw new UnsupportedMediaTypeHttpException();
                        }

                        $extension = $image->getClientOriginalExtension();
                        $filename = date('mdHis') . uniqid('_') . '.' . $extension;
                        $image->move($app['config']['image_temp_realdir'], $filename);
                        $files[] = $filename;
                    }
                    /**
                     * プラグインの階層
                     */
                    else {
                        foreach ($image as $ex_images) {
                            foreach ($ex_images as $ex_image) {
                                //ファイルフォーマット検証
                                $mimeType = $ex_image->getMimeType();
                                if (0 !== strpos($mimeType, 'image')) {
                                    throw new UnsupportedMediaTypeHttpException();
                                }

                                $extension = $ex_image->getClientOriginalExtension();
                                $filename = date('mdHis') . uniqid('_') . '.' . $extension;
                                $ex_image->move($app['config']['image_temp_realdir'], $filename);
                                $files[] = $filename;
                            }
                        }

                    }
                }
            }
        }

        $event = new EventArgs(
            array(
                'images' => $images,
                'files' => $files,
            ),
            $request
        );
        $app['eccube.event.dispatcher']->dispatch(EccubeEvents::ADMIN_PRODUCT_ADD_IMAGE_COMPLETE, $event);
        $files = $event->getArgument('files');

        return $app->json(array('files' => $files), 200);
    }

    /**
     *
     * Overrideしている箇所は最後のrender時に渡している1行だけ。
     * Upgradeがあって、ロジックが変更された場合は、このdetailメソッドを
     * コピペして、最後のrender部分だけ修正して対応する
     *
     * @param Application $app
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function detail(Application $app, Request $request, $id)
    {
        $BaseInfo = $app['eccube.repository.base_info']->get();
        if ($BaseInfo->getNostockHidden() === Constant::ENABLED) {
            $app['orm.em']->getFilters()->enable('nostock_hidden');
        }

        /* @var $Product \Eccube\Entity\Product */
        $Product = $app['eccube.repository.product']->get($id);
        if (!$request->getSession()->has('_security_admin') && $Product->getStatus()->getId() !== 1) {
            throw new NotFoundHttpException();
        }
        if (count($Product->getProductClasses()) < 1) {
            throw new NotFoundHttpException();
        }

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $app['form.factory']->createNamedBuilder('', 'add_cart', null, array(
            'product' => $Product,
            'id_add_product_id' => false,
        ));
        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $addCartData = $form->getData();
                if ($addCartData['mode'] === 'add_favorite') {
                    if ($app->isGranted('ROLE_USER')) {
                        $Customer = $app->user();
                        $app['eccube.repository.customer_favorite_product']->addFavorite($Customer, $Product);
                        $app['session']->getFlashBag()->set('product_detail.just_added_favorite', $Product->getId());
                    }

                    return $app->redirect($app->url('product_detail', array('productId' => $Product->getId())));
                } else {
                    try {
                        $app['eccube.service.cart']->addProduct($addCartData['product_class_id'], $addCartData['quantity'])->save();
                    } catch (CartException $e) {
                        $app->addRequestError($e->getMessage());
                    }

                    return $app->redirect($app->url('cart'));
                }
            }
        }

        if ($app->isGranted('ROLE_USER')) {
            $Customer = $app->user();
            $is_favorite = $app['eccube.repository.customer_favorite_product']->isFavorite($Customer, $Product);
        } else {
            $is_favorite = false;
        }

        return $app->render('Product/detail.twig', array(
            'title' => $this->title,
            'subtitle' => $Product->getName(),
            'form' => $form->createView(),
            'Product' => $Product,
            'is_favorite' => $is_favorite,
            'expand_values' => $this->getExpandValues($app, $Product)
        ));
    }

    private function getExpandValues(Application $app, \Eccube\Entity\Product $Product)
    {
        $result = array();
        $columns = $app['eccube.plugin.repository.plg_expand_product_columns']
            ->findAll();
        $values = $app['eccube.plugin.repository.plg_expand_product_columns_value']
            ->findBy(array(
                'productId' => $Product->getId()
            ));

        foreach ($columns as $column) {
            $val = '';
            foreach ($values as $value) {
                if ($column['column_id'] == $value['column_id']) {
                    $val = $value['value'];
                    break;
                }
            }
            $result[$column['column_id']] = array (
                'name' => $column['column_name'],
                'value' => $val
            );
        }


        return $result;
    }


}
