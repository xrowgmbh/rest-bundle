<?php
namespace xrow\restBundle\EventListener;

use eZ\Publish\API\Repository\UserService;
use eZ\Publish\Core\MVC\Symfony\Event\InteractiveLoginEvent;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class InteractiveLoginListener implements EventSubscriberInterface
{
    /**
     * @var \eZ\Publish\API\Repository\UserService
     */
    private $userService;

    public function __construct( UserService $userService )
    {
        $this->userService = $userService;
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin'
        );
    }

    public function onInteractiveLogin( InteractiveLoginEvent $event )
    {
        // We just load a generic user and assign it back to the event.
        // You may want to create users here, or even load predefined users depending on your own rules.
        $event->setApiUser( $this->userService->loadUserByLogin( 'lolautruche' ) );
    }
}