<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional;

use Sulu\Bundle\ContentBundle\Document\PageDocument;
use Sulu\Bundle\SnippetBundle\Document\SnippetDocument;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure\Snippet;

abstract class BaseFunctionalTestCase extends SuluTestCase
{
    /**
     * @var SnippetDocument
     */
    protected $hotel1;

    /**
     * @var SnippetDocument
     */
    protected $hotel2;

    /**
     * @var SnippetDocument
     */
    protected $car1;

    /**
     * @var DocumentManager
     */
    private $manager;

    /**
     * {@inheritdoc}
     */
    public function getKernelConfiguration()
    {
        return ['environment' => 'dev'];
    }

    private function createSnippet($type, array $localizedData)
    {
        $snippet = new SnippetDocument();
        $snippet->setStructureType($type);

        foreach ($localizedData as $locale => $data) {
            $snippet->setTitle($data['title']);
            $this->manager->persist($snippet, $locale);
        }

        return $snippet;
    }

    /**
     * Load fixtures for snippet functional tests.
     */
    protected function loadFixtures()
    {
        $this->manager = $this->getContainer()->get('sulu_document_manager.document_manager');

        $this->hotel1 = $this->createSnippet(
            'hotel',
            [
                'en' => [
                    'title' => 'Le grande budapest (en)',
                ],
                'de' => [
                    'title' => 'Le grande budapest',
                ],
            ]
        );

        $this->hotel2 = $this->createSnippet(
            'hotel',
            [
                'de' => [
                    'title' => 'L\'Hôtel New Hampshire',
                ],
            ]
        );

        $page = new PageDocument();
        $page->setTitle('Hotels Page');
        $page->setStructureType('hotel_page');
        $page->setResourceSegment('/hotels');
        $page->getStructure()->bind([
            'hotels' => [
                $this->hotel1->getUuid(),
                $this->hotel2->getUuid(),
            ],
        ]);

        $this->manager->persist($page, 'de', [
            'path' => '/cmf/sulu_io/contents/hotels',
        ]);

        $page->setTitle('Hotels');
        $page->setShadowLocaleEnabled(true);
        $page->setShadowLocale('de');
        $page->getStructure()->bind([
            'hotels' => [],
        ]);

        $this->manager->persist($page, 'en', [
            'path' => '/cmf/sulu_io/contents/hotels',
        ]);

        $this->car1 = $this->createSnippet('car', ['de' => ['title' => 'C car']]);
        $this->createSnippet('car', ['de' => ['title' => 'B car']]);
        $this->createSnippet('car', ['de' => ['title' => 'A car']]);

        $this->manager->flush();
    }
}
