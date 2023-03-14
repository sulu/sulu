<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\CustomUrl\Manager;

use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * Thrown when a title already exists.
 */
class TitleAlreadyExistsException extends RestException implements TranslationErrorMessageExceptionInterface
{
    /**
     * @param string $title
     */
    private $title;

    public function __construct($title, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Title "%s" already in use', $title), 9001, $previous);

        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_custom_url.title_used_for_other_url';
    }

    public function getMessageTranslationParameters(): array
    {
        return ['{title}' => $this->title];
    }
}
