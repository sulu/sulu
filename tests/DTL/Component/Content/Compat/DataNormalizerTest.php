<?php

namespace DTL\Component\Content\Compat;

use DTL\Component\Content\Compat\DataNormalizer;
use Sulu\Component\Content\StructureInterface;

class DataNormalizerTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->normalizer = new DataNormalizer();
    }

    public function provideNormalizer()
    {
        return array(
            array(
                array(
                    'title' => 'Title',
                    'url' => '/path/to',
                    'nodeType' => 4,
                    'navContexts' => array('one', 'two'),
                    'nodeState' => 2,
                    'animal' => 'dog',
                    'car' => 'skoda',
                    'duck' => 'quack',
                ),
                StructureInterface::STATE_PUBLISHED,
                1234,
                array(
                    'title' => 'Title',
                    'resourceSegment' => 'to',
                    'redirectType' => 'external',
                    'workflowState' => 'published',
                    'navigationContexts' => array('one', 'two'),
                    'parent' => 1234,
                    'workflowState' => 'published',
                    'content' => array(
                        'animal' => 'dog',
                        'car' => 'skoda',
                        'duck' => 'quack',
                    ),
                    'redirectTarget' => null,
                    'redirectExternal' => null,
                    'shadowLocaleEnabled' => false,
                    'shadowLocale' => null,
                ),
            ),
        );
    }

    /**
     * @dataProvider provideNormalizer
     */
    public function testNormalizer($data, $workflowState, $parentUuid, $expectedNormalizedData)
    {
        $this->assertEquals(
            $expectedNormalizedData,
            $this->normalizer->normalize($data, $workflowState, $parentUuid)
        );
    }
}
