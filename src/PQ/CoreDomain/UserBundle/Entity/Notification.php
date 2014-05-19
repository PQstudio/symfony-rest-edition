<?php
namespace PQ\CoreDomain\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("notification")
 */
class Notification
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $isRead;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $message;

    /**
     * @ORM\ManyToOne(targetEntity="PQ\CoreDomain\UserBundle\Entity\User")
     */
    protected $owner;

    public function __construct()
    {
        $this->isRead = false;
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

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setIsRead($isRead)
    {
        $this->isRead = $isRead;
    }

    public function getIsRead()
    {
        return $this->isRead;
    }

    public function setMessage($message)
    {
        $this->message = $message;
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }
}
