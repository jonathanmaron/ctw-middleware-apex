<?php
declare(strict_types=1);

namespace Ctw\Middleware\ApexMiddleware;

use Ctw\Http\HttpStatus;
use Middlewares\Utils\Factory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApexMiddleware extends AbstractApexMiddleware
{
    private const PREFIX = 'www.';

    private const HEADER = 'Location';

    public function process(
        ServerRequestInterface  $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {

        $response = $handler->handle($request);

        $scheme   = $request->getUri()->getScheme();
        $host     = $request->getUri()->getHost();
        $path     = $request->getUri()->getPath();
        $query    = $request->getUri()->getQuery();

        if (str_starts_with(strtolower($host), self::PREFIX)) {
            return $response;
        }

        $location = sprintf('%s://%s%s%s', $scheme, self::PREFIX, $host, $path);

        if (strlen($query) > 0) {
            $location .= sprintf('?%s', $query);
        }

        $response = Factory::getResponseFactory()->createResponse(HttpStatus::STATUS_MOVED_PERMANENTLY);

        return $response->withHeader(self::HEADER, $location);
    }
}
