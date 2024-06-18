<?php

use Connector\Exceptions\UnsupportedFeature;
use Connector\Integrations\AbstractIntegration;
use Connector\Integrations\Fake;
use Connector\Schema\GenericSchema;
use PHPUnit\Framework\TestCase;

/**
 * @uses Connector\Schema\Builder
 * @uses Connector\Schema\Builder\RecordProperties
 * @uses Connector\Schema\Builder\RecordTypes
 * @uses Connector\Schema\GenericSchema
 * @uses Connector\Schema\IntegrationSchema
 * @uses Connector\Integrations\Fake\Integration
 * @covers Connector\Integrations\AbstractIntegration
 */
final class AbstractIntegrationTest extends TestCase
{

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSetSchema(): void
    {
        $mock = $this->getMockForAbstractClass(AbstractIntegration::class);

        $mock->setSchema(new GenericSchema(), "fr-FR",'GMT+1');
        $this->assertInstanceOf(Connector\Schema\GenericSchema::class, $mock->getSchema());

        $this->assertEquals('fr-FR', $mock->schema->getLocale());
        $this->assertEquals('GMT+1', $mock->schema->getTimeZone());
    }

    public function testNoRollbackWithDefaultTransaction(): void
    {
        $mock = $this->getMockForAbstractClass(AbstractIntegration::class);

        $mock->begin();

        $this->expectException(UnsupportedFeature::class);
        $mock->rollback();

        $mock->end();
    }

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testLog(): void
    {
        $mock = new Fake\Integration();
        $mock->log("abc");
        $mock->log("def");
        $this->assertSame(['abc','def'], $mock->getLog());
    }
}
