<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Functional\Repository;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\LocalizationFinderInterface;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Content;
use Sulu\Component\Content\Repository\ContentRepository;
use Sulu\Component\Content\Repository\Mapping\MappingBuilder;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authentication\RoleInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Util\SuluNodeHelper;
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

    /**
     * @var LocalizationFinderInterface
     */
    private $localizationFinder;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    /**
     * @var HomeDocument
     */
    private $homeDocument;

    public function setUp()
    {
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');
        $this->sessionManager = $this->getContainer()->get('sulu.phpcr.session');
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->propertyEncoder = $this->getContainer()->get('sulu_document_manager.property_encoder');
        $this->webspaceManager = $this->getContainer()->get('sulu_core.webspace.webspace_manager');
        $this->localizationFinder = $this->getContainer()->get('sulu.content.localization_finder');
        $this->structureManager = $this->getContainer()->get('sulu.content.structure_manager');
        $this->nodeHelper = $this->getContainer()->get('sulu.util.node_helper');

        $this->contentRepository = new ContentRepository(
            $this->sessionManager,
            $this->propertyEncoder,
            $this->webspaceManager,
            $this->localizationFinder,
            $this->structureManager,
            $this->nodeHelper
        );

        $this->initPhpcr();

        $this->homeDocument = $this->documentManager->find($this->sessionManager->getContentPath('sulu_io'), 'de');
    }

    public function testFindByParent()
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFindByParentMapping()
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithShadow()
    {
        $this->createShadowPage('test-1', 'de', 'en');
        $this->createPage('test-2', 'en');
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithShadowNoHydrate()
    {
        $this->createShadowPage('test-1', 'en_us', 'en');
        $this->createPage('test-2', 'en');
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'en',
            'sulu_io',
            MappingBuilder::create()->setHydrateShadow(false)->addProperties(['title'])->getMapping()
        );

        $this->assertCount(2, $result);

        $this->assertEquals('test-2', $result[0]['title']);
        $this->assertEquals('test-3', $result[1]['title']);
    }

    public function testFindByParentWithGhost()
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('de', $result[0]->getLocale());
        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('ghost', $result[0]->getLocalizationType()->getName());
        $this->assertEquals('en', $result[0]->getLocalizationType()->getValue());
        $this->assertEquals('de', $result[1]->getLocale());
        $this->assertNull($result[1]->getLocalizationType());
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('de', $result[2]->getLocale());
        $this->assertNull($result[2]->getLocalizationType());
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithGhostNoHydrate()
    {
        $this->createPage('test-1', 'en');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->setHydrateGhost(false)->addProperties(['title'])->getMapping()
        );

        $this->assertCount(2, $result);

        $this->assertEquals('test-2', $result[0]['title']);
        $this->assertEquals('test-3', $result[1]['title']);
    }

    public function testFindByParentWithInternalLink()
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-1', $result[1]['title']);
        $this->assertEquals(RedirectType::INTERNAL, $result[1]->getNodeType());
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithDraftInternalLink()
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link, false);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title', 'published'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-1', $result[1]['title']);
        $this->assertEquals(RedirectType::INTERNAL, $result[1]->getNodeType());
        $this->assertEmpty($result[1]['published']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithInternalLinkNotFollow()
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'de',
            'sulu_io',
            MappingBuilder::create()->setFollowInternalLink(false)->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentWithInternalLinkAndShadow()
    {
        $link = $this->createShadowPage('test-1', 'de', 'en');
        $this->createInternalLinkPage('test-2', 'en', $link);
        $this->createPage('test-3', 'en');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid(
            $parentUuid,
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-1', $result[1]['title']);
        $this->assertEquals(RedirectType::INTERNAL, $result[1]->getNodeType());
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByParentOneLayer()
    {
        $page1 = $this->createPage('test-1', 'de');
        $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('test-1-2', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $this->createPage('test-2-1', 'de', [], $page2);
        $this->createPage('test-2-2', 'de', [], $page2);
        $this->createPage('test-3', 'de');

        $parentUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();

        $result = $this->contentRepository->findByParentUuid($parentUuid, 'de', 'sulu_io',
            MappingBuilder::create()->getMapping());

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFindByWebspaceRoot()
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot('de', 'sulu_io',
            MappingBuilder::create()->getMapping());

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFindByWebspaceRootMapping()
    {
        $this->createPage('test-1', 'de');
        $this->createPage('test-2', 'de');
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootWithShadow()
    {
        $this->createShadowPage('test-1', 'de', 'en');
        $this->createPage('test-2', 'en');
        $this->createPage('test-3', 'en');

        $result = $this->contentRepository->findByWebspaceRoot(
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-2', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootWithInternalLink()
    {
        $link = $this->createPage('test-1', 'de');
        $this->createInternalLinkPage('test-2', 'de', $link);
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-1', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootWithInternalLinkAndShadow()
    {
        $link = $this->createShadowPage('test-1', 'de', 'en');
        $this->createInternalLinkPage('test-2', 'en', $link);
        $this->createPage('test-3', 'en');

        $result = $this->contentRepository->findByWebspaceRoot(
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $this->assertEquals('test-1', $result[0]['title']);
        $this->assertEquals('test-1', $result[1]['title']);
        $this->assertEquals('test-3', $result[2]['title']);
    }

    public function testFindByWebspaceRootOneLayer()
    {
        $page1 = $this->createPage('test-1', 'de');
        $this->createPage('test-1-1', 'de', [], $page1);
        $this->createPage('test-1-2', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $this->createPage('test-2-1', 'de', [], $page2);
        $this->createPage('test-2-2', 'de', [], $page2);
        $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByWebspaceRoot('de', 'sulu_io', MappingBuilder::create()->getMapping());

        $this->assertCount(3, $result);

        $this->assertNotNull($result[0]->getId());
        $this->assertEquals('/test-1', $result[0]->getPath());
        $this->assertNotNull($result[1]->getId());
        $this->assertEquals('/test-2', $result[1]->getPath());
        $this->assertNotNull($result[2]->getId());
        $this->assertEquals('/test-3', $result[2]->getPath());
    }

    public function testFind()
    {
        $page = $this->createPage('test-1', 'de');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertNotNull($result->getId());
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-1', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithGhost()
    {
        $page = $this->createPage('test-1', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'en_us',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertNotNull($result->getId());
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-1', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithShadow()
    {
        $page = $this->createShadowPage('test-1', 'de', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'en',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertNotNull($result->getId());
        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/1-tset', $result->getPath()); // path will be generated with reversed string
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithInternalLink()
    {
        $link = $this->createPage('test-1', 'de');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithEmptyInternalLink()
    {
        $link = $this->createPage('test-1', 'de');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $node = $this->session->getNodeByIdentifier($page->getUuid());
        $node->getProperty('i18n:de-internal_link')->remove();
        $this->session->save();

        // should load content with requested node and not try to follow internal link

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-2', $result['title']);
    }

    public function testFindWithInternalLinkToItself()
    {
        $link = $this->createPage('test-1', 'de');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $node = $this->session->getNodeByIdentifier($page->getUuid());
        $node->setProperty('i18n:de-internal_link', $node);
        $this->session->save();

        // should load content with requested node and not try to follow internal link

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-2', $result['title']);
    }

    public function testFindWithInternalLinkAndShadow()
    {
        $link = $this->createShadowPage('test-1', 'de', 'en');
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindWithNonFallbackProperties()
    {
        $link = $this->createPage('test-1', 'de');
        sleep(1); // create a difference between link and page (created / changed)
        $page = $this->createInternalLinkPage('test-2', 'de', $link);

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(
                [
                    'title',
                    'created',
                    'changed',
                ]
            )->getMapping()
        );

        $created = $result['created'];
        $changed = $result['changed'];

        // Jackalope Jackrabbit will return a \DateTime and DBAL will return a
        // string. See: https://github.com/jackalope/jackalope-doctrine-dbal/issues/325
        if (is_string($created)) {
            $created = new \DateTime($result['created']);
        }

        if (is_string($changed)) {
            $changed = new \DateTime($result['changed']);
        }

        $this->assertGreaterThan($link->getCreated(), $created);
        $this->assertGreaterThan($link->getChanged(), $changed);

        // Reload the document.
        //
        // Jackalope Doctrine DBAL will currently "normalize" to UTC when
        // persisting and use the system TZ when loading, however there is/was
        // an actual bug whereby the state of the objects DateTime object was
        // changed such that it was incorrect in the loaded entity after
        // persisting.
        //
        // Reloading the document is a workaround to ensure we have the correct
        // value.
        //
        // PR: https://github.com/jackalope/jackalope-doctrine-dbal/pull/326 has
        // already been merged for this, and we can remove this after upgrading
        // to the next jackalope DBAL release.
        $page = $this->documentManager->find($page->getUuid(), 'de');

        $this->assertEquals($page->getCreated()->format('c'), $created->format('c'));
        $this->assertEquals($page->getChanged()->format('c'), $changed->format('c'));

        $this->assertEquals($page->getUuid(), $result->getId());
        $this->assertEquals('/test-2', $result->getPath());
        $this->assertEquals('test-1', $result['title']);
    }

    public function testFindPermissions()
    {
        $role1 = $this->prophesize(RoleInterface::class);
        $role1->getId()->willReturn(1);
        $role1->getIdentifier()->willReturn('ROLE_SULU_ROLE 1');
        $role2 = $this->prophesize(RoleInterface::class);
        $role2->getId()->willReturn(2);
        $role2->getIdentifier()->willReturn('ROLE_SULU_ROLE-2');

        $user = $this->prophesize(UserInterface::class);
        $user->getRoleObjects()->willReturn([$role1->reveal(), $role2->reveal()]);

        $page = $this->createPage(
            'test-1',
            'de',
            [],
            null,
            [
                1 => ['edit' => true],
                2 => ['view' => true, 'archive' => true],
                3 => ['add' => true],
            ]
        );

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->getMapping(),
            $user->reveal()
        );

        $this->assertEquals(
            [1 => ['edit' => true], 2 => ['view' => true, 'archive' => true]],
            $result->getPermissions()
        );
    }

    public function provideWebspaceKeys()
    {
        return [['sulu_io'], ['test_io']];
    }

    /**
     * @dataProvider provideWebspaceKeys
     */
    public function testFindParentsWithSiblingsByUuid($webspaceKey)
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de', [], $page1);
        $page4 = $this->createPage('test-4', 'de', [], $page1);
        $page5 = $this->createPage('test-5', 'de', [], $page2);
        $page6 = $this->createPage('test-6', 'de', [], $page2);
        $page7 = $this->createPage('test-7', 'de', [], $page3);
        $page8 = $this->createPage('test-8', 'de', [], $page4);
        $page9 = $this->createPage('test-9', 'de', [], $page6);
        $page10 = $this->createPage('test-10', 'de', [], $page6);
        $page11 = $this->createPage('test-11', 'de', [], $page10);
        $page12 = $this->createPage('test-12', 'de', [], $page10);
        $page13 = $this->createPage('test-13', 'de', [], $page12);

        $result = $this->contentRepository->findParentsWithSiblingsByUuid(
            $page10->getUuid(),
            'de',
            $webspaceKey,
            MappingBuilder::create()->getMapping()
        );

        $layer = $result;
        $this->assertCount(2, $layer);
        $this->assertEquals($page1->getUuid(), $layer[0]->getId());
        $this->assertTrue($layer[0]->hasChildren());
        $this->assertCount(0, $layer[0]->getChildren());
        $this->assertEquals($page2->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertCount(2, $layer[1]->getChildren());

        $layer = $layer[1]->getChildren();
        $this->assertCount(2, $layer);
        $this->assertEquals($page5->getUuid(), $layer[0]->getId());
        $this->assertFalse($layer[0]->hasChildren());
        $this->assertCount(0, $layer[0]->getChildren());
        $this->assertEquals($page6->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertCount(2, $layer[1]->getChildren());

        $layer = $layer[1]->getChildren();
        $this->assertCount(2, $layer);
        $this->assertEquals($page9->getUuid(), $layer[0]->getId());
        $this->assertFalse($layer[0]->hasChildren());
        $this->assertCount(0, $layer[0]->getChildren());
        $this->assertEquals($page10->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertCount(2, $layer[1]->getChildren());

        $layer = $layer[1]->getChildren();
        $this->assertCount(2, $layer);
        $this->assertEquals($page11->getUuid(), $layer[0]->getId());
        $this->assertFalse($layer[0]->hasChildren());
        $this->assertCount(0, $layer[0]->getChildren());
        $this->assertEquals($page12->getUuid(), $layer[1]->getId());
        $this->assertTrue($layer[1]->hasChildren());
        $this->assertCount(0, $layer[1]->getChildren());
    }

    public function testFindByPaths()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page11 = $this->createPage('test-1/test-1', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByPaths(
            ['/cmf/sulu_io/contents', '/cmf/sulu_io/contents/test-1', '/cmf/sulu_io/contents/test-2'],
            'de',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(3, $result);

        $items = array_map(
            function (Content $content) {
                return [
                    'uuid' => $content->getId(),
                    'hasChildren' => $content->hasChildren(),
                    'children' => $content->getChildren(),
                ];
            },
            $result
        );

        $homepageUuid = $this->sessionManager->getContentNode('sulu_io')->getIdentifier();
        $this->assertContains(['uuid' => $homepageUuid, 'hasChildren' => true, 'children' => []], $items);
        $this->assertContains(['uuid' => $page1->getUuid(), 'hasChildren' => true, 'children' => []], $items);
        $this->assertContains(['uuid' => $page2->getUuid(), 'hasChildren' => false, 'children' => []], $items);
    }

    public function testFindByUuids()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page11 = $this->createPage('test-1/test-1', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findByUuids(
            [$page1->getUuid(), $page2->getUuid()],
            'de',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(2, $result);

        $items = array_map(
            function (Content $content) {
                return [
                    'uuid' => $content->getId(),
                    'hasChildren' => $content->hasChildren(),
                    'children' => $content->getChildren(),
                ];
            },
            $result
        );

        $this->assertContains(['uuid' => $page1->getUuid(), 'hasChildren' => true, 'children' => []], $items);
        $this->assertContains(['uuid' => $page2->getUuid(), 'hasChildren' => false, 'children' => []], $items);
    }

    public function testFindAll()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page11 = $this->createPage('test-1-1', 'de', [], $page1);
        $page2 = $this->createPage('test-2', 'de');
        $page3 = $this->createPage('test-3', 'de');

        $result = $this->contentRepository->findAll(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $paths = array_map(
            function (Content $content) {
                return $content->getPath();
            },
            $result
        );

        $this->assertContains('/', $paths);
        $this->assertContains('/test-1', $paths);
        $this->assertContains('/test-1/test-1-1', $paths);
        $this->assertContains('/test-2', $paths);
        $this->assertContains('/test-3', $paths);
    }

    public function testFindAllNoPage()
    {
        $result = $this->contentRepository->findAll(
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['title'])->getMapping()
        );

        $this->assertCount(1, $result);

        $paths = array_map(
            function (Content $content) {
                return $content->getPath();
            },
            $result
        );

        $this->assertContains('/', $paths);
    }

    public function testFindAllByPortal()
    {
        $this->createPage('test-1', 'de_at');

        $result = $this->contentRepository->findAllByPortal(
            'de_at',
            'sulucmf_at',
            MappingBuilder::create()->setResolveUrl(true)->getMapping()
        );

        usort($result, function ($content1, $content2) {
            return strcmp($content1->getPath(), $content2->getPath());
        });

        $this->assertCount(2, $result);
        $urls = $result[1]->getUrls();
        $this->assertEquals('/test-1', $urls['de_at']);
        $this->assertNull($urls['de']);
        $this->assertNull($urls['en']);
        $this->assertNull($urls['en_us']);

        $urls = $result[0]->getUrls();
        $this->assertEquals('/', $urls['de_at']);
        $this->assertEquals('/', $urls['de']);
        $this->assertEquals('/', $urls['en']);
        $this->assertEquals('/', $urls['en_us']);
    }

    public function testFindUrl()
    {
        $page1 = $this->createPage('test-1', 'de');

        $result = $this->contentRepository->find(
            $page1->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->setResolveUrl(true)->getMapping()
        );

        $this->assertEquals('/test-1', $result->getUrl());
        $this->assertEquals(['en' => null, 'en_us' => null, 'de' => '/test-1', 'de_at' => null], $result->getUrls());
    }

    public function testFindUrls()
    {
        $page1 = $this->createShadowPage('test-1', 'de', 'en');

        $result = $this->contentRepository->find(
            $page1->getUuid(),
            'de_at',
            'sulu_io',
            MappingBuilder::create()->setResolveUrl(true)->getMapping()
        );

        $this->assertEquals(['en' => '/test-1', 'en_us' => null, 'de' => '/test-1', 'de_at' => null], $result->getUrls());
    }

    public function testFindByWebspaceRootPublished()
    {
        $page1 = $this->createPage('test-1', 'de');
        $page2 = $this->createPage('test-2', 'de');
        $page2->setWorkflowStage(WorkflowStage::TEST);
        $this->documentManager->persist($page2,
            'de',
            [
                'path' => $this->sessionManager->getContentPath('sulu_io') . '/test-2',
                'auto_create' => true,
            ]
        );
        $this->documentManager->flush();

        $result = $this->contentRepository->findByWebspaceRoot(
            'de',
            'sulu_io',
            MappingBuilder::create()->setOnlyPublished(true)->getMapping()
        );

        $this->assertCount(1, $result);
        $this->assertEquals('/test-1', $result[0]->getPath());
    }

    public function testFindConcreteLanguages()
    {
        $page = $this->createShadowPage('test', 'de', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->setResolveConcreteLocales(true)->getMapping()
        );

        $this->assertEquals(['de'], $result->getConcreteLanguages());
    }

    public function testFindNonExistingProperty()
    {
        $page = $this->createShadowPage('test', 'de', 'en');

        $result = $this->contentRepository->find(
            $page->getUuid(),
            'de',
            'sulu_io',
            MappingBuilder::create()->addProperties(['testProperty'])->getMapping()
        );

        $this->assertEquals('', $result['testProperty']);
    }

    /**
     * @param string $title
     * @param string $locale
     * @param array $data
     * @param PageDocument $parent
     * @param array $permissions
     *
     * @return PageDocument
     */
    private function createPage($title, $locale, $data = [], $parentDocument = null, array $permissions = [])
    {
        /** @var PageDocument $document */
        $document = $this->documentManager->create('page');

        if (!$parentDocument) {
            $parentDocument = $this->homeDocument;
        }

        $document->setParent($parentDocument);

        $data['title'] = $title;
        $data['url'] = '/' . $title;

        $document->setStructureType('simple');
        $document->setTitle($title);
        $document->setResourceSegment($data['url']);
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setLocale($locale);
        $document->setRedirectType(RedirectType::NONE);
        $document->setShadowLocaleEnabled(false);
        $document->getStructure()->bind($data);
        $document->setPermissions($permissions);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'auto_create' => true,
            ]
        );
        $this->documentManager->publish($document, $locale);
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

        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setShadowLocaleEnabled(true);
        $document->setTitle(strrev($title));
        $document->setShadowLocale($locale);
        $document->setLocale($shadowedLocale);
        $document->setResourceSegment($document1->getResourceSegment());

        $this->documentManager->persist($document, $shadowedLocale);
        $this->documentManager->publish($document, $shadowedLocale);
        $this->documentManager->flush();

        return $document;
    }

    private function createInternalLinkPage($title, $locale, PageDocument $link, $publish = true)
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
        $document->setWorkflowStage(WorkflowStage::PUBLISHED);
        $document->setRedirectTarget($link);
        $document->getStructure()->bind($data);
        $document->setParent($this->homeDocument);
        $this->documentManager->persist(
            $document,
            $locale,
            [
                'auto_create' => true,
            ]
        );

        if ($publish) {
            $this->documentManager->publish($document, $locale);
        }

        $this->documentManager->flush();

        return $document;
    }
}
