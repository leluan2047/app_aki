<?php
/* ActiveFusions 2015/11/10 14:22 */

namespace Plugin\MailTemplateEdit\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * MailTemplateEdit
 */
class MailTemplateEdit extends \Eccube\Entity\AbstractEntity{
	/**
	* @return string
	*/
	public function __toString(){
		return $this->getSubject();
	}

	/**
	* @var integer
	*/
	private $id;

	/**
	* @var string
	*/
	private $name;

	/**
	* @var string
	*/
	private $file_name;

	/**
	* @var string
	*/
	private $subject;

	/**
	* @var string
	*/
	private $header;

	/**
	* @var string
	*/
	private $footer;

	/**
	* @var integer
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
	* @var \Eccube\Entity\Member
	*/
    private $Creator;


	/**
	* Get id
	*
	* @return integer 
	*/
	public function getId(){
		return $this->id;
	}

	public function setId($id){
		$this->id = $id;
		return $this;
	}

	/**
	* Set name
	*
	* @param string $name
	* @return MailTemplate
	*/
	public function setName($name){
		$this->name = $name;
		return $this;
	}

	/**
	* Get name
	*
	* @return string 
	*/
	public function getName(){
		return $this->name;
	}

	/**
	* Set file_name
	*
	* @param string $fileName
	* @return MailTemplate
	*/
	public function setFileName($fileName){
		$this->file_name = $fileName;
		return $this;
	}

	/**
	* Get file_name
	*
	* @return string 
	*/
	public function getFileName(){
		return $this->file_name;
	}

	/**
	* Set subject
	*
	* @param string $subject
	* @return MailTemplate
	*/
	public function setSubject($subject){
		$this->subject = $subject;
		return $this;
	}

	/**
	* Get subject
	*
	* @return string 
	*/
	public function getSubject(){
		return $this->subject;
	}

	/**
	* Set header
	*
	* @param string $header
	* @return MailTemplate
	*/
	public function setHeader($header){
		$this->header = $header;
		return $this;
	}

	/**
	* Get header
	*
	* @return string 
	*/
	public function getHeader(){
		return $this->header;
	}

	/**
	* Set footer
	*
	* @param string $footer
	* @return MailTemplate
	*/
	public function setFooter($footer){
		$this->footer = $footer;
		return $this;
	}

	/**
	* Get footer
	*
	* @return string 
	*/
	public function getFooter(){
		return $this->footer;
	}

	/**
	* Set del_flg
	*
	* @param integer $delFlg
	* @return MailTemplate
	*/
	public function setDelFlg($delFlg){
		$this->del_flg = $delFlg;
		return $this;
	}

	/**
	* Get del_flg
	*
	* @return integer 
	*/
	public function getDelFlg(){
		return $this->del_flg;
	}

	/**
	* Set create_date
	*
	* @param \DateTime $createDate
	* @return MailTemplate
	*/
	public function setCreateDate($createDate){
		$this->create_date = $createDate;
		return $this;
	}

	/**
	* Get create_date
	*
	* @return \DateTime 
	*/
	public function getCreateDate(){
		return $this->create_date;
	}

	/**
	* Set update_date
	*
	* @param \DateTime $updateDate
	* @return MailTemplate
	*/
	public function setUpdateDate($updateDate){
		$this->update_date = $updateDate;
		return $this;
	}

	/**
	* Get update_date
	*
	* @return \DateTime 
	*/
	public function getUpdateDate(){
		return $this->update_date;
	}

	/**
	* Set Creator
	*
	* @param \Eccube\Entity\Member $creator
	* @return MailTemplate
	*/
	public function setCreator(\Eccube\Entity\Member $creator){
		$this->Creator = $creator;
		return $this;
	}

	/**
	* Get Creator
	*
	* @return \Eccube\Entity\Member 
	*/
	public function getCreator(){
		return $this->Creator;
	}

}
