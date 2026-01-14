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

namespace Sulu\Content\Tests\Application\ExampleTestBundle\Teaser;

use Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration;
use Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface;
use Sulu\Bundle\AdminBundle\Teaser\Teaser;
use Sulu\Content\Application\ContentAggregator\ContentAggregatorInterface;
use Sulu\Content\Application\ContentEnhancer\ContentEnhancerInterface;
use Sulu\Content\Domain\Exception\ContentNotFoundException;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Infrastructure\Doctrine\DimensionContentQueryEnhancer;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\Example;
use Sulu\Content\Tests\Application\ExampleTestBundle\Entity\ExampleDimensionContent;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;
use Sulu\Route\Application\Routing\Generator\RouteGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExampleTeaserProvider implements TeaserProviderInterface
{
    public function __construct(
        protected ExampleRepository $exampleRepository,
        protected ContentAggregatorInterface $contentAggregator,
        protected ContentEnhancerInterface $contentEnhancer,
        protected RouteGeneratorInterface $routeGenerator,
        protected TranslatorInterface $translator,
    ) {
    }

    public function getConfiguration(): TeaserConfiguration
    {
        return new TeaserConfiguration(
            $this->translator->trans('example_test.example', [], 'admin'),
            Example::RESOURCE_KEY,
            'table',
            ['title'],
            $this->translator->trans('example_test.select_examples', [], 'admin'),
        );
    }

    /**
     * @param array<string> $ids
     *
     * @return Teaser[]
     */
    public function find(array $ids, $locale): array
    {
        if (0 === \count($ids)) {
            return [];
        }

        $examples = $this->findExamplesByIds($ids, $locale);

        $teasers = [];
        foreach ($examples as $example) {
            $teaser = $this->createTeaserFromExample($example, $locale);
            if (null !== $teaser) {
                $teasers[] = $teaser;
            }
        }

        return $teasers;
    }

    /**
     * @param array<string> $ids
     *
     * @return array<Example>
     */
    private function findExamplesByIds(array $ids, string $locale): array
    {
        /** @var array<Example> $examples */
        $examples = \iterator_to_array($this->exampleRepository->findBy(
            filters: [
                'ids' => $ids,
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ],
            selects: [
                ExampleRepository::SELECT_EXAMPLE_CONTENT => [
                    DimensionContentQueryEnhancer::GROUP_SELECT_CONTENT_WEBSITE => true,
                ],
            ]
        ));

        // Sort by original order
        $idPositions = \array_flip($ids);
        \usort(
            $examples,
            static fn (Example $a, Example $b) => ($idPositions[(string) $a->getId()] ?? 0) - ($idPositions[(string) $b->getId()] ?? 0)
        );

        return $examples;
    }

    private function createTeaserFromExample(Example $example, string $locale): ?Teaser
    {
        $dimensionContent = $this->resolveDimensionContent($example, $locale);
        if (null === $dimensionContent) {
            return null;
        }

        /** @var ExampleDimensionContent $dimensionContent */
        $dimensionContent = $this->contentEnhancer->enhance($dimensionContent);

        $url = $this->resolveUrl($dimensionContent);
        if (null === $url) {
            return null;
        }

        /** @var string $title */
        $title = $this->resolveTitle($dimensionContent);

        /** @var string $description */
        $description = $this->resolveDescription($dimensionContent); // @phpstan-ignore-line

        /** @var string $moreText */
        $moreText = $this->resolveMoreText($dimensionContent); // @phpstan-ignore-line

        /** @var int $mediaId */
        $mediaId = $this->resolveMediaId($dimensionContent);

        return new Teaser(
            (string) $example->getId(),
            Example::RESOURCE_KEY,
            $locale,
            $title,
            $description,
            $moreText,
            $url,
            $mediaId,
            $this->getAttributes($dimensionContent),
        );
    }

    protected function resolveDimensionContent(Example $example, string $locale): ?ExampleDimensionContent
    {
        try {
            /** @var ExampleDimensionContent $dimensionContent */
            $dimensionContent = $this->contentAggregator->aggregate($example, [
                'locale' => $locale,
                'stage' => DimensionContentInterface::STAGE_LIVE,
            ]);
        } catch (ContentNotFoundException) {
            return null;
        }

        return $dimensionContent;
    }

    protected function resolveUrl(ExampleDimensionContent $dimensionContent): ?string
    {
        $route = $dimensionContent->getRoute();
        if (null !== $route) {
            return $this->routeGenerator->generate(
                $route->getSlug(),
                $route->getLocale(),
                $dimensionContent->getMainWebspace(),
            );
        }

        $templateData = $dimensionContent->getTemplateData();
        $url = $templateData['url'] ?? null;

        return \is_string($url) ? $url : null;
    }

    protected function resolveTitle(ExampleDimensionContent $dimensionContent): ?string
    {
        $title = $dimensionContent->getExcerptTitle() ?? $dimensionContent->getTitle();

        return \is_string($title) && '' !== $title ? $title : null;
    }

    protected function resolveDescription(ExampleDimensionContent $dimensionContent): ?string
    {
        $templateData = $dimensionContent->getTemplateData();
        $articleValue = $templateData['article'] ?? '';
        $article = \is_string($articleValue) ? \strip_tags($articleValue) : '';

        if ('' !== $article) {
            return $article;
        }

        $description = $dimensionContent->getExcerptDescription();
        if (null === $description || '' === $description) {
            return null;
        }

        return \strip_tags($description);
    }

    protected function resolveMoreText(ExampleDimensionContent $dimensionContent): ?string
    {
        $moreText = $dimensionContent->getExcerptMore();

        return '' !== ($moreText ?? '') ? $moreText : null;
    }

    protected function resolveMediaId(ExampleDimensionContent $dimensionContent): ?int
    {
        return $dimensionContent->getExcerptImage()['id'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getAttributes(ExampleDimensionContent $dimensionContent): array
    {
        return [];
    }
}
