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
    public function testTextEditorContentLocalesDefaultToEmpty(): void
    {
        /** @var array{text_editor: array{content_locales: array<string>}} $config */
        $config = (new Processor())->processConfiguration(new Configuration(false), []);

        $this->assertSame([], $config['text_editor']['content_locales']);
    }

    public function testTextEditorContentLocales(): void
    {
        /** @var array{text_editor: array{content_locales: array<string>}} $config */
        $config = (new Processor())->processConfiguration(new Configuration(false), [
            ['text_editor' => ['content_locales' => ['en', 'de', 'ar']]],
        ]);

        $this->assertSame(['en', 'de', 'ar'], $config['text_editor']['content_locales']);
    }

    public function testTextEditorContentLocalesWithCountry(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new Configuration(false), [
            ['text_editor' => ['content_locales' => ['en', 'de_at']]],
        ]);
    }
}
