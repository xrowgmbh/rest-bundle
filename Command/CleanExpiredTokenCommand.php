<?php

namespace xrow\restBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use FOS\OAuthServerBundle\Model\TokenManagerInterface;
use FOS\OAuthServerBundle\Model\AuthCodeManagerInterface;

class CleanExpiredTokenCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('xrow:oauth:clean-expired')
            ->setDescription('Clean expired tokens')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command will remove expired OAuth2 tokens.

  <info>php %command.full_name%</info>
EOT
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $services = array(
            'fos_oauth_server.access_token_manager'     => 'Access token',
            'fos_oauth_server.refresh_token_manager'    => 'Refresh token',
            'fos_oauth_server.auth_code_manager'        => 'Auth code',
        );

        foreach ($services as $service => $name) {
            /** @var $instance TokenManagerInterface */
            if ($container->has($service)) {
                $instance = $container->get($service);
                if ($instance instanceof TokenManagerInterface || $instance instanceof AuthCodeManagerInterface) {
                    $result = $instance->deleteExpired();
                    $output->writeln(sprintf('Removed <info>%d</info> items from <comment>%s</comment> storage.', $result, $name));
                }
            }
        }
        $OAuth2Tokens = array('OAuth2ServerBundle:AccessToken', 'OAuth2ServerBundle:RefreshToken', 'OAuth2ServerBundle:AuthorizationCode');
        foreach ($OAuth2Tokens as $OAuth2Token) {
            $result = $this->deleteExpired($OAuth2Token, $em);
            if ($result !== null) {
                $output->writeln($result);
            }
        }
    }

    /**
     * Removes all bshaffer/oauth2-server-php tokens
     * 
     * @param string $token
     * @param \Doctrine\ORM\EntityManager $entityManager $em
     * @return NULL|string
     */
    private function deleteExpired($token, $em)
    {
        if ($tokenResponse = $em->getRepository($token)) {
            $qb = $tokenResponse->createQueryBuilder('token');
            $qb
                ->delete()
                ->where('token.expires < ?1')
                ->setParameters(array(1 => date('Y-m-d H:i:s', time())));
            $result = $qb->getQuery()->execute();
            return sprintf('Removed <info>%d</info> items from <comment>%s</comment>.', $result, $token);
        }
        return null;
    }
}