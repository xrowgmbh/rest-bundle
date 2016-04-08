<?php

namespace xrow\restBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

class AngularController extends Controller
{
    /**
     * @Route("/showoicauth")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showOauth2Action(Request $request)
    {
        return $this->render('xrowRestBundle:angular:oicauth/index.html.twig');
    }

    /**
     * @Route("/oicauth")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function setOpenIDConnectAuthenticationAction(Request $request)
    {
        return $this->get('xrow_rest.api.helper')->setAuthentication($request, 'OAuth2');
    }
}