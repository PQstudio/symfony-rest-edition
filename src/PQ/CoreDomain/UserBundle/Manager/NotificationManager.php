<?php
namespace PQ\CoreDomain\UserBundle\Manager;

use JMS\DiExtraBundle\Annotation as DI;
use PQ\CoreDomain\UserBundle\Entity\User;
use PQ\CoreDomain\UserBundle\Entity\Notification;
use PQ\CoreDomain\UserBundle\Event\UserEvent;
use PQ\CoreDomain\UserBundle\Event\UserEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @DI\Service("notification_manager")
 **/
class NotificationManager
{
    protected $dispatcher;

    protected $repo;

    protected $redis;

    protected $userRepository;

    /**
     * @DI\InjectParams({
     *     "dispatcher" = @DI\Inject("event_dispatcher"),
     *     "repo" = @DI\Inject("notification_repository"),
     *     "redis" = @DI\Inject("snc_redis.default"),
     *     "userRepository" = @DI\Inject("user_repository")
     * })
     */
    public function __construct(EventDispatcherInterface $dispatcher, $repo, $redis, $userRepository)
    {
        $this->dispatcher = $dispatcher;
        $this->repo = $repo;
        $this->redis = $redis;
        $this->userRepository = $userRepository;
    }

    public function notifyUser(User $user, $type, $message)
    {
        $n = new Notification();
        $n->setType($type);
        $n->setMessage($message);
        $n->setOwner($user);

        $data = ['type' => $n->getType(), 'message' => $n->getMessage()];

        $accessToken = $this->userRepository->findAccessTokenByUser($user);

        if(null != $accessToken && !$accessToken->hasExpired()) {
            $this->redis->publish('user:'.$accessToken->getToken(), json_encode($data));
        }
        $this->repo->add($n, $user);
    }
}
