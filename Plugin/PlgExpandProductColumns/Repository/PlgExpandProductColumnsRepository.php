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
 * PlgExpandProductColumnsRepository
 *
 */
class PlgExpandProductColumnsRepository extends EntityRepository
{
    public function save($column_name, $column_type, $csv_id, $column_setting = null, $column_id = null)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            if (is_null($column_id)) {
                $entity = null;
            } else {
                $entity = $em->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns')
                    ->findOneBy(array(
                        'columnId' => $column_id
                    ));
            }

            if (is_null($entity)) {
                $entity = new \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns();
                /*$entity->setColumnName($column_name);
                $entity->setColumnType($column_type);
                $entity->setCsvId($csv_id);
                $entity->setColumnSetting($column_setting);*/
                $em->persist($entity);
            }
            $entity->setColumnName($column_name);
            $entity->setColumnType($column_type);
            $entity->setColumnSetting($column_setting);
            $entity->setCsvId($csv_id);
            $em->flush();
            $em->getConnection()->commit();
            $saved_id = $entity->getColumnId();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();

            return false;
        }

        return $saved_id;
    }

    public function delete($column_id)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            /** @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns $entity */
            $entity = $em->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns')
                ->findOneBy(array(
                    'columnId' => $column_id
                ));
            $entity_values = $em->getRepository('\Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue')
                ->findBy(array(
                    'columnId' => $column_id
                ));
            $csv = $em->getRepository('Eccube\Entity\Csv')
                ->findOneBy(array(
                    'id' => $entity->getCsvId()
                ));

            $em->remove($entity);
            $em->remove($csv);
            foreach ($entity_values as $entity_value) {
                $em->remove($entity_value);
            }

            $em->flush();
            $em->getConnection()->commit();
        }catch (\Exception $e) {
            $em->getConnection()->rollback();
            return false;
        }
        
        return true;
    }
}
