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
     * @Route("/test1")
     * @Method({"GET", "POST"})
     * 
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showTestContentAction(Request $request)
    {
        return $this->render(
            'xrowRestBundle:angular:test1/index.html.twig', array(
                'postData' => array('user' => 'kristina', 'password' => 'Xr0wpasX')));
    }

    /**
     * @Route("/test1forts")
     * @Method({"GET", "POST"})
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showTestForTsContentAction(Request $request)
    {
        return $this->render(
            'xrowRestBundle:angular:test1forts/index.html.twig', array(
                'postData' => array('user' => 'kristina', 'password' => 'Xr0wpasX')));
    }
}