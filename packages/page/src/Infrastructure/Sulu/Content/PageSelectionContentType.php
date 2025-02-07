<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Page\Infrastructure\Sulu\Content;

use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

class PageSelectionContentType extends SimpleContentType implements PreResolvableContentTypeInterface
{
    private ReferenceStoreInterface $referenceStore;
    private ContentManagerInterface $contentManager;
    private PageRepositoryInterface $pageRepository;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        ContentManagerInterface $contentManager,
        ReferenceStoreInterface $referenceStore
    ) {
        parent::__construct('Page', []);
        $this->referenceStore = $referenceStore;
        $this->contentManager = $contentManager;
        $this->pageRepository = $pageRepository;
    }

    public function getContentData(PropertyInterface $property)
    {
        $value = $property->getValue();
        if (null === $value || !\is_array($value) || 0 === \count($value)) {
            return [];
        }

        $dimensionAttributes = [
            'locale' => $property->getStructure()->getLanguageCode(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ];

        $page = $this->pageRepository->findBy(
            filters: \array_merge(
                ['uuids' => $value],
                $dimensionAttributes,
            ),
            selects: [
                PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true,
            ]);

        $result = [];
        foreach ($page as $page) {
            $dimensionContent = $this->contentManager->resolve($page, $dimensionAttributes);
            $result[\array_search($page->getUuid(), $value, false)] = $this->contentManager->normalize($dimensionContent);
        }

        \ksort($result);

        return \array_values($result);
    }

    public function preResolve(PropertyInterface $property): void
    {
        $uuids = $property->getValue();
        if (!\is_array($uuids)) {
            return;
        }

        foreach ($uuids as $uuid) {
            $this->referenceStore->add($uuid);
        }
    }
}
