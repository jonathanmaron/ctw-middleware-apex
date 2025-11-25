# Package "ctw/ctw-middleware-apex"

[![Latest Stable Version](https://poser.pugx.org/ctw/ctw-middleware-apex/v/stable)](https://packagist.org/packages/ctw/ctw-middleware-apex)
[![GitHub Actions](https://github.com/jonathanmaron/ctw-middleware-apex/actions/workflows/tests.yml/badge.svg)](https://github.com/jonathanmaron/ctw-middleware-apex/actions/workflows/tests.yml)
[![Scrutinizer Build](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-apex/badges/build.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-apex/build-status/master)
[![Scrutinizer Quality](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-apex/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-apex/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-apex/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/jonathanmaron/ctw-middleware-apex/?branch=master)

PSR-15 middleware that redirects apex (bare) domains to www-prefixed domains using HTTP 301 permanent redirects.

## Introduction

### Why This Library Exists

Many web applications need to canonicalize their URLs by redirecting apex domains (e.g., `example.com`) to their www-prefixed equivalents (e.g., `www.example.com`). This is important for:

- **SEO consistency**: Search engines treat `example.com` and `www.example.com` as different sites, potentially diluting page rank
- **Cookie scope**: Cookies set on `www.example.com` are more restrictive than those on the apex domain
- **CDN and DNS flexibility**: The www subdomain allows CNAME records, while apex domains typically require A records
- **Load balancing**: Subdomains provide more flexibility for DNS-based traffic distribution

This middleware handles the redirect automatically at the application layer, ensuring all requests arrive at the canonical www-prefixed domain.

### Problems This Library Solves

1. **Duplicate content issues**: Without canonicalization, search engines index the same content under multiple URLs
2. **Session inconsistencies**: Cookies may not be shared between apex and www domains
3. **Certificate complexity**: Some CDNs and hosting providers handle www subdomains more gracefully
4. **Manual redirect configuration**: Eliminates the need to configure redirects at the web server level
5. **Environment-aware prefixes**: Supports developer-specific prefixes (e.g., `www-pl.example.com`) via `APP_ENV`

### Where to Use This Library

- **Mezzio applications**: Add to your middleware pipeline early in the request lifecycle
- **PSR-15 compatible frameworks**: Any framework supporting PSR-15 middleware
- **Multi-environment deployments**: Use `APP_ENV` for developer-specific prefixes (e.g., `development-pl` creates `www-pl.`)
- **Production web applications**: Ensure consistent canonical URLs across all requests

### Design Goals

1. **Permanent redirects**: Uses HTTP 301 status code for proper SEO handling
2. **Query string preservation**: Maintains all query parameters during redirect
3. **Environment awareness**: Supports custom prefixes via `APP_ENV` environment variable
4. **Transparent operation**: Only redirects when necessary, passes through already-prefixed requests
5. **Zero configuration**: Works out of the box with sensible defaults

## Requirements

- PHP 8.3 or higher
- ctw/ctw-middleware ^4.0
- ctw/ctw-http ^4.0

## Installation

Install by adding the package as a [Composer](https://getcomposer.org) requirement:

```bash
composer require ctw/ctw-middleware-apex
```

## Usage Examples

### Basic Pipeline Registration (Mezzio)

```php
use Ctw\Middleware\ApexMiddleware\ApexMiddleware;

// In config/pipeline.php or similar
$app->pipe(ApexMiddleware::class);
```

### Redirect Behavior

| Request URL | Redirect URL | Status |
|-------------|--------------|--------|
| `http://example.com/` | `http://www.example.com/` | 301 |
| `http://example.com/page?id=1` | `http://www.example.com/page?id=1` | 301 |
| `https://example.com/path` | `https://www.example.com/path` | 301 |
| `http://www.example.com/` | (no redirect) | - |
| `http://www-pl.example.com/` | (no redirect) | - |

### Environment-Aware Prefixes

When `APP_ENV` contains a two-letter suffix separated by a hyphen (e.g., `development-pl`), the middleware uses that as a developer-specific prefix:

```bash
# Environment variable
APP_ENV=development-pl
```

| Request URL | Redirect URL |
|-------------|--------------|
| `http://example.com/` | `http://www-pl.example.com/` |

This enables multiple developers to work on the same domain with isolated environments.

### ConfigProvider Registration

The package includes a `ConfigProvider` for automatic factory registration:

```php
// config/config.php
return [
    // ...
    \Ctw\Middleware\ApexMiddleware\ConfigProvider::class,
];
```
