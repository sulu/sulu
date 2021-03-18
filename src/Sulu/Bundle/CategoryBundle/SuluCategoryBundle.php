<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle;

use Sulu\Bundle\CategoryBundle\DependencyInjection\DeprecationCompilerPass;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryTranslationRepositoryInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordInterface;
use Sulu\Bundle\CategoryBundle\Entity\KeywordRepositoryInterface;
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
                CategoryInterface::class => 'sulu.model.category.class',
                CategoryMetaInterface::class => 'sulu.model.category_meta.class',
                CategoryTranslationInterface::class => 'sulu.model.category_translation.class',
                KeywordInterface::class => 'sulu.model.keyword.class',
            ],
            $container
        );

        $container->addAliases(
            [
                CategoryRepositoryInterface::class => 'sulu.repository.category',
                CategoryMetaRepositoryInterface::class => 'sulu.repository.category_meta',
                CategoryTranslationRepositoryInterface::class => 'sulu.repository.category_translation',
                KeywordRepositoryInterface::class => 'sulu.repository.keyword',
            ]
        );

        $container->addCompilerPass(new DeprecationCompilerPass());
    }
}
