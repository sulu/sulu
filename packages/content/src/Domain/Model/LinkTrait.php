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

namespace Sulu\Content\Domain\Model;

use Webmozart\Assert\Assert;

trait LinkTrait
{
    private ?string $linkProvider = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $linkData = null;

    public function getLinkProvider(): ?string
    {
        return $this->linkProvider;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getLinkData(): ?array
    {
        if (null === $this->linkData) {
            return null;
        }

        return [
            'provider' => $this->linkProvider,
            ...$this->linkData,
        ];
    }

    /**
     * @param array<string, mixed>|null $linkData
     */
    public function setLinkData(?array $linkData): void
    {
        if (\is_array($linkData)) {
            $linkProvider = $linkData['provider'] ?? null;
            Assert::string($linkProvider);
            $this->linkProvider = $linkProvider;
            unset($linkData['provider']);
        }

        $this->linkData = $linkData;
    }
}
