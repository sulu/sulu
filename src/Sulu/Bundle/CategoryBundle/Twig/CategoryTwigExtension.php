<?php
/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\CategoryBundle\Twig;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Component\Cache\MemoizeInterface;
use Sulu\Component\Category\Request\CategoryRequestHandlerInterface;

/**
 * Provides functionality to handle categories in twig templates.
 */
class CategoryTwigExtension extends \Twig_Extension
{
    /**
     * @var CategoryManagerInterface
     */
    private $categoryManager;

    /**
     * @var CategoryRequestHandlerInterface
     */
    private $categoryRequestHandler;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var MemoizeInterface
     */
    private $memoizeCache;

    public function __construct(
        CategoryManagerInterface $categoryManager,
        CategoryRequestHandlerInterface $categoryRequestHandler,
        SerializerInterface $serializer,
        MemoizeInterface $memoizeCache
    ) {
        $this->categoryManager = $categoryManager;
        $this->categoryRequestHandler = $categoryRequestHandler;
        $this->serializer = $serializer;
        $this->memoizeCache = $memoizeCache;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('sulu_categories', [$this, 'getCategoriesFunction']),
            new \Twig_SimpleFunction('sulu_category_url', [$this, 'setCategoryUrlFunction']),
            new \Twig_SimpleFunction('sulu_category_url_append', [$this, 'appendCategoryUrlFunction']),
            new \Twig_SimpleFunction('sulu_category_url_clear', [$this, 'clearCategoryUrlFunction']),
        ];
    }

    /**
     * Returns an array of serialized categories.
     *
     * @param string $locale
     * @param int $parent id of parent category. null for root.
     * @param int $depth number of children.
     *
     * @return array
     */
    public function getCategoriesFunction($locale, $parent = null)
    {
        return $this->memoizeCache->memoize(
            function ($locale, $parent = null) {
                if (null === $parent) {
                    $entities = $this->categoryManager->find();
                } else {
                    $entities = $this->categoryManager->findChildren($parent);
                }
                $apiEntities = $this->categoryManager->getApiObjects($entities, $locale);

                $context = SerializationContext::create();
                $context->setSerializeNull(true);

                return $this->serializer->serialize($apiEntities, 'array', $context);
            }
        );
    }

    /**
     * Extends current URL with given category.
     *
     * @param array $category will be included in the URL.
     * @param string $categoriesParameter GET parameter name.
     *
     * @return string
     */
    public function appendCategoryUrlFunction($category, $categoriesParameter = 'categories')
    {
        return $this->categoryRequestHandler->appendCategoryToUrl($category, $categoriesParameter);
    }

    /**
     * Set category to current URL.
     *
     * @param array $category will be included in the URL.
     * @param string $categoriesParameter GET parameter name.
     *
     * @return string
     */
    public function setCategoryUrlFunction($category, $categoriesParameter = 'categories')
    {
        return $this->categoryRequestHandler->setCategoryToUrl($category, $categoriesParameter);
    }

    /**
     * Remove categories from current URL.
     *
     * @param string $categoriesParameter GET parameter name.
     *
     * @return string
     */
    public function clearCategoryUrlFunction($categoriesParameter = 'categories')
    {
        return $this->categoryRequestHandler->removeCategoriesFromUrl($categoriesParameter);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sulu_category';
    }
}
