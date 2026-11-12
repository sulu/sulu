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

use Sulu\Component\Persistence\Model\AuditableTrait;
use Sulu\Redirect\Domain\Exception\InvalidRedirectException;
use Sulu\Redirect\Domain\Value\RedirectStatusCodeEnum;
use Symfony\Component\Uid\Uuid;

class Redirect implements RedirectInterface
{
    use AuditableTrait;

    protected string $id;

    protected bool $enabled = true;

    protected int $statusCode = 301;

    protected string $source = '';

    protected ?string $sourceHost = null;

    protected string $target = '';

    public function __construct(?string $id = null)
    {
        $this->id = $id ?: Uuid::v7()->toRfc4122();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getSourceHost(): ?string
    {
        return $this->sourceHost;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function modify(int $statusCode, string $source, ?string $sourceHost, ?string $target): void
    {
        $status = RedirectStatusCodeEnum::tryFrom($statusCode);
        if (null === $status) {
            throw new InvalidRedirectException('statusCode', 'unsupported_status_code');
        }

        $source = \mb_strtolower('/' . \ltrim($source, '/'));
        if ('/' === $source) {
            throw new InvalidRedirectException('source', 'empty');
        }

        if (\preg_match('/[?#\x00-\x1F]/', $source)) {
            throw new InvalidRedirectException('source', 'invalid_character');
        }

        if (\mb_strlen($source) > 191) {
            throw new InvalidRedirectException('source', 'too_long');
        }

        $sourceHost = \mb_strtolower(\trim($sourceHost ?? '')) ?: null;
        if (null !== $sourceHost && \preg_match('/[\/:\s]/', $sourceHost)) {
            throw new InvalidRedirectException('sourceHost', 'invalid_host');
        }

        if (null !== $sourceHost && \mb_strlen($sourceHost) > 191) {
            throw new InvalidRedirectException('sourceHost', 'too_long');
        }

        $target = RedirectStatusCodeEnum::GONE === $status ? '' : \trim($target ?? '');
        if (RedirectStatusCodeEnum::GONE !== $status) {
            $this->validateTarget($target);
        }

        $this->statusCode = $statusCode;
        $this->source = $source;
        $this->sourceHost = $sourceHost;
        $this->target = $target;
    }

    private function validateTarget(string $target): void
    {
        if ('' === $target) {
            throw new InvalidRedirectException('target', 'empty');
        }

        if (\preg_match('/[\x00-\x1F]/', $target)) {
            throw new InvalidRedirectException('target', 'invalid_character');
        }

        $isRelative = \str_starts_with($target, '/') && !\str_starts_with($target, '//') && !\str_starts_with($target, '/\\');
        $scheme = \strtolower((string) \parse_url($target, \PHP_URL_SCHEME));
        $host = (string) \parse_url($target, \PHP_URL_HOST);
        if (!$isRelative && (!\in_array($scheme, ['http', 'https'], true) || '' === $host)) {
            throw new InvalidRedirectException('target', 'invalid_target');
        }

        if (\mb_strlen($target) > 255) {
            throw new InvalidRedirectException('target', 'too_long');
        }
    }
}
