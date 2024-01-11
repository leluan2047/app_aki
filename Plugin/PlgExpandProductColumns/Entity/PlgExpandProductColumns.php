<?php

namespace Plugin\PlgExpandProductColumns\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PlgExpandProductColumns
 */
class PlgExpandProductColumns extends \Eccube\Entity\AbstractEntity
{

    public function __get($name)
    {
        if (0 === strpos($name, 'columnId')) {
            return $this->getColumnId();
        } else if (0 === strpos($name, 'columnName')) {
            return $this->getColumnName();
        } else if (0 === strpos($name, 'columnType')) {
            return $this->getColumnType();
        }

        throw new  \Exception('no property >>> '. $name);
    }

    public function __set($property, $value)
    {
        if (0 === strpos($property, 'columnId')) {
            return $this->setColumnId($value);
        } else if (0 === strpos($property, 'columnName')) {
            return $this->setColumnName($value);
        } else if (0 === strpos($property, 'columnType')) {
            return $this->setColumnType($value);
        }

        throw new  \Exception('no property >>> '. $property);
    }
    /**
     * @var integer
     */
    private $columnId;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var integer
     */
    private $columnType;

    /**
     * @var string
     */
    private $columnSetting;
    
    /**
     * @var integer
     */
    private $csvId;

    /**
     * Set columnId
     *
     * @param integer $columnId
     * @return PlgExpandProductColumns
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
     * Set columnName
     *
     * @param string $columnName
     * @return PlgExpandProductColumns
     */
    public function setColumnName($columnName)
    {
        $this->columnName = $columnName;

        return $this;
    }

    /**
     * Get columnName
     *
     * @return string 
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * Set columnType
     *
     * @param integer $columnType
     * @return PlgExpandProductColumns
     */
    public function setColumnType($columnType)
    {
        $this->columnType = $columnType;

        return $this;
    }

    /**
     * Get columnType
     *
     * @return integer 
     */
    public function getColumnType()
    {
        return $this->columnType;
    }


    /**
     * Set columnSetting
     *
     * @param string $columnSetting
     * @return PlgExpandProductColumns
     */
    public function setColumnSetting($columnSetting)
    {
        $this->columnSetting = $columnSetting;

        return $this;
    }

    /**
     * Get columnSetting
     *
     * @return string
     */
    public function getColumnSetting()
    {
        return $this->columnSetting;
    }
    
    
    /**
     * Set csvId
     *
     * @param integer $csvId
     * @return PlgExpandProductColumns
     */
    public function setCsvId($csvId)
    {
        $this->csvId = $csvId;

        return $this;
    }

    /**
     * Get csvId
     *
     * @return integer
     */
    public function getCsvId()
    {
        return $this->csvId;
    }
}
