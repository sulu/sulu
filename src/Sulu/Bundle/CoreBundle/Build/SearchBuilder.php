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

/**
 * Builder for initializing the search indexes.
 */
class SearchBuilder extends SuluBuilder
{
    public function getName()
    {
        return 'search';
    }

    public function getDependencies()
    {
        return [];
    }

    public function build()
    {
        if ($this->input->getOption('destroy')) {
            $this->execCommand('Dropping the search indexes', 'cmsig:seal:index-drop', ['--force' => true]);
        }

        $this->execCommand('Creating the search indexes', 'cmsig:seal:index-create');
    }
}
