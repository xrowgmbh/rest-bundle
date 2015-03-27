<?php

namespace xrow\restBundle\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use xrow\restBundle\Controller\RestRequest;

class TestApiController extends Controller
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function testApiAction()
    {
        $request = new RestRequest();

        $insertUrl = "http://abo.example.com/xrowapi/user";
        $postParams = array(
            "username"=>"test",
            "is_active"=>'false',
            "other"=>"3g12g53g5gg4g246542g542g4"
        );

        $postParams = array();
        $getParams = array("access_token"=>$request->getToken(),
                           "grant_type"=>"client_credentials");
        $test = $this->container->get('security.context')->getToken();
        #$bla = $this->container->get('security.context')->getToken()->getUser();
        #$test = $this->getSecureResourceAction();
        $response = $request->call($insertUrl, "GET", $getParams, $postParams);
        echo "<pre>";
        var_dump($test);
        var_dump($request);
        var_dump($getParams);
        var_dump($response);
        echo "</pre>";
        die();
    }
    
    public function getSecureResourceAction()
    {
        # this is it
        if (false === $this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException();
        }
    }
}