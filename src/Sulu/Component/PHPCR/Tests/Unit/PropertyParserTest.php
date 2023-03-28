<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\PHPCR\Tests\Unit;

use Generator;
use PHPUnit\Framework\TestCase;
use Sulu\Component\PHPCR\PropertyParser\PropertyParser;

final class PropertyParserTest extends TestCase
{
    private PropertyParser $propertyParser;

    public function setUp(): void
    {
        $this->propertyParser = new PropertyParser();
    }

    public function testParsingStructure(): void
    {
        $data = [
            'i18n:de_de-title' => 'Rock Band',
            'i18n:de_de-url' => '/rock-band',
            'i18n:de_de-show_page_title' => false,
            'i18n:de_de-template' => 'blog_post',
            'i18n:de_de-state' => 2,
        ];

        $result = $this->propertyParser->parse($data);
        self::assertArrayHasKey('i18n:de_de', $result);
        $mainNode = $result['i18n:de_de'];

        self::assertSame($mainNode['title']->getValue(), 'Rock Band');
        self::assertSame($mainNode['title']->getPath(), 'i18n:de_de-title');

        self::assertSame($mainNode['url']->getValue(), '/rock-band');
        self::assertSame($mainNode['url']->getPath(), 'i18n:de_de-url');

        self::assertSame($mainNode['show_page_title']->getValue(), false);
        self::assertSame($mainNode['show_page_title']->getPath(), 'i18n:de_de-show_page_title');

        self::assertSame($mainNode['template']->getValue(), 'blog_post');
        self::assertSame($mainNode['state']->getValue(), 2);
    }

    public function testParsingArray(): void
    {
        $data = [
            'i18n:de_de-length' => 1,
            'i18n:de_de-type#0' => 'overview',
            'i18n:de_de-settings#0' => '[]',
            'i18n:de_de-bands_per_page#0' => 30,
            'i18n:de_de-sorting#0' => 'none',
            'i18n:de_de-sort_order#0' => 'asc',
        ];

        $result = $this->propertyParser->parse($data);
        $mainNode = $result['i18n:de_de'];
        self::arrayHasKey(0, $mainNode);

        self::assertSame($mainNode['length']->getValue(), 1);
        self::assertSame($mainNode[0]['type']->getValue(), 'overview');
        self::assertSame($mainNode[0]['settings']->getValue(), '[]');
        self::assertSame($mainNode[0]['bands_per_page']->getValue(), 30);
        self::assertSame($mainNode[0]['sorting']->getValue(), 'none');
        self::assertSame($mainNode[0]['sort_order']->getValue(), 'asc');
    }

    public function testParsingSomething(): void
    {
        $data = [
            'i18n:de_de-content#0' => 'a3fb197d-8555-4d21-b42e-29ec377e3083',
            'i18n:de_de-content#0-generic_content_snippet#0' => '0f97ea0c-4d8e-4412-9c18-33e53e0f8af6',
        ];
        $result = $this->propertyParser->parse($data);

        $mainKey = $result['i18n:de_de'];
        self::assertArrayHasKey(0, $mainKey);
        self::assertCount(1, $mainKey);
        self::assertSame($mainKey[0]['content'][0]['generic_content_snippet']->getValue(), '0f97ea0c-4d8e-4412-9c18-33e53e0f8af6');
    }

    /**
     * @param array<string, string> $data
     *
     * @dataProvider dataFindingShadowKeys
     */
    public function testFindingShadowKeys(array $data): void
    {
        $this->propertyParser->parse($data);

        $shadowedKeys = $this->propertyParser->shadowedKeys;
        self::assertCount(1, $shadowedKeys);

        self::assertSame('i18n:de_de-content#0', $shadowedKeys[0]->getPath());
        self::assertSame('a3fb197d-8555-4d21-b42e-29ec377e3083', $shadowedKeys[0]->getValue());
    }

    /** @return Generator<string,array{array<string,string>}> */
    public function dataFindingShadowKeys(): \Generator
    {
        yield 'Shadow key is first' => [[
            'i18n:de_de-content#0' => 'a3fb197d-8555-4d21-b42e-29ec377e3083',
            'i18n:de_de-content#0-generic_content_snippet#0' => '0f97ea0c-4d8e-4412-9c18-33e53e0f8af6',
        ]];

        yield 'Shadow key comes after content' => [[
            'i18n:de_de-content#0-generic_content_snippet#0' => '0f97ea0c-4d8e-4412-9c18-33e53e0f8af6',
            'i18n:de_de-content#0' => 'a3fb197d-8555-4d21-b42e-29ec377e3083',
        ]];
    }

    /**
     * @param array<string, string> $data
     *
     * @dataProvider dataAllKeysAreHandled
     */
    public function testAllKeysAreHandled(array $data): void
    {
        $result = $this->propertyParser->parse($data);
        $extractCode = fn ($data) => $data->getPath();

        $iterableKeys = \iterator_to_array($this->propertyParser->keyIterator($result), false);
        \sort($iterableKeys);

        $expectedKeys = \array_keys($data);
        \sort($expectedKeys);

        $actualKeys = \array_merge($iterableKeys, \array_map($extractCode, $this->propertyParser->shadowedKeys));
        \sort($actualKeys);

        self::assertEquals($expectedKeys, $actualKeys);
    }

