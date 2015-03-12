<?php

class Client
{
    static protected $url = '';
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';

    static function getAuthtoken( $parameters )
    {
        $parameters['username'] = '';
        $parameters['password'] = '';
        $parameters['ezuserlogin'] = '';
        try
        {
            // wo bringe ich die Route für Salesforce unter?
            $result = self::get( '/oauth/v2/token', $parameters, self::HTTP_POST );
            if( $result )
            {
                // Session mit dem Token setzen
                return true;
            }
        }
        catch ( Exception $e )
        {
            echo 'Methode: ' . __METHOD__;
            die(var_dump($e));
        }
    }
    
    public function getUser()
    {
        $test = self::getAuthtoken();
        $data = self::get('/user/14333');
    }
    
    public function getUserSubscription()
    {
        $test = self::getAuthtoken();
        $data = get('/user/subscriptions/14333');
    }
    
    static function get( $action = null, $parameters = array(), $httpaction = self::HTTP_POST )
    {
        $url = self::$url . '/' . $action;
        try {
            $response = self::sendRequest( $url, $parameters, 443, $httpaction );
            die(var_dump($response));
            if( $serverData->attribute( 'response_http_status' ) == 500 )
            {
                if( $apiVersion != 1 && $serverData->attribute( 'response_body' ) != '' )
                {
                    $response_body = $serverData->attribute( 'response_body' );
                    $doc = new DOMDocument();
                    $doc->loadXML( $response_body );
                    $responseXML = $doc->getElementsByTagName( 'response' );
                    if( $messagesXML = $doc->getElementsByTagName( 'message' ) )
                    {
                        foreach( $messagesXML as $message )
                        {
                            $msgtext = (string)$message->nodeValue;
                            $msgcode = (int)$message->getAttribute( 'msgcode' );
                        }
                    }
                    if( $serverData->attribute( 'response_http_status' ) > 0 && isset( $msgtext ) && $msgtext != '' && isset( $msgcode ) && $msgcode > 0 )
                    {
                        throw new eRASMoWebserviceException( $msgtext . '|' . $msgcode, $serverData->attribute( 'response_http_status' ) );
                    }
                }
                throw new eRASMoWebserviceException( $serverData->attribute( 'response_error' ), $serverData->attribute( 'response_http_status' ) );
            }
        }
        catch (Exception $e)
        {
            echo 'Methode: ' . __METHOD__;
            die(var_dump($e));
        }
    }

    static function sendRequest( $url, $request_data = array(), $port = 443, $serverMethod = 'POST' )
    {
        $url = new ezcUrl( $url );
        $Host = $url->__get( 'host' );
        $RequestURI = $url->__get( 'path' );
        if( is_array( $request_data ) && count( $request_data ) > 0 )
        {
            $RequestData = $request_data;
        }
        $Port = $port;

        $ch = curl_init();

        if ( strpos( $Host, '://' ) !== false )
        {
            $Host = substr( $Host, strpos( $Host, '://' ) + 3 );
        }

        $requestURI = 'http' . ( $Port == 443  ? 's' : '' ) . '://' . $Host . '/' . implode( '/', $RequestURI );

        if( $serverMethod == 'POST' )
        {
            curl_setopt( $ch, CURLOPT_POST, 1 );

            if( is_array( $RequestData ) && count( $RequestData ) > 0 )
            {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $RequestData );
            }
        }
        else if( $serverMethod == 'PUT' )
        {
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, "PUT" );
            if( is_array( $RequestData ) && count( $RequestData ) > 0 )
            {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $RequestData ) );
            }
        }
        else
        {
            if( is_array( $RequestData ) && count( $RequestData ) > 0 )
            {
                $query = http_build_query( $RequestData );
                $requestURI .= '?' . $query;
            }
        }

        curl_setopt( $ch, CURLOPT_URL, $requestURI );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER,1 );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 5 );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 20 );
        curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, 10 );
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( 'Host: '.$host ) );
        $result = curl_exec( $ch );

        $ResponseBody = $result;
        $ResponseHTTPStatus = curl_getinfo( $ch, CURLINFO_HTTP_CODE );

        if( $ResponseHTTPStatus == 500 )
        {
            $ResponseError = 'Internal Server Error';
        }

        curl_close($ch);

        eZDebug::writeNotice( array( 'RequestURI: ' . $RequestURI ), __METHOD__ );
        eZDebug::writeNotice( array( 'Response HTTP Method ' . $ServerMethod . ':', $this ), __METHOD__ );
    }
}