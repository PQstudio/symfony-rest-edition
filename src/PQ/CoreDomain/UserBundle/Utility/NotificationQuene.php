<?php
namespace PQ\CoreDomain\UserBundle\Utility;

use JMS\DiExtraBundle\Annotation as DI;


/**
 * @DI\Service("notification_quene")
 */
class NotificationQuene
{
    protected $quene = [];

    protected $notificationManager;

    /**
     * @DI\InjectParams({
     *     "notificationManager" = @DI\Inject("notification_manager")
     * })
     */
    public function __construct($notificationManager)
    {
        $this->notificationManager = $notificationManager;
    }

   public function scheduleNotify($user, $type, $message)
    {
        $this->quene[] = ['user' => $user, 'type' => $type, 'message' => $message];
    }

    public function execute()
    {
        foreach($this->quene as $notification) {
            $this->notificationManager->notifyUser($notification['user'], $notification['type'], $notification['message']);

        }
    }

    public function getQuene()
    {
        return $this->quene;
    }
}
