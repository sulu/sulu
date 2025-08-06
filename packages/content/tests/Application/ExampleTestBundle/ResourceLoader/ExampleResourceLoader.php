<?php

namespace Sulu\Content\Tests\Application\ExampleTestBundle\ResourceLoader;

use Sulu\Content\Application\ResourceLoader\Loader\ResourceLoaderInterface;
use Sulu\Content\Tests\Application\ExampleTestBundle\Repository\ExampleRepository;

class ExampleResourceLoader implements ResourceLoaderInterface
{
    public const RESOURCE_LOADER_KEY = 'example';

    public function __construct(
        private ExampleRepository $exampleRepository,
    ) {
    }

    /**
     * @param string[] $ids
     */
    public function load(array $ids, ?string $locale, array $params = []): array
    {
        $result = $this->exampleRepository->findBy(['ids' => $ids]);

        $mappedResult = [];
        foreach ($result as $example) {
            $mappedResult[$example->getId()] = $example;
        }

        return $mappedResult;
    }

    public static function getKey(): string
    {
        return self::RESOURCE_LOADER_KEY;
    }
}
