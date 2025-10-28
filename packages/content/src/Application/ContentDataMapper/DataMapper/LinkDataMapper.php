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

namespace Sulu\Content\Application\ContentDataMapper\DataMapper;

use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\LinkInterface;
use Webmozart\Assert\Assert;

/**
 * @internal This class should not be instantiated by a project.
 *           Create your own data mapper instead.
 */
class LinkDataMapper implements DataMapperInterface
{
    public function map(
        DimensionContentInterface $unlocalizedDimensionContent,
        DimensionContentInterface $localizedDimensionContent,
        array $data
    ): void {
        if (!$localizedDimensionContent instanceof LinkInterface) {
            return;
        }

        $this->setLinkData($localizedDimensionContent, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setLinkData(LinkInterface $dimensionContent, array $data): void
    {
        if (\array_key_exists('linkOn', $data)) {
            Assert::boolean($data['linkOn']);

            /** @var array<string, mixed>|null $linkData */
            $linkData = null;

            if ($data['linkOn']) {
                $linkData = $data['linkData'] ?? [];
                Assert::nullOrIsArray($linkData);
                /** @var array<string, mixed> $linkData */
                $linkData = \array_filter($linkData, fn ($linkData) => null !== $linkData);
            }

            $dimensionContent->setLinkData($linkData);
        }
    }
}
