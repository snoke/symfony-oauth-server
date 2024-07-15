<?php

namespace Snoke\OAuthServer\Controller;

use App\Security\ResourceOwnerAuthenticator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Snoke\OAuthServer\Exception\AuthServerException;
use Snoke\OAuthServer\Interface\ScopeCollectionInterface;
use Snoke\OAuthServer\Interface\ScopeInterface;
use Snoke\OAuthServer\Service\RequestService;
use Snoke\OAuthServer\Service\SessionService;
use Snoke\OAuthServer\Service\TokenService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Snoke\OAuthServer\Entity\AccessToken;
use Snoke\OAuthServer\Entity\RefreshToken;
use Snoke\OAuthServer\Entity\AuthCode;
use Snoke\OAuthServer\Entity\Client;
use Symfony\Component\Security\Core\User\UserInterface;

class OAuthController extends AbstractController
{
    private ParameterBag $parameters;

    public function __construct(
        private readonly TokenService $tokenService,
        private readonly RequestService $requestService,
        private readonly SessionService $sessionService,
        private readonly ScopeCollectionInterface $scopeCollection,
        private readonly EntityManagerInterface $em,
        private readonly ?ResourceOwnerAuthenticator $resourceOwnerAuthenticator,
        private readonly ParameterBagInterface $parameterBag,
    )
    {
        $this->parameters = new ParameterBag($parameterBag->get('snoke_o_auth_server'));
    }

    public function decodeToken(Request $request): Response
    {
        $bearer = $this->requestService->extractBearer();
        $scopes = $this->requestService->getScopes();
        $clientSecret = $this->requestService->getClientSecret();
        $clientID = $this->requestService->getClientID();

        $token = $this->em->getRepository(AccessToken::class)->findOneBy(['token' => $bearer]);
        $client = $token->getClient();

        if ($token->getScopes()->toArray() !==  $scopes->toArray()) {
            throw new AuthServerException('scopes mismatch');
        }
        if ($clientID !== $client->getClientId()) {
            throw new AuthServerException('invalid client id');
        }
        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $user = $token->getUser();

        $response = [];

        foreach($scopes as $scope) {
            try {
                /** @var ScopeInterface $scopeDto */
                $scopeDto = $this->scopeCollection->get($scope);
            } catch(\Error $e) {
                throw new AuthServerException('invalid scope');
            }
            $response = array_merge($response,$scopeDto->toArray($user));
        }

        return new JsonResponse($response);
    }

    public function accessToken(Request $request): Response
    {
        $bearer = $this->requestService->extractBearer();
        $clientID = $this->requestService->getClientID();
        $clientSecret = $this->requestService->getClientSecret();
        $grantType = $this->requestService->getGrantType();
        $scopes = $this->requestService->getScopes();

        $authCode = $this->em->getRepository(AuthCode::class)->findOneBy([
            'code' =>  $bearer,
        ]);

        if ($authCode->getDeletedAt() !==  null) {
            throw new AuthServerException('Auth Token already used');
        }
        if ($authCode->getScopes()->toArray() !==  $scopes->toArray()) {
            throw new AuthServerException('scopes mismatch');
        }

        $client = $authCode->getClient();
        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $user = $authCode->getUser();

        $accessToken = $this->tokenService->createAccessToken($client,$user, $scopes ,$this->parameters);
        $refreshToken = $accessToken->getRefreshToken();

        $authCode->setDeletedAt(new \DateTime());
        $this->em->persist($authCode);

        $this->em->flush();

        return new JsonResponse(array_filter([
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken?->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $this->parameters->get('access_token')['invalidate_after']
        ], function ($value) {
            return $value !== null;
        }));
    }

    public function authorize(): Response
    {
        $clientID = $this->requestService->getClientID();
        $clientSecret = $this->requestService->getClientSecret();
        $grantType = $this->requestService->getGrantType();
        $scopes = $this->requestService->getScopes();

        $this->sessionService->setClientID($clientID);
        $this->sessionService->setClientSecret($clientSecret);
        $this->sessionService->setScopes($scopes);
        $this->sessionService->setGrantType($grantType);

        return new RedirectResponse($this->parameters->get('login_uri'));
    }

    public function authCode(Request $request, UserInterface $user): Response
    {
        $clientID = $this->sessionService->getClientID();
        $clientSecret = $this->sessionService->getClientSecret();
        $grantType = $this->sessionService->getGrantType();
        $scopes = $this->sessionService->getScopes();

        $client = $this->em->getRepository(Client::class)->findOneBy(['clientID' => $clientID]);
        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $authCode = new AuthCode($client, $user, $scopes, new ParameterBag($this->parameters->get('auth_code')));

        $this->em->persist($authCode);
        $this->em->flush();

        return new RedirectResponse($client->getRedirectUri().'?code='.$authCode->getCode());
    }

    public function resourceOwner(Request $request): Reponse
    {
        $clientID = $this->sessionService->getClientID();

        $client = $this->em->getRepository(Client::class)->findOneBy(['clientID' => $clientID]);
        $scopes = $this->requestService->getScopes();

        $passport = $this->resourceOwnerAuthenticator->authenticate($request);
        $accessToken = $this->tokenService->createAccessToken($client,$passport->getUser(), $scopes);

        $refreshToken = $accessToken->getRefreshToken();

        $this->em->flush();

        return new JsonResponse(array_filter([
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken?->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $this->parameters->get('access_token')['invalidate_after']
        ], function ($value) {
            return $value !== null;
        }));
    }

    public function refreshToken(): Response
    {
        $bearer = $this->requestService->extractBearer();
        $clientSecret = $this->requestService->getClientSecret();

        $existingToken = $this->em->getRepository(RefreshToken::class)->findOneBy(['refreshToken' => $bearer]);
        $client = $existingToken->getClient();
        if (!in_array('refresh_token',json_decode($client->getGrantTypes()))) {
            throw new AuthServerException('invalid grant type');
        }

        if (!$existingToken || $existingToken->getExpiresAt() < new \DateTime()) {
            throw new AuthServerException('invalid_grant');
        }

        $client = $existingToken->getClient();

        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $user = $existingToken->getUser();
        $scopes = $existingToken->getScopes();

        $accessToken = $this->tokenService->createAccessToken($client,$user,$scopes);
        $refreshToken = $accessToken->getRefreshToken();

        $this->em->flush();

        return new JsonResponse(array_filter([
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken?->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $this->parameters->get('access_token')['invalidate_after']
        ], function ($value) {
            return $value !== null;
        }));
    }
}
