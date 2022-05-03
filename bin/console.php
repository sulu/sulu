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
use Symfony\Bundle\FrameworkBundle\Console\Application;

if (!is_file(dirname(__DIR__).'/vendor/autoload_runtime.php')) {
    throw new LogicException('Symfony Runtime is missing. Try running "composer require symfony/runtime".');
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

if (!isset($suluContext)) {
    $suluContext = Kernel::CONTEXT_ADMIN;
}

return function (array $context) use ($suluContext) {
    $kernel = new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG'], $suluContext);

    return new Application($kernel);
};
