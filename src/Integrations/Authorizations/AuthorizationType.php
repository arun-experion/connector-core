<?php

declare(strict_types=1);

namespace Connector\Integrations\Authorizations;

enum AuthorizationType: string
{
    case NONE  = '';
    case OAUTH = 'OAUTH';
    case BASIC = 'BASIC';
}
