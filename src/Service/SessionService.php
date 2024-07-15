<?php

namespace Snoke\OAuthServer\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class SessionService
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly RequestStack $requestStack
    ) {
        $this->session = $this->requestStack->getCurrentRequest()->getSession();
        if (!$this->session->isStarted()) {
            $this->session->start();
        }
    }

    public function getClientID(): string {
        return $this->session->get('client_id');
    }

    public function setClientID($clientID): static
    {
        $this->session->set('client_id', $clientID);

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->session->get('client_secret');
    }

    public function setClientSecret($clientSecret): static
    {
        $this->session->set('client_secret', $clientSecret);

        return $this;
    }

    public function getScopes(): ArrayCollection
    {
        return new ArrayCollection(json_decode($this->session->get('scopes')));
    }

    public function setScopes(ArrayCollection $scopes): static
    {
        $this->session->set('scopes', json_encode($scopes->toArray()));

        return $this;
    }

    public function getGrantType(): ?string
    {
        return $this->session->get('grant_type');
    }

    public function setGrantType(string $grantType): static
    {
        $this->session->set('grant_type',$grantType);

        return $this;
    }
}