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
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsCommand(
    name: 'oauth:create:client',
    description: 'Add a short description for your command',
)]
class AuthCreateClientCommand extends Command
{
    private const GRANT_TYPES = ['implicit_grant','authorization_code','refresh_token','resource_owner','client_credentials'];
    private array $parameters;

    public function __construct(private readonly EntityManagerInterface $em, ParameterBagInterface $parameterBag)
    {
        $this->parameters = $parameterBag->get('snoke_o_auth_server');
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    private function selectGrants($input, $output) {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'select ermitted grant type (comma separated for multiple types)',
            self::GRANT_TYPES,
            0
        );
        $question->setErrorMessage('Grant Type %s is invalid.');
        $question->setMultiselect(true);

        return $helper->ask($input, $output, $question);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $redirect_uri = $io->ask('client redirect_uri','http://localhost/redirect_uri');
        $client = new Client($redirect_uri,new ParameterBag($this->parameters['client']));
        $grantTypes = $this->selectGrants($input, $output);
        $client->setGrantTypes(json_encode($grantTypes));
        $this->em->persist($client);
        $this->em->flush();
        $io->success('You have a new client! ');
        $io->note('client_id: ' . $client->getClientID());
        $io->note('client_secret: ' . $client->getClientSecret());
        $io->note('redirect_uri: ' . $client->getRedirectUri());
        $io->note('permitted grant types: ' . $grantTypes);

        return Command::SUCCESS;
    }
}
