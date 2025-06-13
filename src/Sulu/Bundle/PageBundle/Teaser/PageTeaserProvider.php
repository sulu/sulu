<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Teaser;

use Massive\Bundle\SearchBundle\Search\Document;
use Massive\Bundle\SearchBundle\Search\QueryHit;
use Massive\Bundle\SearchBundle\Search\SearchManagerInterface;
use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Search\Metadata\StructureProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @deprecated This class will be replaced with the `PHPCRPageTeaserProvider`
 */
class PageTeaserProvider implements TeaserProviderInterface
{
    /**
     * @param PHPCRPageTeaserProvider|null $phpcrPageTeaserProvider
     */
    public function __construct(
        private SearchManagerInterface $searchManager,
        private TranslatorInterface $translator,
        private bool $showDrafts = false,
        private ?TeaserProviderInterface $phpcrPageTeaserProvider = null
    ) {
        if (null === $this->phpcrPageTeaserProvider) {
            @trigger_deprecation(
                'sulu/sulu',
                '2.3',
                'Instantiating a PageTeaserProvider without the $phpcrPageTeaserProvider argument is deprecated!'
            );
        }
    }

    public function getConfiguration()
    {
        if (null !== $this->phpcrPageTeaserProvider) {
            return $this->phpcrPageTeaserProvider->getConfiguration();
        }

        return new TeaserConfiguration(
            $this->translator->trans('sulu_page.page', [], 'admin'),
            'pages',
            'column_list',
            ['title'],
            $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin'),
            PageAdmin::EDIT_FORM_VIEW,
            ['id' => 'id', 'attributes/webspaceKey' => 'webspace']
        );
    }

    public function find(array $ids, $locale)
    {
        if (null !== $this->phpcrPageTeaserProvider) {
            return $this->phpcrPageTeaserProvider->find($ids, $locale);
        }

        $statements = \array_map(
            function($item) {
                return \sprintf('__id:"%s"', $item);
            },
            $ids
        );

        $result = [];
        $searchResult = $this->searchManager
            ->createSearch(\implode(' OR ', $statements))
            ->indexes($this->getPageIndexes())
            ->locale($locale)
            ->setLimit(\count($ids))
            ->execute();

        /** @var QueryHit $item */
        foreach ($searchResult as $item) {
            $document = $item->getDocument();

            $title = $this->getTitleFromDocument($document);
            $excerptTitle = $this->getExcerptTitleFromDocument($document);
            $excerptDescription = $this->getExcerptDescritionFromDocument($document);
            $excerptMedia = $this->getMedia($document, 'excerptImages');

            $teaserDescription = $document->hasField(StructureProvider::FIELD_TEASER_DESCRIPTION) ?
                $document->getField(StructureProvider::FIELD_TEASER_DESCRIPTION)->getValue() : '';
            $teaserMedia = $document->hasField(StructureProvider::FIELD_TEASER_MEDIA) ?
                $this->getMedia($document, StructureProvider::FIELD_TEASER_MEDIA) : null;

            $result[] = new Teaser(
                $item->getId(),
                'pages',
                $locale,
                '' !== $excerptTitle ? $excerptTitle : $title,
                '' !== $excerptDescription ? $excerptDescription : $teaserDescription,
                $document->getField('excerptMore')->getValue(),
                $document->getField('__url')->getValue(),
                null !== $excerptMedia ? $excerptMedia : $teaserMedia,
                $this->getAttributes($document)
            );
        }

        return $result;
    }

    /**
     * @deprecated
     */
    protected function getTitleFromDocument(Document $document)
    {
        return $document->getField('title')->getValue();
    }

    /**
     * @deprecated
     */
    protected function getExcerptTitleFromDocument(Document $document)
    {
        return $document->getField('excerptTitle')->getValue();
    }

    /**
     * @deprecated
     */
    protected function getExcerptDescritionFromDocument(Document $document)
    {
        return $document->getField('excerptDescription')->getValue();
    }

    /**
     * Returns media-id.
     *
     * @param string $field
     *
     * @return int|null
     */
    private function getMedia(Document $document, $field)
    {
        $images = \json_decode($document->getField($field)->getValue(), true);

        if (!$images || !\array_key_exists('ids', $images) || 0 === \count($images['ids'])) {
            return;
        }

        return $images['ids'][0];
    }

    /**
     * Returns page indexes.
     *
     * @return array
     */
    private function getPageIndexes()
    {
        $allPageIndexNames = \array_filter(
            $this->searchManager->getIndexNames(),
            function($index) {
                return \preg_match('/page_(.+)/', $index) > 0;
            }
        );

        $publishedPageIndexNames = \array_filter(
            $allPageIndexNames,
            function($index) {
                return \preg_match('/page_(.+)_published/', $index) > 0;
            }
        );

        return $this->showDrafts
            ? \array_values(\array_diff($allPageIndexNames, $publishedPageIndexNames))
            : \array_values($publishedPageIndexNames);
    }

    /**
     * Returns attributes for teaser.
     *
     * @deprecated
     *
     * @return array
     */
    protected function getAttributes(Document $document)
    {
        return [
            'structureType' => $document->getField(StructureProvider::FIELD_STRUCTURE_TYPE)->getValue(),
            'webspaceKey' => $document->getField(StructureProvider::FIELD_WEBSPACE_KEY)->getValue(),
        ];
    }
}
