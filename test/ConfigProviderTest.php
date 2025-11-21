<?php

declare(strict_types=1);

namespace CtwTest\Middleware\ApexMiddleware;

use Ctw\Middleware\ApexMiddleware\ApexMiddleware;
use Ctw\Middleware\ApexMiddleware\ApexMiddlewareFactory;
use Ctw\Middleware\ApexMiddleware\ConfigProvider;
use PHPUnit\Framework\TestCase;

final class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new ConfigProvider();
    }

    /**
     * Test that invoke returns expected configuration array structure
     */
    public function testInvokeReturnsConfigurationArray(): void
    {
        $config = ($this->provider)();

        self::assertIsArray($config);
        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * Test that invoke returns dependencies configuration
     */
    public function testInvokeReturnsDependenciesConfiguration(): void
    {
        $config = ($this->provider)();

        self::assertIsArray($config['dependencies']);
        self::assertArrayHasKey('factories', $config['dependencies']);
    }

    /**
     * Test that getDependencies returns expected structure
     */
    public function testGetDependenciesReturnsExpectedStructure(): void
    {
        $dependencies = $this->provider->getDependencies();

        self::assertIsArray($dependencies);
        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that factories contains ApexMiddleware mapping
     */
    public function testFactoriesContainApexMiddlewareMapping(): void
    {
        $dependencies = $this->provider->getDependencies();
        $factories = $dependencies['factories'];

        self::assertArrayHasKey(ApexMiddleware::class, $factories);
        self::assertSame(ApexMiddlewareFactory::class, $factories[ApexMiddleware::class]);
    }

    /**
     * Test that invoke method calls getDependencies internally
     */
    public function testInvokeCallsGetDependencies(): void
    {
        $expectedDependencies = $this->provider->getDependencies();
        $config = ($this->provider)();

        self::assertSame($expectedDependencies, $config['dependencies']);
    }

    /**
     * Test that configuration array contains only expected keys
     */
    public function testConfigurationArrayContainsOnlyExpectedKeys(): void
    {
        $config = ($this->provider)();

        self::assertCount(1, $config);
        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * Test that dependencies array contains only expected keys
     */
    public function testDependenciesArrayContainsOnlyExpectedKeys(): void
    {
        $dependencies = $this->provider->getDependencies();

        self::assertCount(1, $dependencies);
        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that factories array contains only ApexMiddleware entry
     */
    public function testFactoriesArrayContainsOnlyApexMiddlewareEntry(): void
    {
        $dependencies = $this->provider->getDependencies();
        $factories = $dependencies['factories'];

        self::assertCount(1, $factories);
        self::assertArrayHasKey(ApexMiddleware::class, $factories);
    }

    /**
     * Test that multiple invocations return consistent results
     */
    public function testMultipleInvocationsReturnConsistentResults(): void
    {
        $firstCall = ($this->provider)();
        $secondCall = ($this->provider)();

        self::assertSame($firstCall, $secondCall);
    }

    /**
     * Test that multiple getDependencies calls return consistent results
     */
    public function testMultipleGetDependenciesCallsReturnConsistentResults(): void
    {
        $firstCall = $this->provider->getDependencies();
        $secondCall = $this->provider->getDependencies();

        self::assertSame($firstCall, $secondCall);
    }
}
