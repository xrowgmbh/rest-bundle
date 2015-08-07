<?php
namespace wuv\aboshopBundle\Listener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class CorsListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $responseHeaders = $event->getResponse()->headers;
        $requestHeaders = $event->getRequest()->headers;

        $responseHeaders->set('Access-Control-Allow-Headers', 'Content-type');
        if ($requestHeaders->get('Origin')) {
            $responseHeaders->set('Access-Control-Allow-Origin', $requestHeaders->get('Origin'));
        }
        else {
            $responseHeaders->set('Access-Control-Allow-Origin', '*');
        }
        $responseHeaders->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PATCH, DELETE');
        $responseHeaders->set('Access-Control-Allow-Credentials', 'true');
    }
}