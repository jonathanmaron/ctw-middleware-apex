<?php
declare(strict_types=1);

namespace Ctw\Middleware\ApexMiddleware;

use Psr\Container\ContainerInterface;

class ApexMiddlewareFactory
{
    public function __invoke(ContainerInterface $container): ApexMiddleware
    {
        return new ApexMiddleware();
    }
}
