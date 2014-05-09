<?php
namespace PQ\CoreDomain\UserBundle\EventListener;

use JMS\DiExtraBundle\Annotation as DI;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PQ\CoreDomain\UserBundle\Entity\User;
use PQ\CoreDomain\UserBundle\Event\UserEvent;
use PQ\CoreDomain\UserBundle\Event\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\DoctrineListener(
 *     events = {"preUpdate"},
 *     connection = "default",
 *     lazy = true,
 *     priority = 0
 * )
 **/
class EmailChangeListener
{
    protected $dispatcher;

    /**
     * @DI\InjectParams({
     *     "dispatcher" = @DI\Inject("event_dispatcher")
     * })
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $entity = $eventArgs->getEntity();
        $em = $eventArgs->getEntityManager();
        $uow = $em->getUnitOfWork();

        if($entity instanceof User) {
            if($eventArgs->hasChangedField('email')) {
                if(!$eventArgs->hasChangedField('newEmail')) {
                    $eventArgs->setNewValue('email', $eventArgs->getOldValue('email'));
                    $entity->setNewEmail($entity->getEmail());
                    $this->dispatcher->dispatch(UserEvents::EmailChange, new UserEvent($entity));
                }

                $meta = $em->getClassMetadata(get_class($entity));
                $uow->recomputeSingleEntityChangeSet($meta, $entity);
            }
        }
    }
}
