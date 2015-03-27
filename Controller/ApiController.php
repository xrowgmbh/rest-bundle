<?php

namespace xrow\restBundle\Controller;

#use FOS\RestBundle\Controller\Annotations\View;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\JsonResponse;
use xrow\restBundle\CRM\LoadCRMPlugin;

class ApiController extends Controller
{
    protected $crmPluginClassObject;
    protected $container;

    public function __construct(LoadCRMPlugin $loadCRMPlugin)
    {
        $this->crmPluginClassObject = $loadCRMPlugin->crmPluginClass;
        $this->container = $loadCRMPlugin->container;
    }

    public function getUserAction()
    {
        die('hallo');
        #$user = $this->crmPluginClassObject->getUserData();
        $user = $this->container->get('security.context')->getToken()->getUser();
        die(var_dump($user));
        if($user) {
            return new JsonResponse(array(
                'id' => $user->getId(),
                'username' => $user->getUsername()
            ));
        }

        return new JsonResponse(array(
            'message' => 'User is not identified'
        ));
    }

    public function getSubscriptionsAction()
    {
        $userSubscriptions = $this->crmPluginClassObject->getUserSubscriptions();
        return new JsonResponse($userSubscriptions);
    }
}