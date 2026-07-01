<?php

declare(strict_types=1);

namespace CtwTest\Middleware\ApexMiddleware;

use Ctw\Http\HttpStatus;
use Ctw\Middleware\ApexMiddleware\ApexMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
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
        $this->request = $this->createStub(ServerRequestInterface::class);
        $this->handler = $this->createStub(RequestHandlerInterface::class);
        $this->response = $this->createStub(ResponseInterface::class);
        $this->uri = $this->createStub(UriInterface::class);

        // Default setup
        $this->request->method('getUri')
            ->willReturn($this->uri);
        $this->handler->method('handle')
            ->willReturn($this->response);
    }

    protected function tearDown(): void
    {
        // Clear environment variable
        putenv('APP_ENV');
        parent::tearDown();
    }

    /**
     * Test that the handler response passes through unchanged when the host already carries the lowercase www prefix.
     */
    public function testRequestWithLowercaseWwwPrefixReturnsHandlerResponseUnchanged(): void
    {
        $result = $this->process('https', 'www.example.com', '/', '');

        self::assertSame($this->response, $result);
    }

    /**
     * Test that the handler response passes through unchanged when the www prefix is uppercase.
     */
    public function testRequestWithUppercaseWwwPrefixReturnsHandlerResponseUnchanged(): void
    {
        $result = $this->process('https', 'WWW.example.com', '/', '');

        self::assertSame($this->response, $result);
    }

    /**
     * Test that the handler response passes through unchanged when the www prefix is mixed case.
     */
    public function testRequestWithMixedCaseWwwPrefixReturnsHandlerResponseUnchanged(): void
    {
        $result = $this->process('https', 'WwW.example.com', '/', '');

        self::assertSame($this->response, $result);
    }

    /**
     * Test that the handler response passes through unchanged when the host carries a www-pl two-letter prefix.
     */
    public function testRequestWithWwwDashTwoLetterPrefixReturnsHandlerResponseUnchanged(): void
    {
        $result = $this->process('https', 'www-pl.example.com', '/', '');

        self::assertSame($this->response, $result);
    }

    /**
     * Test that the handler response passes through unchanged when the host carries a www-en two-letter prefix.
     */
    public function testRequestWithWwwDashEnPrefixReturnsHandlerResponseUnchanged(): void
    {
        $result = $this->process('https', 'www-en.example.com', '/', '');

        self::assertSame($this->response, $result);
    }

    /**
     * Test that the handler response passes through unchanged when an uppercase WWW-PL prefix matches the pattern.
     */
    public function testRequestWithUppercaseWwwDashTwoLetterPrefixReturnsHandlerResponseUnchanged(): void
    {
        $result = $this->process('https', 'WWW-PL.example.com', '/', '');

        self::assertSame($this->response, $result);
    }

    /**
     * Test that the handler response passes through unchanged when a mixed case Www-Pl prefix matches the pattern.
     */
    public function testRequestWithMixedCaseWwwDashTwoLetterPrefixReturnsHandlerResponseUnchanged(): void
    {
        $result = $this->process('https', 'WwW-Pl.example.com', '/', '');

        self::assertSame($this->response, $result);
    }

    /**
     * Test that an apex host is redirected to the www subdomain with an empty path when no path or query is present.
     */
    public function testApexHostRedirectsToWwwSubdomainWithoutTrailingSlashWhenPathIsEmpty(): void
    {
        $result = $this->process('https', 'example.com', '', '');

        self::assertNotSame($this->response, $result);
        self::assertSame('https://www.example.com', $result->getHeaderLine('Location'));
    }

    /**
     * Test that the redirect response carries an HTTP 301 status and www location for every apex-style host.
     */
    #[DataProvider('apexHostProvider')]
    public function testApexHostRedirectResponseHasMovedPermanentlyStatusAndWwwLocation(
        string $host,
        string $expectedLocation
    ): void {
        $result = $this->process('https', $host, '/', '');

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $result->getStatusCode());
        self::assertSame($expectedLocation, $result->getHeaderLine('Location'));
    }

    /**
     * @return array<string, array{host: string, expectedLocation: string}>
     */
    public static function apexHostProvider(): array
    {
        return [
            'simple domain' => [
                'host' => 'example.com',
                'expectedLocation' => 'https://www.example.com/',
            ],
            'subdomain' => [
                'host' => 'api.example.com',
                'expectedLocation' => 'https://www.api.example.com/',
            ],
            'deep subdomain' => [
                'host' => 'api.v1.example.com',
                'expectedLocation' => 'https://www.api.v1.example.com/',
            ],
            'single word' => [
                'host' => 'localhost',
                'expectedLocation' => 'https://www.localhost/',
            ],
            'with numbers' => [
                'host' => 'example123.com',
                'expectedLocation' => 'https://www.example123.com/',
            ],
            'with hyphens' => [
                'host' => 'my-example.com',
                'expectedLocation' => 'https://www.my-example.com/',
            ],
        ];
    }

    /**
     * Test that the redirect location keeps the https scheme when the incoming request is served over https.
     */
    public function testRedirectLocationPreservesHttpsSchemeWhenRequestIsHttps(): void
    {
        $result = $this->process('https', 'example.com', '/test', '');

        self::assertSame('https://www.example.com/test', $result->getHeaderLine('Location'));
    }

    /**
     * Test that the redirect location keeps the http scheme when the incoming request is served over http.
     */
    public function testRedirectLocationPreservesHttpSchemeWhenRequestIsHttp(): void
    {
        $result = $this->process('http', 'example.com', '/test', '');

        self::assertSame('http://www.example.com/test', $result->getHeaderLine('Location'));
    }

    /**
     * Test that the redirect location preserves the request path for a range of path shapes.
     */
    #[DataProvider('pathProvider')]
    public function testRedirectLocationPreservesRequestPath(string $path): void
    {
        $result = $this->process('https', 'example.com', $path, '');

        self::assertSame('https://www.example.com' . $path, $result->getHeaderLine('Location'));
    }

    /**
     * @return array<string, array{path: string}>
     */
    public static function pathProvider(): array
    {
        return [
            'root path' => [
                'path' => '/',
            ],
            'simple path' => [
                'path' => '/about',
            ],
            'nested path' => [
                'path' => '/blog/post/123',
            ],
            'path with extension' => [
                'path' => '/file.html',
            ],
            'path with dots' => [
                'path' => '/path/to/file.json',
            ],
            'path with hyphens' => [
                'path' => '/my-custom-page',
            ],
            'path with underscores' => [
                'path' => '/my_custom_page',
            ],
            'empty path' => [
                'path' => '',
            ],
        ];
    }

    /**
     * Test that the redirect location appends the query string after a question mark for a range of query shapes.
     */
    #[DataProvider('queryStringProvider')]
    public function testRedirectLocationAppendsQueryString(string $query): void
    {
        $result = $this->process('https', 'example.com', '/search', $query);

        self::assertSame('https://www.example.com/search?' . $query, $result->getHeaderLine('Location'));
    }

    /**
     * @return array<string, array{query: string}>
     */
    public static function queryStringProvider(): array
    {
        return [
            'single parameter' => [
                'query' => 'q=test',
            ],
            'multiple parameters' => [
                'query' => 'q=test&page=1',
            ],
            'with special chars' => [
                'query' => 'q=hello+world&sort=desc',
            ],
            'with encoded chars' => [
                'query' => 'q=hello%20world',
            ],
            'with numbers' => [
                'query' => 'id=123&count=456',
            ],
            'with array notation' => [
                'query' => 'items[]=1&items[]=2',
            ],
        ];
    }

    /**
     * Test that the redirect location omits the question mark when the request has no query string.
     */
    public function testRedirectLocationOmitsQuestionMarkWhenQueryStringIsEmpty(): void
    {
        $result = $this->process('https', 'example.com', '/test', '');

        self::assertSame('https://www.example.com/test', $result->getHeaderLine('Location'));
    }

    /**
     * Test that a two-letter APP_ENV suffix produces a www-pl prefixed redirect location.
     */
    public function testAppEnvWithTwoLetterSuffixProducesInitialsPrefixedLocation(): void
    {
        putenv('APP_ENV=staging-pl');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www-pl.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that assorted two-letter APP_ENV suffixes each produce the matching www-initials redirect location.
     */
    #[DataProvider('appEnvTwoLetterProvider')]
    public function testAppEnvWithTwoLetterSuffixProducesMatchingInitialsPrefix(
        string $appEnv,
        string $expectedLocation
    ): void {
        putenv("APP_ENV={$appEnv}");

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame($expectedLocation, $result->getHeaderLine('Location'));
    }

    /**
     * @return array<string, array{appEnv: string, expectedLocation: string}>
     */
    public static function appEnvTwoLetterProvider(): array
    {
        return [
            'staging-pl' => [
                'appEnv' => 'staging-pl',
                'expectedLocation' => 'https://www-pl.example.com/',
            ],
            'staging-en' => [
                'appEnv' => 'staging-en',
                'expectedLocation' => 'https://www-en.example.com/',
            ],
            'prod-us' => [
                'appEnv' => 'prod-us',
                'expectedLocation' => 'https://www-us.example.com/',
            ],
            'dev-fr' => [
                'appEnv' => 'dev-fr',
                'expectedLocation' => 'https://www-fr.example.com/',
            ],
        ];
    }

    /**
     * Test that a two-letter APP_ENV suffix surrounded by whitespace is trimmed before building the initials prefix.
     */
    public function testAppEnvWithSurroundingWhitespaceIsTrimmedBeforeBuildingPrefix(): void
    {
        putenv('APP_ENV=  staging-pl  ');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www-pl.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an APP_ENV value without a dash falls back to the default www prefix.
     */
    public function testAppEnvWithoutDashFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=production');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an APP_ENV value with more than one dash falls back to the default www prefix.
     */
    public function testAppEnvWithMultipleDashesFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=staging-dev-pl');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an APP_ENV value with a one-letter suffix falls back to the default www prefix.
     */
    public function testAppEnvWithOneLetterSuffixFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=staging-p');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an APP_ENV value with a three-letter suffix falls back to the default www prefix.
     */
    public function testAppEnvWithThreeLetterSuffixFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=staging-pol');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an empty APP_ENV value falls back to the default www prefix.
     */
    public function testEmptyAppEnvFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an unset APP_ENV variable falls back to the default www prefix.
     */
    public function testUnsetAppEnvFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV');  // Unset the variable

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an APP_ENV value whose dash is at the end falls back to the default www prefix.
     */
    public function testAppEnvWithTrailingDashFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=staging-');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an APP_ENV value with a leading dash and two-letter suffix still produces the initials prefix.
     */
    public function testAppEnvWithLeadingDashStillProducesInitialsPrefix(): void
    {
        putenv('APP_ENV=-pl');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www-pl.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that an APP_ENV value consisting of only a dash falls back to the default www prefix.
     */
    public function testAppEnvWithOnlyDashFallsBackToDefaultWwwPrefix(): void
    {
        putenv('APP_ENV=-');

        $result = $this->process('https', 'example.com', '/', '');

        self::assertSame('https://www.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that a www-abc three-letter prefix is not recognized and the host is redirected to www.
     */
    public function testHostWithWwwDashThreeLetterPrefixIsRedirectedToWww(): void
    {
        $result = $this->process('https', 'www-abc.example.com', '/', '');

        self::assertSame('https://www.www-abc.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that a www-a one-letter prefix is not recognized and the host is redirected to www.
     */
    public function testHostWithWwwDashOneLetterPrefixIsRedirectedToWww(): void
    {
        $result = $this->process('https', 'www-a.example.com', '/', '');

        self::assertSame('https://www.www-a.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that a host starting with www but lacking the dot delimiter is not recognized and is redirected to www.
     */
    public function testHostStartingWithWwwButLackingDelimiterIsRedirectedToWww(): void
    {
        $result = $this->process('https', 'wwwtest.example.com', '/', '');

        self::assertSame('https://www.wwwtest.example.com/', $result->getHeaderLine('Location'));
    }

    /**
     * Test that the redirect location preserves both the path and the query string when both are present.
     */
    public function testRedirectLocationPreservesBothPathAndQueryString(): void
    {
        $result = $this->process('https', 'example.com', '/blog/post/123', 'ref=twitter&utm_source=social');

        self::assertSame(
            'https://www.example.com/blog/post/123?ref=twitter&utm_source=social',
            $result->getHeaderLine('Location')
        );
    }

    /**
     * Test that the handler is invoked exactly once with the incoming request when the host already has the www prefix.
     */
    public function testHandlerIsInvokedWithRequestWhenHostAlreadyHasWwwPrefix(): void
    {
        $this->uri->method('getHost')
            ->willReturn('www.example.com');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $handler);

        self::assertSame($this->response, $result);
    }

    /**
     * Test that the handler is still invoked with the request even when the apex host triggers a redirect.
     */
    public function testHandlerIsStillInvokedWhenApexHostTriggersRedirect(): void
    {
        $this->uri->method('getScheme')
            ->willReturn('https');
        $this->uri->method('getHost')
            ->willReturn('example.com');
        $this->uri->method('getPath')
            ->willReturn('/');
        $this->uri->method('getQuery')
            ->willReturn('');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())
            ->method('handle')
            ->with($this->request)
            ->willReturn($this->response);

        $result = $this->middleware->process($this->request, $handler);

        self::assertSame(HttpStatus::STATUS_MOVED_PERMANENTLY, $result->getStatusCode());
    }

    private function process(string $scheme, string $host, string $path, string $query): ResponseInterface
    {
        $this->uri->method('getScheme')
            ->willReturn($scheme);
        $this->uri->method('getHost')
            ->willReturn($host);
        $this->uri->method('getPath')
            ->willReturn($path);
        $this->uri->method('getQuery')
            ->willReturn($query);

        return $this->middleware->process($this->request, $this->handler);
    }
}
