<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit;

class WebspaceTestCase extends \PHPUnit_Framework_TestCase
{
    protected function getResourceDirectory()
    {
        $dir = __DIR__ . '/../../../../../../tests/Resources';

        return $dir;
    }
}
