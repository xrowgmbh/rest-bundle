<?php

namespace xrow\restBundle\CRM;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadCRMPlugin
{
    public $crmPluginClass;
    public $container;

    public function __construct(ContainerInterface $container)
    {
        $crmClassName = $container->getParameter('xrow_rest.plugins.crmclass');
        $this->crmPluginClass = new $crmClassName();
        $this->container = $container;
    }
}