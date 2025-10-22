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

namespace Sulu\Article\Tests\Unit\Application\Message;

use PHPUnit\Framework\TestCase;
use Sulu\Article\Application\Message\RemoveArticleTranslationMessage;

class RemoveArticleTranslationMessageTest extends TestCase
{
    public function testGetIdentifier(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $this->assertSame($identifier, $message->getIdentifier());
    }

    public function testGetLocale(): void
    {
        $identifier = ['uuid' => 'article-123'];
        $locale = 'en';
        $message = new RemoveArticleTranslationMessage($identifier, $locale);

        $this->assertSame($locale, $message->getLocale());
    }

    public function testWithDifferentLocales(): void
    {
        $identifier = ['uuid' => 'article-456'];
        $locales = ['en', 'de', 'fr', 'es'];

        foreach ($locales as $locale) {
            $message = new RemoveArticleTranslationMessage($identifier, $locale);
            $this->assertSame($locale, $message->getLocale());
            $this->assertSame($identifier, $message->getIdentifier());
        }
    }
}
