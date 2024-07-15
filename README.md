# Symfony OAuth Server Bundle
bundle for Symfony7 to run your own oauth2 server

Work in progress

## installation
add the custom repository to composer.json
```yaml
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:snoke/symfony-oauth-server.git"
    }
]
```

hit ```composer require snoke/symfony-oauth-server:dev-master``` to install the package

## configuration
edit ```config/packes/snoke_o_auth_server.yaml```
- set the **login_uri** to your custom login uri
- set **authenticatable** to the class you want to authenticate (probably your User class)
```yaml
snoke_o_auth_server:
  authenticatable: App\Entity\User # the class to authenticate (probably your user class)
  login_uri: '/login' # your custom login uri
```

### implement **AuthenticatableInterface** 
implement **AuthenticatableInterface**  in your Authenticatable (User) class as shown in this example:
```php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Snoke\OAuthServer\Interface\AuthenticatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User implements UserInterface, PasswordAuthenticatedUserInterface, AuthenticatableInterface
{
// ...
```

### Scopes
#### create a scope:
Scopes are implemented as DTOs (Data Transfer Objects). Here is an example:
```php
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Snoke\OAuthServer\Interface\AuthenticatableInterface;
use Snoke\OAuthServer\Interface\ScopeInterface;

readonly class EmailScope implements ScopeInterface
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }
    
    public function toArray(AuthenticatableInterface $authenticatable): array 
    {

        /** @var User $authenticatable  */
        $user = $this->em->getRepository(User::class)->find($authenticatable->getId());
        return ['email' => $user->getEmail()];
    }
}
```
#### create a scope collection:
You will also need a scope collection to define the available scopes for the bundle:
```php
namespace App\Collection;

use App\Dto\Scope\EmailScope;
use Doctrine\Common\Collections\ArrayCollection;
use Snoke\OAuthServer\Interface\ScopeCollectionInterface;

class ScopesCollection extends ArrayCollection implements ScopeCollectionInterface
{
    public function __construct(private readonly EmailScope $emailScope)
    {
        parent::__construct([
            'email' => $this->emailScope
        ]);
    }
}
```

make sure your scope collection is registered in ```config/packages/snoke_o_auth.yaml```:
```yaml
snoke_o_auth_server:
    scopes: App\Collection\ScopesCollection # your custom scopes
```

### Authenticator
#### Edit your authenticator, a successful login must redirect to the auth_code_uri.

If you are using the default form authenticator, add the following line to your login template within the ```<form>```-block
```html
<input type="hidden" name="_target_path" value="{{ path('snoke_o_auth_server_auth_code') }}">
```
if you are using a custom authenticator, you can use the method ```onAuthenticationSuccess```
```php
public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
{
    return new RedirectResponse($this->generateUrl('snoke_o_auth_server_auth_code'));
}
```

## Clients
### create a client
you can create a client with the following command:
```php bin/console oauth:create:client```
### client workflow
- The client redirects the user to the authorization URI, including the client_id, client_secret, and requested scopes as query parameters:
```
https://www.yourserver.example/authorize?client_id=123456&client_secret=12346&scopes=email
```

The user is directed to the login page. Upon successful login, the user is redirected to the client's redirect_uri with an authorization code (AuthCode) included as a query parameter.

- The client exchanges the authorization code for an access token by making a request to the access token URI. 
Depending on the configuration, the response may include both an access token and a refresh token.
The authorization code is included in the Authorization header as a Bearer token:
  
```
GET https://www.yourserver.example/accessToken?client_id=123456&client_secret=12346&scopes=email
Headers:
Authorization: Bearer <AuthCode>
```

- With the access token, the client can fetch user information (as defined by the scopes) by making a request to the user information URI. The access token is included in the Authorization header as a Bearer token:
  
```
GET https://www.yourserver.example/decodeToken?client_id=123456&client_secret=12346&scopes=email
Headers:
Authorization: Bearer <AccessToken>
```

- When the access token expires, the client can use the refresh token to obtain a new access token by making a request to the refresh token URI. The refresh token is included in the Authorization header as a Bearer token:
```
GET https://www.yourserver.example/refreshToken?client_id=123456&client_secret=12346&scopes=email
Headers:
    Authorization: Bearer <RefreshToken>
```

note that these are the default routes, you can change them in the configuration yaml
