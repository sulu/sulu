<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tests\Unit\Search;

use Sulu\Bundle\TagBundle\Search\TagsConverter;
use Sulu\Bundle\TagBundle\Tag\TagManager;

class TagsConverterTest extends \PHPUnit_Framework_TestCase
{
    public function testConvert()
    {
        $manager = $this->prophesize(TagManager::class);
        $manager->resolveTagNames(['Tag1', 'Tag2', 'Tag3'])->willReturn([1, 2, 3]);

        $converter = new TagsConverter($manager->reveal());

        $this->assertEquals([1, 2, 3], $converter->convert(['Tag1', 'Tag2', 'Tag3']));
        $manager->resolveTagNames(['Tag1', 'Tag2', 'Tag3'])->shouldBeCalled();
    }
}
