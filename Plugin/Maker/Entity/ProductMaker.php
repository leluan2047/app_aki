<?php
/*
 * This file is part of the Maker plugin
 *
 * Copyright (C) 2016 LOCKON CO.,LTD. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\Maker\Entity;

use Eccube\Entity\AbstractEntity;
use Eccube\Util\EntityUtil;

/**
 * Class ProductMaker.
 */
class ProductMaker extends AbstractEntity
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $maker_url;
    /**
     * @var int
     */
    private $del_flg;
    /**
     * @var \DateTime
     */
    private $create_date;
    /**
     * @var \DateTime
     */
    private $update_date;

    /**
     * @var Maker
     */
    private $Maker;

    /**
     * Set Id.
     *
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set maker url.
     *
     * @param string $makerUrl
     *
     * @return $this
     */
    public function setMakerUrl($makerUrl)
    {
        $this->maker_url = $makerUrl;

        return $this;
    }

    /**
     * Get maker url.
     *
     * @return mixed
     */
    public function getMakerUrl()
    {
        return $this->maker_url;
    }

    /**
     * Set Del flg.
     *
     * @param $delFlg
     *
     * @return $this
     */
    public function setDelFlg($delFlg)
    {
        $this->del_flg = $delFlg;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDelFlg()
    {
        return $this->del_flg;
    }

    /**
     * @param \DateTime $createDate
     *
     * @return $this
     */
    public function setCreateDate(\DateTime $createDate)
    {
        $this->create_date = $createDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $updateDate
     *
     * @return $this
     */
    public function setUpdateDate(\DateTime $updateDate)
    {
        $this->update_date = $updateDate;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * @param Maker $maker
     *
     * @return $this
     */
    public function setMaker(Maker $maker)
    {
        $this->Maker = $maker;

        return $this;
    }

    /**
     * @return null|Maker
     */
    public function getMaker()
    {
        if (EntityUtil::isEmpty($this->Maker)) {
            return null;
        }

        return $this->Maker;
    }
}
