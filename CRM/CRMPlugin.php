<?php

namespace xrow\restBundle\CRM;

use Symfony\Component\DependencyInjection\ContainerInterface;

class CRMPlugin implements CRMPluginInterface
{
    public $crmPluginClass;

    public function __construct(ContainerInterface $container)
    {
        $crmClassName = $container->getParameter('xrow_rest.plugins.crmclass');
        $this->crmPluginClass = new $crmClassName();
    }

    public function loadUser($username, $password)
    {}

    public function getUserData($userId)
    {}

    public function getUserSubscriptions($userId)
    {}

    public function getUserSubscription($userId, $subscriptionId)
    {}
}