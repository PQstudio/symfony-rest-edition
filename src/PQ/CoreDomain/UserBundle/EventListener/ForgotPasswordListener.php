<?php
namespace PQ\CoreDomain\UserBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use JMS\DiExtraBundle\Annotation as DI;
use PQ\CoreDomain\UserBundle\Entity\User;
use PQ\CoreDomain\UserBundle\Event\UserEvent;
use PQ\CoreDomain\UserBundle\Event\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service("pq.user.forgotpassword.listener")
 * @DI\Tag("kernel.event_subscriber")
 **/
class ForgotPasswordListener implements EventSubscriberInterface
{
    protected $dispatcher;

    protected $mailer;

    protected $from;

    protected $fromName;

    /**
     * @DI\InjectParams({
     *     "mailer" = @DI\Inject("mailer.twig"),
     *     "dispatcher" = @DI\Inject("event_dispatcher"),
     *     "from" = @DI\Inject("%mailer.from%"),
     *     "fromName" = @DI\Inject("%mailer.fromName%")
     * })
     */
    public function __construct($mailer, EventDispatcherInterface $dispatcher, $from, $fromName)
    {
        $this->dispatcher = $dispatcher;
        $this->mailer = $mailer;
        $this->from = $from;
        $this->fromName = $fromName;
    }


    public static function getSubscribedEvents()
    {
        return array(
            UserEvents::ForgotPasswordRequest => 'forgotPasswordRequestEmail',
            UserEvents::ForgotPasswordChanged => 'forgotPasswordChangedEmail',
        );
    }

    public function forgotPasswordRequestEmail(UserEvent $event)
    {
        $user = $event->getUser();
        $templateName = "PQUserBundle:User:forgotPasswordRequest.email.twig";
        $data = ['user' => $user];
        $to = $user->getEmail();

        $this->mailer->send($templateName, $data, $this->from, $to, $this->fromName);
    }

    public function forgotPasswordChangedEmail(UserEvent $event)
    {
        $user = $event->getUser();
        $templateName = "PQUserBundle:User:forgotPasswordChanged.email.twig";
        $data = ['user' => $user];
        $to = $user->getEmail();

        $this->mailer->send($templateName, $data, $this->from, $to, $this->fromName);
    }
}
