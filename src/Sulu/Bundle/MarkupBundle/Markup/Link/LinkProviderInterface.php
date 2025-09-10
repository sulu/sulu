<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\MarkupBundle\Markup\Link;

interface LinkProviderInterface
{
    public function getConfigurationBuilder(): LinkConfigurationBuilder;

    /**
     * @param string[] $hrefs
     *
     * @return LinkItem[]
     */
    public function preload(array $hrefs, string $locale, bool $published = true): iterable;
}
