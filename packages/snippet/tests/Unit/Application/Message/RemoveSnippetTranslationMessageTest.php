<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Snippet\Tests\Unit\Application\Message;

use PHPUnit\Framework\TestCase;
use Sulu\Snippet\Application\Message\RemoveSnippetTranslationMessage;

class RemoveSnippetTranslationMessageTest extends TestCase
{
    public function testGetIdentifier(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $this->assertSame($identifier, $message->getIdentifier());
    }

    public function testGetLocale(): void
    {
        $identifier = ['uuid' => 'snippet-123'];
        $locale = 'en';
        $message = new RemoveSnippetTranslationMessage($identifier, $locale);

        $this->assertSame($locale, $message->getLocale());
    }

    public function testWithDifferentLocales(): void
    {
        $identifier = ['uuid' => 'snippet-456'];
        $locales = ['en', 'de', 'fr', 'es'];

        foreach ($locales as $locale) {
            $message = new RemoveSnippetTranslationMessage($identifier, $locale);
            $this->assertSame($locale, $message->getLocale());
            $this->assertSame($identifier, $message->getIdentifier());
        }
    }
}
