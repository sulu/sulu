<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TestBundle\Testing;

use Sulu\Component\HttpKernel\SuluKernel;

class WebsiteTestCase extends SuluTestCase
{
    protected static function getSuluContext(): string
    {
        return SuluKernel::CONTEXT_WEBSITE;
    }
}
