<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SearchBundle\Build;

use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

/**
 * Builder for index.
 *
 * @internal no backwards compatibility promise is given for this class it can be removed or changed at any time
 */
class InitBuilder extends SuluBuilder
{
    /**
     * Return the name for this builder.
     */
    public function getName(): string
    {
        return 'search_init';
    }

    /**
     * Return the dependencies for this builder.
     */
    public function getDependencies(): array
    {
        return [];
    }

    /**
     * Execute the build logic.
     */
    public function build(): void
    {
        if ($this->input->getOption('destroy')) {
            $this->execCommand('Purging search indexes', 'massive:search:purge', ['--all' => true, '--force' => true]);
        }
        $this->execCommand('Create search indexes', 'massive:search:init');
    }
}
