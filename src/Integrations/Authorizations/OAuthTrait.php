<?php

namespace Connector\Integrations\Authorizations;

use Connector\Exceptions\InvalidExecutionPlan;

trait OAuthTrait
{
    protected string $accessToken;
    protected string $refreshToken;
    protected int $expires;

    /**
     * @param string $authorization  JSON string with authorization data
     *
     * @return void
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    function setOAuthCredentials(string $authorization): void
    {
        $authorization = json_decode($authorization, JSON_OBJECT_AS_ARRAY);

        if (! array_key_exists('accessToken', $authorization)) {
            throw new InvalidExecutionPlan('Expected accessToken in authorization data');
        }
        if (! array_key_exists('refreshToken', $authorization)) {
            throw new InvalidExecutionPlan('Expected refreshToken in authorization data');
        }
        if (! array_key_exists('expires', $authorization)) {
            throw new InvalidExecutionPlan('Expected expires in authorization data');
        }

        $this->accessToken  = $authorization['accessToken'];
        $this->refreshToken = $authorization['refreshToken'];
        $this->expires      = $authorization['expires'];
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @return int
     */
    public function getExpires(): int
    {
        return $this->expires;
    }

}
