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
use Sulu\Content\Infrastructure\Sulu\Webspace\Exception\WebspaceNotFoundException;

class WebspaceNotFoundExceptionTest extends TestCase
{
    public function testRespondsWithUnprocessableEntityStatus(): void
    {
        $exception = new WebspaceNotFoundException('magazine');

        $this->assertSame(422, $exception->getStatusCode());
    }

    public function testExposesTranslationContract(): void
    {
        $exception = new WebspaceNotFoundException('magazine');

        $this->assertSame(
            'sulu_content.webspace_not_found',
            $exception->getMessageTranslationKey(),
        );
        $this->assertSame(
            ['{webspace}' => 'magazine'],
            $exception->getMessageTranslationParameters(),
        );
    }

    public function testKeepsRawMessage(): void
    {
        $exception = new WebspaceNotFoundException('magazine');

        $this->assertSame('Webspace "magazine" not found', $exception->getMessage());
    }
}
