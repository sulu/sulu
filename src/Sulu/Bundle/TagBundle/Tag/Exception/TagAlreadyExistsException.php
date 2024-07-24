<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\TagBundle\Tag\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * This Exception is thrown when a Tag already exists.
 */
class TagAlreadyExistsException extends \Exception implements TranslationErrorMessageExceptionInterface
{
    /**
     * @param string $name The name of the tag which already exists
     */
    public function __construct(protected $name, ?\Throwable $previous = null)
    {
        $message = 'The tag with the name "' . $this->name . '" already exists.';
        parent::__construct($message, 0, $previous);
    }

    /**
     * Returns the name of the tag, which already exists.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_tag.tag_already_exists';
    }

    /**
     * @return array<string, mixed>
     */
    public function getMessageTranslationParameters(): array
    {
        return [
            '{name}' => $this->name,
        ];
    }
}
