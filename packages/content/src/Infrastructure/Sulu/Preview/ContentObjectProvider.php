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

namespace Sulu\Content\Infrastructure\Sulu\Preview;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NoResultException;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\PreviewBundle\Preview\PreviewContext;
use Sulu\Bundle\PreviewBundle\Preview\Provider\PreviewDefaultsProviderInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentDataMapper\ContentDataMapperInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\ContentRichEntityInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\ShadowInterface;
use Sulu\Content\Domain\Model\TemplateInterface;

/**
 * @template B of DimensionContentInterface
 * @template T of ContentRichEntityInterface<B>
 */
class ContentObjectProvider implements PreviewDefaultsProviderInterface
{
    /**
     * @var MetadataProviderRegistry
     */
    private $metadataProviderRegistry;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ContentAggregatorInterface
     */
    private $contentAggregator;

    /**
     * @var ContentDataMapperInterface
     */
    private $contentDataMapper;

    /**
     * @var class-string<T>
     */
    private $contentRichEntityClass;

    /**
     * @var string|null
     */
    private $securityContext;

    /**
     * @param class-string<T> $contentRichEntityClass
     */
    public function __construct(
        MetadataProviderRegistry $metadataProviderRegistry,
        EntityManagerInterface $entityManager,
        ContentAggregatorInterface $contentAggregator,
        ContentDataMapperInterface $contentDataMapper,
        string $contentRichEntityClass,
        ?string $securityContext = null
    ) {
        $this->metadataProviderRegistry = $metadataProviderRegistry;
        $this->entityManager = $entityManager;
        $this->contentAggregator = $contentAggregator;
        $this->contentDataMapper = $contentDataMapper;
        $this->contentRichEntityClass = $contentRichEntityClass;
        $this->securityContext = $securityContext;
    }

    public function getDefaults(PreviewContext $previewContext): array
    {
        $id = $previewContext->getId();
        $locale = $previewContext->getLocale();

        if (null === $id) {
            throw new \RuntimeException('The ContentObjectProvider requires a id to be set in the PreviewContext.');
        }

        if (null === $locale) {
            throw new \RuntimeException('The ContentObjectProvider requires a locale to be set in the PreviewContext.');
        }

        try {
            /** @var T $contentRichEntity */
            $contentRichEntity = $this->entityManager->createQueryBuilder()
                ->select('entity')
                ->from($this->contentRichEntityClass, 'entity')
                ->where('entity = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->getSingleResult();
        } catch (NoResultException $exception) {
            return [];
        }

        $object = $this->resolveContent($contentRichEntity, $locale);

        if (!$object instanceof TemplateInterface) {
            return [];
        }

        $templateKey = $object->getTemplateKey();
        if (!$templateKey) {
            return [];
        }

        $templateMetadata = $this->resolveTemplateMetadata($object::getTemplateType(), $templateKey, $locale);

        if (!$templateMetadata instanceof TemplateMetadata) {
            return [];
        }

        return [
            'object' => $object,
            'view' => $templateMetadata->getView(),
            '_controller' => $templateMetadata->getController(),
        ];
    }

    public function updateValues(PreviewContext $previewContext, array $defaults, array $data): array
    {
        $object = $defaults['object'];
        if (!$object instanceof DimensionContentInterface) {
            throw new \RuntimeException('Object must be instance of DimensionContentInterface');
        }

        $locale = $previewContext->getLocale();

        if (null === $locale) {
            throw new \RuntimeException('The ContentObjectProvider requires a locale to be set in the PreviewContext.');
        }

        $previewDimensionContentCollection = new PreviewDimensionContentCollection($object, $locale);
        $this->contentDataMapper->map(
            $previewDimensionContentCollection,
            $previewDimensionContentCollection->getDimensionAttributes(),
            $data
        );

        return $defaults;
    }

    public function updateContext(PreviewContext $previewContext, array $defaults, array $context): array
    {
        $object = $defaults['object'];
        if (!$object instanceof DimensionContentInterface) {
            throw new \RuntimeException('Object must be instance of DimensionContentInterface');
        }

        if ($object instanceof TemplateInterface) {
            if (\array_key_exists('template', $context)) {
                \assert(\is_string($context['template']));
                $object->setTemplateKey($context['template']);
            }
        }

        return $defaults;
    }

    public function getSecurityContext(PreviewContext $previewContext): ?string
    {
        return $this->securityContext;
    }

    /**
     * @param T $contentRichEntity
     *
     * @return B|null
     */
    protected function resolveContent(ContentRichEntityInterface $contentRichEntity, string $locale): ?DimensionContentInterface
    {
        try {
            $resolvedDimensionContent = $this->contentAggregator->aggregate(
                $contentRichEntity,
                [
                    'locale' => $locale,
                    'stage' => DimensionContentInterface::STAGE_DRAFT,
                ]
            );

            // unfortunately we can only check if it is a shadow after the dimensionContent was loaded
            if ($resolvedDimensionContent instanceof ShadowInterface && $resolvedDimensionContent->getShadowLocale()) {
                return $this->resolveContent($contentRichEntity, $resolvedDimensionContent->getShadowLocale());
            }

            if (!$resolvedDimensionContent->getLocale()) {
                // avoid 500 error when ghostLocale is loaded by still use correct locale in serialize method
                $resolvedDimensionContent->setLocale($locale);
            }

            return $resolvedDimensionContent;
        } catch (ContentNotFoundException $exception) {
            return null;
        }
    }

    private function resolveTemplateMetadata(string $type, string $templateKey, string $locale): ?TemplateMetadata
    {
        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($type, $locale, []);

        if (!$typedMetadata instanceof TypedFormMetadata) {
            return null;
        }

        $metadata = $typedMetadata->getForms()[$templateKey] ?? null;

        if (!$metadata instanceof FormMetadata) {
            return null;
        }

        $templateMetadata = $metadata->getTemplate();

        if (!$templateMetadata instanceof TemplateMetadata) {
            return null;
        }

        return $templateMetadata;
    }
}
