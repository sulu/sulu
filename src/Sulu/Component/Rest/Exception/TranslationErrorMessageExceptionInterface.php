<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

interface TranslationErrorMessageExceptionInterface
{
    public function getMessageTranslationKey(): string;

    /**
     * @return array<string, int|string>
     */
    public function getMessageTranslationParameters(): array;
}
