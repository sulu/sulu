<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ContentRepositoryTest extends SuluTestCase
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    public function setUp()
    {
        $this->session = $this->getContainer()->get('doctrine_phpcr.default_session');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->propertyEncoder = $this->getContainer()->get('sulu_document_manager.property_encoder');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');

        $this->contentRepository = new ContentRepository(
            $this->sessionManager,
            $this->propertyEncoder,
            $this->webspaceManager
        );
    }

    public function testFindByParent()
    {
        $this->initPhpcr();

        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid($parentUuid, 'de', 'sulu_io');

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getUuid());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getUuid());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getUuid());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFindByParentMapping()
    {
        $this->initPhpcr();

        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid($parentUuid, 'de', 'sulu_io', ['title']);

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithShadow()
    {
        $this->initPhpcr();

        $this->createShadowPage('test-1', 'de', 'en');
        $this->createPage('test-2', 'en');
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid($parentUuid, 'en', 'sulu_io', ['title']);

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithInternalLink()
    {
        $this->initPhpcr();

        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid($parentUuid, 'de', 'sulu_io', ['title']);

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-1', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithInternalLinkAndShadow()
    {
        $this->initPhpcr();

        $link = $this->createShadowPage('test-1', 'de', 'en');
        $this->createInternalLinkPage('test-2', 'en', $link);
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid($parentUuid, 'en', 'sulu_io', ['title']);

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-1', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFind()
    {
        $this->initPhpcr();

        $page = $this->createPage('test-1', 'de');

        $result = $this->contentRepository->find($page->getUuid(), 'de', 'sulu_io', ['title']);

        $this->assertNotNull($result->getUuid());
        $this->assertEquals($page->getUuid(), $result->getUuid());
        $this->assertEquals('/test-1', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithShadow()
    {
        $this->initPhpcr();

        $page = $this->createShadowPage('test-1', 'de', 'en');

        $result = $this->contentRepository->find($page->getUuid(), 'en', 'sulu_io', ['title']);

        $this->assertNotNull($result->getUuid());
        $this->assertEquals($page->getUuid(), $result->getUuid());
        $this->assertEquals('/1-tset', $result->getPath()); // path will be generated with reversed string
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithInternalLink()
    {
        $this->initPhpcr();

        $link = $this->createPage('test-1', 'de');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find($page->getUuid(), 'de', 'sulu_io', ['title']);

        $this->assertEquals($page->getUuid(), $result->getUuid());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithInternalLinkAndShadow()
    {
        $this->initPhpcr();

        $link = $this->createShadowPage('test-1', 'de', 'en');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find($page->getUuid(), 'de', 'sulu_io', ['title']);

        $this->assertEquals($page->getUuid(), $result->getUuid());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithNonFallbackProperties()
    {
        $this->initPhpcr();

        $link = $this->createPage('test-1', 'de');
        usleep(1000000); // create a difference between link and page (created / changed)
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            [
                'title',
                'created',
                'changed',
            ]
        );

        $this->assertGreaterThan($link->getCreated(), $result['created']);
        $this->assertGreaterThan($link->getChanged(), $result['changed']);

        $this->assertEquals($page->getChanged(), $result['changed']);
        $this->assertEquals($page->getCreated(), $result['created']);

        $this->assertEquals($page->getUuid(), $result->getUuid());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    /**
     * @param string $title
     * @param string $locale
     * @param array $data
     *
     * @return PageDocument
     */
    private function createPage($title, $locale, $data = [])
    {
        $data['title'] = $title;
        $data['url'] = '/' . $title;

        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::NONE);
        $document->setShadowLocaleEnabled(false);
        $document->getStructure()->bind($data);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'path' => $this->sessionManager->getContentPath('sulu_io') . '/' . $title,
                'auto_create' => true,
            ]
        );
        $this->documentManager->flush();

        return $document;
    }

    /**
     * @param string $title
     * @param string $locale
     * @param string $shadowedLocale
     *
     * @return PageDocument
     */
    private function createShadowPage($title, $locale, $shadowedLocale)
    {
        $document1 = $this->createPage($title, $locale);
        $document = $this->documentManager->find(
            $document1->getUuid(),
            $shadowedLocale,
            ['load_ghost_content' => false]
        );

        $document->setShadowLocaleEnabled(true);
        $document->setTitle(strrev($title));
        $document->setShadowLocale($locale);
        $document->setLocale($shadowedLocale);
        $document->setResourceSegment($document1->getResourceSegment());

        $this->documentManager->persist($document, $shadowedLocale);
        $this->documentManager->flush();

        return $document;
    }

    private function createInternalLinkPage($title, $locale, PageDocument $link)
    {
        $data['title'] = $title;
        $data['url'] = '/' . $title;

        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');
        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::INTERNAL);
        $document->setRedirectTarget($link);
        $document->getStructure()->bind($data);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'path' => $this->sessionManager->getContentPath('sulu_io') . '/' . $title,
                'auto_create' => true,
            ]
        );
        $this->documentManager->flush();

        return $document;
    }
}
