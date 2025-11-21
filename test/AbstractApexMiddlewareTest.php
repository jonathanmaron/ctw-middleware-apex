<?php

declare(strict_types=1);

namespace CtwTest\Middleware\ApexMiddleware;

use Ctw\Middleware\AbstractMiddleware;
use Ctw\Middleware\ApexMiddleware\AbstractApexMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AbstractApexMiddlewareTest extends TestCase
{
    /**
     * Test that AbstractApexMiddleware extends AbstractMiddleware
     */
    public function testAbstractApexMiddlewareExtendsAbstractMiddleware(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertTrue($reflection->isAbstract());
        self::assertTrue($reflection->isSubclassOf(AbstractMiddleware::class));
    }

    /**
     * Test that AbstractApexMiddleware is abstract
     */
    public function testAbstractApexMiddlewareIsAbstract(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertTrue($reflection->isAbstract());
    }

    /**
     * Test that AbstractApexMiddleware implements MiddlewareInterface
     */
    public function testAbstractApexMiddlewareImplementsMiddlewareInterface(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertTrue($reflection->implementsInterface(MiddlewareInterface::class));
    }

    /**
     * Test that concrete implementation can be instantiated
     */
    public function testConcreteImplementationCanBeInstantiated(): void
    {
        $concrete = new class extends AbstractApexMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        self::assertInstanceOf(AbstractApexMiddleware::class, $concrete);
        self::assertInstanceOf(AbstractMiddleware::class, $concrete);
        self::assertInstanceOf(MiddlewareInterface::class, $concrete);
    }

    /**
     * Test that concrete implementation has process method
     */
    public function testConcreteImplementationHasProcessMethod(): void
    {
        $concrete = new class extends AbstractApexMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        self::assertTrue(method_exists($concrete, 'process'));
    }

    /**
     * Test that concrete implementation can process requests
     */
    public function testConcreteImplementationCanProcessRequests(): void
    {
        $concrete = new class extends AbstractApexMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        $request = $this->createMock(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $concrete->process($request, $handler);

        self::assertSame($response, $result);
    }

    /**
     * Test that AbstractApexMiddleware has no public methods besides inherited ones
     */
    public function testAbstractApexMiddlewareHasNoPublicMethods(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Filter out inherited methods
        $ownMethods = array_filter($methods, function ($method) {
            return $method->getDeclaringClass()->getName() === AbstractApexMiddleware::class;
        });

        self::assertCount(0, $ownMethods);
    }

    /**
     * Test that AbstractApexMiddleware has no properties
     */
    public function testAbstractApexMiddlewareHasNoProperties(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);
        $properties = $reflection->getProperties();

        // Filter out inherited properties
        $ownProperties = array_filter($properties, function ($property) {
            return $property->getDeclaringClass()->getName() === AbstractApexMiddleware::class;
        });

        self::assertCount(0, $ownProperties);
    }

    /**
     * Test that AbstractApexMiddleware namespace is correct
     */
    public function testAbstractApexMiddlewareNamespaceIsCorrect(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertSame('Ctw\Middleware\ApexMiddleware', $reflection->getNamespaceName());
    }

    /**
     * Test that AbstractApexMiddleware is a class
     */
    public function testAbstractApexMiddlewareIsClass(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertFalse($reflection->isInterface());
        self::assertFalse($reflection->isTrait());
    }

    /**
     * Test that multiple concrete implementations are independent
     */
    public function testMultipleConcreteImplementationsAreIndependent(): void
    {
        $concrete1 = new class extends AbstractApexMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        $concrete2 = new class extends AbstractApexMiddleware {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                return $handler->handle($request);
            }
        };

        self::assertNotSame($concrete1, $concrete2);
        self::assertInstanceOf(AbstractApexMiddleware::class, $concrete1);
        self::assertInstanceOf(AbstractApexMiddleware::class, $concrete2);
    }
}
