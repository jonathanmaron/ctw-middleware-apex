<?php
declare(strict_types=1);

namespace Ctw\Middleware\ApexMiddleware;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        return [
            'factories' => [
                ApexMiddleware::class => ApexMiddlewareFactory::class,
            ],
        ];
    }
}
