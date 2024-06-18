<?php

namespace Connector\Integrations\Authorizations;

use Connector\Exceptions\InvalidExecutionPlan;

trait BasicAuthTrait
{
    protected string $username;
    protected string $password;

    /**
     * @throws \Connector\Exceptions\InvalidExecutionPlan
     */
    function setBasicCredentials(string $authorization): void
    {
        $authorization = json_decode($authorization, JSON_OBJECT_AS_ARRAY);

        if(!array_key_exists('username', $authorization)) {
            throw new InvalidExecutionPlan('Expected username in authorization data');
        }
        if(!array_key_exists('password', $authorization)) {
            throw new InvalidExecutionPlan('Expected password in authorization data');
        }

        $this->username = $authorization['username'];
        $this->password = $authorization['password'];
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

}
