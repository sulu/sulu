<?php
/*
 * This file is part of the Sulu CMS.
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
class FixturesBuilder extends SuluBuilder
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'fixtures';
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return array('database', 'phpcr');
    }

    /**
     * {@inheritDoc}
     */
    public function build()
    {
        $this->execCommand('Loading ORM fixtures', 'doctrine:fixtures:load', array('--no-interaction' => true, '--append' => false));
        $this->execCommand('Loading SULU fixtures', 'sulu:fixtures:load', array('--no-interaction' => true));
    }
}
