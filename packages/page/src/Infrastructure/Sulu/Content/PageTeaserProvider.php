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
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderInterface;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Application\ContentMetadataInspector\ContentMetadataInspectorInterface;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Sulu\Teaser\ContentTeaserProvider;
use Sulu\Page\Domain\Model\PageDimensionContentInterface;
use Sulu\Page\Domain\Model\PageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends ContentTeaserProvider<PageDimensionContentInterface, PageInterface>
 *
 * TODO should not inherit from a generic TeaserProvider
 */
class PageTeaserProvider extends ContentTeaserProvider
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var ContentEnhancerInterface
     */
    private $contentEnhancer;

    public function __construct(
        ContentManagerInterface $contentManager,
        EntityManagerInterface $entityManager,
        ContentMetadataInspectorInterface $contentMetadataInspector,
        MetadataProviderInterface $metadataProvider,
        TranslatorInterface $translator,
        ContentEnhancerInterface $contentEnhancer,
    ) {
        parent::__construct($contentManager, $entityManager, $contentMetadataInspector, $metadataProvider, PageInterface::class);

        $this->translator = $translator;
        $this->contentEnhancer = $contentEnhancer;
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

    /**
     * Override to add content enhancement for page links.
     *
     * @template E of DimensionContentInterface
     *
     * @param ContentRichEntityInterface<E> $contentRichEntity
     *
     * @return E|null
     */
    protected function resolveContent(ContentRichEntityInterface $contentRichEntity, string $locale, bool $showDrafts = false): ?DimensionContentInterface
    {
        $dimensionContent = parent::resolveContent($contentRichEntity, $locale, $showDrafts);

        if (null === $dimensionContent) {
            return null;
        }

        // Enhance the content to resolve page links and other enhancements
        /** @var E */
        return $this->contentEnhancer->enhance($dimensionContent);
    }
}
