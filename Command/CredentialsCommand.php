<?php

namespace xrow\restBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Request;

class CredentialsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('xrow:test-client:credentials')
            ->setDescription('Executes OAuth2 Credentials grant');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            // in shell you can't get http host
            $url = $this->getContainer()->getParameter('host_url');
            $username = $this->getContainer()->getParameter('oauth2_username');
            $password = $this->getContainer()->getParameter('oauth2_password');
            $parameters = array('username' => $username, 'password' => $password, 'url' => $url);
            $request = new Request(array(), $parameters);
            $request->setMethod('POST');
            $OAuth2Client = $this->getContainer()->get('xrow_rest.oauth2.client');
            $accessTokenArray = $OAuth2Client->requestAccessTokenWithUserCredentials($request, $output);
            $accessToken = $accessTokenArray['access_token'];
            $output->writeln(sprintf('Obtained Access Token: <info>%s</info>', $accessToken));
            $output->writeln('');
            $parameters = array('access_token' => $accessToken,
                                'grant_type' => 'client_credentials');
            // $session = $request->getSession();
            // User
            $userUri = '/xrowapi/v1/user';
            $output->writeln(sprintf('Get user: <info>%s</info>', $userUri));
            $userResponse = $OAuth2Client->getApiDataWithPath($userUri, $accessToken);
            $output->writeln(sprintf('Response: <info>%s</info>', var_export($userResponse, true)));
            // Account
            $accountUri = '/xrowapi/v1/account';
            $output->writeln(sprintf('Get account: <info>%s</info>', $accountUri));
            $accountResponse = $OAuth2Client->getApiDataWithPath($accountUri, $accessToken);
            $output->writeln(sprintf('Response: <info>%s</info>', var_export($accountResponse, true)));
            // Subscriptions
            $subscriptionsUri = '/xrowapi/v1/subscriptions';
            $output->writeln(sprintf('Get subscriptions: <info>%s</info>', $subscriptionsUri));
            $subscriptionsResponse = $OAuth2Client->getApiDataWithPath($subscriptionsUri, $accessToken);
            $output->writeln(sprintf('Response: <info>%s</info>', var_export($subscriptionsResponse, true)));
        }
        catch (Exception $e)
        {
            die(var_dump($e->getMessage()));
        }
    }
}