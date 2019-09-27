<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use App\Kernel;
use Sulu\Component\HttpKernel\SuluKernel;
use Symfony\Component\Debug\Debug;
use Symfony\Component\HttpFoundation\Request;

defined('SULU_MAINTENANCE') || define('SULU_MAINTENANCE', getenv('SULU_MAINTENANCE') ?: false);

// maintenance mode
if (SULU_MAINTENANCE) {
    $maintenanceFilePath = __DIR__ . '/maintenance.php';
    // show maintenance mode and exit if no allowed IP is met
    if (require $maintenanceFilePath) {
        exit();
    }
}

require dirname(__DIR__) . '/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? $_ENV['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_ALL ^ Request::HEADER_X_FORWARDED_HOST);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? $_ENV['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$suluContext = SuluKernel::CONTEXT_WEBSITE;

if (preg_match('/^\/admin(\/|$)/', $_SERVER['REQUEST_URI'])) {
    $suluContext = SuluKernel::CONTEXT_ADMIN;
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG'], $suluContext);

// Comment this line if you want to use the "varnish" http
// caching strategy. See http://sulu.readthedocs.org/en/latest/cookbook/caching-with-varnish.html
if ('dev' !== $_SERVER['APP_ENV'] && SuluKernel::CONTEXT_WEBSITE === $suluContext) {
    $kernel = $kernel->getHttpCache();
}

// When using the HttpCache, you need to call the method in your front controller
// instead of relying on the configuration parameter
// https://symfony.com/doc/3.4/reference/configuration/framework.html#http-method-override
Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
