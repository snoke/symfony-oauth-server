<?php

namespace Snoke\OAuthServer\Command;

use Doctrine\ORM\EntityManagerInterface;
use Snoke\OAuthServer\Entity\Client;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'oauth:create:client',
    description: 'Add a short description for your command',
)]
class AuthCreateClientCommand extends Command
{
    private array $parameters;

    public function __construct(private readonly EntityManagerInterface $em, ParameterBagInterface $parameterBag)
    {
        $this->parameters = $parameterBag->get('snoke_o_auth_server');
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $redirect_uri = $io->ask('client redirect_uri','http://localhost/redirect_uri');
        $client = new Client($redirect_uri,new ParameterBag($this->parameters['client']));
        $this->em->persist($client);
        $this->em->flush();
        $io->success('You have a new client! ');
        $io->note('client_id: ' . $client->getClientID());
        $io->note('client_secret: ' . $client->getClientSecret());
        $io->note('redirect_uri: ' . $client->getRedirectUri());

        return Command::SUCCESS;
    }
}
