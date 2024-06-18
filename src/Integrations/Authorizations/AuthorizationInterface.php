<?php

namespace Connector\Integrations\Authorizations;

Interface AuthorizationInterface
{
    /**
     * Set the authorization parameters for the integration. E.g. OAuth tokens.
     */
    public function setAuthorization(string $authorization): void;
}
