<?php
declare(strict_types=1);

namespace Ctw\Middleware\ApexMiddleware;

class ConfigProvider
{
    #[\NoDiscard('The returned configuration array must be consumed.')]
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    #[\NoDiscard('The returned dependencies array must be consumed.')]
    public function getDependencies(): array
    {
        return [
            'factories' => [
                ApexMiddleware::class => ApexMiddlewareFactory::class,
            ],
        ];
    }
}
