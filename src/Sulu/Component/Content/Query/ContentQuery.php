<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Content\Query;

use Jackalope\Query\Row;
use PHPCR\Query\QueryResultInterface;
use PHPCR\RepositoryException;
use Sulu\Component\Content\Mapper\Translation\TranslatedProperty;
use Sulu\Component\Content\PropertyInterface;
use Sulu\Component\Content\StructureInterface;
use Sulu\Component\Content\StructureManagerInterface;
use Sulu\Component\Content\Template\TemplateResolverInterface;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;

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
     * @var string
     */
    private $languageNamespace;

    function __construct(
        SessionManagerInterface $sessionManager,
        StructureManagerInterface $structureManager,
        TemplateResolverInterface $templateResolver,
        $languageNamespace
    ) {
        $this->languageNamespace = $languageNamespace;
        $this->sessionManager = $sessionManager;
        $this->structureManager = $structureManager;
        $this->templateResolver = $templateResolver;
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
    )
    {
        list($sql2, $fields) = $contentQueryBuilder->build($webspaceKey, $locales);

        $query = $this->createSql2Query($sql2);
        $queryResult = $query->execute();

        $result = $this->rowsToList($queryResult, $webspaceKey, $locales, $fields, $depth);

        if (!$flat) {
            $converter = new ListToTreeConverter($this->sessionManager);
            $result = $converter->convert($result, $webspaceKey);
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
        $uuid = $row->getValue('page.jcr:uuid');

        $templateKey = $row->getValue('page.i18n:' . $locale . '-template');
        $changed = $row->getValue('page.i18n:' . $locale . '-changed');
        $nodeType = $row->getValue('page.i18n:' . $locale . '-nodeType');
        if ($templateKey !== '') {
            $path = $row->getPath('page');
            $templateKey = $this->templateResolver->resolve($nodeType, $templateKey);
            $structure = $this->structureManager->getStructure($templateKey);

            $fieldsData = array();
            foreach ($fields[$locale] as $field) {
                if (!isset($fieldsData[$field['target']])) {
                    $fieldsData[$field['target']] = array();
                }

                $fieldsData[$field['target']][$field['name']] = $row->getValue($field['column']);
            }

            return array_merge(
                array(
                    'uuid' => $uuid,
                    'nodeType' => $nodeType,
                    'path' => $path,
                    'changed' => $changed,
                    'title' => $this->getTitle($row, $structure, $locale),
                    'url' => $this->getUrl($path, $row, $structure, $webspaceKey, $locale, $routesPath),
                    'locale' => $locale
                ),
                $fieldsData
            );
        }

        return false;
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
