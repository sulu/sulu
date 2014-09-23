<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Query;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Jackalope\Query\Row;
use PHPCR\NodeInterface;
use PHPCR\Query\QueryResultInterface;
use PHPCR\RepositoryException;
use Sulu\Component\Content\ContentTypeManagerInterface;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureExtension\StructureExtension;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Template\TemplateResolverInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Util\ArrayableInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Executes a query over the content
 */
class ContentQuery implements ContentQueryInterface
{
    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var TemplateResolverInterface
     */
    private $templateResolver;

    /**
     * @var ContentTypeManagerInterface
     */
    private $contentTypeManager;

    /**
     * @var string
     */
    private $languageNamespace;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @var Cache
     */
    private $extensionDataCache;

    function __construct(
        SessionManagerInterface $sessionManager,
        StructureManagerInterface $structureManager,
        TemplateResolverInterface $templateResolver,
        ContentTypeManagerInterface $contentTypeManager,
        $languageNamespace,
        Stopwatch $stopwatch = null
    ) {
        $this->languageNamespace = $languageNamespace;
        $this->sessionManager = $sessionManager;
        $this->structureManager = $structureManager;
        $this->templateResolver = $templateResolver;
        $this->contentTypeManager = $contentTypeManager;
        $this->stopwatch = $stopwatch;

        $this->initializeExtensionCache();
    }

