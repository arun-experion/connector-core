<?php

namespace Connector\Integrations\Payment;

use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Response;
use Connector\Mapping;
use Connector\Record\RecordKey;
use Connector\Record\RecordLocator;
use Connector\Exceptions\NotImplemented;

abstract class AbstractPayment extends AbstractIntegration
{
    public function extract(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response {
        throw new NotImplemented();
    }

    public function load(RecordLocator $recordLocator, Mapping $mapping, ?RecordKey $scope): Response {
        throw new NotImplemented();
    }

    abstract protected function createPaymentMethod();
    abstract protected function getPaymentMethod();

    abstract protected function createCustomer();
    abstract protected function getCustomer();

    abstract protected function createSubscription();
    abstract protected function getSubscription();

    abstract protected function createCharge();
    abstract protected function getCharge();
    abstract protected function captureCharge();



}