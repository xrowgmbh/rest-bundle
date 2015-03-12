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
        $configResolver = $this->getConfigResolver();
        // hier wird dann ein User aus dem CRM System geladen, sofert welches in xrow_rest_settings.plugins.crmclass gesetzt ist
        if($configResolver->hasParameter( 'xrow_rest_settings.plugins.crmclass' ))
        {
            $CRMClass = $configResolver->getParameter( 'xrow_rest_settings.plugins.crmclass' );
            $eZUserLoginName = $CRMClass::getAPIUser();
        }
        else
        {
            $eZUserLoginName = 'anonymous';
        }
        $event->setApiUser( $this->userService->loadUserByLogin( $eZUserLoginName ) );
    }
}