    private function initializeExtensionCache()
    {
        $this->extensionDataCache = new ArrayCache();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(
        $webspaceKey,
        $locales,
        ContentQueryBuilderInterface $contentQueryBuilder,
        $flat = true,
        $depth = -1
    ) {
        if ($this->stopwatch) {
            $this->stopwatch->start('ContentQuery::execute.build-query');
        }

        list($sql2, $fields) = $contentQueryBuilder->build($webspaceKey, $locales);

        if ($this->stopwatch) {
            $this->stopwatch->stop('ContentQuery::execute.build-query');
            $this->stopwatch->start('ContentQuery::execute.execute-query');
        }

        $query = $this->createSql2Query($sql2);
        $queryResult = $query->execute();

        if ($this->stopwatch) {
            $this->stopwatch->stop('ContentQuery::execute.execute-query');
            $this->stopwatch->start('ContentQuery::execute.rowsToList');
        }

        $result = $this->rowsToList($queryResult, $webspaceKey, $locales, $fields, $depth);

        if ($this->stopwatch) {
            $this->stopwatch->stop('ContentQuery::execute.rowsToList');
        }

        if (!$flat) {
            if ($this->stopwatch) {
                $this->stopwatch->start('ContentQuery::execute.build-tree');
            }

            $converter = new ListToTreeConverter($this->sessionManager);
            $result = $converter->convert($result, $webspaceKey);

            if ($this->stopwatch) {
                $this->stopwatch->stop('ContentQuery::execute.build-tree');
            }
        }

        return $result;
    }

    /**
     * converts a query result in a list of arrays
     */
    private function rowsToList(QueryResultInterface $queryResult, $webspaceKey, $locales, $fields, $maxDepth)
    {
        $rootDepth = substr_count($this->sessionManager->getContentNode($webspaceKey)->getPath(), '/');

        $result = array();
        foreach ($locales as $locale) {
            $routesPath = $this->sessionManager->getRouteNode($webspaceKey, $locale)->getPath();

            /** @var \Jackalope\Query\Row $row */
            foreach ($queryResult->getRows() as $row) {
                $pageDepth = substr_count($row->getPath('page'), '/') - $rootDepth;

                if ($maxDepth === null || $maxDepth < 0 || ($maxDepth > 0 && $pageDepth <= $maxDepth)) {
                    $item = $this->rowToArray($row, $locale, $webspaceKey, $routesPath, $fields);

                    if (false !== $item && !in_array($item, $result)) {
                        $result[] = $item;
                    }
                }
            };
        }

        return $result;
    }

    /**
     * converts a query row to an array
     */
    private function rowToArray(Row $row, $locale, $webspaceKey, $routesPath, $fields)
    {
        // reset cache
        $this->initializeExtensionCache();

        // load default data
        $uuid = $row->getValue('page.jcr:uuid');

        $templateKey = $row->getValue('page.i18n:' . $locale . '-template');
        $nodeType = $row->getValue('page.i18n:' . $locale . '-nodeType');

        $changed = $row->getValue('page.i18n:' . $locale . '-changed');
        $changer = $row->getValue('page.i18n:' . $locale . '-changer');
        $created = $row->getValue('page.i18n:' . $locale . '-created');
        $creator = $row->getValue('page.i18n:' . $locale . '-creator');

        if ($templateKey !== '') {
            $path = $row->getPath('page');

            // get structure
            $templateKey = $this->templateResolver->resolve($nodeType, $templateKey);
            $structure = $this->structureManager->getStructure($templateKey);

            // generate field data
            $fieldsData = $this->getFieldsData($row, $fields, $templateKey, $webspaceKey, $locale);

            return array_merge(
                array(
                    'uuid' => $uuid,
                    'nodeType' => $nodeType,
                    'path' => str_replace($this->sessionManager->getContentNode($webspaceKey)->getPath(), '', $path),
                    'changed' => $changed,
                    'changer' => $changer,
                    'created' => $created,
                    'creator' => $creator,
                    'title' => $this->getTitle($row, $structure, $locale),
                    'url' => $this->getUrl($path, $row, $structure, $webspaceKey, $locale, $routesPath),
                    'locale' => $locale,
                    'template' => $templateKey
                ),
                $fieldsData
            );
        }

        return false;
    }

    private function getFieldsData(Row $row, $fields, $templateKey, $webspaceKey, $locale)
    {
        $fieldsData = array();
        foreach ($fields[$locale] as $field) {
            // determine target for data in result array
            if (isset($fieldsData['target'])) {
                if (!isset($fieldsData[$field['target']])) {
                    $fieldsData[$field['target']] = array();
                }
                $target = & $fieldsData[$field['target']];
            } else {
                $target = & $fieldsData;
            }

            // create target
            if (!isset($target[$field['name']])) {
                $target[$field['name']] = '';
            }
            if (($data = $this->getFieldData($field, $row, $templateKey, $webspaceKey, $locale)) !== null) {
                $target[$field['name']] = $data;
            }
        }

        return $fieldsData;
    }

    private function getFieldData($field, Row $row, $templateKey, $webspaceKey, $locale)
    {
        if (!isset($field['property'])) {
            // normal data from node property
            return $row->getValue($field['column']);
        } elseif (!isset($field['extension']) && (!isset($field['templateKey']) || $field['templateKey'] === $templateKey)) {
            // not extension data but property of node
            return $this->getPropertyData($row->getNode('page'), $field['property'], $webspaceKey, $locale);
        } elseif (isset($field['extension'])) {
            // data from extension
            return $this->getExtensionData(
                $row->getNode('page'),
                $field['extension'],
                $field['property'],
                $webspaceKey,
                $locale
            );
        }

        return null;
    }

    /**
     * Returns data for property
     */
    private function getPropertyData(NodeInterface $node, PropertyInterface $property, $webspaceKey, $locale)
    {
        $contentType = $this->contentTypeManager->get($property->getContentTypeName());

        $contentType->read(
            $node,
            $this->getTranslatedProperty($property, $locale),
            $webspaceKey,
            $locale,
            null
        );

        return $contentType->getContentData($property);
    }

    /**
     * Returns data for extension and property name
     */
    private function getExtensionData(
        NodeInterface $node,
        StructureExtension $extension,
        $propertyName,
        $webspaceKey,
        $locale
    ) {
        // extension data: load ones
        if (!$this->extensionDataCache->contains($extension->getName())) {
            $this->extensionDataCache->save(
                $extension->getName(),
                $this->loadExtensionData(
                    $node,
                    $extension,
                    $webspaceKey,
                    $locale
                )
            );
        }

        // get extension data from cache
        $data = $this->extensionDataCache->fetch($extension->getName());

        // if property exists set it to target (with default value '')
        return isset($data[$propertyName]) ? $data[$propertyName] : null;
    }

    /**
     * load data from extension
     */
    private function loadExtensionData(NodeInterface $node, StructureExtension $extension, $webspaceKey, $locale)
    {
        $extension->setLanguageCode($locale, $this->languageNamespace, '');
        $data = $extension->load(
            $node,
            $webspaceKey,
            $locale
        );

        // insure array
        if ($data instanceof ArrayableInterface) {
            $data = $data->toArray();
        }

        return $data;
    }

    /**
     * Returns title of a row
     */
    private function getTitle(Row $row, StructureInterface $structure, $locale)
    {
        return $row->getValue(
            'page.' . $this->getTranslatedProperty(
                $structure->getPropertyByTagName('sulu.node.name'),
                $locale
            )->getName()
        );
    }

    /**
     * Returns url of a row
     */
    private function getUrl($path, Row $row, StructureInterface $structure, $webspaceKey, $locale, $routesPath)
    {
        $url = '';
        // if homepage
        if ($this->sessionManager->getContentNode($webspaceKey)->getPath() === $path) {
            $url = '/';
        } else {
            if ($structure->hasTag('sulu.rlp')) {
                $property = $structure->getPropertyByTagName('sulu.rlp');

                if ($property->getContentTypeName() !== 'resource_locator') {
                    $url = $row->getValue(
                        'page.' . $this->getTranslatedProperty(
                            $structure->getPropertyByTagName('sulu.rlp'),
                            $locale
                        )->getName()
                    );
                }
            }

            try {
                $routePath = $row->getPath('route');
                $url = str_replace($routesPath, '', $routePath);
            } catch (RepositoryException $ex) {
                // ignore exception because no route node exists
                // could have several reasons:
                //  - external links has text-line as "rlp"
                //  - internal links has a "reference" on another node
                //  - no url exists
            }
        }

        return $url;
    }

    /**
     * returns a sql2 query
     */
    private function createSql2Query($sql2, $limit = null)
    {
        $queryManager = $this->sessionManager->getSession()->getWorkspace()->getQueryManager();
        $query = $queryManager->createQuery($sql2, 'JCR-SQL2');
        if ($limit) {
            $query->setLimit($limit);
        }

        return $query;
    }

    /**
     * Returns a translated property
     */
    private function getTranslatedProperty(PropertyInterface $property, $locale)
    {
        return new TranslatedProperty($property, $locale, $this->languageNamespace);
    }
}
