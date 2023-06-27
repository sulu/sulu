<?php

declare(strict_types=1);

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Rest\Exception;

class InsufficientDescendantPermissionsException extends \Exception implements RestExceptionInterface, TranslationErrorMessageExceptionInterface
{
    public function __construct(
        private int $unauthorizedDescendantsCount,
        private string $permissionType
    ) {
        parent::__construct(
            \sprintf(
                'Insufficient permissions for %d descendant elements.',
                $this->unauthorizedDescendantsCount
            ),
            static::EXCEPTION_CODE_INSUFFICIENT_DESCENDANT_PERMISSIONS
        );
    }

    public function getUnauthorizedDescendantsCount(): int
    {
        return $this->unauthorizedDescendantsCount;
    }

    public function getPermissionType(): string
    {
        return $this->permissionType;
    }

    public function getMessageTranslationKey(): string
    {
        return 'sulu_admin.insufficient_descendant_permissions';
    }

    /**
     * @return array<string, mixed>
     */
    public function getMessageTranslationParameters(): array
    {
        return [
            '{count}' => $this->getUnauthorizedDescendantsCount(),
        ];
    }
}
