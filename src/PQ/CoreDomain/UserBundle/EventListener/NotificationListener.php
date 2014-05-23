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

    protected $redis;

    protected $userRepository;

    protected $notificationManager;

    protected $notificationQuene;

    /**
     * @DI\InjectParams({
     *     "mailer" = @DI\Inject("mailer.twig"),
     *     "dispatcher" = @DI\Inject("event_dispatcher"),
     *     "redis" = @DI\Inject("snc_redis.default"),
     *     "userRepository" = @DI\Inject("user_repository"),
     *     "notificationManager" = @DI\Inject("notification_manager"),
     *     "notificationQuene" = @DI\Inject("notification_quene")
     * })
     */
    public function __construct($mailer, EventDispatcherInterface $dispatcher, $redis, $userRepository, $notificationManager, $notificationQuene)
    {
        $this->dispatcher = $dispatcher;
        $this->mailer = $mailer;
        $this->redis = $redis;
        $this->userRepository = $userRepository;
        $this->notificationManager = $notificationManager;
        $this->notificationQuene = $notificationQuene;
    }


    public static function getSubscribedEvents()
    {
        return array(
            UserEvents::EmailChange => 'emailChange',
            UserEvents::PasswordChange => 'passwordChange',
        );
    }

    public function emailChange(UserEvent $event)
    {
        $user = $event->getUser();

        $this->notificationQuene->scheduleNotify($user, "user:changed:email", "email zmieniony, niech to dundel swisnie");
    }

    public function passwordChange(UserEvent $event)
    {
        $user = $event->getUser();

        $this->notificationQuene->scheduleNotify($user, "user:change:password", "Haslo zmienione o nie!!");
    }
}
