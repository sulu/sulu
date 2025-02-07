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

use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\PreResolvableContentTypeInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Content\Application\ContentManager\ContentManagerInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Page\Domain\Repository\PageRepositoryInterface;

class SinglePageSelectionContentType extends SimpleContentType implements PreResolvableContentTypeInterface
{
    private PageRepositoryInterface $pageRepository;

    private ContentManagerInterface $contentManager;

    private ReferenceStoreInterface $referenceStore;

    public function __construct(
        PageRepositoryInterface $pageRepository,
        ContentManagerInterface $contentManager,
        ReferenceStoreInterface $referenceStore,
    ) {
        parent::__construct('Page');

        $this->pageRepository = $pageRepository;
        $this->contentManager = $contentManager;
        $this->referenceStore = $referenceStore;
    }

    public function getContentData(PropertyInterface $property)
    {
        $uuid = $property->getValue();
        if (null === $uuid) {
            return null;
        }

        $dimensionAttributes = [
            'locale' => $property->getStructure()->getLanguageCode(),
            'stage' => DimensionContentInterface::STAGE_LIVE,
        ];

        $page = $this->pageRepository->getOneBy(
            filters: \array_merge(
                [
                    'uuid' => $uuid,
                ],
                $dimensionAttributes,
            ),
            selects: [
                PageRepositoryInterface::GROUP_SELECT_PAGE_WEBSITE => true,
            ]);

        $dimensionContent = $this->contentManager->resolve($page, $dimensionAttributes);

        return $this->contentManager->normalize($dimensionContent);
    }

    public function preResolve(PropertyInterface $property): void
    {
        $uuid = $property->getValue();
        if (null === $uuid) {
            return;
        }

        $this->referenceStore->add($uuid);
    }
}
