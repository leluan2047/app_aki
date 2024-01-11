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


namespace Plugin\PlgExpandProductColumns\Service;

use Eccube\Common\Constant;
use Eccube\Service\CsvExportService;
use Eccube\Util\EntityUtil;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Collections\ArrayCollection;

class PlgCsvExportService extends CsvExportService
{
    /**
     * @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns
     */
    private $productColumnsRepository;

    /**
     * @var \Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumnsValue
     */
    private $productColumnsValueRepository;

    function __construct($productColumnsRepository, $productColumnsValueRepository)
    {
        $this->productColumnsRepository = $productColumnsRepository;
        $this->productColumnsValueRepository = $productColumnsValueRepository;
    }


    /**
     * [override]
     * CSV出力項目と比較し, 合致するデータを返す.
     *
     * @param \Eccube\Entity\Csv $Csv
     * @param $entity
     * @return mixed|null|string|void
     */
    public function getData(\Eccube\Entity\Csv $Csv, $entity)
    {
        $csvEntityName = str_replace('\\\\', '\\', $Csv->getEntityName());
        $entityName = str_replace('\\\\', '\\', get_class($entity));

        // プラグインによって設定された値は、拡張した動作をさせる
        $colValueEntity = null;
        $plg = false;
        if ($csvEntityName === 'Plugin\PlgExpandProductColumns\Entity\PlgExpandProductColumns') {
            $plg = true;
            // CSVテーブルのFieldNameにcolumnIdを入れている
            $colValueEntity = $this->productColumnsValueRepository->findOneBy(
                array(
                    'productId' => $entity->getId(),
                    'columnId' => str_replace('PlgExpandProductColumns_', '', $Csv->getFieldName())
                )
            );
            if (is_null($colValueEntity)) {
                return null;
            }
        }

        // エンティティ名が一致するかどうかチェック.
        if ($csvEntityName !== $entityName && $plg === false) {
            return null;
        }

        // カラム名がエンティティに存在するかどうかをチェック.
        if ($plg) {
            if (!$colValueEntity->offsetExists('value')) {
                return null;
            }
        } else {
            if (!$entity->offsetExists($Csv->getFieldName())) {
                return null;
            }
        }

        // データを取得.
        if ($plg) {
            $data = $colValueEntity->offsetGet('value');
        } else {
            $data = $entity->offsetGet($Csv->getFieldName());
        }

        /**
         * 以降は編集していない
         */

        // one to one の場合は, dtb_csv.referece_field_nameと比較し, 合致する結果を取得する.
        if ($data instanceof \Eccube\Entity\AbstractEntity) {
            if (EntityUtil::isNotEmpty($data)) {
                return $data->offsetGet($Csv->getReferenceFieldName());
            }
        } elseif ($data instanceof \Doctrine\Common\Collections\Collection) {
            // one to manyの場合は, カンマ区切りに変換する.
            $array = array();
            foreach ($data as $elem) {
                if (EntityUtil::isNotEmpty($elem)) {
                    $array[] = $elem->offsetGet($Csv->getReferenceFieldName());
                }
            }
            return '="'.implode($this->config['csv_export_multidata_separator'], $array).'"';

        } elseif ($data instanceof \DateTime) {
            // datetimeの場合は文字列に変換する.
            return $data->format($this->config['csv_export_date_format']);

        } else {
            // スカラ値の場合はそのまま.
            return $data;
        }

        return null;
    }
}
