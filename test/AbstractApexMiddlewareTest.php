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
     * Test that AbstractApexMiddleware reports as both abstract and a subclass of AbstractMiddleware when reflected.
     */
    public function testAbstractApexMiddlewareExtendsAbstractMiddleware(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertTrue($reflection->isAbstract());
        self::assertTrue($reflection->isSubclassOf(AbstractMiddleware::class));
    }

    /**
     * Test that AbstractApexMiddleware is declared abstract when reflected.
     */
    public function testAbstractApexMiddlewareIsAbstract(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertTrue($reflection->isAbstract());
    }

    /**
     * Test that AbstractApexMiddleware implements the PSR-15 MiddlewareInterface when reflected.
     */
    public function testAbstractApexMiddlewareImplementsMiddlewareInterface(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertTrue($reflection->implementsInterface(MiddlewareInterface::class));
    }

    /**
     * Test that a concrete subclass is an instance of the whole middleware hierarchy when instantiated.
     */
    public function testConcreteImplementationCanBeInstantiated(): void
    {
        $concrete = new class() extends AbstractApexMiddleware {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        self::assertInstanceOf(AbstractApexMiddleware::class, $concrete);
        self::assertInstanceOf(AbstractMiddleware::class, $concrete);
        self::assertInstanceOf(MiddlewareInterface::class, $concrete);
    }

    /**
     * Test that a concrete subclass exposes a process method when instantiated.
     */
    public function testConcreteImplementationHasProcessMethod(): void
    {
        $concrete = new class() extends AbstractApexMiddleware {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        self::assertTrue(method_exists($concrete, 'process'));
    }

    /**
     * Test that a concrete subclass returns the handler response when its process method delegates to the handler.
     */
    public function testConcreteImplementationCanProcessRequests(): void
    {
        $concrete = new class() extends AbstractApexMiddleware {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $request = $this->createStub(ServerRequestInterface::class);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createStub(ResponseInterface::class);

        $handler->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $result = $concrete->process($request, $handler);

        self::assertSame($response, $result);
    }

    /**
     * Test that AbstractApexMiddleware declares no public methods of its own when inherited methods are excluded.
     */
    public function testAbstractApexMiddlewareHasNoPublicMethods(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        // Filter out inherited methods
        $ownMethods = array_filter($methods, function ($method) {
            return AbstractApexMiddleware::class === $method->getDeclaringClass()
                ->getName();
        });

        self::assertCount(0, $ownMethods);
    }

    /**
     * Test that AbstractApexMiddleware declares no properties of its own when inherited properties are excluded.
     */
    public function testAbstractApexMiddlewareHasNoProperties(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);
        $properties = $reflection->getProperties();

        // Filter out inherited properties
        $ownProperties = array_filter($properties, function ($property) {
            return AbstractApexMiddleware::class === $property->getDeclaringClass()
                ->getName();
        });

        self::assertCount(0, $ownProperties);
    }

    /**
     * Test that AbstractApexMiddleware resides in the ApexMiddleware namespace when reflected.
     */
    public function testAbstractApexMiddlewareNamespaceIsCorrect(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertSame('Ctw\Middleware\ApexMiddleware', $reflection->getNamespaceName());
    }

    /**
     * Test that AbstractApexMiddleware is a class rather than an interface or trait when reflected.
     */
    public function testAbstractApexMiddlewareIsClass(): void
    {
        $reflection = new \ReflectionClass(AbstractApexMiddleware::class);

        self::assertFalse($reflection->isInterface());
        self::assertFalse($reflection->isTrait());
    }

    /**
     * Test that two concrete subclasses are distinct instances when instantiated separately.
     */
    public function testMultipleConcreteImplementationsAreIndependent(): void
    {
        $concrete1 = new class() extends AbstractApexMiddleware {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        $concrete2 = new class() extends AbstractApexMiddleware {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return $handler->handle($request);
            }
        };

        self::assertNotSame($concrete1, $concrete2);
        self::assertInstanceOf(AbstractApexMiddleware::class, $concrete1);
        self::assertInstanceOf(AbstractApexMiddleware::class, $concrete2);
    }
}
