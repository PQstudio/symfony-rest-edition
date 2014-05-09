<?php
namespace PQ\CoreDomain\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("user")
 */
class User extends BaseUser
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
    protected $newEmail;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $changePassToken;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $changePassTokenDate;

    protected $currentPassword;

    public function __construct()
    {
        parent::__construct();
        parent::setUsername(uniqid("u", true));
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

    public function setEmail($email){
        parent::setEmail($email);
    }

    public function setNewEmail($email)
    {
        $this->newEmail = $email;
    }

    public function getNewEmail()
    {
        return $this->newEmail;
    }

    public function setCurrentPassword($password)
    {
        $this->currentPassword = $password;
    }

    public function getCurrentPassword()
    {
        return $this->currentPassword;
    }

    public function setChangePassToken($token)
    {
        $this->changePassToken = $token;
    }

    public function getChangePassToken()
    {
        return $this->changePassToken;
    }

    public function setChangePassTokenDate($tokenDate)
    {
        $this->changePassTokenDate = $tokenDate;
    }

    public function getChangePassTokenDate()
    {
        return $this->changePassTokenDate;
    }
}
