<?php

namespace Snoke\OAuthServer\Interface;

interface ScopeInterface
{
    public function toArray(AuthenticatableInterface $authenticatable): array;
}