<?php

namespace Snoke\OAuthServer\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Snoke\OAuthServer\Interface\AuthenticatableInterface;

#[ORM\Entity]
#[ORM\Table(name: "auth_code")]
class AuthCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\Column(type: "string", length: 255, unique: true)]
    private ?string $code = null;

    #[ORM\Column(type: "text")]
    private ?string $scopes = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'authCodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: AuthenticatableInterface::class, inversedBy: 'authCodes')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private AuthenticatableInterface $user;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?\DateTime $expiresAt = null;

    public function __construct(Client $client, AuthenticatableInterface $user, ParameterBag $options)
    {
        $this->setClient($client);
        $this->setUser($user);
        $this->setScopes($options->get('scopes'));

        $invalidateCodeAfterSeconds = $options->get('invalidate_after');

        if ($invalidateCodeAfterSeconds > 0) {
            $this->setExpiresAt((new \DateTime())->modify('+' . $invalidateCodeAfterSeconds . ' seconds'));
        }

        $this->setCode(bin2hex(random_bytes($options->get('length'))));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
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
}
