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

use PHPCR\NodeInterface;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\RouteBundle\Generator\ChainRouteGeneratorInterface;
use Sulu\Bundle\RouteBundle\Manager\ConflictResolverInterface;
use Sulu\Component\Content\Compat\PropertyInterface;
use Sulu\Component\Content\SimpleContentType;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Sulu\Component\DocumentManager\DocumentRegistry;
use Sulu\Component\DocumentManager\Exception\DocumentManagerException;

/**
 * Provides simple route edit.
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

    public function __construct(
        DocumentManagerInterface $documentManager,
        DocumentRegistry $documentRegistry,
        ChainRouteGeneratorInterface $chainRouteGenerator,
        ConflictResolverInterface $conflictResolver
    ) {
        parent::__construct('MyPapeTreeRoute');

        $this->documentManager = $documentManager;
        $this->documentRegistry = $documentRegistry;
        $this->chainRouteGenerator = $chainRouteGenerator;
        $this->conflictResolver = $conflictResolver;
    }

    /**
     * {@inheritdoc}
     */
    public function read(NodeInterface $node, PropertyInterface $property, $webspaceKey, $languageCode, $segmentKey)
    {
        $propertyName = $property->getName();
        $value = [
            'page' => $node->getPropertyValueWithDefault($propertyName . '-page', null),
            'suffix' => $node->getPropertyValueWithDefault($propertyName . '-suffix', null),
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
            $this->remove($node, $property, $webspaceKey, $languageCode, $segmentKey);
        }

        $page = $this->getAttribute('page', $value);
        $suffix = $this->getAttribute('suffix', $value);

        if (!$suffix || !trim($suffix, '/')) {
            $pagePath = $this->getAttribute('pagePath', $value);

            if (!$pagePath) {
                $pagePath = '/';

                if ($page) {
                    try {
                        /** @var BasePageDocument $pageDocument */
                        $pageDocument = $this->documentManager->find($page);

                        $pagePath = $pageDocument->getPath();
                    } catch (DocumentManagerException $e) {
                    }
                }
            }

            $suffix = $this->generateSuffix($node, $languageCode, $pagePath);
        }

        $propertyName = $property->getName();
        $node->setProperty($propertyName . '-page', $page);
        $node->setProperty($propertyName . '-suffix', $suffix);
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
     * Returns value of array or default.
     *
     * @param mixed[]|null $value
     * @param mixed $default
     *
     * @return mixed
     */
    private function getAttribute(string $name, ?array $value, $default = null)
    {
        return $value[$name] ?? $default;
    }

    /**
     * Generate a new suffix for document.
     */
    private function generateSuffix(NodeInterface $node, string $locale, string $pagePath): string
    {
        $document = $this->documentRegistry->getDocumentForNode($node, $locale);
        $route = $this->chainRouteGenerator->generate($document);
        $route->setPath(rtrim($pagePath, '/') . '/' . ltrim($route->getPath(), '/'));

        $route = $this->conflictResolver->resolve($route);

        return substr($route->getPath(), strlen(rtrim($pagePath, '/')) + 1);
    }
}
