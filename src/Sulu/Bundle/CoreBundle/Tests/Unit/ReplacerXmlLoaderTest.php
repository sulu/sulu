<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CoreBundle\Tests\Unit;

use Sulu\Bundle\CoreBundle\DataFixtures\ReplacerXmlLoader;
use Symfony\Component\Config\FileLocatorInterface;

class ReplacerXmlLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $filename = 'replacers.xml';

        $locator = $this->prophesize(FileLocatorInterface::class);
        $locator->locate($filename)->willReturn(__DIR__ . '/' . $filename);

        $loader = new ReplacerXmlLoader($locator->reveal());
        $result = $loader->load($filename);

        $this->assertEquals(
            [
                'de' => [
                    'ü' => 'ue',
                    '&' => 'und',
                ],
                'en' => [
                    '&' => 'and',
                ],
                'default' => [
                    '.' => '-',
                    '+' => '-',
                    ' ' => '-',
                ],
            ],
            $result
        );
    }
}
