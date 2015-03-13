<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sylius\Bundle\ApiBundle\Command;

use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Sylius\Bundle\ApiBundle\Model\Client;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClientCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('xrow:oauth-server:client-create')
            ->setDescription('Create a new client')
            ->addArgument(
                'name', 
                InputArgument::REQUIRED, 'Sets the client name', 
                null)
            ->addOption(
                'redirect-uri', 
                null, 
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 
                'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.', 
                null)
            ->addOption(
                'grant-type', 
                null, 
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 
                'Sets allowed grant type for client. Use this option multiple times to set multiple grant types.', 
                null)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientManager = $this->getApplication()->getKernel()->getContainer()->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setName($input->getArgument('name'));
        $client->setRedirectUris($input->getOption('redirect-uri'));
        $client->setAllowedGrantTypes($input->getOption('grant-type'));
        $clientManager->updateClient($client);
        $output->writeln(sprintf('Added a new client with name <info>%s</info> and public id <info>%s</info>.', $client->getName(), $client->getPublicId()));
    }
}