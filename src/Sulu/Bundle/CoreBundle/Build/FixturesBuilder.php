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
 * Builder for loading the fixtures.
 */
class FixturesBuilder extends SuluBuilder
{
    public function getName()
    {
        return 'fixtures';
    }

    public function getDependencies()
    {
        return ['database'];
    }

    public function build()
    {
        $this->execCommand('Loading ORM fixtures', 'doctrine:fixtures:load', ['--no-interaction' => true, '--append' => true]);
    }
}
