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

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Teaser\ContentTeaserProvider;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ContentTeaserProvider<PageDimensionContentInterface, PageInterface>
 */
class PageTeaserProvider extends ContentTeaserProvider
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(
        ContentManagerInterface $contentManager,
        EntityManagerInterface $entityManager,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        MetadataProviderRegistry $metadataProviderRegistry,
        TranslatorInterface $translator,
    ) {
        parent::__construct($contentManager, $entityManager, $contentMetadataInspector, $metadataProviderRegistry, PageInterface::class);

        $this->translator = $translator;
    }

    public function getConfiguration(): TeaserConfiguration
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_page.page', [], 'admin'),
            $this->getResourceKey(),
            'table',
            ['title'],
            $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'),
        );
    }

    /**
     * @param array{
     *     page?: string|null,
     *     description?: string|null,
     * } $data
     */
    protected function getDescription(DimensionContentInterface $dimensionContent, array $data): ?string
    {
        $page = \strip_tags($data['page'] ?? '');

        return $page ?: parent::getDescription($dimensionContent, $data);
    }

    protected function getEntityIdField(): string
    {
        return 'uuid';
    }
}
