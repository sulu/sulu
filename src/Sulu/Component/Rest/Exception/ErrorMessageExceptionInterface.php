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

interface ErrorMessageExceptionInterface
{
    public function getMessageTranslationKey(): string;

    public function getMessageTranslationParameters(): array;
}
