<?php
/**
 * File containing the SessionSetDynamicNameListener class.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 * @version //autogentag//
 */

namespace xrow\restBundle\EventListener;

use eZ\Publish\Core\MVC\ConfigResolverInterface;
use eZ\Publish\Core\MVC\Symfony\MVCEvents;
use eZ\Publish\Core\MVC\Symfony\Event\PostSiteAccessMatchEvent;
use eZ\Bundle\EzPublishCoreBundle\Kernel;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * SiteAccess match listener.
 *
 * Allows to set a dynamic session name based on the siteaccess name.
 */
class SessionNameListener implements EventSubscriberInterface
{
    /**
     * Prefix for session name.
     */
    const SESSION_NAME_PREFIX = 'eZSESSID';

    /**
     * @var \eZ\Publish\Core\MVC\ConfigResolverInterface
     */
    private $configResolver;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\SessionInterface|null
     */
    private $session;

    /**
     * @var \Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface|NativeSessionStorage
     */
    private $sessionStorage;

    /**
     * @param ConfigResolverInterface $configResolver
     * @param SessionInterface $session
     * @param \Symfony\Component\HttpFoundation\Session\Storage\SessionStorageInterface $sessionStorage
     */
    public function __construct( ConfigResolverInterface $configResolver, SessionInterface $session = null, SessionStorageInterface $sessionStorage = null )
    {
        $this->configResolver = $configResolver;
        $this->session = $session;
        $this->sessionStorage = $sessionStorage;
    }

    public static function getSubscribedEvents()
    {
        return array(
            MVCEvents::SITEACCESS => array( 'onSiteAccessMatch', 250 )
        );
    }

    public function onSiteAccessMatch( PostSiteAccessMatchEvent $event )
    {
        if (
            !(
                $event->getRequestType() === HttpKernelInterface::MASTER_REQUEST
                && isset( $this->session )
                && !$this->session->isStarted()
                && $this->sessionStorage instanceof NativeSessionStorage
            )
        )
        {
            return;
        }

        $sessionOptions = (array)$this->configResolver->getParameter( 'session' );
        $sessionName = isset( $sessionOptions['name'] ) ? $sessionOptions['name'] : $this->session->getName();
        $sessionOptions['name'] = $this->getSessionName( $sessionName, $event->getSiteAccess() );
        $this->sessionStorage->setOptions( $sessionOptions );
    }

    /**
     * @param string $sessionName
     * @param \eZ\Publish\Core\MVC\Symfony\SiteAccess $siteAccess
     *
     * @return string
     */
    private function getSessionName( $sessionName, SiteAccess $siteAccess )
    {
        return static::SESSION_NAME_PREFIX;
    }
}