    /**
     * @return Generator<string,array{array<string,mixed>}|array{array<string,string>}>
     */
    public function dataAllKeysAreHandled(): \Generator
    {
        yield 'Normal data' => [[
            'i18n:en_us-title' => 'Bands',
            'i18n:en_us-url' => '/bands',
            'i18n:en_us-show_page_title' => false,
            'i18n:en_us-template' => 'blog_post',
            'i18n:en_us-state' => 2,
        ]];

        yield 'Shadowed properties' => [[
            'i18n:de_de-content-length' => 1,
            'i18n:de_de-content#0' => 'a3fb197d-8555-4d21-b42e-29ec377e3083',
            'i18n:de_de-content#0-generic_content_snippet#0' => '0f97ea0c-4d8e-4412-9c18-33e53e0f8af6',
        ]];

        yield 'Listing page example' => [[
            'element-length' => 1,
            'element-type#0' => 'generic_content_snippet',
            'element-simple_text#0' => '<p style="text-align:center;">Hallo</p>',
            'element-color_simple_text#0' => 'black',
            'element-font_family#0' => 'default',
            'element-type#1' => 'media_type_selector',
            'element-media_type_selector#1-length' => 1,
            'element-media_type_selector#1-type#0' => 'viewport_based_media',
            'element-media_type_selector#1-small_image#0' => '{"id":1123,"displayOption":"left"}',
            'element-media_type_selector#1-medium_image#0' => '{"id":1123,"displayOption":"left"}',
            'element-media_type_selector#1-large_image#0' => '{id":1123,"displayOption":"left"}',
            'element-media_type_selector#1-unscaled_image#0' => false,
            'element-media_type_selector#1-link_type_selector_media#0-length' => 1,
            'element-media_type_selector#1-link_type_selector_media#0-type#0' => 'link_type_none',
            'element-media_type_selector#1-link_type_selector_media#0-link_type_none#0' => false,
            'element-type#2' => 'simple_text',
            'element-simple_text#2' => '<p style="text-align:center;"><sulu-link href="60e5a79f-13dd-4d38-ac31-e3d8b6437f2e" target="_self" provider="page">Jetzt entdecken</sulu-link></p>',
            'element-color_simple_text#2' => 'black',
            'element-font_family#2' => 'default',
            'element-font_size#2' => 'default',
            'element-image_only#0' => 'no',
            'element-media_type_selector#0-length' => 1,
            'element-media_type_selector#0-type#0' => 'viewport_based_media',
            'element-media_type_selector#0-unscaled_image#0' => true,
            'element-media_type_selector#0-link_type_selector_media#0-length' => 1,
            'element-media_type_selector#0-link_type_selector_media#0-type#0' => 'link_type_intern',
            'element-media_type_selector#0-link_type_selector_media#0-link_type_none#0' => false,
            'element-headline#0' => 'Some headline',
            'element-type_headline#0' => 'h3',
            'element-style_headline#0' => 'default',
            'element-text_align_headline#0' => 'center',
            'element-color_headline#0' => 'black',
            'element-hide_for_mobile#0' => false,
            'element-content_position#0' => 'top',
            'element-width_size_large_content#0' => '50%',
            'element-width_size_medium_content#0' => '50%',
            'element-width_size_small_content#0' => '100%',
            'element-show_button#0' => 'yes',
            'element-link_type_selector_media#0-length' => 1,
            'element-link_type_selector_media#0-type#0' => 'link_type_intern',
            'element-link_type_selector_media#0-link_type_none#0' => false,
            'element-link_type_none#0' => false,
            'element-link_target#0' => '_self',
            'element-media_type_selector#0-meta_title#0' => 'Deine Vorteile',
            'element-media_type_selector#0-link_type_selector_media#0-link_intern#0' => 'b033d86c-b8ad-4693-a4c1-515cd5b3030d',
            'element-media_type_selector#0-link_type_selector_media#0-link_target#0' => '_overlay',
            'element-media_type_selector#0-link_type_selector_media#0-link_title#0' => 'Some title',
            'element-metadata#0' => 'Some title',
            'element-content_position_large#0' => 'right',
            'element-content_position_medium#0' => 'right',
            'element-link_type_selector_media#0-link_intern#0' => 'b033d86c-b8ad-4693-a4c1-515cd5b3030d',
            'element-link_type_selector_media#0-link_target#0' => '_overlay',
            'element-link_type_selector_media#0-link_title#0' => 'Some title',
            'element-link_title#0' => 'Some title',
            'element-settings#0' => '[]',
            'element-media_type_selector#0-settings#0' => '[]',
            'element-media_type_selector#0-link_type_selector_media#0-settings#0' => '[]',
            'element-button_label#0' => 'Explore',
            'element-button_simple_style#0' => 'default',
            'element-link_type_selector_media#0-settings#0' => '[]',
            'element-full_container#0' => true,
            'element-collapse_margin#0' => true,
            'element-flex_box#0-length' => 1,
            'element-flex_box#0-type#0' => 'htmlfield',
            'element-flex_box#0-settings#0' => '[]',
            'element-flex_box#0-htmlfield#0' => '<div style="background-color:#D8838D; padding: 15px 15px 2px 15px;"><p style="text-align:center; color: #ffffff;">Some fancy html</div>',
            'element-generic_content_snippet#0' => 'cf91441f-42ea-4a29-b901-60c9087108a5',
            'element' => '103b3c03-0cc1-43be-9890-ce292e12c7e6',
        ]];
    }
}
