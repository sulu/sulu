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
use Sulu\Bundle\PageBundle\Search\Metadata\StructureProvider;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

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
     * @param SearchManagerInterface $searchManager
     */
    public function __construct(SearchManagerInterface $searchManager, TranslatorInterface $translator)
    {
        $this->searchManager = $searchManager;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration()
    {
        return new TeaserConfiguration(
            $this->translator->trans('sulu_page.page', [], 'admin'),
            'pages',
            'column_list',
            ['title'],
            $this->translator->trans('sulu_page.single_selection_overlay_title', [], 'admin')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function find(array $ids, $locale)
    {
        $statements = array_map(
            function($item) {
                return sprintf('__id:"%s"', $item);
            },
            $ids
        );

        $result = [];
        $searchResult = $this->searchManager
            ->createSearch(implode(' OR ', $statements))
            ->indexes($this->getPageIndexes())
            ->locale($locale)
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
                [
                    'structureType' => $document->getField(StructureProvider::FIELD_STRUCTURE_TYPE)->getValue(),
                ]
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
     * @param Document $document
     * @param string $field
     *
     * @return int|null
     */
    private function getMedia(Document $document, $field)
    {
        $images = json_decode($document->getField($field)->getValue(), true);

        if (!array_key_exists('ids', $images) || 0 === count($images['ids'])) {
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
        return array_filter(
            $this->searchManager->getIndexNames(),
            function($index) {
                return preg_match('/page_(.*)_published/', $index) > 0;
            }
        );
    }
}
