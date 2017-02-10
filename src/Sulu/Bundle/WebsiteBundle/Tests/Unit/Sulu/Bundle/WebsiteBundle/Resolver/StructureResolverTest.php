<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Resolver;

use Prophecy\Argument;
use Sulu\Component\Content\ContentTypeInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Document\Behavior\LocalizedAuthorBehavior;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

class StructureResolverTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var ContentTypeInterface
     */
    private $contentType;

    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    public function setUp()
    {
        parent::setUp();

        $this->contentTypeManager = $this->prophesize(ContentTypeManagerInterface::class);
        $this->extensionManager = $this->prophesize(ExtensionManagerInterface::class);
        $this->contentType = $this->prophesize(ContentTypeInterface::class);

        $this->structureResolver = new StructureResolver(
            $this->contentTypeManager->reveal(),
            $this->extensionManager->reveal()
        );
    }

    public function testResolve()
    {
        $this->contentTypeManager->get('content_type')->willReturn($this->contentType);

        $this->contentType->getViewData(Argument::any())->willReturn('view');
        $this->contentType->getContentData(Argument::any())->willReturn('content');

        $excerptExtension = $this->prophesize('Sulu\Component\Content\Extension\ExtensionInterface');
        $excerptExtension->getContentData(['test1' => 'test1'])->willReturn(['test1' => 'test1']);
        $this->extensionManager->getExtension('test', 'excerpt')->willReturn($excerptExtension);

        $property = $this->prophesize('Sulu\Component\Content\Compat\PropertyInterface');
        $property->getName()->willReturn('property');
        $property->getContentTypeName()->willReturn('content_type');

        $structure = $this->prophesize('Sulu\Component\Content\Compat\Structure\PageBridge');
        $structure->getKey()->willReturn('test');
        $structure->getExt()->willReturn(new ExtensionContainer(['excerpt' => ['test1' => 'test1']]));
        $structure->getUuid()->willReturn('some-uuid');
        $structure->getProperties(true)->willReturn([$property->reveal()]);
        $structure->getCreator()->willReturn(1);
        $structure->getChanger()->willReturn(1);
        $structure->getCreated()->willReturn('date');
        $structure->getChanged()->willReturn('date');
        $structure->getPublished()->willReturn('date');
        $structure->getPath()->willReturn('test-path');
        $structure->getUrls()->willReturn(['en' => '/description', 'de' => '/beschreibung', 'es' => null]);
        $structure->getShadowBaseLanguage()->willReturn('en');

        $authored = new \DateTime();

        $document = $this->prophesize()->willImplement(LocalizedAuthorBehavior::class);
        $structure->getDocument()->willReturn($document->reveal());
        $document->getAuthored()->willReturn($authored);
        $document->getAuthor()->willReturn(1);

        $expected = [
            'extension' => [
                'excerpt' => ['test1' => 'test1'],
            ],
            'uuid' => 'some-uuid',
            'view' => [
                'property' => 'view',
            ],
            'content' => [
                'property' => 'content',
            ],
            'creator' => 1,
            'changer' => 1,
            'created' => 'date',
            'changed' => 'date',
            'published' => 'date',
            'template' => 'test',
            'urls' => ['en' => '/description', 'de' => '/beschreibung', 'es' => null],
            'path' => 'test-path',
            'shadowBaseLocale' => 'en',
            'authored' => $authored,
            'author' => 1,
        ];

        $this->assertEquals($expected, $this->structureResolver->resolve($structure->reveal()));
    }
}
