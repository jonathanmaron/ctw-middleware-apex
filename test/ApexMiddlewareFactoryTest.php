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
        $this->container = $this->createStub(ContainerInterface::class);
    }

    /**
     * Test that the factory returns an ApexMiddleware instance when invoked with a container.
     */
    public function testFactoryCreatesApexMiddlewareInstance(): void
    {
        $middleware = ($this->factory)($this->container);

        self::assertInstanceOf(ApexMiddleware::class, $middleware);
    }

    /**
     * Test that the factory returns a distinct instance when invoked more than once.
     */
    public function testFactoryReturnsNewInstanceOnEachInvocation(): void
    {
        $firstInstance = ($this->factory)($this->container);
        $secondInstance = ($this->factory)($this->container);

        self::assertNotSame($firstInstance, $secondInstance);
    }

    /**
     * Test that the factory never resolves any service from the container when creating the middleware.
     */
    public function testFactoryCreatesInstanceWithoutContainerDependencies(): void
    {
        // Container should not be called for any services
        $container = $this->createMock(ContainerInterface::class);
        $container->expects(self::never())
            ->method('get');

        $middleware = ($this->factory)($container);

        self::assertInstanceOf(ApexMiddleware::class, $middleware);
    }

    /**
     * Test that the factory produces an ApexMiddleware when invoked with any container implementation.
     */
    public function testFactoryWorksWithDifferentContainerImplementations(): void
    {
        $anotherContainer = $this->createStub(ContainerInterface::class);

        $middleware = ($this->factory)($anotherContainer);

        self::assertInstanceOf(ApexMiddleware::class, $middleware);
    }

    /**
     * Test that two separate factory objects produce distinct middleware instances when each is invoked.
     */
    public function testMultipleFactoryInstancesCreateIndependentMiddlewares(): void
    {
        $anotherFactory = new ApexMiddlewareFactory();

        $middlewareFromFirstFactory = ($this->factory)($this->container);
        $middlewareFromSecondFactory = ($anotherFactory)($this->container);

        self::assertNotSame($middlewareFromFirstFactory, $middlewareFromSecondFactory);
    }

    /**
     * Test that the factory object is callable when treated as an invokable.
     */
    public function testFactoryIsInvokable(): void
    {
        self::assertTrue(is_callable($this->factory));
    }

    /**
     * Test that the created middleware exposes the PSR-15 process method when built by the factory.
     */
    public function testCreatedMiddlewareIsCallableAsPsr15Middleware(): void
    {
        $middleware = ($this->factory)($this->container);

        self::assertTrue(method_exists($middleware, 'process'));
    }

    /**
     * Test that __invoke() carries the #[\NoDiscard] attribute so callers are warned when the created middleware is ignored.
     */
    public function testInvokeMethodIsMarkedNoDiscardToWarnWhenCreatedMiddlewareIsIgnored(): void
    {
        $reflection = new \ReflectionMethod(ApexMiddlewareFactory::class, '__invoke');
        $attributes = $reflection->getAttributes(\NoDiscard::class);

        self::assertCount(1, $attributes);
        self::assertInstanceOf(\NoDiscard::class, $attributes[0]->newInstance());
    }
}
