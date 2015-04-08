<?php

namespace xrow\restBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use OAuth2\OAuth2;
use OAuth2\OAuth2Client as FOSOAuth2Client;
use OAuth2\OAuth2ServerException;
use OAuth2\OAuth2Exception;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

class OAuth2Client
{
    protected $token_uri;
    protected $client_id;
    protected $client_secret;
    protected $currentUrl;
    /**
     * @var OAuth2
     */
    protected $server;

    public function __construct(OAuth2 $server, $params)
    {
        $this->server = $server;
        $this->token_uri = $params['token_uri'];
        $this->client_id = $params['client_id'];
        $this->client_secret = $params['client_secret'];
    }

    public function getCurrentUrl($url = '')
    {
        if($this->currentUrl === null)
        {
            if($url != '')
                $this->currentUrl = $url;
            else 
            {
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on'
                    ? 'https://'
                    : 'http://';
                $server_host = $_SERVER['HTTP_HOST'];
                $current_uri = $protocol . $server_host;
                $parts = parse_url($current_uri);
                // Use port if non default.
                $port = isset($parts['port']) &&
                (($protocol === 'http://' && $parts['port'] !== 80) || ($protocol === 'https://' && $parts['port'] !== 443))
                    ? ':' . $parts['port'] : '';
                // Rebuild.
                $this->currentUrl = $protocol . $parts['host'] . $port;
            }
        }
        return $this->currentUrl;
    }

    /**
     * 
     * @param Request $request
     * @throws InvalidArgumentException
     * @throws OAuth2Exception
     * @return array|null A valid OAuth2.0 JSON decoded access token in associative array, and null if not enough parameters or JSON decode failed.
     */
    public function requestAccessTokenWithUserCredentials(Request $request)
    {
        try {
            if ($this->token_uri && $this->client_id && $this->client_secret) {
                if ($request->getMethod() === 'POST')
                    $parameters = $request->request->all();
                else 
                    $parameters = $request->query->all();
                if(isset($parameters['username']) && isset($parameters['password'])) {
                    if(isset($parameters['url']))
                        $url = $this->getCurrentUrl($parameters['url']);
                    else
                        $url = $this->getCurrentUrl();
                    $fullTokenURL = $url . $this->token_uri;
                    $params = array('grant_type' => 'password',
                                    'client_id' => $this->client_id,
                                    'client_secret' => $this->client_secret,
                                    'username' => $parameters['username'],
                                    'password' => $parameters['password']);
                    $accessTokenCall = json_decode($this->makeRequest($fullTokenURL, 'POST', $params), true);
                    if (isset($accessTokenCall['access_token'])) {
                        return $accessTokenCall;
                    }
                    else {
                        throw new InvalidArgumentException('not enough parameters in accessTokenCall, ' . __METHOD__);
                    }
                }
                else {
                    throw new OAuth2ServerException(self::HTTP_BAD_REQUEST, self::ERROR_INVALID_GRANT, "Invalid username and password combination");
                }
            }
            else {
                throw new InvalidArgumentException('token_uri, client_id and client_secret have to set in ' . __METHOD__);
            }
        } catch (OAuth2Exception $e) {
            throw new OAuth2Exception($e->__toString());
        }
    }

    public function getApiDataWithPath($path, $accessToken, $method = 'GET')
    {
        $fullTokenURL = $this->getCurrentUrl() . $path;
        $params = array('access_token' => $accessToken,
                        'grant_type' => 'client_credentials');
        $jsonDataCall = json_decode($this->makeRequest($fullTokenURL, $method, $params), true);
        if (count($jsonDataCall) > 0) {
            return $jsonDataCall;
        }
        else {
            throw new InvalidArgumentException('not enough parameters in accessTokenCall, ' . __METHOD__);
        }
    }

    /**
     * Makes an HTTP request.
     *
     * This method can be overriden by subclasses if developers want to do
         * fancier things or use something other than cURL to make the request.
     *
     * @param string        $path   The target path, relative to base_path/service_uri or an absolute URI.
     * @param string        $method (optional) The HTTP method (default 'GET').
     * @param array         $params (optional The GET/POST parameters.
     * @param resource|null $ch     (optional) An initialized curl handle
     *
     * @throws OAuth2Exception
     * @return string The JSON decoded response object.
     */
    protected function makeRequest($path, $method = 'GET', $params = array(), $ch = null)
    {
        if (!$ch) {
            $ch = curl_init();
        }

        $opts = FOSOAuth2Client::$CURL_OPTS;
        if ($params) {
            switch ($method) {
                case 'GET':
                    $path .= '?' . http_build_query($params, null, '&');
                    break;
                    // Method override as we always do a POST.
                default:
                    $opts[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
            }
        }
        $opts[CURLOPT_URL] = $path;

        // Disable the 'Expect: 100-continue' behaviour. This causes CURL to wait
        // for 2 seconds if the server does not support this header.
        if (isset($opts[CURLOPT_HTTPHEADER])) {
            $existing_headers = $opts[CURLOPT_HTTPHEADER];
            $existing_headers[] = 'Expect:';
            $opts[CURLOPT_HTTPHEADER] = $existing_headers;
        } else {
            $opts[CURLOPT_HTTPHEADER] = array('Expect:');
        }

        curl_setopt_array($ch, $opts);
        $result = curl_exec($ch);

        if (curl_errno($ch) == 60) { // CURLE_SSL_CACERT
            error_log('Invalid or no certificate authority found, using bundled information');
            curl_setopt(
            $ch,
            CURLOPT_CAINFO,
            dirname(__FILE__) . '/fb_ca_chain_bundle.crt'
                    );
                    $result = curl_exec($ch);
        }

        if ($result === false) {
            $e = new OAuth2Exception(array(
                    'code' => curl_errno($ch),
                    'message' => curl_error($ch),
            ));
            curl_close($ch);
            throw $e;
        }
        curl_close($ch);

        // Split the HTTP response into header and body.
        list($headers, $body) = explode("\r\n\r\n", $result);
        $headers = explode("\r\n", $headers);
        // We catch HTTP/1.1 4xx or HTTP/1.1 5xx error response.
        if (strpos($headers[0], 'HTTP/1.1 4') !== false || strpos($headers[0], 'HTTP/1.1 5') !== false) {
            $result = array(
                    'code' => 0,
                    'message' => '',
            );

            if (preg_match('/^HTTP\/1.1 ([0-9]{3,3}) (.*)$/', $headers[0], $matches)) {
                $result['code'] = $matches[1];
                $result['message'] = $matches[2];
            }

            // In case retrun with WWW-Authenticate replace the description.
            foreach ($headers as $header) {
                if (preg_match("/^WWW-Authenticate:.*error='(.*)'/", $header, $matches)) {
                    $result['error'] = $matches[1];
                }
            }

            return json_encode($result);
        }

        return $body;
    }
}