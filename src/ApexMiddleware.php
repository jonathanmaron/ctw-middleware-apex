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
    private const string HEADER = 'Location';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $uri      = $request->getUri();

        $scheme = $uri->getScheme();
        $host   = $uri->getHost();
        $path   = $uri->getPath();
        $query  = $uri->getQuery();

        if (1 === preg_match('#^(www|www-[a-z]{2})\.#', strtolower($host))) {
            return $response;
        }

        // Default prefix "www."
        $prefix = 'www.';

        // Initials prefix "www-<initials>." (e.g. "www-pl.")
        $appEnv = (string) getenv('APP_ENV');
        $appEnv = trim($appEnv);

        $separator = '-';
        if (1 === substr_count($appEnv, $separator)) {
            $parts    = explode($separator, $appEnv);
            $initials = array_pop($parts);
            if (2 === strlen($initials)) {
                $prefix = sprintf('www-%s.', $initials);
            }
        }

        $location = sprintf('%s://%s%s%s', $scheme, $prefix, $host, $path);

        if ('' !== $query) {
            $location .= sprintf('?%s', $query);
        }

        $response = Factory::getResponseFactory()->createResponse(HttpStatus::STATUS_MOVED_PERMANENTLY);

        return $response->withHeader(self::HEADER, $location);
    }
}
