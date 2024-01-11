<?php

namespace Plugin\PlgExpandProductColumns\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlgExpandProductColumnsValue
 */
class PlgExpandProductColumnsValue extends \Eccube\Entity\AbstractEntity
{

    public function __get($name)
    {
        if (0 === strpos($name, 'value')) {
            return $this->getValue();
        }

        throw new  \Exception('no property >>> '. $name);
    }

    public function __set($property, $value)
    {
        if (0 === strpos($property, 'value')) {
            return $this->setValue($value);
        }

        throw new  \Exception('no property >>> '. $property);
    }

    /**
     * @var integer
     */
    private $productId;

    /**
     * @var integer
     */
    private $columnId;

    /**
     * @var string
     */
    private $value;


    /**
     * Set productId
     *
     * @param integer $productId
     * @return PlgExpandProductColumnsValue
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    /**
     * Get productId
     *
     * @return integer 
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * Set columnId
     *
     * @param integer $columnId
     * @return PlgExpandProductColumnsValue
     */
    public function setColumnId($columnId)
    {
        $this->columnId = $columnId;

        return $this;
    }

    /**
     * Get columnId
     *
     * @return integer 
     */
    public function getColumnId()
    {
        return $this->columnId;
    }

    /**
     * Set value
     *
     * @param string $value
     * @return PlgExpandProductColumnsValue
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }


}
