<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Component\Content\Structure\Snippet;
use Sulu\Component\Content\StructureInterface;

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
     * {@inheritDoc}
     */
    public function getKernelConfiguration()
    {
        return array('environment' => 'dev');
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
            ->setData(array(
                'title' => 'Le grande budapest',
            ));
        $this->hotel1 = $this->contentMapper->saveRequest($req);

        // HOTELS (including page)
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('en')
            ->setUserId(1)
            ->setUuid($this->hotel1->getUuid())
            ->setData(array(
                'title' => 'Le grande budapest (en)',
            ));
        $this->hotel1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'L\'Hôtel New Hampshire',
            ));
        $this->hotel2 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('page')
            ->setWebspaceKey('sulu_io')
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setTemplateKey('hotel_page')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Hotels page',
                'url' => '/hotels',
                'hotels' => array(
                    $this->hotel1->getUuid(),
                    $this->hotel2->getUuid(),
                ),
            ));

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
            ->setData(array(
                'title' => 'Hotels',
            ));

        $this->contentMapper->saveRequest($req);

        // CARS
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'C car',
            ));
        $this->car1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'A car',
            ));
        $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('car')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'B car',
            ));
        $this->contentMapper->saveRequest($req);
    }
}
