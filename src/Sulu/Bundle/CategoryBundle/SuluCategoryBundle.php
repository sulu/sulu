<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle;

use Sulu\Bundle\CategoryBundle\DependencyInjection\DeprecationCompilerPass;
use Sulu\Bundle\PersistenceBundle\PersistenceBundleTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Entry point for the SuluCategoryBundle.
 */
class SuluCategoryBundle extends Bundle
{
    use PersistenceBundleTrait;

    public function build(ContainerBuilder $container)
    {
        $this->buildPersistence(
            [
                'Sulu\Bundle\CategoryBundle\Entity\CategoryInterface' => 'sulu.model.category.class',
                'Sulu\Bundle\CategoryBundle\Entity\CategoryMetaInterface' => 'sulu.model.category_meta.class',
                'Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface' => 'sulu.model.category_translation.class',
                'Sulu\Bundle\CategoryBundle\Entity\KeywordInterface' => 'sulu.model.keyword.class',
            ],
            $container
        );

        $container->addCompilerPass(new DeprecationCompilerPass());
    }
}
