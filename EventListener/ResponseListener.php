<?php
namespace xrow\restBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $responseHeaders = $response->headers;
        $requestHeaders = $request->headers;
        if (strpos($request->getRequestUri(), '/xrowapi/v1/') !== false || strpos($request->getRequestUri(), '/oauth/v2/') !== false) {
            $responseHeaders->set('Access-Control-Allow-Headers', 'Content-type');
            if ($requestHeaders->get('Origin')) {
                $responseHeaders->set('Access-Control-Allow-Origin', $requestHeaders->get('Origin'));
            }
            else {
                $responseHeaders->set('Access-Control-Allow-Origin', '*');
            }
            $responseHeaders->set('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PATCH, DELETE');
            $responseHeaders->set('Access-Control-Allow-Credentials', 'true');
            if (strpos($request->getRequestUri(), '/xrowapi/v1/') !== false) {
                // private and max-age moved to config.yml
                $responseHeaders->addCacheControlDirective('no-cache', true);
                $responseHeaders->addCacheControlDirective('no-store', true);
                $responseHeaders->addCacheControlDirective('must-revalidate', true);
            }
        }
    }
}