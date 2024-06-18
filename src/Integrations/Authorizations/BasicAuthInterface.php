<?php

namespace Connector\Integrations\Authorizations;

interface BasicAuthInterface extends AuthorizationInterface
{
    public function setBasicCredentials(string $authorization): void;

    public function getUsername(): string;

    public function getPassword(): string;
}
