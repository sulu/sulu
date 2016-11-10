<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Tests\Functional\Document\Subscriber;

use PHPCR\SessionInterface;
use Sulu\Bundle\ContentBundle\Document\HomeDocument;
use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

class ShadowCopyPropertiesSubscriberTest extends SuluTestCase
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var HomeDocument
     */
    private $homeDocument;

    public function setUp()
    {
        $this->initPhpcr();
        $this->documentManager = $this->getContainer()->get('sulu_document_manager.document_manager');
        $this->session = $this->getContainer()->get('sulu_document_manager.default_session');

        $this->homeDocument = $this->documentManager->find('/cmf/sulu_io/contents', 'en');

        $englishDocument = $this->documentManager->create('page');
        $englishDocument->setStructureType('default');
        $englishDocument->setParent($this->homeDocument);
        $englishDocument->setTitle('English page');
        $englishDocument->setResourceSegment('/english-page');
        $this->documentManager->persist($englishDocument, 'en');

        $this->documentManager->flush();

        $germanDocument = $this->documentManager->find($englishDocument->getUuid(), 'de');
        $germanDocument->setStructureType('default');
        $germanDocument->setParent($this->homeDocument);
        $germanDocument->setTitle('Deutsche Seite');
        $germanDocument->setResourceSegment('/deutsche-seite');
        $this->documentManager->persist($germanDocument, 'de');

        $this->documentManager->flush();

        $this->documentManager->clear();
        $this->session->refresh(false);
    }

    public function testCopyShadowPropertiesToShadow()
    {
        /** @var PageDocument $germanDocument */
        $germanDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'de');
        $germanDocument->setShadowLocale('en');
        $germanDocument->setShadowLocaleEnabled(true);

        $this->documentManager->persist($germanDocument, 'de');
        $this->documentManager->flush();

        /** @var PageDocument $englishDocument */
        $englishDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'en');
        $englishDocument->setExtensionsData([
            'excerpt' => [
                'tags' => ['tag1', 'tag2'],
            ],
        ]);
        $englishDocument->setNavigationContexts(['main']);

        $this->documentManager->persist($englishDocument, 'en');

        $node = $this->session->getNode('/cmf/sulu_io/contents/english-page');

        $this->assertCount(2, $node->getPropertyValue('i18n:en-excerpt-tags'));
        $this->assertCount(2, $node->getPropertyValue('i18n:de-excerpt-tags'));
        $this->assertEquals(['main'], $node->getPropertyValue('i18n:en-navContexts'));
        $this->assertEquals(['main'], $node->getPropertyValue('i18n:de-navContexts'));
    }

    public function testCopyShadowPropertiesFromShadow()
    {
        /** @var PageDocument $englishDocument */
        $englishDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'en');
        $englishDocument->setExtensionsData([
            'excerpt' => [
                'tags' => ['tag1', 'tag2'],
            ],
        ]);
        $englishDocument->setNavigationContexts(['main']);

        $this->documentManager->persist($englishDocument, 'en');
        $this->documentManager->flush();

        $this->documentManager->clear();
        $this->session->refresh(false);

        /** @var PageDocument $germanDocument */
        $germanDocument = $this->documentManager->find('/cmf/sulu_io/contents/english-page', 'de');
        $germanDocument->setShadowLocale('en');
        $germanDocument->setShadowLocaleEnabled(true);

        $this->documentManager->persist($germanDocument, 'de');

        $node = $this->session->getNode('/cmf/sulu_io/contents/english-page');

        $this->assertCount(2, $node->getPropertyValue('i18n:en-excerpt-tags'));
        $this->assertCount(2, $node->getPropertyValue('i18n:de-excerpt-tags'));
        $this->assertEquals(['main'], $node->getPropertyValue('i18n:en-navContexts'));
        $this->assertEquals(['main'], $node->getPropertyValue('i18n:de-navContexts'));
    }
}
