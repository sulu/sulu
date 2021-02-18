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
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Search\Metadata\StructureProvider;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageTeaserProvider implements TeaserProviderInterface
{
    /**
     * @var SearchManagerInterface
     */
    private $searchManager;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var bool
     */
    private $showDrafts;

    /**
     * @var TeaserProviderInterface|null
     */
    private $phpcrPageTeaserProvider;

    public function __construct(
        SearchManagerInterface $searchManager,
        TranslatorInterface $translator,
        bool $showDrafts = false,
        ?TeaserProviderInterface $phpcrPageTeaserProvider = null
    ) {
        $this->searchManager = $searchManager;
        $this->translator = $translator;
        $this->showDrafts = $showDrafts;
        $this->phpcrPageTeaserProvider = $phpcrPageTeaserProvider;
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

    public function find(array $ids, $locale/*, ?string $webspaceKey = null*/)
    {
        $webspaceKey = null;
        if (\func_num_args() >= 3) {
            $webspaceKey = \func_get_arg(2);
        }

        if (null !== $webspaceKey && null !== $this->phpcrPageTeaserProvider) {
            return $this->phpcrPageTeaserProvider->find($ids, $locale, $webspaceKey);
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
                ('' !== $excerptTitle ? $excerptTitle : $title),
                ('' !== $excerptDescription ? $excerptDescription : $teaserDescription),
                $document->getField('excerptMore')->getValue(),
                $document->getField('__url')->getValue(),
                (null !== $excerptMedia ? $excerptMedia : $teaserMedia),
                $this->getAttributes($document)
            );
        }

        return $result;
    }

    protected function getTitleFromDocument(Document $document)
    {
        return $document->getField('title')->getValue();
    }

    protected function getExcerptTitleFromDocument(Document $document)
    {
        return $document->getField('excerptTitle')->getValue();
    }

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
