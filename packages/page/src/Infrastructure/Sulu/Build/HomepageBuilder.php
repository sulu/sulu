<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Build;

use Sulu\Bundle\CoreBundle\Build\SuluBuilder;

class HomepageBuilder extends SuluBuilder
{
    public const NAME = 'homepage';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDependencies(): array
    {
        return ['database'];
    }

    public function build()
    {
        $this->execCommand('Create homepage per webspace', 'sulu:page:initialize');
    }
}
