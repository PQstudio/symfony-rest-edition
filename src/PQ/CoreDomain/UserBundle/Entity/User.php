<?php
namespace PQ\CoreDomain\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("user")
 * @ORM\AttributeOverrides({
 *      @ORM\AttributeOverride(name="password",
 *          column=@ORM\Column(
 *              name     = "password",
 *              type     = "string",
 *              length   = 255,
 *              nullable = true
 *          )
 *      )
 * })
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
    protected $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $oldEmail;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $changePassToken;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $changePassTokenDate;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $revertToken;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $revertTokenDate;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_id", type="string", nullable=true)
     */
    private $facebookId;

    /**
     * @var string
     *
     * @ORM\Column(name="facebook_access_token", type="string", nullable=true)
     */
    private $facebookAccessToken;

    /**
     * @var string
     *
     * @ORM\Column(name="google_id", type="string", nullable=true)
     */
    private $googleId;

    /**
     * @var string
     *
     * @ORM\Column(name="google_access_token", type="string", nullable=true)
     */
    private $googleAccessToken;

    /**
     * @var string
     *
     * @ORM\Column(name="linkedin_id", type="string", nullable=true)
     */
    private $linkedinId;

    /**
     * @var string
     *
     * @ORM\Column(name="linkedin_access_token", type="string", nullable=true)
     */
    private $linkedinAccessToken;

    protected $currentPassword;

    public $isEmailReverted;

    public function __construct()
    {
        parent::__construct();
        parent::setUsername(uniqid("u", true));
        $this->type = "employee";
        $this->isEmailReverted = false;
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

    public function setOldEmail($email)
    {
        $this->oldEmail = $email;
    }

    public function getOldEmail()
    {
        return $this->oldEmail;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
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

    public function setRevertToken($token)
    {
        $this->revertToken = $token;
    }

    public function getRevertToken()
    {
        return $this->revertToken;
    }

    public function setRevertTokenDate($tokenDate)
    {
        $this->revertTokenDate = $tokenDate;
    }

    public function getRevertTokenDate()
    {
        return $this->revertTokenDate;
    }

    public function setFacebookId($facebookId)
    {
        $this->facebookId = $facebookId;
    }

    public function getFacebookId()
    {
        return $this->facebookId;
    }

    public function setFacebookAccessToken($facebookAccessToken)
    {
        $this->facebookAccessToken = $facebookAccessToken;
    }

    public function getFacebookAccessToken()
    {
        return $this->facebookAccessToken;
    }

    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;
    }

    public function getGoogleId()
    {
        return $this->googleId;
    }

    public function setGoogleAccessToken($googleAccessToken)
    {
        $this->googleAccessToken = $googleAccessToken;
    }

    public function getGoogleAccessToken()
    {
        return $this->googleAccessToken;
    }

    public function setLinkedinId($linkedinId)
    {
        $this->linkedinId = $linkedinId;
    }

    public function getLinkedinId()
    {
        return $this->linkedinId;
    }

    public function setLinkedinAccessToken($linkedinAccessToken)
    {
        $this->linkedinAccessToken = $linkedinAccessToken;
    }

    public function getLinkedinAccessToken()
    {
        return $this->linkedinAccessToken;
    }
}
