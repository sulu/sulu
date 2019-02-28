<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Build;

use Symfony\Component\HttpKernel\Kernel;

/**
 * @deprecated The cache clear builder will be remove with sulu 2.0 use the symfony commands instead.
 *
 * Builder for clearing the cache.
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
        @trigger_error(
            'CacheBuilder is deprecated since version 1.6.10 and will be removed in 2.0 use the default symfony command "cache:clear" instead.',
            E_USER_DEPRECATED
        );

        if (version_compare(Kernel::VERSION, '3.4.0') >= 0) {
            $this->output->writeln(
                '<comment>Skip clearing the cache.' . PHP_EOL
                . 'This is not longer supported with Symfony >=3.4 use "cache:clear" command instead.</comment>'
                . PHP_EOL
            );

            return;
        }

        $options = [
            '--no-optional-warmers' => true,
            '--no-debug' => true,
            '--no-interaction' => true,
        ];

        $this->execCommand('Deleting symfony cache', 'cache:clear', $options);
    }
}
