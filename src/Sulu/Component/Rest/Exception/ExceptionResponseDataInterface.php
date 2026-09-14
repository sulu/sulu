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
 * Lets an exception put structured data into the error response beside the message, for cases a single
 * sentence cannot carry. The keys are merged into the body as they are.
 */
interface ExceptionResponseDataInterface
{
    /**
     * @return array<string, mixed>
     */
    public function getResponseData(): array;
}
