<?php

namespace Snoke\OAuthServer;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\OAuthServer\Interface\ScopeCollectionInterface;

abstract class ScopeCollection extends ArrayCollection implements ScopeCollectionInterface
{

}