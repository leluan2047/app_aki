<?php
/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) 2000-2015 LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */


namespace Plugin\PlgExpandProductColumns\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PlgExpandProductColumnsValueRepository
 *
 */
class PlgExpandProductColumnsValueRepository extends EntityRepository
{
    public function save($product_id, $column_id, $value)
    {
        $em = $this->getEntityManager();
        $em->clear('Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue');
        $em->getConnection()->beginTransaction();
        try {
            $entity = $em->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue')
                ->findOneBy(array(
                    'productId' => $product_id,
                    'columnId' => $column_id
                ));

            /**
             * 保存する値はあるが、まだ登録されていなければ新規追加
             */
            if (is_null($entity) && !empty($value)) {
                $entity = new \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue();
                $entity->setProductId($product_id);
                $entity->setColumnId($column_id);
                $entity->setValue($value);
                $em->persist($entity);

            }
            /**
             * 保存する値がなく、登録済なら削除
             */
            else if (!is_null($entity) && empty($value)) {
                $em->remove($entity);
            }
            /**
             * 保存する値があれば、更新
             */
            else if (!is_null($entity) && !empty($value)) {
                $entity->setValue($value);
            }

            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            error_log($e->getMessage());

            return false;
        }

        return true;
    }

    public function delete($column_id) {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            /** @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue $entity */
            $entity = $em->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue')
                ->findOneBy(array(
                    'columnId' => $column_id
                ));

            $em->remove($entity);

            $em->flush();
            $em->getConnection()->commit();
        }catch (\Exception $e) {
            $em->getConnection()->rollback();
            return false;
        }

        return true;
    }

    public function getExProductImages($product_id)
    {
        $result = array();
        $em = $this->getEntityManager();
        try {
            $columns = $em->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns')
                ->findBy(
                    array('columnType' => EX_TYPE_IMAGE),
                    array('columnId' => 'ASC')
                );

            foreach ($columns as $column) {
                /** @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns $column */
                $column_id = $column->getColumnId();
                $result['value-'.$column_id][] = $this->findOneBy(array(
                    'columnId' => $column_id,
                    'productId' => $product_id
                ));
            }

        }catch (\Exception $e) {
            throw $e;
        }

        return $result;
    }
}
