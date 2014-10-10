<?php

namespace Sulu\Bundle\SnippetBundle\Tests\Functional\Content;

use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Sulu\Component\Content\Mapper\ContentMapperRequest;
use Sulu\Bundle\SnippetBundle\Content\SnippetContent;
use Sulu\Component\Content\StructureInterface;
use Sulu\Bundle\SnippetBundle\Tests\Functional\BaseFunctionalTestCase;

class SnippetRepositoryTest extends BaseFunctionalTestCase
{
    public function setUp()
    {
        $this->contentMapper = $this->getContainer()->get('sulu.content.mapper');
        $this->initPhpcr();
        $this->loadFixtures();

        $this->snippetRepository = $this->getContainer()->get('sulu_snippet.repository');
    }

    public function provideGetSnippets()
    {
        return array(
            array(
                null, null, null,
                5
            ),
            array(
                'hotel', null, null,
                2
            ),
            array(
                'car', null, null,
                3
            ),
            array(
                'car', 1, 2,
                2
            ),
            array(
                'car', 1, 1,
                1
            ),
        );
    }

    /**
     * @dataProvider provideGetSnippets
     */
    public function testGetSnippets($type, $offset, $limit, $expectedCount)
    {
        $snippets = $this->snippetRepository->getSnippets('de', 'sulu_io', $type, $offset, $limit);
        $this->assertCount($expectedCount, $snippets);
        foreach ($snippets as $snippet) {
            $this->assertInstanceOf('Sulu\Component\Content\Structure\Snippet', $snippet);
        }
    }

    public function testOrder()
    {
        $snippets = $this->snippetRepository->getSnippets('de', 'sulu_io', 'car');
        $this->assertNotNull($snippets);
        $this->assertCount(3, $snippets);
        $first = current($snippets);
        $this->assertEquals('A car', $first->getProperty('title')->getValue());
        $second = next($snippets);
        $this->assertEquals('B car', $second->getProperty('title')->getValue());
        $third = next($snippets);
        $this->assertEquals('C car', $third->getProperty('title')->getValue());
    }
}
