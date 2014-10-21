<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\WebsiteBundle\Twig;

use Prophecy\PhpUnit\ProphecyTestCase;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolver;
use Sulu\Bundle\WebsiteBundle\Resolver\StructureResolverInterface;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Property;
use Sulu\Component\Content\Structure;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Types\TextLine;
use Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface;
use Sulu\Component\Webspace\Localization;
use Sulu\Component\Webspace\Webspace;

class TestStructure extends Structure
{
    function __construct($uuid, $title, $userId)
    {
        parent::__construct('test', '', '');

        $this->setUuid($uuid);
        $this->setCreator($userId);
        $this->setChanger($userId);
        $this->setCreated(new \DateTime());
        $this->setChanged(new \DateTime());

        $this->addChild(new Property('title', array(), 'text_line'));
        $this->getProperty('title')->setValue($title);
    }
}

class ContentTwigExtensionTest extends ProphecyTestCase
{
    /**
     * @var StructureResolverInterface
     */
    private $structureResolver;

    /**
     * @var ContentMapperInterface
     */
    private $contentMapper;

    /**
     * @var RequestAnalyzerInterface
     */
    private $requestAnalyzer;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    protected function setUp()
    {
        parent::setUp();

        $this->contentMapper = $this->prophesize('Sulu\Component\Content\Mapper\ContentMapperInterface');
        $this->requestAnalyzer = $this->prophesize('Sulu\Component\Webspace\Analyzer\RequestAnalyzerInterface');
        $this->contentTypeManager = $this->prophesize('Sulu\Component\Content\ContentTypeManagerInterface');
        $this->structureManager = $this->prophesize('Sulu\Component\Content\StructureManagerInterface');

        $webspace= new Webspace();
        $webspace->setKey('sulu_test');

        $locale = new Localization();
        $locale->setCountry('us');
        $locale->setLanguage('en');

        $this
            ->requestAnalyzer
            ->getCurrentWebspace()
            ->willReturn($webspace);

        $this
            ->requestAnalyzer
            ->getCurrentLocalization()
            ->willReturn($locale);

        $this
            ->contentTypeManager
            ->get('text_line')
            ->willReturn(new TextLine(''));


        $this->structureResolver = new StructureResolver(
            $this->contentTypeManager->reveal(),
            $this->structureManager->reveal()
        );
    }

    public function testLoad()
    {
        $this
            ->contentMapper
            ->load('123-123-123', 'sulu_test', 'en_us', true)
            ->willReturn(new TestStructure('123-123-123', 'test', 1));

        $extension = new ContentTwigExtension(
            $this->contentMapper->reveal(),
            $this->structureResolver,
            $this->requestAnalyzer->reveal()
        );

        $result = $extension->load('123-123-123');

        // uuid
        $this->assertEquals('123-123-123', $result['uuid']);

        // metadata
        $this->assertEquals(1, $result['creator']);
        $this->assertEquals(1, $result['changer']);
        $this->assertInstanceOf('\DateTime', $result['created']);
        $this->assertInstanceOf('\DateTime', $result['changed']);

        // content
        $this->assertEquals(array('title' => 'test'), $result['content']);
        $this->assertEquals(array('title' => array()), $result['view']);
    }
}
