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

namespace Sulu\Redirect\Domain\Model;

use Sulu\Component\Persistence\Model\AuditableInterface;

interface RedirectInterface extends AuditableInterface
{
    public const RESOURCE_KEY = 'redirects';

    public function getId(): string;

    public function isEnabled(): bool;

    public function setEnabled(bool $enabled): void;

    public function getStatusCode(): int;

    public function getSource(): string;

    public function getSourceHost(): ?string;

    public function getTarget(): string;

    public function modify(int $statusCode, string $source, ?string $sourceHost, ?string $target): void;
}
