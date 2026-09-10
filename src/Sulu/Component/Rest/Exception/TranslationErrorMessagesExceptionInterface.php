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

namespace Sulu\Component\Rest\Exception;

/**
 * The plural of {@see TranslationErrorMessageExceptionInterface}, for an exception whose detail is made
 * of several messages. They are translated and joined into `detail` in the order given.
 */
interface TranslationErrorMessagesExceptionInterface
{
    /**
     * @return list<array{key: string, parameters: array<string, float|int|string>}>
     */
    public function getMessageTranslations(): array;
}
