<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Types\ResourceLocator\Strategy;

/**
 * Uses Intl component to transliterate generated titles.
 */
class TreeTransliteratorGeneratorDecorator implements ResourceLocatorGeneratorInterface
{
    /** @var ResourceLocatorGeneratorInterface */
    private $originalGenerator;

    /** @var \Transliterator */
    private $transliterator;

    public function __construct(ResourceLocatorGeneratorInterface $originalGenerator, \Transliterator $transliterator)
    {
        $this->originalGenerator = $originalGenerator;
        $this->transliterator = $transliterator;
    }

    /**
     * {@inheritdoc}
     */
    public function generate($title, $parentPath = null)
    {
        $generated = $this->originalGenerator->generate($title, $parentPath);

        return $this->transliterator->transliterate($generated);
    }
}
