<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\RouteBundle\Content\Type;

use Doctrine\ORM\EntityManagerInterface;
use PHPCR\ItemNotFoundException;
use PHPCR\NodeInterface;
use PHPCR\PropertyType;
use Sulu\Bundle\RouteBundle\Entity\Route;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;

/**
 * Provides page_tree_route content-type.
 */
class PageTreeRouteContentType extends SimpleContentType
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var DocumentRegistry
     */
    private $documentRegistry;

    /**
     * @var ChainRouteGeneratorInterface
     */
    private $chainRouteGenerator;

    /**
     * @var ConflictResolverInterface
     */
    private $conflictResolver;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentRegistry $documentRegistry,
        ChainRouteGeneratorInterface $chainRouteGenerator,
        ConflictResolverInterface $conflictResolver,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct('PageTreeRoute');

        $this->documentManager = $documentManager;
        $this->documentRegistry = $documentRegistry;
        $this->chainRouteGenerator = $chainRouteGenerator;
        $this->conflictResolver = $conflictResolver;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $propertyName = $property->getName();
        $value = [
            'page' => $this->readPage($propertyName, $node),
            'path' => $node->getPropertyValueWithDefault($propertyName, ''),
            'suffix' => $node->getPropertyValueWithDefault($propertyName . '-suffix', ''),
        ];

        $property->setValue($value);

        return $value;
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
        if (!$value) {
            return $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }

        $page = $this->getAttribute('page', $value, ['uuid' => null, 'path' => '/']);

        $suffix = $this->getAttribute('suffix', $value);
        if (!$suffix) {
            $suffix = $this->generateSuffix($node, $languageCode);
        } else {
            $suffix = trim($suffix, '/');
        }

        $path = rtrim($page['path'], '/') . '/' . $suffix;
        $path = $this->resolveConflicts($path);
        $suffix = '/' . $this->getSuffix($path, $page['path'] ?? '/');

        $propertyName = $property->getName();
        $node->setProperty($propertyName, $path);
        $node->setProperty($propertyName . '-suffix', $suffix);

        $pagePropertyName = $propertyName . '-page';
        if ($node->hasProperty($pagePropertyName)) {
            $node->getProperty($pagePropertyName)->remove();
        }

        if (!$page['uuid']) {
            // no parent-page given

            return null;
        }

        $node->setProperty($pagePropertyName, $page['uuid'], PropertyType::WEAKREFERENCE);
        $node->setProperty($pagePropertyName . '-path', $page['path']);

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentData(PropertyInterface $property)
    {
        $value = parent::getContentData($property);

        return $value['path'];
    }

    /**
     * {@inheritdoc}
     */
    public function getViewData(PropertyInterface $property)
    {
        return $property->getValue();
    }

    /**
     * Read page-information from given node.
     *
     * @return mixed[]|null
     */
    private function readPage(string $propertyName, NodeInterface $node)
    {
        $pagePropertyName = $propertyName . '-page';
        if (!$node->hasProperty($pagePropertyName)) {
            return null;
        }

        try {
            $pageUuid = $node->getPropertyValue($pagePropertyName, PropertyType::STRING);
        } catch (ItemNotFoundException $exception) {
            return null;
        }

        return [
            'uuid' => $pageUuid,
            'path' => $node->getPropertyValueWithDefault($pagePropertyName . '-path', ''),
        ];
    }

    /**
     * Get value of array or default.
     *
     * @param mixed[] $value
     * @param mixed $default
     *
     * @return mixed
     */
    private function getAttribute(string $name, array $value, $default = null)
    {
        if (!array_key_exists($name, $value)) {
            return $default;
        }

        return $value[$name];
    }

    /**
     * Generate a new suffix for document.
     */
    private function generateSuffix(NodeInterface $node, string $locale): string
    {
        $document = $this->documentRegistry->getDocumentForNode($node, $locale);
        $route = $this->chainRouteGenerator->generate($document);

        return trim($route->getPath(), '/');
    }

    /**
     * Get suffix of given path.
     */
    private function getSuffix(string $path, string $pagePath): string
    {
        return trim(substr($path, strlen(rtrim($pagePath, '/')) + 1), '/');
    }

    /**
     * Resolve conflicts of given path.
     */
    private function resolveConflicts(string $path): string
    {
        $route = $this->getRouteRepository()->createNew();
        $route->setPath($path);
        $route = $this->conflictResolver->resolve($route);

        return $route->getPath();
    }

    /**
     * Get RouteRepository
     */
    private function getRouteRepository(): RouteRepositoryInterface
    {
        return $this->entityManager->getRepository(Route::class);
    }
}
