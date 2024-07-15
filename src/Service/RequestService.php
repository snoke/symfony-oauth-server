<?php

namespace Snoke\OAuthServer\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Snoke\OAuthServer\Entity\AccessToken;
use Snoke\OAuthServer\Entity\Client;
use Snoke\OAuthServer\Entity\RefreshToken;
use Snoke\OAuthServer\Exception\AuthServerException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class RequestService
{
    private readonly Request $request;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function extractBearer(): ?string
    {
        $authorizationHeader = $this->request->headers->get('Authorization');
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            $token = $matches[1];
        } else {
            throw new AuthServerException('invalid authorization header');
        }
        return $token;
    }

    public function getClientSecret(): ?string
    {
        return $this->request->query->get('client_secret');
    }

    public function getScopes(): ArrayCollection
    {
        $scopes = explode(',',$this->request->query->get('scopes'));
        $scopes =  is_array($scopes) ? $scopes : [$scopes];

        return new ArrayCollection($scopes);
    }

    public function getClientID(): ?string
    {
        return $this->request->query->get('client_id');
    }

    public function getGrantType(): ?string {
        return $this->request->query->get('grant_type');
    }
}