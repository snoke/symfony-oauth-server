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
],
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
namespace App\DTO\Scope;
use App\Entity\User;
use Snoke\OAuthServer\Interface\AuthenticatableInterface;
use Snoke\OAuthServer\Interface\ScopeInterface;

readonly class EmailScope implements ScopeInterface
{
    public function toArray(AuthenticatableInterface $authenticatable): array {

        /** @var User $authenticatable  */
        return ['email' => $authenticatable->getEmail()];
    }
}
```
#### create a scope collection:
You will also need a scope collection to define the available scopes for the bundle:
```php
namespace App\Collection;

use App\DTO\Scope\EmailScope;
use Snoke\OAuthServer\Interface\ScopeCollectionInterface;

class ScopesCollection implements ScopeCollectionInterface
{
    public function getScopes(): array {
        return [
            'email' => EmailScope::class
        ];
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
- a client will forward a user to the authorize uri and provide its client_id, client_secret and the requested scopes as query parameters:

  ```https://www.yourserver.example/authorize?client_id=123456&client_secret=12346&scopes=email```

  this will redirect the client to your login. after a successful login the user will be redirected to the clients redirect_uri with an ```AuthCode``` as query parameter


- the client can now trade his auth code for an ```AccessToken``` by making a request to the access-token-uri
  
    ```https://www.yourserver.example/accessToken?client_id=123456&client_secret=12346&scopes=email&code=12346```


- having the access token, it can now be used to fetch user informations (defined by the scopes) from
  
  ```https://www.yourserver.example/decodeToken?client_id=123456&client_secret=12346&scopes=email&token=12346```

note that these are the default routes, you can change them in the configuration yaml
