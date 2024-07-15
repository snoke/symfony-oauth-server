<?php

namespace Snoke\OAuthServer\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Snoke\OAuthServer\Entity\AccessToken;
use Snoke\OAuthServer\Entity\Client;
use Snoke\OAuthServer\Entity\RefreshToken;
use Snoke\OAuthServer\Exception\AuthServerException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenService
{

    public function createAccessToken(Client $client,UserInterface $user, ArrayCollection $scopes): AccessToken
    {
        $accessToken = new AccessToken($client,$user, $scopes ,new ParameterBag($this->parameters->get('access_token')));
        $this->entityManager->persist($accessToken);
        $permittedGrantTypes = json_decode($client->getGrantTypes(),true);

        if (in_array('refresh_token', $permittedGrantTypes)) {
            $refreshToken = new RefreshToken($client,$user, $scopes , new ParameterBag($this->parameters->get('refresh_token')));
            $accessToken->setRefreshToken($refreshToken);
            $this->entityManager->persist($refreshToken);

        }
        $this->entityManager->flush();
        return $accessToken;
    }

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly EntityManagerInterface $entityManager,
        private readonly ParameterBagInterface $parameterBag,
    )
    {
        $this->parameters = new ParameterBag($parameterBag->get('snoke_o_auth_server'));
    }
}