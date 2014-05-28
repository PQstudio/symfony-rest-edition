<?php
namespace PQ\CoreDomain\UserBundle\Repository\User;

use PQ\CoreDomain\UserBundle\Entity\User;
use FOS\UserBundle\Model\UserInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class DoctrineUserRepository
{
    protected $em;

    protected $repo;

    protected $userManager;

    protected $tokenGenerator;

    protected $acl;

    protected $userEntity;

    public function __construct($em, $repo, $userManager, $tokenGenerator, $acl, $userEntity)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->userManager = $userManager;
        $this->tokenGenerator = $tokenGenerator;
        $this->acl = $acl;
        $this->userEntity = $userEntity;
    }

    public function find($userId)
    {
        return $this->repo->find($userId);
    }

    public function findAll($limit, $offset)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            ORDER BY p.id DESC
            '
        )
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        $users = $query->getResult();
        return $users;
    }

    public function findAllCount()
    {
        $query = $this->em->createQuery(
           'SELECT COUNT(p.id)
            FROM '.$this->userEntity.' p
            '
       );

        $count = $query->getSingleScalarResult();
        return $count;
    }

    public function findAccessTokenByUser($user)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM PQ\CoreDomain\OAuthBundle\Entity\AccessToken p
            WHERE p.user = :user
            ORDER BY p.id DESC
            '
        )->setParameter('user', $user->getId());

        $accessTokens = $query->getResult();
        $accessToken = null;
        if(!empty($accessTokens)) {
            $accessToken = $accessTokens[0];
        }

        return $accessToken;
    }

    public function findByConfirmationToken($token)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            WHERE p.confirmationToken = :token
            '
        )->setParameter('token', $token);

        $user = $query->getOneOrNullResult();
        return $user;
    }

    public function findByRevertToken($token)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            WHERE p.revertToken = :token
            '
        )->setParameter('token', $token);

        $user = $query->getOneOrNullResult();
        return $user;
    }

    public function findByChangePassToken($token)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            WHERE p.changePassToken = :token
            '
        )->setParameter('token', $token);

        $user = $query->getOneOrNullResult();
        return $user;
    }

    public function findByEmail($email)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            WHERE p.email = :email
            '
        )->setParameter('email', $email);

        $user = $query->getOneOrNullResult();
        return $user;
    }

    public function findByFacebookId($id)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            WHERE p.facebookId = :id
            '
        )->setParameter('id', $id);

        $user = $query->getOneOrNullResult();
        return $user;
    }

    public function findByGoogleId($id)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            WHERE p.googleId = :id
            '
        )->setParameter('id', $id);

        $user = $query->getOneOrNullResult();
        return $user;
    }

    public function findByLinkedinId($id)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->userEntity.' p
            WHERE p.linkedinId = :id
            '
        )->setParameter('id', $id);

        $user = $query->getOneOrNullResult();
        return $user;
    }

    public function add(UserInterface $user)
    {
        $user->setConfirmationToken($this->tokenGenerator->generateToken());
        $this->userManager->updateUser($user);

        $this->acl->addAclForUser($user, $user, MaskBuilder::MASK_OWNER);

        return $this;
    }

    public function update(UserInterface $user)
    {
        $this->userManager->updateUser($user);

        return $this;
    }

    public function remove(UserInterface $user)
    {
        $this->em->remove($user);
        $this->em->flush();

        return $this;
    }
}
