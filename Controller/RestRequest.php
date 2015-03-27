<?php

namespace xrow\restBundle\Controller;

class RestRequest
{
    private $token_url;
    private $access_token;
    private $refresh_token;
    private $client_id;
    private $client_secret;

    public function __construct(){
        $this->client_id = "1_3ywnyc0ky64g8w8gk4kkgcwgogkss4408kc8kowwocckwogss8";
        $this->client_secret = "2h54ah7402sk0k08wkog8cw0kko44o448scswo0wkgw0wkg8cs"; 
        $this->token_url = "http://abo.example.com/oauth/v2/token";

        $params = array(
            'client_id'=>$this->client_id,
            'client_secret'=>$this->client_secret,
            'username'=>'',
            'password'=>'',
            'grant_type'=>'password'
        );

        $result = $this->call($this->token_url, 'GET', $params);

        $this->access_token = $result->access_token;
        $this->refresh_token = $result->refresh_token;

        /*$params = array(
                'client_id'=>$this->client_id,
                'client_secret'=>$this->client_secret,
                "access_token"=>$this->access_token,
                "grant_type"=>"client_credentials"
        );
        $result = $this->call($this->token_url, 'GET', $params);
        $this->access_token = $result->access_token;*/
    }

    public function getToken(){
        return $this->access_token;
    }

    public function refreshToken(){
        $params = array(
            'client_id'=>$this->client_id,
            'client_secret'=>$this->client_secret,
            'refresh_token'=>$this->refresh_token,
            'grant_type'=>'refresh_token'
        );

        $result = $this->call($this->token_url, "GET", $params);

        $this->access_token = $result->access_token;
        $this->refresh_token = $result->refresh_token;

        return $this->access_token;
    }

    public function call($url, $method, $getParams = array(), $postParams = array()){
        ob_start();
        $curl_request = curl_init();

        curl_setopt($curl_request, CURLOPT_HEADER, 0); // don't include the header info in the output
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1); // don't display the output on the screen
        $url = $url."?".http_build_query($getParams);
        switch(strtoupper($method)){
            case "POST": // Set the request options for POST requests (create)
                curl_setopt($curl_request, CURLOPT_URL, $url); // request URL
                curl_setopt($curl_request, CURLOPT_POST, 1); // set request type to POST
                curl_setopt($curl_request, CURLOPT_POSTFIELDS, http_build_query($postParams)); // set request params
                break;
            case "GET": // Set the request options for GET requests (read)
                curl_setopt($curl_request, CURLOPT_URL, $url); // request URL and params
                break;
            case "PUT": // Set the request options for PUT requests (update)
                curl_setopt($curl_request, CURLOPT_URL, $url); // request URL
                curl_setopt($curl_request, CURLOPT_CUSTOMREQUEST, "PUT"); // set request type
                curl_setopt($curl_request, CURLOPT_POSTFIELDS, http_build_query($postParams)); // set request params
                break;
            case "DELETE":

                break;
            default:
                curl_setopt($curl_request, CURLOPT_URL, $url);
                break;
        }

        $result = curl_exec($curl_request); // execute the request
        if($result === false){
            $result = curl_error($curl_request);
        }

        curl_close($curl_request);
        ob_end_flush();

        return json_decode($result);
    }
}