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

namespace Sulu\Page\Infrastructure\Symfony\DependencyInjection\Compiler;

use Sulu\Page\Domain\Model\Page;
use Sulu\Page\Infrastructure\Doctrine\Tree\SuluPageAwareTreeListener;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class OverrideTreeListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('stof_doctrine_extensions.listener.tree')) {
            return;
        }

        /** @var class-string $pageClass */
        $pageClass = $container->getParameter('sulu.model.page.class');

        if (Page::class === $pageClass) {
            return;
        }

        $container->getDefinition('stof_doctrine_extensions.listener.tree')
            ->setClass(SuluPageAwareTreeListener::class)
            ->addMethodCall('setConcretePageClass', [$pageClass]);
    }
}
