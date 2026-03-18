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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Route;

use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\CacheLifetimeMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TemplateMetadata;
use Sulu\Bundle\AdminBundle\Metadata\FormMetadata\TypedFormMetadata;
use Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeRequestStore;
use Sulu\Bundle\HttpCacheBundle\CacheLifetime\CacheLifetimeResolverInterface;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Route\Application\Routing\Matcher\RouteDefaultsProviderInterface;
use Sulu\Route\Domain\Model\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @internal this class is internal and should not be extended or relied on in custom code
 */
class ExampleRouteDefaultsProvider implements RouteDefaultsProviderInterface
{
    public function __construct(
        private ExampleRepository $exampleRepository,
        private ContentAggregatorInterface $contentAggregator,
        private MetadataProviderRegistry $metadataProviderRegistry,
        private CacheLifetimeResolverInterface $cacheLifetimeResolver,
        private bool $audienceTargetingEnabled = false,
    ) {
    }

    public function getDefaults(Route $route): array
    {
        $id = $route->getResourceId();
        $locale = $route->getLocale();

        $dimensionAttributes = [
            'locale' => $locale,
            'stage' => DimensionContentInterface::STAGE_LIVE,
            'version' => DimensionContentInterface::CURRENT_VERSION,
        ];

        $example = $this->exampleRepository->findOneBy(
            [
                'id' => $id,
            ],
            [
                ExampleRepository::SELECT_EXAMPLE_CONTENT => [
                    'dimensionAttributes' => $dimensionAttributes,
                    'selects' => $this->getContentSelects(),
                ],
            ],
        );

        if (null === $example) {
            throw new NotFoundHttpException(\sprintf('No example found for id "%s" and locale "%s"', $id, $locale));
        }

        try {
            $dimensionContent = $this->contentAggregator->aggregate($example, $dimensionAttributes);
        } catch (ContentNotFoundException $exception) {
            throw new NotFoundHttpException(\sprintf('No example found for id "%s" and locale "%s"', $id, $locale), $exception);
        }

        if (!$dimensionContent instanceof TemplateInterface) {
            throw new \RuntimeException(\sprintf('Expected to get "%s" from ContentResolver but "%s" given.', TemplateInterface::class, $dimensionContent::class));
        }

        $contentLocale = $dimensionContent->getLocale();
        if (!$contentLocale) {
            throw new NotFoundHttpException(\sprintf('No example found for id "%s" and locale "%s"', $id, $locale));
        }

        $templateKey = $dimensionContent->getTemplateKey();
        if (!$templateKey) {
            throw new NotFoundHttpException(\sprintf('No template found for id "%s" and locale "%s"', $id, $locale));
        }

        $templateMetadata = $this->resolveTemplateMetadata($dimensionContent::getTemplateType(), $templateKey, $contentLocale);

        $attributes = [
            'object' => $dimensionContent,
            'view' => $templateMetadata->getView(),
            '_controller' => $templateMetadata->getController(),
        ];

        $cacheLifetime = $this->getCacheLifetime($templateMetadata);
        if ($cacheLifetime) {
            $attributes[CacheLifetimeRequestStore::ATTRIBUTE_KEY] = $cacheLifetime;
        }

        return $attributes;
    }

    /**
     * @return array<string, bool>
     */
    private function getContentSelects(): array
    {
        $selects = [
            DimensionContentQueryEnhancer::SELECT_EXCERPT_TAGS => true,
            DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES => true,
            DimensionContentQueryEnhancer::SELECT_EXCERPT_CATEGORIES_TRANSLATION => true,
        ];

        if ($this->audienceTargetingEnabled) {
            $selects[DimensionContentQueryEnhancer::SELECT_EXCERPT_AUDIENCE_TARGET_GROUPS] = true;
        }

        return $selects;
    }

    private function getCacheLifetime(TemplateMetadata $templateMetadata): ?int
    {
        $cacheLifetime = $templateMetadata->getCacheLifetime();
        if (!$cacheLifetime instanceof CacheLifetimeMetadata) {
            return null;
        }

        $cacheLifeTimeType = $cacheLifetime->getType();
        $cacheLifeTimeValue = $cacheLifetime->getValue();

        if (!$this->cacheLifetimeResolver->supports($cacheLifeTimeType, $cacheLifeTimeValue)) {
            throw new \InvalidArgumentException(\sprintf('Invalid cacheLifeTime in route default provider: %s', \json_encode([
                'type' => $cacheLifeTimeType,
                'value' => $cacheLifeTimeValue,
            ], flags: \JSON_THROW_ON_ERROR)));
        }

        return $this->cacheLifetimeResolver->resolve($cacheLifeTimeType, $cacheLifeTimeValue);
    }

    private function resolveTemplateMetadata(string $type, string $templateKey, string $locale): TemplateMetadata
    {
        $typedMetadata = $this->metadataProviderRegistry->getMetadataProvider('form')
            ->getMetadata($type, $locale, []);

        if (!$typedMetadata instanceof TypedFormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find metadata "%s" of type "%s".', 'form', $type));
        }

        $metadata = $typedMetadata->getForms()[$templateKey] ?? null;

        if (!$metadata instanceof FormMetadata) {
            throw new \RuntimeException(\sprintf('Could not find form metadata "%s" of type "%s".', $templateKey, $type));
        }

        $templateMetadata = $metadata->getTemplate();

        if (!$templateMetadata instanceof TemplateMetadata) {
            throw new \RuntimeException(\sprintf('Could not find template metadata "%s" of type "%s".', $templateKey, $type));
        }

        return $templateMetadata;
    }
}
