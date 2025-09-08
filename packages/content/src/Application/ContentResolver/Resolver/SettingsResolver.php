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

namespace Sulu\Content\Application\ContentResolver\Resolver;

use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ContentResolver\Value\Reference;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;

/**
 * @phpstan-type SettingsData array{
 *      availableLocales?: string[]|null,
 *      localizations?: array<string, array{
 *          locale: string,
 *          url: string|null,
 *          country: string,
 *          alternate: bool
 *      }>,
 *      mainWebspace?: string|null,
 *      template?: string|null,
 *      author?: ContentView|null,
 *      authored?: \DateTime|null,
 *      shadowBaseLocale?: string|null,
 *      lastModified?: \DateTimeImmutable|null
 *  }
 */
readonly class SettingsResolver implements ResolverInterface
{
    public function resolve(DimensionContentInterface $dimensionContent, ?array $properties = null): ?ContentView
    {
        /** @var SettingsData $result */
        $result = [
            'availableLocales' => $dimensionContent->getAvailableLocales() ?? [],
        ];

        if ($dimensionContent instanceof WebspaceInterface) {
            $result = \array_merge($result, $this->getWebspaceData($dimensionContent));
        }

        if ($dimensionContent instanceof TemplateInterface) {
            $result = \array_merge($result, $this->getTemplateData($dimensionContent));
        }

        if ($dimensionContent instanceof AuthorInterface) {
            $result = \array_merge($result, $this->getAuthorData($dimensionContent));
        }

        if ($dimensionContent instanceof ShadowInterface) {
            $result = \array_merge($result, $this->getShadowData($dimensionContent));
        }

        return ContentView::create($result, []);
    }

    /**
     * @return array{
     *     mainWebspace: string|null
     * }
     */
    protected function getWebspaceData(WebspaceInterface $dimensionContent): array
    {
        return [
            'mainWebspace' => $dimensionContent->getMainWebspace(),
        ];
    }

    /**
     * @return array{
     *     template: string|null
     * }
     */
    protected function getTemplateData(TemplateInterface $dimensionContent): array
    {
        return [
            'template' => $dimensionContent->getTemplateKey(),
        ];
    }

    /**
     * @return array{
     *     author: ContentView|null,
     *     authored: \DateTimeInterface|null
     * }
     */
    protected function getAuthorData(AuthorInterface $dimensionContent): array
    {
        $authorId = $dimensionContent->getAuthor()?->getId();
        $author = ContentView::createWithReferences(
            $authorId,
            [],
            $authorId ?
                [new Reference($authorId, UserInterface::RESOURCE_KEY)] :
                []
        );

        return [
            'author' => $author,
            'authored' => $dimensionContent->getAuthored(),
            'lastModified' => $dimensionContent->getLastModified(),
        ];
    }

    /**
     * @return array{
     *     shadowBaseLocale: string|null
     * }
     */
    protected function getShadowData(ShadowInterface $dimensionContent): array
    {
        return [
            'shadowBaseLocale' => $dimensionContent->getShadowLocale(),
        ];
    }
}
