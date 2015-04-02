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
            $accessTokenArray = $OAuth2Client->requestAccessTokenWithUserCredentials($request);
            $accessToken = $accessTokenArray['access_token'];
            $output->writeln(sprintf('Obtained Access Token: <info>%s</info>', $accessToken));
            $output->writeln('');
            $parameters = array('access_token' => $accessToken,
                                'grant_type' => 'client_credentials');
            // User data
            $userDataUri = '/xrowapi/user';
            $output->writeln(sprintf('Get user data: <info>%s</info>', $userDataUri));
            $userDataResponse = $OAuth2Client->getApiDataWithPath($userDataUri, $accessToken);
            $output->writeln(sprintf('Response: <info>%s</info>', var_export($userDataResponse, true)));
            // Subscription data
            $userDataSubscrUri = '/xrowapi/user/subscriptions';
            $output->writeln(sprintf('Get user subscriptions: <info>%s</info>', $userDataSubscrUri));
            $userDataSubscrResponse = $OAuth2Client->getApiDataWithPath($userDataSubscrUri, $accessToken);
            $output->writeln(sprintf('Response: <info>%s</info>', var_export($userDataSubscrResponse, true)));
        }
        catch (Exception $e)
        {
            die(var_dump($e->getMessage()));
        }
    }
}