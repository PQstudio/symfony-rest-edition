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
 * @DI\Service("pq.user.listener")
 * @DI\Tag("kernel.event_subscriber")
 **/
class UserListener implements EventSubscriberInterface
{
    protected $dispatcher;

    protected $mailer;

    protected $redis;

    protected $userRepository;

    protected $from;

    protected $fromName;

    protected $tokenGenerator;

    /**
     * @DI\InjectParams({
     *     "mailer" = @DI\Inject("mailer.twig"),
     *     "dispatcher" = @DI\Inject("event_dispatcher"),
     *     "redis" = @DI\Inject("snc_redis.default"),
     *     "userRepository" = @DI\Inject("user_repository"),
     *     "from" = @DI\Inject("%mailer.from%"),
     *     "fromName" = @DI\Inject("%mailer.fromName%"),
     *     "tokenGenerator" = @DI\Inject("fos_user.util.token_generator")
     * })
     */
    public function __construct($mailer, EventDispatcherInterface $dispatcher, $redis, $userRepository, $from, $fromName, $tokenGenerator)
    {
        $this->dispatcher = $dispatcher;
        $this->mailer = $mailer;
        $this->redis = $redis;
        $this->userRepository = $userRepository;
        $this->from = $from;
        $this->fromName = $fromName;
        $this->tokenGenerator = $tokenGenerator;
    }

    public static function getSubscribedEvents()
    {
        return array(
            UserEvents::Register => 'confirmEmailAfterRegister',
            UserEvents::EmailChange => 'confirmEmail',
        );
    }

    public function confirmEmailAfterRegister(UserEvent $event)
    {
        $user = $event->getUser();

        $templateName = "PQUserBundle:User:confirmEmail.email.twig";
        $data = ['user' => $user];
        $to = $user->getEmail();

        $this->mailer->send($templateName, $data, $this->from, $to, $this->fromName);
    }

    public function confirmEmail(UserEvent $event)
    {
        $user = $event->getUser();

        $user->setConfirmationToken($this->tokenGenerator->generateToken());

        $templateName = "PQUserBundle:User:confirmEmail.email.twig";
        $data = ['user' => $user];
        $to = $user->getEmail();

        $this->mailer->send($templateName, $data, $this->from, $to, $this->fromName);

        $user->setRevertToken($this->tokenGenerator->generateToken());
        $user->setRevertTokenDate(new \DateTime('now'));


        $templateName = "PQUserBundle:User:revertEmail.email.twig";
        $data = ['user' => $user];
        $to = $user->getOldEmail();

        $this->mailer->send($templateName, $data, $this->from, $to, $this->fromName);
    }

    //public function revertEmailToOldAddress(UserEvent $event)
    //{
        //$user = $event->getUser();

        //$user->setRevertToken($this->tokenGenerator->generateToken());
        //$user->setRevertTokenDate(new \DateTime('now'));


        //$templateName = "PQUserBundle:User:revertEmail.email.twig";
        //$data = ['user' => $user];
        //$to = $user->getOldEmail();

        //$this->mailer->send($templateName, $data, $this->from, $to, $this->fromName);
    //}
}
