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

use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\MediaBundle\Api\Media;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\Content\Query\ContentQueryBuilderInterface;
use Sulu\Component\Content\Query\ContentQueryExecutorInterface;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Symfony\Contracts\Translation\TranslatorInterface;

class PHPCRPageTeaserProvider implements TeaserProviderInterface
{
    /**
     * @param bool $showDrafts Parameter "sulu_document_manager.show_drafts"
     * @param array<string, int> $permissions Parameter "sulu_security.permissions"
     */
    public function __construct(
        private ContentQueryExecutorInterface $contentQueryExecutor,
        private ContentQueryBuilderInterface $contentQueryBuilder,
        private StructureMetadataFactoryInterface $structureMetadataFactory,
        private TranslatorInterface $translator,
        private bool $showDrafts,
        private array $permissions,
    ) {
    }

    public function getConfiguration(): TeaserConfiguration
    {
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

    /**
     * @param string[] $ids
     * @param string $locale
     *
     * @return Teaser[]
     */
    public function find(array $ids, $locale): array
    {
        if (0 === \count($ids)) {
            return [];
        }

        $pages = $this->loadPages($ids, $locale);
        $result = [];

        foreach ($pages as $pageData) {
            $teaser = $this->createTeaser($pageData, $locale);

            if (null === $teaser) {
                continue;
            }

            $result[] = $teaser;
        }

        return $result;
    }

    /**
     * @param string[] $ids
     *
     * @return array<array<string, mixed>>
     */
    protected function loadPages(array $ids, string $locale): array
    {
        $this->contentQueryBuilder->init(
            [
                'ids' => $ids,
                'properties' => $this->getPropertiesToLoad(),
                'published' => !$this->showDrafts,
            ]
        );

        return $this->contentQueryExecutor->execute(
            null,
            [$locale],
            $this->contentQueryBuilder,
            true,
            -1,
            null,
            null,
            false,
            $this->permissions[PermissionTypes::VIEW]
        );
    }

    /**
     * @return PropertyParameter[]
     */
    protected function getPropertiesToLoad(): array
    {
        return \array_merge(
            $this->getTeaserPropertiesToLoad(),
            [
                new PropertyParameter('excerptTitle', 'excerpt.title'),
                new PropertyParameter('excerptDescription', 'excerpt.description'),
                new PropertyParameter('excerptMore', 'excerpt.more'),
                new PropertyParameter('excerptImages', 'excerpt.images'),
            ]
        );
    }

    /**
     * @return PropertyParameter[]
     */
    protected function getTeaserPropertiesToLoad(): array
    {
        $allMetadata = $this->structureMetadataFactory->getStructures('page');
        $properties = [];

        foreach ($allMetadata as $metadata) {
            if ($teaserProperty = $this->getTeaserProperty($metadata, 'sulu.teaser.description', 'teaserDescription')) {
                $properties[] = $teaserProperty;
            }

            if ($teaserProperty = $this->getTeaserProperty($metadata, 'sulu.teaser.media', 'teaserMedia')) {
                $properties[] = $teaserProperty;
            }
        }

        return $properties;
    }

    private function getTeaserProperty(StructureMetadata $metadata, string $tagName, string $propertyName): ?PropertyParameter
    {
        if ($metadata->hasPropertyWithTagName($tagName)) {
            $property = $metadata->getPropertyByTagName($tagName);

            return new PropertyParameter($metadata->getName() . '_' . $propertyName, $property->getName());
        }

        return null;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function createTeaser(array $pageData, string $locale): ?Teaser
    {
        return new Teaser(
            $this->getId($pageData),
            'pages',
            $locale,
            $this->getTitle($pageData),
            $this->getDescription($pageData),
            $this->getMoreText($pageData),
            $this->getUrl($pageData),
            $this->getMediaId($pageData),
            $this->getAttributes($pageData)
        );
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getId(array $pageData): ?string
    {
        return $pageData['id'] ?? null;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getTitle(array $pageData): ?string
    {
        $excerptTitle = $pageData['excerptTitle'] ?? null;
        $pageTitle = $pageData['title'] ?? null;

        return $excerptTitle ?: $pageTitle;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getDescription(array $pageData): ?string
    {
        $excerptDescription = $pageData['excerptDescription'];
        $structureType = $this->getStructureType($pageData);
        $teaserDescription = $pageData[$structureType . '_teaserDescription'] ?? null;

        return $excerptDescription ?: $teaserDescription;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getMoreText(array $pageData): ?string
    {
        return $pageData['excerptMore'] ?? null;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getUrl(array $pageData): ?string
    {
        return $pageData['url'] ?? null;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getMediaId(array $pageData): ?int
    {
        $excerptMedia = $pageData['excerptImages'][0] ?? null;

        if ($excerptMedia instanceof Media) {
            return $excerptMedia->getId();
        }

        $structureType = $this->getStructureType($pageData);
        $teaserMedia = $pageData[$structureType . '_teaserMedia'] ?? null;

        if (\is_array($teaserMedia)) {
            $teaserMedia = $teaserMedia[0] ?? null;
        }

        if ($teaserMedia instanceof Media) {
            return $teaserMedia->getId();
        }

        return null;
    }

    /**
     * @param array<string, mixed> $pageData
     *
     * @return array<string, mixed>
     */
    protected function getAttributes(array $pageData): array
    {
        return [
            'structureType' => $this->getStructureType($pageData),
            'webspaceKey' => $this->getWebspaceKey($pageData),
        ];
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getStructureType(array $pageData): ?string
    {
        return $pageData['template'] ?? null;
    }

    /**
     * @param array<string, mixed> $pageData
     */
    protected function getWebspaceKey(array $pageData): ?string
    {
        return $pageData['webspaceKey'] ?? null;
    }
}
