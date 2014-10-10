<?php

namespace Sulu\Bundle\SnippetBundle\Tests\Functional;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Component\Content\StructureInterface;

abstract class BaseFunctionalTestCase extends SuluTestCase
{
    protected function loadFixtures()
    {
        // HOTELS (including page)
        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'Le grande budapest'
            ));
        $hotel1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType('snippet')
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData(array(
                'title' => 'L\'Hôtel New Hampshire',
            ));
        $hotel2 = $this->contentMapper->saveRequest($req);

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
                    $hotel1->getUuid(),
                    $hotel2->getUuid()
                )
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
        $this->contentMapper->saveRequest($req);

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

