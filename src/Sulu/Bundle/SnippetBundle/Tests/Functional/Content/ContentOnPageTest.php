<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Content;

use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\Structure\PageBridge;
use Sulu\Component\Content\Compat\StructureInterface;
use Sulu\Component\Content\Mapper\ContentMapperInterface;
use Sulu\Component\Content\Mapper\ContentMapperRequest;

class ContentOnPageTest extends BaseFunctionalTestCase
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    public function setUp()
    {
        $this->initPhpcr();
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->loadFixtures();
    }

    public function loadFixtures()
    {
        $req = ContentMapperRequest::create()
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'ElePHPant',
                'description' => 'Elephants are large mammals of the family Elephantidae and the order Proboscidea.',
            ])
            ->setState(StructureInterface::STATE_PUBLISHED);

        $this->snippet1 = $this->contentMapper->saveRequest($req);

        $req = ContentMapperRequest::create()
            ->setType(Structure::TYPE_SNIPPET)
            ->setTemplateKey('hotel')
            ->setLocale('de')
            ->setUserId(1)
            ->setData([
                'title' => 'Penguin',
                'Penguins (order Sphenisciformes, family Spheniscidae) are a group of aquatic, flightless birds living almost exclusively in the Southern Hemisphere, especially in Antarctica.',
            ])
            ->setState(StructureInterface::STATE_PUBLISHED);

        $this->snippet2 = $this->contentMapper->saveRequest($req);
    }

    public function provideSaveSnippetPage()
    {
        return [
            [
                'sulu_io',
                'hotel_page',
                'de',
                [
                    'title' => 'My new snippet page',
                    'url' => '/snippetpage',
                    'hotels' => [],
                ],
            ],
            [
                'sulu_io',
                'hotel_page',
                'de',
                [
                    'title' => 'Another snippet page',
                    'url' => '/anothersnippetpage',
                    'hotels' => ['snippet1'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideSaveSnippetPage
     */
    public function testSaveLoadSnippetPage($webspaceKey, $templateKey, $locale, $data)
    {
        foreach ($data['hotels'] as &$varName) {
            $varName = $this->{$varName}->getUUid();
        }

        $req = ContentMapperRequest::create()
            ->setType(Structure::TYPE_PAGE)
            ->setWebspaceKey($webspaceKey)
            ->setTemplateKey($templateKey)
            ->setLocale($locale)
            ->setState(StructureInterface::STATE_PUBLISHED)
            ->setUserId(1)
            ->setData($data);

        $page = $this->contentMapper->saveRequest($req);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $page->getPropertyValue($key));
        }
        $this->assertInstanceOf(PageBridge::class, $page);
        $this->assertEquals($templateKey, $page->getKey());

        $page = $this->contentMapper->load(
            $page->getUuid(),
            $webspaceKey,
            $locale
        );

        $this->assertInstanceOf(PageBridge::class, $page);
        $this->assertEquals($templateKey, $page->getKey());

        foreach ($data as $key => $value) {
            if ($key === 'hotels') {
                continue;
            }

            $this->assertEquals($value, $page->getPropertyValue($key), 'Checking property "' . $key . '"');
        }

        $hotels = $page->getPropertyValue('hotels');
        $this->assertCount(count($data['hotels']), $hotels);

        $this->assertEquals($data['hotels'], $hotels);
    }
}
