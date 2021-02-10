<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\SecurityBundle\Security\Exception;

use Sulu\Component\Rest\Exception\TranslationErrorMessageExceptionInterface;

/**
 * This Exception is thrown if the email for a user is not unique.
 */
class EmailNotUniqueException extends SecurityException implements TranslationErrorMessageExceptionInterface
{
    private $email;

    public function __construct($email)
    {
        $this->email = $email;
        parent::__construct(\sprintf('The email "%s" is not unique!', $email), 1004);
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_security.email_assigned_to_other_user';
    }

    public function getMessageTranslationParameters(): array
    {
        return ['%email%' => $this->email];
    }

    public function toArray()
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'email' => $this->email,
        ];
    }
}
