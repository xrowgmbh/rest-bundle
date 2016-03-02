<?php
namespace xrow\restBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseListener
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $requestUri = $request->getRequestUri();
        if (strpos($requestUri, '/xrowapi/v1/') !== false || strpos($requestUri, '/oauth/v2/') !== false ||
            strpos($requestUri, '/xrowapi/v2/') !== false) {
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
            // If JsonResponse please escape
            if ($response instanceof JsonResponse && 
                (strpos($requestUri, '/xrowapi/v1/user') !== false || strpos($requestUri, '/xrowapi/v1/account') !== false || strpos($requestUri, '/xrowapi/v1/subscription') !== false ||
                 strpos($requestUri, '/xrowapi/v2/user') !== false || strpos($requestUri, '/xrowapi/v2/account') !== false || strpos($requestUri, '/xrowapi/v2/subscription') !== false)) {
                if (($content = $response->getContent()) != '') {
                    $jsonContent = json_decode($content, true);
                    die(var_dump($content));
                    //$response->setData($jsonContent);
                    // We would like to escape only result
                    if (isset($jsonContent->result)) {
                        $jsonArrayEscaped = array();
                        $this->escapeJsonContent($jsonContent->result, $jsonArrayEscaped, array('["result"]'));
                        unset($jsonContent->result);
                        $jsonContent->result = (object)$jsonArrayEscaped['result'];
                        $response->setContent(json_encode($jsonContent));
                    }
                }
            }
        }
    }

    /**
     * Create an array of escaped content
     *
     * @param array $value
     * @param array $jsonArrayEscaped
     * @param array $keyArray
     */
    private function escapeJsonContent($value, &$jsonArrayEscaped, $keyArray = array(), $index = 1)
    {
        if (!is_array($value) && !is_object($value)) {
            //$value = $this->pregReplaceUnwantedTags($value);
            if (count($keyArray) > 0) {
                $keyString = implode('', $keyArray);
                eval('$jsonArrayEscaped'.$keyString.' = htmlspecialchars($value);');
            }
            else {
                $jsonArrayEscaped = htmlspecialchars($value);
            }
        }
        else {
            foreach ($value as $keyItem => $valueItem) {
                if (!is_array($valueItem) && !is_object($valueItem)) {
                    //$valueItem = $this->pregReplaceUnwantedTags($valueItem);
                    if (count($keyArray) > 0) {
                        $keyString = implode('', $keyArray).'["'.$keyItem.'"]';
                        eval('$jsonArrayEscaped'.$keyString.' = htmlspecialchars($valueItem);');
                    }
                    else {
                        $jsonArrayEscaped[$keyItem] = htmlspecialchars($valueItem);
                    }
                }
                else {
                    $valueIndex = count((array)$value);
                    if ($valueIndex == $index) {
                        array_pop($keyArray);
                        $keyArray[] = '["'.$keyItem.'"]';
                    }
                    else {
                        $index++;
                        $keyArray[] = '["'.$keyItem.'"]';
                    }
                    $this->escapeJsonContent($valueItem, $jsonArrayEscaped, $keyArray, $index);
                }
            }
        }
    }

    /**
     * If we want to preg_replace unwanted tags
     * 
     * @param string $cleanedContent
     * @return string
     */
    private function pregReplaceUnwantedTags($cleanedContent)
    {
        do
        {
            // Remove really unwanted tags
            $checkContent = $cleanedContent;
            $cleanedContent = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $cleanedContent);
        }
        while ($checkContent !== $cleanedContent);
        return $cleanedContent;
    }
}