<?php

namespace Snoke\OAuthServer\Controller;

use App\Security\ResourceOwnerAuthenticator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Snoke\OAuthServer\Exception\AuthServerException;
use Snoke\OAuthServer\Interface\ScopeCollectionInterface;
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

    private function createAccessToken(Client $client,UserInterface $user, array $scopes): AccessToken
    {
        $scopes = new ArrayCollection($scopes);
        $accessToken = new AccessToken($client,$user, $scopes ,$this->parameters);
        $this->em->persist($accessToken);
        $supportedGrantTypes = json_decode($client->getGrantTypes(),true);

        if (in_array('refresh_token',$supportedGrantTypes)) {
            $refreshToken = new RefreshToken($client,$user, $scopes ,$this->parameters);
            $accessToken->setRefreshToken($refreshToken);
            $this->em->persist($refreshToken);

        }
        $this->em->flush();

        return $accessToken;
    }

    public function __construct(private readonly ScopeCollectionInterface $scopeCollection, private readonly EntityManagerInterface $em, ParameterBagInterface $parameterBag,private readonly ?ResourceOwnerAuthenticator $resourceOwnerAuthenticator)
    {
        $this->parameters = new ParameterBag($parameterBag->get('snoke_o_auth_server'));
    }

    public function decodeToken(Request $request): Response
    {
        $scopes = explode(',',$request->query->get('scopes'));
        $clientSecret = $request->query->get('client_secret');
        $token = $this->em->getRepository(AccessToken::class)->findOneBy(['token' => $request->query->get('token')]);
        if ($token->getScopes()->toArray() !==  $scopes) {
            throw new AuthServerException('scopes mismatch');
        }
        $client = $token->getClient();
        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $user = $token->getUser();

        $response = [];

        $scopesCollection = new ArrayCollection($this->scopeCollection->getScopes());
        foreach($scopes as $scope) {
            try {
                $scopeDto = $scopesCollection->get($scope);
            } catch(\Error $e) {
                throw new AuthServerException('invalid scope');
            }
            $response = array_merge($response,$scopeDto->toArray($user));
        }

        return new JsonResponse($response);
    }

    public function accessToken(Request $request): Response
    {
        $clientSecret = $request->query->get('client_secret');
        $grantType = $request->query->get('grant_type') ?? 'authorization_code';
        $scopes = explode(',',$request->query->get('scopes'));

        $scopes =  is_array($scopes) ? $scopes : [$scopes];

        $authCode = $this->em->getRepository(AuthCode::class)->findOneBy(['code' =>  $request->query->get('code'), 'deletedAt' => null]);

        if ($authCode->getScopes()->toArray() !==  $scopes) {
            throw new AuthServerException('scopes mismatch');
        }

        $client = $authCode->getClient();
        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $user = $authCode->getUser();

        $authCode->setDeletedAt(new \DateTime());

        $accessToken = $this->createAccessToken($client,$user, $scopes ,$this->parameters);
        $refreshToken = $accessToken->getRefreshToken();


        return new JsonResponse(array_filter([
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken?->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $this->parameters->get('access_token')['invalidate_after']
        ], function ($value) {
            return $value !== null;
        }));
    }

    public function authorize(Request $request): Response {
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        $session->set('client_id', $request->query->get('client_id'));
        $session->set('client_secret', $request->query->get('client_secret'));
        $session->set('scopes', $request->query->get('scopes'));
        return new RedirectResponse($this->parameters->get('login_uri'));
    }

    public function authCode(Request $request, UserInterface $user): Response
    {
        $session = $request->getSession();
        $clientID = $session->get('client_id');
        $clientSecret = $session->get('client_secret');
        $scopes = explode(',',$session->get('scopes'));
        $scopes =  new ArrayCollection(is_array($scopes) ? $scopes : [$scopes]);

        $client = $this->em->getRepository(Client::class)->findOneBy(['clientID' => $clientID]);
        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $authCode = new AuthCode($client, $user, $scopes, new ParameterBag($this->parameters->get('auth_code')));

        $this->em->persist($authCode);

        $this->em->flush();
        return new RedirectResponse($client->getRedirectUri().'?code='.$authCode->getCode());
    }

    public function resourceOwner(Request $request): Reponse {
        $clientID = $session->get('client_id');
        $client = $this->em->getRepository(Client::class)->findOneBy(['clientID' => $clientID]);
        $scopes = explode(',',$request->query->get('scopes'));
        $scopes =  is_array($scopes) ? $scopes : [$scopes];

        $passport = $this->resourceOwnerAuthenticator->authenticate($request);
        $accessToken = $this->createAccessToken($client,$passport->getUser(), $scopes);

        $refreshToken = $accessToken->getRefreshToken();

        return new JsonResponse(array_filter([
            'access_token' => $accessToken->getToken(),
            'refresh_token' => $refreshToken?->getToken(),
            'token_type' => 'Bearer',
            'expires_in' => $this->parameters->get('access_token')['invalidate_after']
        ], function ($value) {
            return $value !== null;
        }));
    }

    public function refreshToken(Request $request): Response
    {
        $clientSecret = $request->query->get('client_secret');
        $existingToken = $this->em->getRepository(AccessToken::class)->findOneBy(['refreshToken' => $refreshToken]);
        $client = $existingToken->getClient();
        if (!in_array('refresh_token',json_decode($client->getGrantTypes()))) {
            throw new AuthServerException('invalid grant type');
        }
        $refreshToken = $this->em->getRepository(RefreshToken::class)->findOneBy(['token' => $request->query->get('refresh_token')]);
        if (!$existingToken || $existingToken->getExpiresAt() < new \DateTime()) {
            throw new AuthServerException('invalid_grant');
        }

        $client = $existingToken->getClient();

        if ($client->getClientSecret() !== $clientSecret) {
            throw new AuthServerException('invalid client secret');
        }

        $user = $existingToken->getUser();
        $scopes = $existingToken->getScopes();

        $accessToken = $this->createAccessToken($client,$user,$scopes);
        $refreshToken = $accessToken->getRefreshToken();

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
