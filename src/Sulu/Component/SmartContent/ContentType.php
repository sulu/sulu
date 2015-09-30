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
use Sulu\Component\Category\Request\CategoryRequestHandlerInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\Compat\PropertyParameter;
use Sulu\Component\Content\ComplexContentType;
use Sulu\Component\Tag\Request\TagRequestHandlerInterface;
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
     * @var TagRequestHandlerInterface
     */
    private $tagRequestHandler;

    /**
     * @var CategoryRequestHandlerInterface
     */
    private $categoryRequestHandler;

    /**
     * SmartContentType constructor.
     *
     * @param DataProviderPoolInterface $dataProviderPool
     * @param TagManagerInterface $tagManager
     * @param RequestStack $requestStack
     * @param TagRequestHandlerInterface $tagRequestHandler
     * @param CategoryRequestHandlerInterface $categoryRequestHandler
     * @param string $template
     */
    public function __construct(
        DataProviderPoolInterface $dataProviderPool,
        TagManagerInterface $tagManager,
        RequestStack $requestStack,
        TagRequestHandlerInterface $tagRequestHandler,
        CategoryRequestHandlerInterface $categoryRequestHandler,
        $template
    ) {
        $this->dataProviderPool = $dataProviderPool;
        $this->tagManager = $tagManager;
        $this->requestStack = $requestStack;
        $this->tagRequestHandler = $tagRequestHandler;
        $this->categoryRequestHandler = $categoryRequestHandler;
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
            'provider' => new PropertyParameter('provider', 'content'),
            'page_parameter' => new PropertyParameter('page_parameter', 'p'),
            'tags_parameter' => new PropertyParameter('tags_parameter', 'tags'),
            'categories_parameter' => new PropertyParameter('categories_parameter', 'categories'),
            'website_tag_operator' => new PropertyParameter('website_tag_operator', 'OR'),
            'website_category_operator' => new PropertyParameter('website_category_operator', 'OR'),
            'sorting' => new PropertyParameter('sorting', $configuration->getSorting(), 'collection'),
            'present_as' => new PropertyParameter('present_as', [], 'collection'),
            'category_root' => new PropertyParameter('category_root', null),
            'has' => [
                'datasource' => $configuration->hasDatasource(),
                'tags' => $configuration->hasTags(),
                'categories' => $configuration->hasCategories(),
                'sorting' => $configuration->hasSorting(),
                'limit' => $configuration->hasLimit(),
                'presentAs' => $configuration->hasPresentAs(),
            ],
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

        // default value of tags/category is an empty array
        if (!array_key_exists('tags', $filters)) {
            $filters['tags'] = [];
        }
        if (!array_key_exists('categories', $filters)) {
            $filters['categories'] = [];
        }

        // extends selected filter with requested tags
        $filters['websiteTags'] = $this->tagRequestHandler->getTags($params['tags_parameter']->getValue());
        $filters['websiteTagOperator'] = $params['website_tag_operator']->getValue();

        // extends selected filter with requested categories
        $filters['websiteCategories'] = $this->categoryRequestHandler->getCategories(
            $params['categories_parameter']->getValue()
        );
        $filters['websiteCategoryOperator'] = $params['website_category_operator']->getValue();

        // resolve tags to id
        if (!empty($filters['tags'])) {
            $filters['tags'] = $this->tagManager->resolveTagNames($filters['tags']);
        }

        // resolve website tags to id
        if (!empty($filters['websiteTags'])) {
            $filters['websiteTags'] = $this->tagManager->resolveTagNames($filters['websiteTags']);
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
            $data = $provider->resolveResourceItems(
                $filters,
                $params,
                $options,
                (!empty($limit) ? intval($limit) : null),
                $page,
                $pageSize
            );
        } else {
            $data = $provider->resolveResourceItems(
                $filters,
                $params,
                $options,
                (!empty($limit) ? intval($limit) : null)
            );
        }

        // append view data
        $filters['page'] = $page;
        $filters['hasNextPage'] = $data->getHasNextPage();
        $filters['referencedUuids'] = $data->getReferencedUuids();
        $filters['paginated'] = $configuration->getPaginated();
        $property->setValue($filters);

        // save result in cache
        return $this->cache[$hash] = $data->getItems();
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        /** @var PropertyParameter[] $params */
        $params = array_merge(
            $this->getDefaultParams($property),
            $property->getParams()
        );

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
                'referencedUuids' => [],
                'categoryRoot' => $params['category_root']->getValue(),
            ],
            $config
        );

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public function getReferencedUuids(PropertyInterface $property)
    {
        $value = $property->getValue();

        if (!array_key_exists('referencedUuids', $value)) {
            return [];
        }

        return $value['referencedUuids'];
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
     * Determine current page from current request.
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
