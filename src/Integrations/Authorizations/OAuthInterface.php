<?php

namespace Connector\Integrations\Authorizations;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;

Interface OAuthInterface extends AuthorizationInterface
{

    /**
     * @param string $authorization JSON string with authorization data
     *
     * @return void
     */
    public function setOAuthCredentials(string $authorization): void;

    public function getAccessToken(): string;

    public function getRefreshToken(): string;

    public function getExpires(): int;

    /**
     * Returns a League\OAuth2 Provider.
     */
    public function getAuthorizationProvider(): AbstractProvider;

    /**
     * Extracts the username from $user
     * @param \League\OAuth2\Client\Provider\ResourceOwnerInterface $user
     *
     * @return string
     */
    public function getAuthorizedUserName(ResourceOwnerInterface $user): string;

}
