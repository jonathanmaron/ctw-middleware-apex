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
     * Test that __invoke returns an array containing the dependencies key when the provider is invoked.
     */
    public function testInvokeReturnsConfigurationArrayWithDependenciesKey(): void
    {
        $config = ($this->provider)();

        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * Test that __invoke nests a factories array under dependencies when the provider is invoked.
     */
    public function testInvokeReturnsDependenciesConfigurationWithFactoriesKey(): void
    {
        $config = ($this->provider)();

        self::assertIsArray($config['dependencies']);
        self::assertArrayHasKey('factories', $config['dependencies']);
    }

    /**
     * Test that getDependencies returns an array exposing a factories key when called directly.
     */
    public function testGetDependenciesReturnsArrayWithFactoriesKey(): void
    {
        $dependencies = $this->provider->getDependencies();

        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that the factories map wires ApexMiddleware to its factory when dependencies are retrieved.
     */
    public function testFactoriesMapApexMiddlewareToApexMiddlewareFactory(): void
    {
        $dependencies = $this->provider->getDependencies();
        $factories = $dependencies['factories'];

        self::assertArrayHasKey(ApexMiddleware::class, $factories);
        self::assertSame(ApexMiddlewareFactory::class, $factories[ApexMiddleware::class]);
    }

    /**
     * Test that __invoke delegates to getDependencies when building the dependencies section.
     */
    public function testInvokeReusesGetDependenciesResultForDependenciesSection(): void
    {
        $expectedDependencies = $this->provider->getDependencies();
        $config = ($this->provider)();

        self::assertSame($expectedDependencies, $config['dependencies']);
    }

    /**
     * Test that the configuration array holds only the dependencies key when the provider is invoked.
     */
    public function testConfigurationArrayContainsOnlyDependenciesKey(): void
    {
        $config = ($this->provider)();

        self::assertCount(1, $config);
        self::assertArrayHasKey('dependencies', $config);
    }

    /**
     * Test that the dependencies array holds only the factories key when dependencies are retrieved.
     */
    public function testDependenciesArrayContainsOnlyFactoriesKey(): void
    {
        $dependencies = $this->provider->getDependencies();

        self::assertCount(1, $dependencies);
        self::assertArrayHasKey('factories', $dependencies);
    }

    /**
     * Test that the factories array holds only the ApexMiddleware entry when dependencies are retrieved.
     */
    public function testFactoriesArrayContainsOnlyApexMiddlewareEntry(): void
    {
        $dependencies = $this->provider->getDependencies();
        $factories = $dependencies['factories'];

        self::assertCount(1, $factories);
        self::assertArrayHasKey(ApexMiddleware::class, $factories);
    }

    /**
     * Test that __invoke returns identical configuration when called multiple times.
     */
    public function testMultipleInvocationsReturnIdenticalConfiguration(): void
    {
        $firstCall = ($this->provider)();
        $secondCall = ($this->provider)();

        self::assertSame($firstCall, $secondCall);
    }

    /**
     * Test that getDependencies returns identical dependencies when called multiple times.
     */
    public function testMultipleGetDependenciesCallsReturnIdenticalDependencies(): void
    {
        $firstCall = $this->provider->getDependencies();
        $secondCall = $this->provider->getDependencies();

        self::assertSame($firstCall, $secondCall);
    }
}
