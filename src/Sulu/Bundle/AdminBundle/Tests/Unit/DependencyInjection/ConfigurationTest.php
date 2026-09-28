<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\AdminBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Sulu\Bundle\AdminBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testTextPartLanguagesDefaultToEmpty(): void
    {
        /** @var array{ckeditor: array{text_part_languages: array<string>}} $config */
        $config = (new Processor())->processConfiguration(new Configuration(false), []);

        $this->assertSame([], $config['ckeditor']['text_part_languages']);
    }

    public function testTextPartLanguages(): void
    {
        /** @var array{ckeditor: array{text_part_languages: array<string>}} $config */
        $config = (new Processor())->processConfiguration(new Configuration(false), [
            ['ckeditor' => ['text_part_languages' => ['en', 'de', 'ar']]],
        ]);

        $this->assertSame(['en', 'de', 'ar'], $config['ckeditor']['text_part_languages']);
    }

    public function testTextPartLanguagesWithCountry(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(false), [
            ['ckeditor' => ['text_part_languages' => ['en', 'de_at']]],
        ]);
    }
}
