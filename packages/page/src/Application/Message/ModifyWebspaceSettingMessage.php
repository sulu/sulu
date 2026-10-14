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

namespace Sulu\Page\Application\Message;

use Webmozart\Assert\Assert;

class ModifyWebspaceSettingMessage
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private string $webspaceKey,
        private array $data,
    ) {
        Assert::string($data['locale'] ?? null, 'Expected a "locale" string given.');
    }

    public function getWebspaceKey(): string
    {
        return $this->webspaceKey;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }
}
