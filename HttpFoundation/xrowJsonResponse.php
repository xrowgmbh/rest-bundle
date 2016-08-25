<?php

namespace xrow\restBundle\HttpFoundation;

use Symfony\Component\HttpFoundation\JsonResponse as BaseJsonResponse;

/**
 * xrow: We overwrite JsonResponse because we would like to encode xss code before output.
 */
class xrowJsonResponse extends BaseJsonResponse
{
    /**
     * Sets the data to be sent as JSON.
     *
     * @param mixed $data
     *
     * @return JsonResponse
     *
     * @throws \InvalidArgumentException
     */
    public function setData($data = array())
    {
        return parent::setData( $data );
        if (count($data) > 0) {
            foreach ($data as $itemName => $dataItem) {
                $newData = array();
                $this->escapeJsonContent($data[$itemName], $newData, array());
                $data[$itemName] = $newData;
            }
        }
        if (defined('HHVM_VERSION')) {
            // HHVM does not trigger any warnings and let exceptions
            // thrown from a JsonSerializable object pass through.
            // If only PHP did the same...
            $data = json_encode($data, $this->encodingOptions);
        } else {
            try {
                if (PHP_VERSION_ID < 50400) {
                    // PHP 5.3 triggers annoying warnings for some
                    // types that can't be serialized as JSON (INF, resources, etc.)
                    // but doesn't provide the JsonSerializable interface.
                    set_error_handler(function () { return false; });
                    $data = @json_encode($data, $this->encodingOptions);
                } else {
                    // PHP 5.4 and up wrap exceptions thrown by JsonSerializable
                    // objects in a new exception that needs to be removed.
                    // Fortunately, PHP 5.5 and up do not trigger any warning anymore.
                    if (PHP_VERSION_ID < 50500) {
                        // Clear json_last_error()
                        json_encode(null);
                        $errorHandler = set_error_handler('var_dump');
                        restore_error_handler();
                        set_error_handler(function () use ($errorHandler) {
                            if (JSON_ERROR_NONE === json_last_error()) {
                                return $errorHandler && false !== call_user_func_array($errorHandler, func_get_args());
                            }
                        });
                    }

                    $data = json_encode($data, $this->encodingOptions);
                }

                if (PHP_VERSION_ID < 50500) {
                    restore_error_handler();
                }
            } catch (\Exception $e) {
                if (PHP_VERSION_ID < 50500) {
                    restore_error_handler();
                }
                if (PHP_VERSION_ID >= 50400 && 'Exception' === get_class($e) && 0 === strpos($e->getMessage(), 'Failed calling ')) {
                    throw $e->getPrevious() ?: $e;
                }
                throw $e;
            }
        }

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException(json_last_error_msg());
        }

        $this->data = $data;

        return $this->update();
    }

    /**
     * Create an array of escaped content
     *
     * @param array $value
     * @param array $jsonArrayEscaped
     * @param array $keyArray
     */
    private function escapeJsonContent($value, &$arrayEscaped, $keyArray = array(), $index = 1)
    {
        if (!is_array($value) && !is_object($value)) {
            //$value = $this->pregReplaceUnwantedTags($value);
            if (count($keyArray) > 0) {
                $keyString = implode('', $keyArray);
                eval('$arrayEscaped'.$keyString.' = htmlspecialchars($value);');
            }
            else {
                $arrayEscaped = htmlspecialchars($value);
            }
        }
        else {
            foreach ($value as $keyItem => $valueItem) {
                if (!is_array($valueItem) && !is_object($valueItem)) {
                    //$valueItem = $this->pregReplaceUnwantedTags($valueItem);
                    if (count($keyArray) > 0) {
                        $keyString = implode('', $keyArray).'["'.$keyItem.'"]';
                        eval('$arrayEscaped'.$keyString.' = htmlspecialchars($valueItem);');
                    }
                    else {
                        $arrayEscaped[$keyItem] = htmlspecialchars($valueItem);
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
                    $this->escapeJsonContent($valueItem, $arrayEscaped, $keyArray, $index);
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