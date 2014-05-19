<?php
namespace PQ\CoreDomain\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use JMS\DiExtraBundle\Annotation as DI;
use PQ\CoreDomain\UserBundle\Entity\User;
use PQ\CoreDomain\UserBundle\Entity\Notification;
use PQ\CoreDomain\UserBundle\Event\UserEvent;
use PQ\CoreDomain\UserBundle\Event\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service("pq.user.notification.listener")
 * @DI\Tag("kernel.event_subscriber")
 **/
class NotificationListener implements EventSubscriberInterface
{
    protected $dispatcher;

    protected $mailer;

    protected $repo;

    protected $redis;

    protected $userRepository;

    protected $notificationManager;

    /**
     * @DI\InjectParams({
     *     "mailer" = @DI\Inject("mailer.twig"),
     *     "dispatcher" = @DI\Inject("event_dispatcher"),
     *     "repo" = @DI\Inject("notification_repository"),
     *     "redis" = @DI\Inject("snc_redis.default"),
     *     "userRepository" = @DI\Inject("user_repository"),
     *     "notificationManager" = @DI\Inject("notification_manager")
     * })
     */
    public function __construct($mailer, EventDispatcherInterface $dispatcher, $repo, $redis, $userRepository, $notificationManager)
    {
        $this->dispatcher = $dispatcher;
        $this->mailer = $mailer;
        $this->repo = $repo;
        $this->redis = $redis;
        $this->userRepository = $userRepository;
        $this->notificationManager = $notificationManager;
    }


    public static function getSubscribedEvents()
    {
        return array(
            UserEvents::EmailChange => 'EmailChange',
        );
    }

    public function EmailChange(UserEvent $event)
    {
        $user = $event->getUser();

        $this->notificationManager->notifyUser($user, "user:event", "lalala");
    }
}
