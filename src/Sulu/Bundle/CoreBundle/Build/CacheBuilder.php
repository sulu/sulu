<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

/**
 * Builder for loading the fictures.
 */
class CacheBuilder extends SuluBuilder
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $options = [
            '--no-optional-warmers' => true,
            '--no-debug' => true,
            '--no-interaction' => true,
        ];

        $this->execCommand('Deleting symfony cache', 'cache:clear', $options);
    }
}
