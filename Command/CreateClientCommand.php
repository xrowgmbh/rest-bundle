<?php

namespace xrow\restBundle\Command;

use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateClientCommand extends ContainerAwareCommand
{
    /**
     * Example: 
     * php ezpublish/console xrow:oauth:create-client --bundle="fos|oauth2" --redirect-uri="http://localhost" --grant-types="password,refresh_token,client_credentials" --scopes="user,openid"
     */
    protected function configure()
    {
        $this
            ->setName('xrow:oauth:create-client')
            ->setDescription('Creates a new client')
            ->addOption(
                'bundle',
                null,
                InputOption::VALUE_REQUIRED,
                'Sets bundle for client (fos or oauth2, oauth2 needs scope(s)).'
            )
            ->addOption(
                'redirect-uri',
                null,
                InputOption::VALUE_REQUIRED,
                'Sets redirect uri for client (comma separated).'
            )
            ->addOption(
                'grant-types',
                null,
                InputOption::VALUE_REQUIRED,
                'Sets allowed grant type for client (comma separated).'
            )
            ->addOption(
                'scopes',
                null,
                InputOption::VALUE_OPTIONAL,
                'Sets allowed scopes for client (comma separated).'
            )
            ->setHelp(<<<EOT
The <info>%command.name%</info>command creates a new client.
<info>php %command.full_name% [--bundle=...] [--redirect-uri=...] [--grant-types=...] [--scopes=...] name</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundle = $input->getOption('bundle');
        if ($bundle == 'fos') {
            $client = $this->createFOSClient($input);
            if ($client !== false) {
                $id = $client->getPublicId();
                $secret = $client->getSecret();
            }
            else
                $output->writeln('Service for FOS Client does not exist. Did you maybe forget to add this bundle?');
        }
        else {
            $client = $this->createOAuth2Client($input);
            if ($client !== false) {
                $id = $client->getClientId();
                $secret = $client->getClientSecret();
            }
            else
                $output->writeln('Service for oauth2 Client does not exist. Did you maybe forget to add this bundle?');
        }

        $output->writeln(
            sprintf(
                'A new client with public id <info>%s</info>, secret <info>%s</info> has been added',
                $id,
                $secret
            )
        );
    }

    private function createFOSClient($input)
    {
        if ($this->getContainer()->has('fos_oauth_server.client_manager.default')) {
            $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');

            // Create a client
            $client = $clientManager->createClient();
           $redirectURIArray = explode(',', $input->getOption('redirect-uri'));
            $grantTypesArray = explode(',', $input->getOption('grant-types'));
            // Set new values
            $client->setRedirectUris($redirectURIArray);
            $client->setAllowedGrantTypes($grantTypesArray);
            // Store new values
            $clientManager->updateClient($client);
            return $client;
        }
        return false;
    }

    private function createOAuth2Client($input)
    {
        $container = $this->getContainer();
        if ($this->getContainer()->has('oauth2.scope_manager')) {
            $scopeManager = $container->get('oauth2.scope_manager');
            $clientManager = $container->get('oauth2.client_manager');

            $redirectURIArray = explode(',', $input->getOption('redirect-uri'));
            $grantTypesArray = explode(',', $input->getOption('grant-types'));
            if ($input->getOption('scopes') != '')
                $scopesArray = explode(',', $input->getOption('scopes'));
            else
                $scopesArray = array('user');
            // Create first a scope
            foreach ($scopesArray as $scope) {
                $scopeExists = $scopeManager->findScopeByScope($scope);
                if ($scopeExists === null)
                    $scopeManager->createScope($scope, 'Scope for sso via '.$scope);
            }
            // Create a client
            $client = $clientManager->createClient(
                                        $this->generateRandom(),
                                        $redirectURIArray,
                                        $grantTypesArray,
                                        $scopesArray
            );
            return $client;
        }
        return false;
    }

    public static function generateRandom()
    {
        return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }
}