<?php

namespace Snoke\OAuthServer\Entity;

use Snoke\OAuthServer\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $clientID = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $grantTypes = null;

    #[ORM\Column(length: 255)]
    private ?string $clientSecret = null;

    #[ORM\Column(length: 255)]
    private ?string $redirectUri = null;

    /**
     * @var Collection<int, AccessToken>
     */
    #[ORM\OneToMany(targetEntity: AccessToken::class, mappedBy: 'client')]
    private Collection $accessTokens;

    /**
     * @var Collection<int, AuthCode>
     */
    #[ORM\OneToMany(targetEntity: AuthCode::class, mappedBy: 'client')]
    private Collection $authCodes;

    /**
     * @var Collection<int, RefreshToken>
     */
    #[ORM\OneToMany(targetEntity: RefreshToken::class, mappedBy: 'client')]
    private Collection $refreshTokens;

    public function __construct(string $redirectUri, ParameterBag $options)
    {
        $this->accessTokens = new ArrayCollection();
        $this->authCodes = new ArrayCollection();
        $this->refreshTokens = new ArrayCollection();

        $this->setRedirectUri($redirectUri);
        $this->setClientID(bin2hex(random_bytes($options->get('client_id')['length'])));
        $this->setClientSecret(bin2hex(random_bytes($options->get('client_secret')['length'])));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientID(): ?string
    {
        return $this->clientID;
    }

    public function setClientID(string $clientID): static
    {
        $this->clientID = $clientID;

        return $this;
    }

    public function getGrantTypes(): ?string
    {
        return $this->grantTypes;
    }

    public function setGrantTypes(string $grantTypes): static
    {
        $this->grantTypes = $grantTypes;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(string $clientSecret): static
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): static
    {
        $this->redirectUri = $redirectUri;

        return $this;
    }

    /**
     * @return Collection<int, AccessToken>
     */
    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function addAccessToken(AccessToken $accessToken): static
    {
        if (!$this->accessTokens->contains($accessToken)) {
            $this->accessTokens->add($accessToken);
            $accessToken->setClient($this);
        }

        return $this;
    }

    public function removeAccessToken(AccessToken $accessToken): static
    {
        if ($this->accessTokens->removeElement($accessToken)) {
            // set the owning side to null (unless already changed)
            if ($accessToken->getClient() === $this) {
                $accessToken->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AuthCode>
     */
    public function getAuthCodes(): Collection
    {
        return $this->authCodes;
    }

    public function addAuthCode(AuthCode $authCode): static
    {
        if (!$this->authCodes->contains($authCode)) {
            $this->authCodes->add($authCode);
            $authCode->setClient($this);
        }

        return $this;
    }

    public function removeAuthCode(AuthCode $authCode): static
    {
        if ($this->authCodes->removeElement($authCode)) {
            // set the owning side to null (unless already changed)
            if ($authCode->getClient() === $this) {
                $authCode->setClient(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, RefreshToken>
     */
    public function getRefreshTokens(): Collection
    {
        return $this->refreshTokens;
    }

    public function addRefreshToken(RefreshToken $refreshToken): static
    {
        if (!$this->refreshTokens->contains($refreshToken)) {
            $this->refreshTokens->add($refreshToken);
            $refreshToken->setClient($this);
        }

        return $this;
    }

    public function removeRefreshToken(RefreshToken $refreshToken): static
    {
        if ($this->refreshTokens->removeElement($refreshToken)) {
            // set the owning side to null (unless already changed)
            if ($refreshToken->getClient() === $this) {
                $refreshToken->setClient(null);
            }
        }

        return $this;
    }
}
