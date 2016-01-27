<?php

namespace xrow\restBundle\Security;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\DefinitionDecorator;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SecurityFactoryInterface;

class OAuth2Factory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.xrow.'.$id;
            $container
                ->setDefinition($providerId, new DefinitionDecorator('xrow.security.authentication.provider'))
                ->replaceArgument(0, new Reference($userProvider))
        ;

        $listenerId = 'security.authentication.listener.xrow.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('xrow.security.authentication.listener'));

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'http';
    }

    public function getKey()
    {
        return 'xrow';
    }

    public function addConfiguration(NodeDefinition $node)
    {
    }
}