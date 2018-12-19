<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle\Tests\Functional;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class DocumentManagerTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionInterface
     */
    private $defaultSession;

    /**
     * @var SessionInterface
     */
    private $liveSession;

    /**
     * @var HomeDocument
     */
    private $homeDocument;

    public function setUp()
    {
        $this->initPhpcr();

        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->defaultSession = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->liveSession = $this->getContainer()->get('sulu_document_manager.live_session');

        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');
    }

    public function testMoveWithDraftAndPublished()
    {
        $parentPage = $this->createSimplePage('Parent', '/parent');
        $this->documentManager->publish($parentPage, 'en');
        $childPage = $this->createSimplePage('Child', '/child');
        $this->documentManager->publish($childPage, 'en');
        $this->documentManager->flush();

        $childPage->setTitle('Child of Parent');
        $childPage->setResourceSegment('/parent-child');
        $this->documentManager->persist($childPage, 'en');
        $this->documentManager->flush();

        $this->documentManager->move($childPage, $parentPage->getUuid());
        $this->documentManager->flush();

        $childDefaultNode = $this->defaultSession->getNode('/cmf/sulu_io/contents/parent/child-of-parent');
        $this->assertEquals('Child of Parent', $childDefaultNode->getPropertyValue('i18n:en-title'));
        $this->assertEquals('/parent/parent-child', $childDefaultNode->getPropertyValue('i18n:en-url'));
        $this->assertTrue($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/parent/child'));
        $this->assertFalse($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/parent/parent-child'));
        $this->assertFalse($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/parent-child'));

        $childLiveNode = $this->liveSession->getNode('/cmf/sulu_io/contents/parent/child-of-parent');
        $this->assertEquals('Child', $childLiveNode->getPropertyValue('i18n:en-title'));
        $this->assertEquals('/parent/child', $childLiveNode->getPropertyValue('i18n:en-url'));
        $this->assertTrue($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/parent/child'));
        $this->assertFalse($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/parent/parent-child'));
        $this->assertFalse($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/parent-child'));
    }

    public function testMoveWithDraftParent()
    {
        $parentPage = $this->createSimplePage('Parent', '/parent');
        $this->documentManager->publish($parentPage, 'en');
        $childPage = $this->createSimplePage('Child', '/child');
        $this->documentManager->publish($childPage, 'en');
        $this->documentManager->flush();

        $parentPage->setResourceSegment('/new-parent');
        $this->documentManager->persist($parentPage, 'en');
        $this->documentManager->flush();

        $this->documentManager->move($childPage, $parentPage->getUuid());
        $this->documentManager->flush();

        $this->documentManager->publish($childPage, 'en');

        $childDefaultNode = $this->defaultSession->getNode('/cmf/sulu_io/contents/parent/child');
        $this->assertEquals('/parent/child', $childDefaultNode->getPropertyValue('i18n:en-url'));
        $this->assertTrue($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/parent/child'));
        $this->assertFalse($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/new-parent/child'));

        $childLiveNode = $this->liveSession->getNode('/cmf/sulu_io/contents/parent/child');
        $this->assertEquals('/parent/child', $childLiveNode->getPropertyValue('i18n:en-url'));
        $this->assertTrue($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/parent/child'));
        $this->assertFalse($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/new-parent-child'));
    }

    public function testMoveWithOnlyDraft()
    {
        $parentPage = $this->createSimplePage('Parent', '/parent');
        $childPage = $this->createSimplePage('Child', '/child');
        $this->documentManager->flush();

        $this->documentManager->move($childPage, $parentPage->getUuid());
        $this->documentManager->flush();

        $this->assertTrue($this->defaultSession->nodeExists('/cmf/sulu_io/contents/parent/child'));
        $this->assertFalse($this->defaultSession->nodeExists('/cmf/sulu_io/contents/child'));
        $this->assertFalse($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/parent'));
        $this->assertFalse($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/child'));
        $this->assertFalse($this->defaultSession->nodeExists('/cmf/sulu_io/routes/en/parent/child'));

        $this->assertTrue($this->liveSession->nodeExists('/cmf/sulu_io/contents/parent/child'));
        $this->assertFalse($this->liveSession->nodeExists('/cmf/sulu_io/contents/child'));
        $this->assertFalse($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/parent'));
        $this->assertFalse($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/child'));
        $this->assertFalse($this->liveSession->nodeExists('/cmf/sulu_io/routes/en/parent/child'));
    }

    /**
     * Creates a simple page.
     *
     * @param string $title
     * @param string $url
     *
     * @return PageDocument
     */
    private function createSimplePage($title, $url)
    {
        /** @var PageDocument $page */
        $page = $this->documentManager->create('page');
        $page->setTitle($title);
        $page->setResourceSegment($url);
        $page->setLocale('en');
        $page->setParent($this->homeDocument);
        $page->setStructureType('simple');
        $page->setExtensionsData([
            'excerpt' => [
                'title' => '',
                'description' => '',
                'categories' => [],
                'tags' => [],
            ],
        ]);

        $this->documentManager->persist($page, 'en');

        return $page;
    }
}
