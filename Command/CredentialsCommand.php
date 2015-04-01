<?php

namespace xrow\restBundle\Command;

use OAuth2;
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
        $username = $this->getContainer()->getParameter('oauth2_username');
        $password = $this->getContainer()->getParameter('oauth2_password');
        $parameters = array('username' => $username, 'password' => $password);
        $request = new Request(array(), $parameters);
        $OAuth2Client = $this->getContainer()->get('xrow_rest.oauth2.client');
        $accessToken = $OAuth2Client->requestAccessTokenWithUserCredentials($request);

        // bearer
        //$credentialsClient->setAccessTokenAsBearer();
        $output->writeln(sprintf('Obtained Access Token: <info>%s</info>', $accessToken));
        $url = 'http://abo.example.com/xrowapi/user';
        $parameters = array('access_token' => $accessToken,
                            'response_type' => 'token',
                            'grant_type' => 'client_credentials',
                            'scope' => 'user');
        $output->writeln(sprintf('Requesting: <info>%s</info>', $url));
        $output->writeln(sprintf('Parameters: <info>%s</info>', var_export($parameters, true)));
        $response = $credentialsClient->fetch($url, $parameters);
        $output->writeln(sprintf('Response: <info>%s</info>', var_export($response, true)));
    }
}
