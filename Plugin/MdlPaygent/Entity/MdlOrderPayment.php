<?php
/*
 * Copyright(c) 2016 MDL Paygent, Inc. All rights reserved.
 * http://www.paygent.co.jp/
 */

namespace Plugin\MdlPaygent\Entity;

/**
 * Information about payment of an order
 *
 */
class MdlOrderPayment extends \Eccube\Entity\AbstractEntity
{

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $memo01;

    /**
     * @var string
     */
    private $memo02;

    /**
     * @var string
     */
    private $memo03;

    /**
     * @var string
     */
    private $memo04;

    /**
     * @var string
     */
    private $memo05;

    /**
     * @var string
     */
    private $memo06;

    /**
     * @var string
     */
    private $memo07;

    /**
     * @var string
     */
    private $memo08;

    /**
     * @var string
     */
    private $memo09;

    /**
     * @var string
     */
    private $memo10;

    /**
     * @var integer
     */
    private $quickFlg;

    /**
     * @var string
     */
    private $quickMemo;
    
    /**
     * @var smallint
     */
    private $invoiceSendType;

    /**
     * Set id
     *
     * @return Order
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set memo01
     *
     * @param  string $memo01
     * @return Order
     */
    public function setMemo01($memo01)
    {
        $this->memo01 = $memo01;

        return $this;
    }

    /**
     * Get memo01
     *
     * @return string
     */
    public function getMemo01()
    {
        return $this->memo01;
    }

    /**
     * Set memo02
     *
     * @param  string $memo02
     * @return Order
     */
    public function setMemo02($memo02)
    {
        $this->memo02 = $memo02;

        return $this;
    }

    /**
     * Get memo02
     *
     * @return string
     */
    public function getMemo02()
    {
        return $this->memo02;
    }

    /**
     * Set memo03
     *
     * @param  string $memo03
     * @return Order
     */
    public function setMemo03($memo03)
    {
        $this->memo03 = $memo03;

        return $this;
    }

    /**
     * Get memo03
     *
     * @return string
     */
    public function getMemo03()
    {
        return $this->memo03;
    }

    /**
     * Set memo04
     *
     * @param  string $memo04
     * @return Order
     */
    public function setMemo04($memo04)
    {
        $this->memo04 = $memo04;

        return $this;
    }

    /**
     * Get memo04
     *
     * @return string
     */
    public function getMemo04()
    {
        return $this->memo04;
    }

    /**
     * Set memo05
     *
     * @param  string $memo05
     * @return Order
     */
    public function setMemo05($memo05)
    {
        $this->memo05 = $memo05;

        return $this;
    }

    /**
     * Get memo05
     *
     * @return string
     */
    public function getMemo05()
    {
        return $this->memo05;
    }

    /**
     * Set memo06
     *
     * @param  string $memo06
     * @return Order
     */
    public function setMemo06($memo06)
    {
        $this->memo06 = $memo06;

        return $this;
    }

    /**
     * Get memo06
     *
     * @return string
     */
    public function getMemo06()
    {
        return $this->memo06;
    }

    /**
     * Set memo07
     *
     * @param  string $memo07
     * @return Order
     */
    public function setMemo07($memo07)
    {
        $this->memo07 = $memo07;

        return $this;
    }

    /**
     * Get memo07
     *
     * @return string
     */
    public function getMemo07()
    {
        return $this->memo07;
    }

    /**
     * Set memo08
     *
     * @param  string $memo08
     * @return Order
     */
    public function setMemo08($memo08)
    {
        $this->memo08 = $memo08;

        return $this;
    }

    /**
     * Get memo08
     *
     * @return string
     */
    public function getMemo08()
    {
        return $this->memo08;
    }

    /**
     * Set memo09
     *
     * @param  string $memo09
     * @return Order
     */
    public function setMemo09($memo09)
    {
        $this->memo09 = $memo09;

        return $this;
    }

    /**
     * Get memo09
     *
     * @return string
     */
    public function getMemo09()
    {
        return $this->memo09;
    }

    /**
     * Set memo10
     *
     * @param  string $memo10
     * @return Order
     */
    public function setMemo10($memo10)
    {
        $this->memo10 = $memo10;

        return $this;
    }

    /**
     * Get memo10
     *
     * @return string
     */
    public function getMemo10()
    {
        return $this->memo10;
    }

    /**
     * Set quickFlg
     *
     * @return quickFlg
     */
    public function setQuickFlg($quickFlg)
    {
    	$this->quickFlg = $quickFlg;
    	return $this;
    }

    /**
     * Get quickFlg
     *
     * @return integer
     */
    public function getQuickFlg()
    {
    	return $this->quickFlg;
    }

    /**
     * Set quickMemo
     *
     * @param  string $quickMemo
     * @return Order
     */
    public function setQuickMemo($quickMemo)
    {
    	$this->quickMemo = $quickMemo;

    	return $this;
    }

    /**
     * Get quickMemo
     *
     * @return string
     */
    public function getQuickMemo()
    {
    	return $this->quickMemo;
    }

    /**
     * Set invoiceSendType
     *
     * @param  smallint $invoiceSendType
     * @return $invoiceSendType
     */
    public function setInvoiceSendType($invoiceSendType)
    {
    	$this->invoiceSendType = $invoiceSendType;

    	return $this;
    }

    /**
     * Get invoiceSendType
     *
     * @return smallint
     */
    public function getInvoiceSendType()
    {
    	return $this->invoiceSendType;
    }

}
