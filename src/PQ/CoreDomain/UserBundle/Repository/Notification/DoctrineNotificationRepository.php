<?php
namespace PQ\CoreDomain\UserBundle\Repository\Notification;

use PQ\CoreDomain\UserBundle\Entity\Notification;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class DoctrineNotificationRepository
{
    protected $em;

    protected $repo;

    protected $acl;

    protected $notificationEntity;

    public function __construct($em, $repo, $acl, $notificationEntity)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->acl = $acl;
        $this->notificationEntity = $notificationEntity;
    }

    public function find($notificationId)
    {
        return $this->repo->find($notificationId);
    }

    public function findAllForUser($user, $type, $limit, $offset)
    {
        $query = $this->em->createQuery(
           'SELECT p
            FROM '.$this->notificationEntity.' p
            WHERE p.owner = :user
            ORDER BY p.id DESC
            '
        )
        ->setParameter('user', $user->getId())
        ->setMaxResults($limit)
        ->setFirstResult($offset);

        $notifications = $query->getResult();
        return $notifications;
    }

    public function findAllForUserCount($user, $type)
    {
        $query = $this->em->createQuery(
           'SELECT COUNT(p.id)
            FROM '.$this->notificationEntity.' p
            WHERE p.owner = :user
            '
       )
       ->setParameter('user', $user->getId())
       ;

        $count = $query->getSingleScalarResult();
        return $count;
    }

    public function findAllNotReadForUserCount($user, $type)
    {
        $query = $this->em->createQuery(
           'SELECT COUNT(p.id)
            FROM '.$this->notificationEntity.' p
            WHERE p.owner = :user
            AND p.isRead = false
            '
       )
       ->setParameter('user', $user->getId())
       ;

        $count = $query->getSingleScalarResult();
        return $count;
    }

    public function add(Notification $notification, $user)
    {
        $this->em->persist($notification);
        $this->em->flush();

        $this->acl->addAclForUser($notification, $user, MaskBuilder::MASK_OWNER);

        return $this;
    }

    public function update(Notification $notification)
    {
        $this->em->persist($notification);
        $this->em->flush();

        return $this;
    }

    public function remove(Notification $notification)
    {
        $this->em->remove($user);
        $this->em->flush();

        return $this;
    }
}
