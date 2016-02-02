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

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Compat\Structure\Snippet;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

abstract class BaseFunctionalTestCase extends SuluTestCase
{
    /**
     * @var Snippet
     */
    protected $hotel1;

    /**
     * @var Snippet
     */
    protected $hotel2;

    /**
     * @var Snippet
     */
    protected $car1;

    /**
     * {@inheritdoc}
     */
    public function getKernelConfiguration()
    {
        return ['environment' => 'dev'];
    }

    /**
     * Load fixtures for snippet functional tests.
     */
    protected function loadFixtures()
    {
        // HOTELS (including page)
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'Le grande budapest',
            ]);
        $this->hotel1 = $this->contentMapper->saveRequest($req);

        // HOTELS (including page)
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('en')
            ->setUserId(1)
            ->setUuid($this->hotel1->getUuid())
            ->setData([
                'title' => 'Le grande budapest (en)',
            ]);
        $this->hotel1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'L\'Hôtel New Hampshire',
            ]);
        $this->hotel2 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('page')
            ->setWebspaceKey('sulu_io')
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setTemplateKey('hotel_page')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'Hotels page',
                'url' => '/hotels',
                'hotels' => [
                    $this->hotel1->getUuid(),
                    $this->hotel2->getUuid(),
                ],
            ]);

        $hotels = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('page')
            ->setWebspaceKey('sulu_io')
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setTemplateKey('hotel_page')
            ->setLocale('en')
            ->setUserId(1)
            ->setUuid($hotels->getUuid())
            ->setIsShadow(true)
            ->setShadowBaseLanguage('de')
            ->setData([
                'title' => 'Hotels',
            ]);

        $this->contentMapper->saveRequest($req);

        // CARS
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'C car',
            ]);
        $this->car1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'A car',
            ]);
        $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'B car',
            ]);
        $this->contentMapper->saveRequest($req);
    }
}
