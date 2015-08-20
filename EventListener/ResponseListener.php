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