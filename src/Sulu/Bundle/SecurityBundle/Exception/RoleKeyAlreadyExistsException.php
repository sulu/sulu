<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * Exception is thrown when a Role is created or updated with an already existing key.
 */
class RoleKeyAlreadyExistsException extends \Exception implements TranslationErrorMessageExceptionInterface
{
    /**
     * @param string $key
     */
    public function __construct(private $key, ?\Throwable $previous = null)
    {
        parent::__construct(\sprintf('Role with key "%s" already exists', $this->key), 1101, $previous);
    }

    /**
     * Returns the non-unique name of the role.
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_security.key_assigned_to_other_role';
    }

    public function getMessageTranslationParameters(): array
    {
        return ['{key}' => $this->key];
    }
}
