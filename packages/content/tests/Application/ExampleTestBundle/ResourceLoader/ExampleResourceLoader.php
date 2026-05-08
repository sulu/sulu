<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader;

use Sulu\Content\Application\ContentResolver\Value\ContentView;
use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderContentViewEnhancementInterface;
use Sulu\Content\Domain\Model\AuthorInterface;
use Sulu\Content\Domain\Model\DimensionContentInterface;
use Sulu\Content\Domain\Model\TemplateInterface;
use Sulu\Content\Domain\Model\WebspaceInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;

class ExampleResourceLoader implements ResourceLoaderContentViewEnhancementInterface
{
    public const RESOURCE_LOADER_KEY = 'example';

    public function __construct(
        private ExampleRepository $exampleRepository,
    ) {
    }

    /**
     * @param int[] $ids
     */
    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->exampleRepository->findBy(
            ['ids' => $ids, 'locale' => $locale, 'stage' => DimensionContentInterface::STAGE_LIVE],
            [],
            [ExampleRepository::GROUP_SELECT_EXAMPLE_WEBSITE => true]
        );

        $mappedResult = [];
        foreach ($result as $example) {
            $mappedResult[$example->getId()] = $example;
        }

        return $mappedResult;
    }

    public function resolveContentViewEnhancement(mixed $resource): ContentView
    {
        $view = [];
        $content = [];

        if ($resource instanceof TemplateInterface) {
            $view['template'] = $resource->getTemplateKey();
        }

        if ($resource instanceof WebspaceInterface) {
            $view['mainWebspace'] = $resource->getMainWebspace();
        }

        if ($resource instanceof AuthorInterface) {
            $content['authored'] = $resource->getAuthored()?->format('c');
            $content['lastModified'] = $resource->getLastModified()?->format('c');
        }

        return ContentView::create($content, $view);
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
