<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Media\SmartContent;

use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\SmartContent\DatasourceItem;
use Sulu\Component\SmartContent\Orm\BaseDataProvider;
use Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Media DataProvider for SmartContent.
 */
class MediaDataProvider extends BaseDataProvider
{
    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var CollectionManagerInterface
     */
    private $collectionManager;

    public function __construct(
        DataProviderRepositoryInterface $repository,
        CollectionManagerInterface $collectionManager,
        SerializerInterface $serializer,
        RequestStack $requestStack
    ) {
        parent::__construct($repository, $serializer);

        $this->configuration = self::createConfigurationBuilder()
            ->enableTags()
            ->enableCategories()
            ->enableLimit()
            ->enablePagination()
            ->enablePresentAs()
            ->enableDatasource(
                'media-datasource@sulumedia',
                [
                    'rootUrl' => '/admin/api/collections?sortBy=title&limit=9999&locale={locale}&include-root=true',
                    'selectedUrl' => '/admin/api/collections/{datasource}?tree=true&sortBy=title&locale={locale}&include-root=true',
                    'resultKey' => 'collections',
                ]
            )
            ->getConfiguration();

        $this->requestStack = $requestStack;
        $this->collectionManager = $collectionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultPropertyParameter()
    {
        return [
            'mimetype_parameter' => new PropertyParameter('mimetype_parameter', 'mimetype', 'string'),
            'type_parameter' => new PropertyParameter('type_parameter', 'type', 'string'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function resolveDatasource($datasource, array $propertyParameter, array $options)
    {
        if (empty($datasource)) {
            return;
        }

        if ($datasource === 'root') {
            $title = 'smart-content.media.all-collections';

            return new DatasourceItem('root', $title, $title);
        }

        $entity = $this->collectionManager->getById($datasource, $options['locale']);

        return new DatasourceItem($entity->getId(), $entity->getTitle(), $entity->getTitle());
    }

    /**
     * {@inheritdoc}
     */
    protected function getOptions(
        array $propertyParameter,
        array $options = []
    ) {
        $request = $this->requestStack->getCurrentRequest();

        $queryOptions = [];

        if (array_key_exists('mimetype_parameter', $propertyParameter)) {
            $queryOptions['mimetype'] = $request->get($propertyParameter['mimetype_parameter']->getValue());
        }
        if (array_key_exists('type_parameter', $propertyParameter)) {
            $queryOptions['type'] = $request->get($propertyParameter['type_parameter']->getValue());
        }

        return array_merge($options, array_filter($queryOptions));
    }

    /**
     * {@inheritdoc}
     */
    protected function decorateDataItems(array $data)
    {
        return array_map(
            function ($item) {
                return new MediaDataItem($item);
            },
            $data
        );
    }
}
