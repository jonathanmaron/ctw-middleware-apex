<?php

declare(strict_types=1);

namespace CtwTest\Middleware\ApexMiddleware;

use Ctw\Middleware\ApexMiddleware\ApexMiddleware;
use Ctw\Middleware\ApexMiddleware\ApexMiddlewareFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class ApexMiddlewareFactoryTest extends TestCase
{
    private ApexMiddlewareFactory $factory;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new ApexMiddlewareFactory();
        $this->container = $this->createMock(ContainerInterface::class);
    }

    /**
     * Test that factory creates ApexMiddleware instance
     */
    public function testFactoryCreatesApexMiddlewareInstance(): void
    {
        $middleware = ($this->factory)($this->container);

        self::assertInstanceOf(ApexMiddleware::class, $middleware);
    }

    /**
     * Test that factory returns new instance on each invocation
     */
    public function testFactoryReturnsNewInstanceOnEachInvocation(): void
    {
        $firstInstance = ($this->factory)($this->container);
        $secondInstance = ($this->factory)($this->container);

        self::assertNotSame($firstInstance, $secondInstance);
    }

    /**
     * Test that factory creates instance without container dependencies
     */
    public function testFactoryCreatesInstanceWithoutContainerDependencies(): void
    {
        // Container should not be called for any services
        $this->container->expects(self::never())
            ->method('get');

        $middleware = ($this->factory)($this->container);

        self::assertInstanceOf(ApexMiddleware::class, $middleware);
    }

    /**
     * Test that factory works with different container implementations
     */
    public function testFactoryWorksWithDifferentContainerImplementations(): void
    {
        $anotherContainer = $this->createMock(ContainerInterface::class);

        $middleware = ($this->factory)($anotherContainer);

        self::assertInstanceOf(ApexMiddleware::class, $middleware);
    }

    /**
     * Test that multiple factory instances create independent middlewares
     */
    public function testMultipleFactoryInstancesCreateIndependentMiddlewares(): void
    {
        $anotherFactory = new ApexMiddlewareFactory();

        $middlewareFromFirstFactory = ($this->factory)($this->container);
        $middlewareFromSecondFactory = ($anotherFactory)($this->container);

        self::assertNotSame($middlewareFromFirstFactory, $middlewareFromSecondFactory);
    }

    /**
     * Test that factory is invokable
     */
    public function testFactoryIsInvokable(): void
    {
        self::assertTrue(is_callable($this->factory));
    }

    /**
     * Test that created middleware is callable as PSR-15 middleware
     */
    public function testCreatedMiddlewareIsCallableAsPsr15Middleware(): void
    {
        $middleware = ($this->factory)($this->container);

        self::assertTrue(method_exists($middleware, 'process'));
    }
}
