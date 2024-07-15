<?php

namespace Snoke\OAuthServer\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\OAuthServer\Interface\AuthenticatableInterface;
use Snoke\OAuthServer\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
class RefreshToken
{
    public function __construct(Client $client, AuthenticatableInterface $user, ArrayCollection $scopes,ParameterBag $options) {

        $invalidateTokenAfterSeconds = $options->get('invalidate_after');

        if ($invalidateTokenAfterSeconds>0) {
            $this->setExpiresAt((new \DateTime())->modify('+' . $invalidateTokenAfterSeconds . ' seconds'));
        }

        $this->setToken(bin2hex(random_bytes($options->get('length'))));
        $this->setUser($user);
        $this->setClient($client);
        $this->setScopes($scopes);
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $token = null;

    #[ORM\Column(length: 255)]
    private ?string $scopes = null;

    #[ORM\ManyToOne(inversedBy: 'accessTokens')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(inversedBy: 'accessTokens')]
    private AuthenticatableInterface $user;

    #[ORM\ManyToOne(inversedBy: 'refreshToken')]
    private AccessToken $accessToken;

    #[ORM\Column(nullable: true)]
    private ?\DateTime $expiresAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getScopes(): ?ArrayCollection
    {
        return new ArrayCollection(json_decode($this->scopes));
    }

    public function setScopes(ArrayCollection $scopes): static
    {
        $this->scopes = json_encode($scopes->toArray());

        return $this;
    }
    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getUser(): ?AuthenticatableInterface
    {
        return $this->user;
    }

    public function setUser(?AuthenticatableInterface $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getExpiresAt(): ?\DateTime
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTime $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getAccessToken(): AccessToken
    {
        return $this->accessToken;
    }

    public function setAccessToken(AccessToken $accessToken): static
    {
        $this->accessToken = $accessToken;

        return $this;
    }
}
