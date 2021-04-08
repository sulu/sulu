<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use App\Kernel;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\Tools\ResolveTargetEntityListener;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Dotenv\Dotenv;

require \dirname(__DIR__, 2) . '/vendor/autoload.php';
(new Dotenv())->bootEnv(\dirname(__DIR__, 2) . '/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG'], Kernel::CONTEXT_ADMIN);
$kernel->boot();

/** @var ContainerInterface $container */
$container = $kernel->getContainer();

/** @var EntityManager $objectManager */
$objectManager = $container->get('doctrine')->getManager();

// remove ResolveTargetEntityListener from returned EntityManager to not resolve SuluPersistenceBundle classes
// this is a workaround for the following phpstan issue: https://github.com/phpstan/phpstan-doctrine/issues/98
$resolveTargetEntityListener = \current(\array_filter(
    $objectManager->getEventManager()->getListeners('loadClassMetadata'),
    static function($listener) {
        return $listener instanceof ResolveTargetEntityListener;
    }
));
if ($resolveTargetEntityListener) {
    $objectManager->getEventManager()->removeEventListener([Events::loadClassMetadata], $resolveTargetEntityListener);
}

return $objectManager;
