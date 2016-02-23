<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\DocumentManagerBundle\Tests\Functional\Bridge;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;

class DocumentManagerRegistryTest extends SuluTestCase
{
    private $registry;

    public function setUp()
    {
        $this->registry = $this->getContainer()->get('sulu_document_manager.registry');
        $this->getContainer()->get('sulu_document_manager.initializer')->initialize(null, true);
    }

    /**
     * It should be able to write to both the default and "live" workspace as configured
     * in the TestBundle.
     */
    public function testWriteToDifferentSessions()
    {
        $defaultManager = $this->registry->getManager('default');
        $liveManager = $this->registry->getManager('live');

        $pageDocument = new PageDocument();
        $pageDocument->setStructureType('default');
        $pageDocument->setTitle('Draft');
        $pageDocument->setResourceSegment('/hai');

        $defaultManager->persist($pageDocument, 'de', [
            'path' => '/cmf/sulu_io/contents/home',
        ]);

        $defaultManager->flush();

        $pageDocument->setResourceSegment('/hoo');
        $liveManager->persist($pageDocument, 'de', [
            'path' => '/cmf/sulu_io/contents/home',
            'auto_create' => true,
        ]);
        $liveManager->flush();

        $defaultManager->persist($pageDocument, 'de', [
            'path' => '/cmf/sulu_io/contents/home',
        ]);

        $defaultManager->flush();

        $pageDocument->setTitle('Live');
        $pageDocument->setResourceSegment('/boo');
        $liveManager->persist($pageDocument, 'de', [
            'path' => '/cmf/sulu_io/contents/home',
        ]);
        $liveManager->flush();
    }
}
