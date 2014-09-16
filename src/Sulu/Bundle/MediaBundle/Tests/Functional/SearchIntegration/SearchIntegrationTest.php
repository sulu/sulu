<?php

namespace Sulu\Bundle\MediaBundle\Tests\Functional\SearchIntegration;

use Sulu\Bundle\TestBundle\Testing\DatabaseTestCase;
use Sulu\Bundle\MediaBundle\Tests\Fixtures\DefaultStructureCache;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Sulu\Bundle\MediaBundle\Api\Media as ApiMedia;
use Sulu\Bundle\MediaBundle\Entity\Media;

class SearchIntegrationTest extends WebTestCase
{
    protected $container;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->suluContext = 'website';
        static::$kernel->boot();
        $this->container = self::$kernel->getContainer();
    }

    public function provideIndex()
    {
        return array(
            array('170x170', false, null),
            array('invalid', false, '\InvalidArgumentException'),
        );
    }

    /**
     * @dataProvider provideIndex
     */
    public function testIndex($format, $noMedia, $expectedException)
    {
        if ($expectedException) {
            $this->setExpectedException($expectedException);
        }

        $searchManager = $this->container->get('massive_search.search_manager');
        $testAdapter = $this->container->get('massive_search.adapter.test');

        $structure = new DefaultStructureCache();
        $mediaEntity = new Media();
        $tagManager = $this->getMock('Sulu\Bundle\TagBundle\Tag\TagManagerInterface');
        $media = new ApiMedia($mediaEntity, 'de', null, $tagManager);
        $media->setFormats(array(
            $format => 'myimage.jpg'
        ));
        $structure->getProperty('images')->setValue(array($media));
        $searchManager->index($structure);

        $documents = $testAdapter->getDocuments();
        $this->assertCount(1, $documents);
        $document = current($documents);
        $this->assertEquals('myimage.jpg', $document->getImageUrl());
    }

    public function testIndexNoMedia()
    {
        $searchManager = $this->container->get('massive_search.search_manager');
        $structure = new DefaultStructureCache();
        $searchManager->index($structure);
    }
}
