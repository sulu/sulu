<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Build;

use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

/**
 * Builder for creating anonymous roles.
 *
 * @internal no backward compatibility promise is given for this class
 */
final class SecurityBuilder extends SuluBuilder
{
    public function getName()
    {
        return 'security';
    }

    public function getDependencies()
    {
        return ['fixtures', 'database'];
    }

    public function build()
    {
        $this->execCommand(
            'Initialize security: ',
            'sulu:security:init'
        );
    }
}
