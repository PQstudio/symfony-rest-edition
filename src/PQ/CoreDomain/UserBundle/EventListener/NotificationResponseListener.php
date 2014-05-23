<?php
namespace PQ\CoreDomain\UserBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Response;

use JMS\DiExtraBundle\Annotation as DI;

/**
 * @DI\Service("notification_response.listener")
 */
class NotificationResponseListener
{
    protected $notificationQuene;

    /**
     * @DI\InjectParams({
     *     "notificationQuene" = @DI\Inject("notification_quene")
     * })
     */
    public function __construct($notificationQuene)
    {
        $this->notificationQuene = $notificationQuene;
    }

    /**
     * @DI\Observe("kernel.response", priority = 255)
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if(HttpKernelInterface::MASTER_REQUEST !== $event->getRequestType())
            return;

        $this->notificationQuene->execute();
    }
}
