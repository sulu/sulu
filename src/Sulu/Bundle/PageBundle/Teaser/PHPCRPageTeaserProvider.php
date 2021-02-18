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

use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Component\Content\Document\Structure\PropertyValue;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PHPCRPageTeaserProvider implements TeaserProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(
        DocumentManagerInterface $documentManager,
        StructureMetadataFactoryInterface $structureMetadataFactory,
        TranslatorInterface $translator
    ) {
        $this->documentManager = $documentManager;
        $this->structureMetadataFactory = $structureMetadataFactory;
        $this->translator = $translator;
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

        /** @var BasePageDocument $document */
        foreach ($pages as $document) {
            $result[] = new Teaser(
                $document->getUuid(),
                'pages',
                $locale,
                $this->getExcerptTitleFromDocument($document)
                    ?: $this->getTitleFromDocument($document),
                $this->getExcerptDescriptionFromDocument($document)
                    ?: $this->getTeaserDescriptionFromDocument($document),
                $this->getExcerptMoreFromDocument($document),
                $this->getUrlFromDocument($document),
                $this->getExcerptMediaFromDocument($document)
                    ?: $this->getTeaserMediaFromDocument($document),
                $this->getAttributes($document)
            );
        }

        return $result;
    }

    /**
     * @param string[] $ids
     * @param string $locale
     *
     * @return iterable<BasePageDocument>
     */
    protected function loadPages(array $ids, $locale): iterable
    {
        $query = $this->documentManager->createQuery(\sprintf(
            'SELECT * FROM [nt:unstructured] AS page WHERE ([jcr:mixinTypes] = "sulu:page" OR [jcr:mixinTypes] = "sulu:home") AND (%s)',
            \implode(' OR ', \array_map(function($uuid) {
                return \sprintf('[jcr:uuid] = "%s"', $uuid);
            }, $ids))
        ));

        $query->setLocale($locale);
        $query->setMaxResults(\count($ids));

        return $query->execute();
    }

    protected function getTitleFromDocument(BasePageDocument $document): ?string
    {
        return $document->getTitle();
    }

    protected function getExcerptTitleFromDocument(BasePageDocument $document): ?string
    {
        return $document->getExtensionsData()['excerpt']['title'] ?? null;
    }

    protected function getExcerptDescriptionFromDocument(BasePageDocument $document): ?string
    {
        return $document->getExtensionsData()['excerpt']['description'] ?? null;
    }

    protected function getExcerptMoreFromDocument(BasePageDocument $document): ?string
    {
        return $document->getExtensionsData()['excerpt']['more'] ?? null;
    }

    protected function getUrlFromDocument(BasePageDocument $document): ?string
    {
        return $document->getResourceSegment();
    }

    protected function getExcerptMediaFromDocument(BasePageDocument $document): ?int
    {
        return $document->getExtensionsData()['excerpt']['images']['ids'][0] ?? null;
    }

    protected function getTeaserDescriptionFromDocument(BasePageDocument $document): ?string
    {
        $property = $this->getTaggedPropertyFromDocument($document, 'sulu.teaser.description');

        if (null === $property) {
            return null;
        }

        return $property->getValue();
    }

    protected function getTeaserMediaFromDocument(BasePageDocument $document): ?int
    {
        $property = $this->getTaggedPropertyFromDocument($document, 'sulu.teaser.media');

        if (null === $property) {
            return null;
        }

        $value = $property->getValue();

        return $value['ids'][0] ?? $value['id'] ?? null;
    }

    protected function getTaggedPropertyFromDocument(BasePageDocument $document, string $tagName): ?PropertyValue
    {
        $metadata = $this->structureMetadataFactory->getStructureMetadata('page', $document->getStructureType());

        if (!$metadata->hasPropertyWithTagName($tagName)) {
            return null;
        }

        $property = $metadata->getPropertyByTagName($tagName);

        return $document->getStructure()->getProperty($property->getName());
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(BasePageDocument $document): array
    {
        return [
            'structureType' => $document->getStructureType(),
            'webspaceKey' => $document->getWebspaceName(),
        ];
    }
}
