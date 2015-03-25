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

    public function __construct(LoadCRMPlugin $loadCRMPlugin)
    {
        $this->crmPluginClassObject = $loadCRMPlugin->crmPluginClass;
    }

    public function getUserAction($crmuserId)
    {
        $user = $this->crmPluginClassObject->getUserData($crmuserId);
        #$user = $this->container->get('security.context')->getToken()->getUser();
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

    public function getSubscriptionsAction($crmuserId)
    {
        $userSubscriptions = $this->crmPluginClassObject->getUserSubscriptions($crmuserId);
        return new JsonResponse($userSubscriptions);
    }
}