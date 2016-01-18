<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Url;

class ReplacerTest extends \PHPUnit_Framework_TestCase
{
    public function testGet()
    {
        $replacer = new Replacer('sulu.io');
        self::assertEquals('sulu.io', $replacer->get());
    }
}
