# Symfony OAuth Server Bundle
bundle to run your own oauth2 server for Symfony7

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

hit ```composer require snoke/symfony-oauth-server``` to install the package

## configuration
edit ```config/packes/snoke_o_auth_server.yaml``` in your project folder
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
scopes are implemented as DTOs as shown in the following example
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
you will also need a scope collection to tell the bundle which scopes are available
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

if you are using the default form authenticator you can simply add following line to your login template within the
```<form>```-block
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
