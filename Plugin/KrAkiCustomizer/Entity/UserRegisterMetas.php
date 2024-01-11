<?php

namespace Plugin\KrAkiCustomizer\Entity;

/**
 * UserRegisterMetas
 */
class UserRegisterMetas extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $customer_id;

    /**
     * @var string
     */
    private $user_ip;

    /**
     * @var string
     */
    private $user_agent;

    /**
     * @var integer
     */
    private $status;

    /**
     * @var \DateTime
     */
    private $created_at;

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
     * Set id
     *
     * @param integer $id
     * @return UserRegisterMetas
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set customer_id
     *
     * @param integer $customerId
     * @return UserRegisterMetas
     */
    public function setCustomerId($customerId)
    {
        $this->customer_id = $customerId;

        return $this;
    }

    /**
     * Get customer_id
     *
     * @return integer
     */
    public function getCustomerId()
    {
        return $this->customer_id;
    }

    /**
     * Set user_ip
     *
     * @param string $userIp
     * @return UserRegisterMetas
     */
    public function setUserIp($userIp)
    {
        $this->user_ip = $userIp;

        return $this;
    }

    /**
     * Get user_ip
     *
     * @return string
     */
    public function getUserIp()
    {
        return $this->user_ip;
    }

    /**
     * Set user_agent
     *
     * @param string $userAgent
     * @return UserRegisterMetas
     */
    public function setUserAgent($userAgent)
    {
        $this->user_agent = $userAgent;

        return $this;
    }

    /**
     * Get user_agent
     *
     * @return string
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return UserRegisterMetas
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created_at
     *
     * @param date $createdAt
     * @return UserRegisterMetas
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

}
