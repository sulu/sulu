<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Webspace\Tests\Unit\Url;

use Sulu\Component\Webspace\Url\ReplacerFactory;
use Sulu\Component\Webspace\Url\ReplacerInterface;

class ReplacerFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testCreate()
    {
        $factory = new ReplacerFactory();

        self::assertInstanceOf(ReplacerInterface::class, $factory->create('test'));
    }
}
