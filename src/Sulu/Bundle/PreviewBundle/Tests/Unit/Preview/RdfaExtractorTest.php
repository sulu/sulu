<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PreviewBundle\Tests\Unit\Preview;

use Sulu\Bundle\PreviewBundle\Preview\RdfaExtractor;

class RdfaExtractorTest extends \PHPUnit_Framework_TestCase
{
    public function providePropertyValueData()
    {
        return [
            ['<h1 property="title">Test</h1>', 'title', [['property' => 'title', 'html' => 'Test']]],
            [
                '<h1 property="title" class="test">Test</h1>',
                'title',
                [['property' => 'title', 'html' => 'Test', 'class' => 'test']],
            ],
            [
                '<h1 property="title">Test</h1><p property="description">Lorem ipsum</p>',
                'title',
                [['property' => 'title', 'html' => 'Test']],
            ],
            [
                '<h1 property="title">Test</h1><p property="description">Lorem ipsum</p>',
                'description',
                [['property' => 'description', 'html' => 'Lorem ipsum']],
            ],
            [
                '<h1 property="title">Test<span property="description">Lorem ipsum</span></h1>',
                'title[description]',
                [['property' => 'description', 'html' => 'Lorem ipsum']],
            ],
            [
                '<h1 property="title">Test<span property="description">Lorem ipsum</span></h1>',
                'description',
                [['property' => 'description', 'html' => 'Lorem ipsum']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test</h1></div></div>',
                'blocks[0][title]',
                [['property' => 'title', 'html' => 'Test']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test</h1><p property="description">Lorem ipsum</p></div></div>',
                'blocks[0][title]',
                [['property' => 'title', 'html' => 'Test']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test</h1><p property="description">Lorem ipsum</p></div></div>',
                'blocks[0][description]',
                [['property' => 'description', 'html' => 'Lorem ipsum']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test<span property="description">Lorem ipsum</span></h1></div></div>',
                'blocks[0][title][description]',
                [['property' => 'description', 'html' => 'Lorem ipsum']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test<span property="description">Lorem ipsum</span></h1></div></div>',
                'blocks[0][description]',
                [['property' => 'description', 'html' => 'Lorem ipsum']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test-1</h1></div><div rel="blocks" typeof="block"><h1 property="title">Test-2</h1></div></div>',
                'blocks[0][title]',
                [['property' => 'title', 'html' => 'Test-1']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test-1</h1></div><div rel="blocks" typeof="block"><h1 property="title">Test-2</h1></div></div>',
                'blocks[1][title]',
                [['property' => 'title', 'html' => 'Test-2']],
            ],
            [
                '<div property="blocks" typeof="collection"><div rel="blocks" typeof="block"><h1 property="title">Test-1</h1></div><div rel="blocks" typeof="block"><h1 property="title">Test-2</h1></div></div>',
                'blocks[2][title]',
                false,
            ],
        ];
    }

    public function providePropertyValuesData()
    {
        return [
            ['<h1 property="title">Test</h1>', [], []],
            ['<h1 property="title">Test</h1>', ['title'], ['title' => [['property' => 'title', 'html' => 'Test']]]],
            [
                '<h1 property="title">Test</h1><p property="description">Lorem ipsum</p>',
                ['description'],
                ['description' => [['property' => 'description', 'html' => 'Lorem ipsum']]],
            ],
            [
                '<h1 property="title">Test</h1><p property="description">Lorem ipsum</p>',
                ['title', 'description'],
                [
                    'title' => [['property' => 'title', 'html' => 'Test']],
                    'description' => [['property' => 'description', 'html' => 'Lorem ipsum']],
                ],
            ],
            [
                '<h1 property="title">Test</h1><p property="description">Lorem ipsum</p>',
                ['title', 'description', 'test'],
                [
                    'title' => [['property' => 'title', 'html' => 'Test']],
                    'description' => [['property' => 'description', 'html' => 'Lorem ipsum']],
                    'test' => false,
                ],
            ],
        ];
    }

    /**
     * @dataProvider providePropertyValueData
     */
    public function testGetPropertyValue($html, $property, $expected)
    {
        $extractor = new RdfaExtractor($html);

        $this->assertEquals($expected, $extractor->getPropertyValue($property));
    }

    /**
     * @dataProvider providePropertyValuesData
     */
    public function testGetPropertyValues($html, $properties, $expected)
    {
        $extractor = new RdfaExtractor($html);

        $this->assertEquals($expected, $extractor->getPropertyValues($properties));
    }
}
