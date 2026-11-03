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

/**
 * Lets an exception name the form fields it is about, so the admin shows the message below each field instead of in
 * the toolbar. The response body gets a `violations` list of `propertyPath` and `title`, like the one the Symfony
 * serializer creates for the violations of the validator.
 *
 * A `propertyPath` is the path to the field in the notation of the validator, e.g. `title` or `blocks[0].key`, and may
 * repeat for a field with several messages. A `title` is either given as translated text or as a translation key of
 * the `admin` domain, which is translated with the given parameters. The exception can implement this interface
 * beside the TranslationErrorMessageExceptionInterface.
 *
 * The violations of a `ValidationFailedException`, which is thrown by itself or as previous exception of an HTTP
 * exception, are added to the response without implementing this interface.
 *
 * @experimental
 */
interface FieldErrorsExceptionInterface
{
    /**
     * @return list<array{propertyPath: string, title: string}|array{propertyPath: string, key: string, parameters?: array<string, float|int|string>}>
     */
    public function getFieldErrors(): array;
}
