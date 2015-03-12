<?php

namespace xrow\restBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class xrowRestExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    /**
     * {@inheritdoc}
     *
     * @throws ServiceNotFoundException
     */
    public function prepend(ContainerBuilder $container)
    {
        // das ist ein Beispiel vom HWI Bundle
        #if (!$container->hasExtension('fos_oauth_server')) {
        #    throw new ServiceNotFoundException('FOSOAuthServerBundle must be registered in kernel.');
        #}

        #$config = $this->processConfiguration(new Configuration(), $container->getExtensionConfig($this->getAlias()));

        /*$container->prependExtensionConfig('fos_oauth_server', array(
                'db_driver'           => 'orm',
                'client_class'        => $config['classes']['api_client']['model'],
                'access_token_class'  => $config['classes']['api_access_token']['model'],
                'refresh_token_class' => $config['classes']['api_refresh_token']['model'],
                'auth_code_class'     => $config['classes']['api_auth_code']['model'],
                
                'service'             => array(
                        'user_provider' => 'fos_user.user_provider.username'
                ),
        ));*/
    }
}