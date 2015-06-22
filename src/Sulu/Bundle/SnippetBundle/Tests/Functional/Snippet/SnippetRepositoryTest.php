<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Content;

use Sulu\Bundle\SnippetBundle\Snippet\SnippetRepository;
use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;
use Sulu\Component\Content\Mapper\ContentMapperInterface;

class SnippetRepositoryTest extends BaseFunctionalTestCase
{
    /**
     * @var ContentMapperInterface
     */
    protected $contentMapper;

    /**
     * @var SnippetRepository
     */
    protected $snippetRepository;

    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();

        $this->snippetRepository = $this->getContainer()->get('sulu_snippet.repository');
        $this->phpcrSession = $this->getContainer()->get('doctrine_phpcr')->getConnection();
    }

    public function provideGetSnippets()
    {
        return array(
            array(
                null, null, null,
                5,
            ),
            array(
                'hotel', null, null,
                2,
            ),
            array(
                'car', null, null,
                3,
            ),
            array(
                'car', 1, 2,
                2,
            ),
            array(
                'car', 1, 1,
                1,
            ),
        );
    }

    /**
     * @dataProvider provideGetSnippets
     */
    public function testGetSnippets($type, $offset, $limit, $expectedCount)
    {
        $snippets = $this->snippetRepository->getSnippets('de', $type, $offset, $limit);
        $this->assertCount($expectedCount, $snippets);
        foreach ($snippets as $snippet) {
            $this->assertInstanceOf('Sulu\Component\Content\Structure\Snippet', $snippet);
        }
    }

    public function provideGetSnippetsByUuids()
    {
        return array(
            array(
                array('hotel1', 'hotel2', 'car1'), 'de', 3,
            ),
            // Currently fails because a default template does not exist...
            //array(
            //    array('hotel1', 'hotel2', 'car1'), 'en', 3
            //),
            array(
                array('hotel1', '842e61c0-09ab-42a9-1111-111111111111', 'car1'), 'de', 2,
            ),
            array(
                array(), 'de', 0,
            ),
        );
    }

    /**
     * @dataProvider provideGetSnippetsByUuids
     */
    public function testGetSnippetsByUuids($snippets, $languageCode, $expectedCount)
    {
        $uuids = array();
        foreach ($snippets as $snippetVarName) {
            if (isset($this->{$snippetVarName})) {
                $snippet = $this->{$snippetVarName};
                $uuids[] = $snippet->getUuid();
            } else {
                $uuids[] = $snippetVarName; // test invalid things too
            }
        }

        $snippets = $this->snippetRepository->getSnippetsByUuids($uuids, $languageCode);
        $this->assertNotNull($snippets);
        $this->assertCount($expectedCount, $snippets);
    }

    public function testOrder()
    {
        $snippets = $this->snippetRepository->getSnippets('de', 'car');
        $this->assertNotNull($snippets);
        $this->assertCount(3, $snippets);
        $first = current($snippets);
        $this->assertEquals('A car', $first->getProperty('title')->getValue());
        $second = next($snippets);
        $this->assertEquals('B car', $second->getProperty('title')->getValue());
        $third = next($snippets);
        $this->assertEquals('C car', $third->getProperty('title')->getValue());
    }

    public function testGetReferences()
    {
        $res = $this->snippetRepository->getReferences($this->hotel1->getUuid());
        $this->assertCount(1, $res);
    }
}
