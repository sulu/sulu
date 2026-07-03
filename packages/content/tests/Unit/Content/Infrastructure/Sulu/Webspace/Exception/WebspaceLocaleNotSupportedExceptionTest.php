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

namespace Sulu\Content\Tests\Unit\Content\Infrastructure\Sulu\Webspace\Exception;

use PHPUnit\Framework\TestCase;
use Sulu\Content\Infrastructure\Sulu\Webspace\Exception\WebspaceLocaleNotSupportedException;

class WebspaceLocaleNotSupportedExceptionTest extends TestCase
{
    public function testRespondsWithUnprocessableEntityStatus(): void
    {
        $exception = new WebspaceLocaleNotSupportedException('magazine', 'de');

        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testExposesTranslationContract(): void
    {
        $exception = new WebspaceLocaleNotSupportedException('magazine', 'de');

        $this->assertSame(
            'sulu_content.webspace_does_not_support_locale',
            $exception->getMessageTranslationKey(),
        );
        $this->assertSame(
            ['{webspace}' => 'magazine', '{locale}' => 'de'],
            $exception->getMessageTranslationParameters(),
        );
    }

    public function testKeepsRawMessageAndAccessors(): void
    {
        $exception = new WebspaceLocaleNotSupportedException('magazine', 'de');

        $this->assertSame('Webspace "magazine" does not support locale "de"', $exception->getMessage());
        $this->assertSame('magazine', $exception->getWebspaceKey());
        $this->assertSame('de', $exception->getLocale());
    }
}
