<?php
namespace xrow\restBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        if (strpos($request->getRequestUri(), '/xrowapi/v1/') !== false || strpos($request->getRequestUri(), '/oauth/v2/') !== false ||
            strpos($request->getRequestUri(), '/xrowapi/v2/') !== false) {
            $response->headers->set('Access-Control-Allow-Headers', 'Content-type');
            if ($request->headers->get('Origin')) {
                $response->headers->set('Access-Control-Allow-Origin', $request->headers->get('Origin'));
            }
            else {
                $response->headers->set('Access-Control-Allow-Origin', '*');
            }
            $response->headers->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PATCH, DELETE');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->setPrivate();
            $response->setMaxAge(0);
            $response->setSharedMaxAge(0);
            $response->mustRevalidate();
            $response->headers->addCacheControlDirective('no-store', true);
        }
    }
}