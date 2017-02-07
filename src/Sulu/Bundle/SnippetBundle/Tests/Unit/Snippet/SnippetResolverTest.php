<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Snippet;

use Prophecy\Argument;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\Compat\Structure\SnippetBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

class SnippetResolverTest extends \PHPUnit_Framework_TestCase
{
    public function dataProvider()
    {
        return [
            [[]],
            [['123-123-123']],
            [['123-123-123', '123-123-123']],
            [['123-123-123', '312-312-312']],
            [[], 'test_io'],
            [['123-123-123'], 'test_io'],
            [['123-123-123', '123-123-123'], 'test_io'],
            [['123-123-123', '312-312-312'], 'test_io'],
            [[], 'test_io', 'en'],
            [['123-123-123'], 'test_io', 'en'],
            [['123-123-123', '123-123-123'], 'test_io', 'en'],
            [['123-123-123', '312-312-312'], 'test_io', 'en'],
        ];
    }

    /**
     * @dataProvider dataProvider
     */
    public function testResolve($uuids, $webspaceKey = 'sulu_io', $locale = 'de')
    {
        $contentMapper = $this->prophesize(ContentMapperInterface::class);
        $structureResolver = $this->prophesize(StructureResolverInterface::class);

        $structures = [];
        foreach (array_unique($uuids) as $uuid) {
            $structure = $this->prophesize(SnippetBridge::class);
            $structure->getUuid()->willReturn($uuid);
            $structure->getKey()->willReturn('test');
            $structure->getHasTranslation()->willReturn(true);
            $structure->setIsShadow(false)->shouldBeCalled();
            $structure->setShadowBaseLanguage(null)->shouldBeCalled();

            $structures[$uuid] = $structure->reveal();
        }

        $resolver = new SnippetResolver($contentMapper->reveal(), $structureResolver->reveal());

        $contentMapper->load(
            Argument::that(
                function ($item) use ($uuids) {
                    return in_array($item, $uuids);
                }
            ),
            $webspaceKey,
            $locale
        )->shouldBeCalledTimes(count(array_unique($uuids)))->will(
            function ($arguments) use ($structures) {
                return $structures[$arguments[0]];
            }
        );

        $structureResolver->resolve(
            Argument::that(
                function (StructureInterface $structure) use ($uuids) {
                    return in_array($structure->getUuid(), $uuids);
                }
            )
        )->shouldBeCalledTimes(count(array_unique($uuids)))->willReturn(
            ['content' => ['title' => 'test'], 'view' => ['title' => []]]
        );

        $result = $resolver->resolve($uuids, $webspaceKey, $locale);

        foreach ($result as $item) {
            $this->assertContains($item['view']['uuid'], $uuids);
            $this->assertEquals(
                [
                    'content' => ['title' => 'test'],
                    'view' => ['title' => [], 'template' => 'test', 'uuid' => $item['view']['uuid']],
                ],
                $item
            );
        }
    }

    public function testResolveWithShadowLocale()
    {
        $contentMapper = $this->prophesize(ContentMapperInterface::class);
        $structureResolver = $this->prophesize(StructureResolverInterface::class);

        $structure1 = $this->prophesize(SnippetBridge::class);
        $structure1->getUuid()->willReturn('123-123-123');
        $structure1->getKey()->willReturn('test');
        $structure1->getHasTranslation()->willReturn(false);

        $structure2 = $this->prophesize(SnippetBridge::class);
        $structure2->getUuid()->willReturn('123-123-123');
        $structure2->getKey()->willReturn('test');
        $structure2->getHasTranslation()->willReturn(false);
        $structure2->setIsShadow(true)->shouldBeCalled();
        $structure2->setShadowBaseLanguage('en')->shouldBeCalled();

        $resolver = new SnippetResolver($contentMapper->reveal(), $structureResolver->reveal());

        $contentMapper->load('123-123-123', 'sulu_io', 'de')->shouldBeCalledTimes(1)->willReturn($structure1->reveal());
        $contentMapper->load('123-123-123', 'sulu_io', 'en')->shouldBeCalledTimes(1)->willReturn($structure2->reveal());

        $structureResolver->resolve($structure2->reveal())
            ->willReturn(['content' => ['title' => 'test'], 'view' => ['title' => []]]);

        $resolver->resolve(['123-123-123'], 'sulu_io', 'de', 'en');
    }
}
