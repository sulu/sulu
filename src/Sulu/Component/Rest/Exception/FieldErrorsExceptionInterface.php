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
 * the toolbar. The response body gets a `fieldErrors` list of `path` and `title`.
 *
 * A `path` is a JSON pointer into the form data without the leading slash, e.g. `title` or `blocks/0/key`, and may
 * repeat for a field with several messages. A `title` is either given as translated text or as a translation key of
 * the `admin` domain, which is translated with the given parameters.
 *
 * @experimental
 */
interface FieldErrorsExceptionInterface
{
    /**
     * @return list<array{path: string, title: string}|array{path: string, key: string, parameters?: array<string, float|int|string>}>
     */
    public function getFieldErrors(): array;
}
