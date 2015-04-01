<?php

namespace xrow\restBundle\Service;

use Symfony\Component\HttpFoundation\Request;
use OAuth2\OAuth2;
use OAuth2\OAuth2ServerException;

class OAuth2Client
{
    protected $token_uri;
    protected $client_id;
    protected $client_secret;
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
        #$this->container = twig
    }

    public function getAuthenticationUrl()
    {
        return $this->client->getAuthenticationUrl($this->authEndpoint, $this->redirectUrl, array(''));
    }

    public function requestAccessTokenWithUserCredentials(Request $request)
    {
        try {
            if ($request->getMethod() === 'POST')
                $parameters = $request->request->all();
            else 
                $parameters = $request->query->all();
            $parameters['client_id'] = $this->client_id;
            $parameters['client_secret'] = $this->client_secret;
die(var_dump($request->server->all()));
            $response = Request::create($this->token_uri, $request->getMethod(), $parameters, array(), array(), $request->server->all());
            
            $accessTokenJson = $this->server->grantAccessToken($request);
            $json = json_decode((string)$accessTokenJson, true);
            die(var_dump($json));
            if (isset($json['access_token'])) {
                // weiter zu /xrowapi/user
                return $json['access_token'];
                #return $twig->render('client/show_access_token.twig', array('response' => $json));
            }
            #return $twig->render('client/failed_token_request.twig', array('response' => $json ? $json : $response));
        } catch (OAuth2ServerException $e) {
            return $e->getHttpResponse();
        }
        // exchange user credentials for access token
        $query = array(
            'grant_type' => 'password',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'username' => $username,
            'password' => $password,
        );
        // determine the token endpoint to call based on our config (do this somewhere else?)
        $grantRoute = $config['token_route'];
        $endpoint = 0 === strpos($grantRoute, 'http') ? $grantRoute : $urlgen->generate($grantRoute, array(), true);
        // make the token request via http and decode the json response
        $response = $http->post($endpoint, null, $query, $config['http_options'])->send();
        $json = json_decode((string) $response->getBody(), true);
        // if it is succesful, display the token in our app
        if (isset($json['access_token'])) {
        return $twig->render('client/show_access_token.twig', array('response' => $json));
        }
        return $twig->render('client/failed_token_request.twig', array('response' => $json ? $json : $response));
    }
}