<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\SmartContent;

use PHPCR\NodeInterface;
use Sulu\Bundle\TagBundle\Tag\TagManagerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Util\ArrayableInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Content type for smart selection.
 */
class ContentType extends ComplexContentType
{
    /**
     * @var string
     */
    private $template;

    /**
     * @var TagManagerInterface
     */
    private $tagManager;

    /**
     * @var DataProviderPoolInterface
     */
    private $dataProviderPool;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * Contains cached values.
     *
     * @var array
     */
    private $cache = [];

    /**
     * SmartContentType constructor.
     *
     * @param string $template
     * @param TagManagerInterface $tagManager
     * @param DataProviderPoolInterface $dataProviderPool
     * @param RequestStack $requestStack
     */
    public function __construct(
        DataProviderPoolInterface $dataProviderPool,
        TagManagerInterface $tagManager,
        RequestStack $requestStack,
        $template
    ) {
        $this->dataProviderPool = $dataProviderPool;
        $this->tagManager = $tagManager;
        $this->requestStack = $requestStack;
        $this->template = $template;
    }

    /**
     * {@inheritdoc}
     */
    public function read(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $data = $node->getPropertyValueWithDefault($property->getName(), '{}');
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        if (!empty($data['tags'])) {
            $data['tags'] = $this->tagManager->resolveTagIds($data['tags']);
        }

        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function readForPreview(
        $data,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($data instanceof ArrayableInterface) {
            $data = $data->toArray();
        }

        $property->setValue($data);
    }

    /**
     * {@inheritdoc}
     */
    public function write(
        NodeInterface $node,
        PropertyInterface $property,
        $userId,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        $value = $property->getValue();
        if ($value instanceof ArrayableInterface) {
            $value = $value->toArray();
        }

        if (!empty($value['tags'])) {
            $value['tags'] = $this->tagManager->resolveTagNames($value['tags']);
        }

        $node->setProperty($property->getName(), json_encode($value));
    }

    /**
     * {@inheritdoc}
     */
    public function remove(
        NodeInterface $node,
        PropertyInterface $property,
        $webspaceKey,
        $languageCode,
        $segmentKey
    ) {
        if ($node->hasProperty($property->getName())) {
            $node->getProperty($property->getName())->remove();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultParams(PropertyInterface $property = null)
    {
        $provider = $this->getProvider($property);
        $configuration = $provider->getConfiguration();

        $defaults = [
            'page_parameter' => new PropertyParameter('page_parameter', 'p'),
            'tag_parameter' => new PropertyParameter('tag_parameter', 'tag'),
            'sorting' => new PropertyParameter('sorting', $configuration->getSorting(), 'collection'),
            'present_as' => new PropertyParameter('present_as', [], 'collection'),
            'has_datasource' => $configuration->hasDatasource(),
            'has_tags' => $configuration->hasTags(),
            'has_categories' => $configuration->hasCategories(),
            'has_sorting' => $configuration->hasSorting(),
            'has_limit' => $configuration->hasLimit(),
            'has_present_as' => $configuration->hasPresentAs(),
            'datasource' => $configuration->getDatasource(),
        ];

        return array_merge(
            parent::getDefaultParams(),
            $defaults,
            $provider->getDefaultPropertyParameter()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return self::PRE_SAVE;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        // check memoize
        $hash = spl_object_hash($property);
        if (array_key_exists($hash, $this->cache)) {
            return $this->cache[$hash];
        }

        /** @var PropertyParameter[] $params */
        $params = array_merge(
            $this->getDefaultParams($property),
            $property->getParams()
        );

        // prepare filters
        $filters = $property->getValue();
        $filters['excluded'] = [$property->getStructure()->getUuid()];

        // resolve tags to id
        if (!empty($filters['tags'])) {
            $filters['tags'] = $this->tagManager->resolveTagNames($filters['tags']);
        }

        // get provider
        $provider = $this->getProvider($property);
        $configuration = $provider->getConfiguration();

        // prepare pagination, limitation and options
        $page = 1;
        $limit = (array_key_exists('limitResult', $filters) && $configuration->hasLimit()) ?
            $filters['limitResult'] : null;
        $options = [
            'webspaceKey' => $property->getStructure()->getWebspaceKey(),
            'locale' => $property->getStructure()->getLanguageCode(),
        ];

        if (isset($params['max_per_page']) && $configuration->getPaginated()) {
            // is paginated
            $page = $this->getCurrentPage($params['page_parameter']->getValue());
            $pageSize = intval($params['max_per_page']->getValue());

            // resolve paginated filters
            $data = $provider->resolveFilters(
                $filters,
                $params,
                $options,
                (!empty($limit) ? intval($limit) : null),
                $page,
                $pageSize
            );
        } else {
            $data = $provider->resolveFilters($filters, $params, $options, (!empty($limit) ? intval($limit) : null));
        }

        // append view data
        $filters['page'] = $page;
        $filters['hasNextPage'] = $provider->getHasNextPage();
        $filters['paginated'] = $configuration->getPaginated();
        $property->setValue($filters);

        // save result in cache
        $this->cache[$hash] = $data;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        $this->getContentData($property);
        $config = $property->getValue();

        $config = array_merge(
            [
                'dataSource' => null,
                'includeSubFolders' => null,
                'category' => null,
                'tags' => [],
                'sortBy' => null,
                'sortMethod' => null,
                'presentAs' => null,
                'limitResult' => null,
                'page' => null,
                'hasNextPage' => null,
                'paginated' => false,
            ],
            $config
        );

        return $config;
    }

    /**
     * Returns provider for given property.
     *
     * @param PropertyInterface $property
     *
     * @return DataProviderInterface
     */
    private function getProvider(PropertyInterface $property)
    {
        $params = $property->getParams();

        // default fallback to content
        $providerAlias = 'content';
        if (array_key_exists('provider', $params)) {
            $providerAlias = $params['provider']->getValue();
        }

        return $this->dataProviderPool->get($providerAlias);
    }

    /**
     * determine current page from current request.
     *
     * @param string $pageParameter
     *
     * @return int
     */
    private function getCurrentPage($pageParameter)
    {
        if ($this->requestStack->getCurrentRequest() !== null) {
            $page = $this->requestStack->getCurrentRequest()->get($pageParameter, 1);
        } else {
            $page = 1;
        }

        return intval($page);
    }
}
