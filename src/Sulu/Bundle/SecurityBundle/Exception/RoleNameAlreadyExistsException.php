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
 * Exception is thrown when a Role is created or updated with an already existing name.
 */
class RoleNameAlreadyExistsException extends \Exception implements TranslationErrorMessageExceptionInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name, ?\Throwable $previous = null)
    {
        $this->name = $name;
        parent::__construct(\sprintf('Role "%s" already exists', $name), 1101, $previous);
    }

    /**
     * Returns the non-unique name of the role.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_security.name_assigned_to_other_role';
    }

    public function getMessageTranslationParameters(): array
    {
        return ['{name}' => $this->name];
    }
}
