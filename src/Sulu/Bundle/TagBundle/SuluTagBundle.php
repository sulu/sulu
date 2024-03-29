<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle;

use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Sulu\Bundle\TagBundle\Tag\TagInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry-point of tag-bundle.
 *
 * @final
 */
class SuluTagBundle extends Bundle
{
    use PersistenceBundleTrait;

    /**
     * @internal
     */
    public function build(ContainerBuilder $container): void
    {
        $this->buildPersistence(
            [
                TagInterface::class => 'sulu.model.tag.class',
            ],
            $container
        );
    }
}
