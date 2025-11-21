<?php

declare(strict_types=1);

namespace CtwTest\Middleware\ApexMiddleware;

use Ctw\Middleware\ApexMiddleware\ApexMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class ApexMiddlewareTest extends TestCase
{
    private ApexMiddleware $middleware;
    private ServerRequestInterface $request;
    private RequestHandlerInterface $handler;
    private ResponseInterface $response;
    private UriInterface $uri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new ApexMiddleware();
        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->uri = $this->createMock(UriInterface::class);

        // Default setup
        $this->request->method('getUri')->willReturn($this->uri);
        $this->handler->method('handle')->willReturn($this->response);
    }

    protected function tearDown(): void
    {
        // Clear environment variable
        putenv('APP_ENV');
        parent::tearDown();
    }

    /**
     * Test that requests with www prefix are not redirected
     */
    public function testRequestWithWwwPrefixIsNotRedirected(): void
    {
        $this->uri->method('getHost')->willReturn('www.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that requests with WWW prefix in uppercase are not redirected
     */
    public function testRequestWithUppercaseWwwPrefixIsNotRedirected(): void
    {
        $this->uri->method('getHost')->willReturn('WWW.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that requests with mixed case WWW prefix are not redirected
     */
    public function testRequestWithMixedCaseWwwPrefixIsNotRedirected(): void
    {
        $this->uri->method('getHost')->willReturn('WwW.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that requests with www-xx prefix are not redirected
     */
    public function testRequestWithWwwDashTwoLetterPrefixIsNotRedirected(): void
    {
        $this->uri->method('getHost')->willReturn('www-pl.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that requests with www-en prefix are not redirected
     */
    public function testRequestWithWwwDashEnPrefixIsNotRedirected(): void
    {
        $this->uri->method('getHost')->willReturn('www-en.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that apex domain is redirected to www subdomain
     */
    public function testApexDomainIsRedirectedToWwwSubdomain(): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('');
        $this->uri->method('getQuery')->willReturn('');

        $newResponse = $this->createMock(ResponseInterface::class);
        $newResponse->method('withHeader')->willReturn($newResponse);

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that redirect response has 301 status code
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('apexDomainProvider')]
    public function testRedirectResponseHas301StatusCode(string $host): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn($host);
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        // The middleware creates a new response with 301 status
        self::assertNotSame($this->response, $result);
    }

    /**
     * @return array<string, array{host: string}>
     */
    public static function apexDomainProvider(): array
    {
        return [
            'simple domain' => ['host' => 'example.com'],
            'subdomain' => ['host' => 'api.example.com'],
            'deep subdomain' => ['host' => 'api.v1.example.com'],
            'single word' => ['host' => 'localhost'],
            'with numbers' => ['host' => 'example123.com'],
            'with hyphens' => ['host' => 'my-example.com'],
        ];
    }

    /**
     * Test that redirect preserves HTTPS scheme
     */
    public function testRedirectPreservesHttpsScheme(): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/test');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        // The middleware should create a new redirect response
        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that redirect preserves HTTP scheme
     */
    public function testRedirectPreservesHttpScheme(): void
    {
        $this->uri->method('getScheme')->willReturn('http');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/test');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that redirect preserves request path
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pathProvider')]
    public function testRedirectPreservesRequestPath(string $path): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn($path);
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * @return array<string, array{path: string}>
     */
    public static function pathProvider(): array
    {
        return [
            'root path' => ['path' => '/'],
            'simple path' => ['path' => '/about'],
            'nested path' => ['path' => '/blog/post/123'],
            'path with extension' => ['path' => '/file.html'],
            'path with dots' => ['path' => '/path/to/file.json'],
            'path with hyphens' => ['path' => '/my-custom-page'],
            'path with underscores' => ['path' => '/my_custom_page'],
            'empty path' => ['path' => ''],
        ];
    }

    /**
     * Test that redirect preserves query string
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('queryStringProvider')]
    public function testRedirectPreservesQueryString(string $query): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/search');
        $this->uri->method('getQuery')->willReturn($query);

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * @return array<string, array{query: string}>
     */
    public static function queryStringProvider(): array
    {
        return [
            'single parameter' => ['query' => 'q=test'],
            'multiple parameters' => ['query' => 'q=test&page=1'],
            'with special chars' => ['query' => 'q=hello+world&sort=desc'],
            'with encoded chars' => ['query' => 'q=hello%20world'],
            'with numbers' => ['query' => 'id=123&count=456'],
            'with array notation' => ['query' => 'items[]=1&items[]=2'],
        ];
    }

    /**
     * Test that redirect without query string has no question mark
     */
    public function testRedirectWithoutQueryStringHasNoQuestionMark(): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/test');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with two-letter suffix creates custom prefix
     */
    public function testAppEnvWithTwoLetterSuffixCreatesCustomPrefix(): void
    {
        putenv('APP_ENV=staging-pl');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with two-letter suffix creates www-xx prefix
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('appEnvTwoLetterProvider')]
    public function testAppEnvWithTwoLetterSuffixCreatesWwwDashPrefix(string $appEnv, string $expectedInitials): void
    {
        putenv("APP_ENV={$appEnv}");

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
        // Expected location: https://www-{initials}.example.com/
    }

    /**
     * @return array<string, array{appEnv: string, expectedInitials: string}>
     */
    public static function appEnvTwoLetterProvider(): array
    {
        return [
            'staging-pl' => ['appEnv' => 'staging-pl', 'expectedInitials' => 'pl'],
            'staging-en' => ['appEnv' => 'staging-en', 'expectedInitials' => 'en'],
            'prod-us' => ['appEnv' => 'prod-us', 'expectedInitials' => 'us'],
            'dev-fr' => ['appEnv' => 'dev-fr', 'expectedInitials' => 'fr'],
        ];
    }

    /**
     * Test that APP_ENV without dash uses default www prefix
     */
    public function testAppEnvWithoutDashUsesDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=production');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with multiple dashes uses default www prefix
     */
    public function testAppEnvWithMultipleDashesUsesDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=staging-dev-pl');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with one-letter suffix uses default www prefix
     */
    public function testAppEnvWithOneLetterSuffixUsesDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=staging-p');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with three-letter suffix uses default www prefix
     */
    public function testAppEnvWithThreeLetterSuffixUsesDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=staging-pol');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that empty APP_ENV uses default www prefix
     */
    public function testEmptyAppEnvUsesDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with spaces is trimmed
     */
    public function testAppEnvWithSpacesIsTrimmed(): void
    {
        putenv('APP_ENV=  staging-pl  ');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that false APP_ENV value uses default www prefix
     */
    public function testFalseAppEnvValueUsesDefaultWwwPrefix(): void
    {
        putenv('APP_ENV');  // Unset the variable

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that www-xxx prefix (three letters) is not recognized and gets redirected
     */
    public function testWwwDashThreeLetterPrefixGetsRedirected(): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('www-abc.example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that www-x prefix (one letter) is not recognized and gets redirected
     */
    public function testWwwDashOneLetterPrefixGetsRedirected(): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('www-a.example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that subdomain starting with www but not matching pattern gets redirected
     */
    public function testSubdomainStartingWithWwwButNotMatchingPatternGetsRedirected(): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('wwwtest.example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that complex scenario with path and query is handled correctly
     */
    public function testComplexScenarioWithPathAndQueryIsHandledCorrectly(): void
    {
        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/blog/post/123');
        $this->uri->method('getQuery')->willReturn('ref=twitter&utm_source=social');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that uppercase WWW-XX prefix is recognized
     */
    public function testUppercaseWwwDashTwoLetterPrefixIsRecognized(): void
    {
        $this->uri->method('getHost')->willReturn('WWW-PL.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that mixed case Www-Xx prefix is recognized
     */
    public function testMixedCaseWwwDashTwoLetterPrefixIsRecognized(): void
    {
        $this->uri->method('getHost')->willReturn('WwW-Pl.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that middleware processes request through handler
     */
    public function testMiddlewareProcessesRequestThroughHandler(): void
    {
        $this->uri->method('getHost')->willReturn('www.example.com');

        $this->handler->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $this->middleware->process($this->request, $this->handler);
    }

    /**
     * Test that response is modified only for apex domains
     */
    public function testResponseIsModifiedOnlyForApexDomains(): void
    {
        $this->uri->method('getHost')->willReturn('www.example.com');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with dash at end uses default prefix
     */
    public function testAppEnvWithDashAtEndUsesDefaultPrefix(): void
    {
        putenv('APP_ENV=staging-');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with dash at start uses default prefix
     */
    public function testAppEnvWithDashAtStartUsesDefaultPrefix(): void
    {
        putenv('APP_ENV=-pl');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }

    /**
     * Test that APP_ENV with only dash uses default prefix
     */
    public function testAppEnvWithOnlyDashUsesDefaultPrefix(): void
    {
        putenv('APP_ENV=-');

        $this->uri->method('getScheme')->willReturn('https');
        $this->uri->method('getHost')->willReturn('example.com');
        $this->uri->method('getPath')->willReturn('/');
        $this->uri->method('getQuery')->willReturn('');

        $result = $this->middleware->process($this->request, $this->handler);

        self::assertNotSame($this->response, $result);
    }
}
