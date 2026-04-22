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

namespace Sulu\Page\Infrastructure\Doctrine\Tree;

use Doctrine\Persistence\ObjectManager;
use Gedmo\Tree\Strategy;
use Gedmo\Tree\TreeListener;
use Sulu\Page\Domain\Model\Page as SuluPage;

/**
 * Works around Gedmo's ExtensionMetadataFactory returning an empty configuration for
 * mapped-superclasses (`if ($meta->isMappedSuperclass) return [];`). When a project uses
 * a concrete subclass of Sulu's `Page` mapped-superclass, we redirect lookups for the
 * parent class to the concrete class so the tree strategy can resolve its configuration.
 *
 * Upstream issue (closed without fix since 2016): https://github.com/doctrine-extensions/DoctrineExtensions/issues/1696
 *
 * @internal
 */
final class SuluPageAwareTreeListener extends TreeListener
{
    /**
     * @var class-string
     */
    private string $concretePageClass = SuluPage::class;

    /**
     * Reentry guard: Gedmo walks parent classes during metadata loading, which would
     * otherwise cause `SuluPage -> concretePageClass -> SuluPage` redirect recursion.
     */
    private bool $redirecting = false;

    /**
     * @param class-string $class
     */
    public function setConcretePageClass(string $class): void
    {
        $this->concretePageClass = $class;
    }

    public function getConfiguration(ObjectManager $objectManager, $class): array
    {
        if (SuluPage::class === $class && !$this->redirecting) {
            $this->redirecting = true;
            try {
                return parent::getConfiguration($objectManager, $this->concretePageClass);
            } finally {
                $this->redirecting = false;
            }
        }

        return parent::getConfiguration($objectManager, $class);
    }

    public function getStrategy(ObjectManager $objectManager, $class): Strategy
    {
        if (SuluPage::class === $class && !$this->redirecting) {
            $this->redirecting = true;
            try {
                return parent::getStrategy($objectManager, $this->concretePageClass);
            } finally {
                $this->redirecting = false;
            }
        }

        return parent::getStrategy($objectManager, $class);
    }
}
