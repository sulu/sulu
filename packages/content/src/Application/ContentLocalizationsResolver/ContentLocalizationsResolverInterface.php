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

namespace Sulu\Content\Application\ContentLocalizationsResolver;

use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;

/**
 * Builds the `localizations` of a website page: the URL of the content in every locale of the
 * webspace, for the language switcher and the hreflang links.
 */
interface ContentLocalizationsResolverInterface
{
    /**
     * @template T of ContentRichEntityInterface
     *
     * @param DimensionContentInterface<T> $dimensionContent
     *
     * @return array<string, array{
     *      url: string,
     *      locale: string,
     *      alternate: bool
     * }>
     */
    public function resolve(DimensionContentInterface $dimensionContent, string $webspaceKey): array;
}
