<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Repository;

use Jackalope\Query\QOM\PropertyValue;
use Jackalope\Query\Row;
use PHPCR\ItemNotFoundException;
use PHPCR\Query\QOM\QueryObjectModelConstantsInterface;
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\PathHelper;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Component\Content\Compat\LocalizationFinderInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Compat\StructureManagerInterface;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Document\WorkflowStage;
use Sulu\Component\Content\Repository\Mapping\MappingInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Util\SuluNodeHelper;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

/**
 * Content repository which query content with sql2 statements.
 */
class ContentRepository implements ContentRepositoryInterface
{
    private static $nonFallbackProperties = [
        'uuid',
        'state',
        'order',
        'created',
        'creator',
        'changed',
        'changer',
        'published',
        'shadowOn',
        'shadowBase',
    ];

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var QueryObjectModelFactoryInterface
     */
    private $qomFactory;

    /**
     * @var LocalizationFinderInterface
     */
    private $localizationFinder;

    /**
     * @var StructureManagerInterface
     */
    private $structureManager;

    /**
     * @var SuluNodeHelper
     */
    private $nodeHelper;

    public function __construct(
        SessionManagerInterface $sessionManager,
        PropertyEncoder $propertyEncoder,
        WebspaceManagerInterface $webspaceManager,
        LocalizationFinderInterface $localizationFinder,
        StructureManagerInterface $structureManager,
        SuluNodeHelper $nodeHelper
    ) {
        $this->sessionManager = $sessionManager;
        $this->propertyEncoder = $propertyEncoder;
        $this->webspaceManager = $webspaceManager;
        $this->localizationFinder = $localizationFinder;
        $this->structureManager = $structureManager;
        $this->nodeHelper = $nodeHelper;

        $this->session = $sessionManager->getSession();
        $this->qomFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function find($uuid, $locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null)
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);
        $queryBuilder->where(
            $this->qomFactory->comparison(
                new PropertyValue('node', 'jcr:uuid'),
                '=',
                $this->qomFactory->literal($uuid)
            )
        );
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        $rows = $queryBuilder->execute();

        if (count(iterator_to_array($rows->getRows())) !== 1) {
            throw new ItemNotFoundException();
        }

        return $this->resolveContent($rows->getRows()->current(), $locale, $locales, $mapping, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findByParentUuid(
        $uuid,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        UserInterface $user = null
    ) {
        $path = $this->resolvePathByUuid($uuid);

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);
        $queryBuilder->where($this->qomFactory->childNode('node', $path));
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findByWebspaceRoot($locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null)
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);
        $queryBuilder->where(
            $this->qomFactory->childNode('node', $this->sessionManager->getContentPath($webspaceKey))
        );
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findParentsWithSiblingsByUuid(
        $uuid,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        UserInterface $user = null
    ) {
        $contentPath = $this->sessionManager->getContentPath($webspaceKey);
        $path = $this->resolvePathByUuid($uuid);

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user)
            ->orderBy($this->qomFactory->propertyValue('node', 'jcr:path'))
            ->where($this->qomFactory->childNode('node', $path));

        while (PathHelper::getPathDepth($path) > PathHelper::getPathDepth($contentPath)) {
            $path = PathHelper::getParentPath($path);
            $queryBuilder->orWhere($this->qomFactory->childNode('node', $path));
        }

        $mapping->addProperties(['order']);
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        $result = $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);

        return $this->generateTreeByPath($result);
    }

    /**
     * {@inheritdoc}
     */
    public function findByPaths(
        array $paths,
        $locale,
        MappingInterface $mapping,
        UserInterface $user = null
    ) {
        $locales = $this->getLocales();
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);

        foreach ($paths as $path) {
            $queryBuilder->orWhere(
                $this->qomFactory->sameNode('node', $path)
            );
        }
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUuids(
        array $uuids,
        $locale,
        MappingInterface $mapping,
        UserInterface $user = null
    ) {
        if (count($uuids) === 0) {
            return [];
        }

        $locales = $this->getLocales();
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user);

        foreach ($uuids as $uuid) {
            $queryBuilder->orWhere(
                $this->qomFactory->comparison(
                    $queryBuilder->qomf()->propertyValue('node', 'jcr:uuid'),
                    QueryObjectModelConstantsInterface::JCR_OPERATOR_EQUAL_TO,
                    $queryBuilder->qomf()->literal($uuid)
                )
            );
        }
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll($locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null)
    {
        $contentPath = $this->sessionManager->getContentPath($webspaceKey);

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user)
            ->where($this->qomFactory->descendantNode('node', $contentPath))
            ->orWhere($this->qomFactory->sameNode('node', $contentPath));

        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    /**
     * {@inheritdoc}
     */
    public function findAllByPortal($locale, $portalKey, MappingInterface $mapping, UserInterface $user = null)
    {
        $webspaceKey = $this->webspaceManager->findPortalByKey($portalKey)->getWebspace()->getKey();

        $contentPath = $this->sessionManager->getContentPath($webspaceKey);

        $locales = $this->getLocalesByPortalKey($portalKey);
        $queryBuilder = $this->getQueryBuilder($locale, $locales, $user)
            ->where($this->qomFactory->descendantNode('node', $contentPath))
            ->orWhere($this->qomFactory->sameNode('node', $contentPath));

        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return $this->resolveQueryBuilder($queryBuilder, $locale, $locales, $mapping, $user);
    }

    /**
     * Generates a content-tree with paths of given content array.
     *
     * @param Content[] $contents
     *
     * @return Content[]
     */
    private function generateTreeByPath(array $contents)
    {
        $childrenByPath = [];

        foreach ($contents as $content) {
            $path = PathHelper::getParentPath($content->getPath());
            if (!isset($childrenByPath[$path])) {
                $childrenByPath[$path] = [];
            }

            $order = $content['order'];
            while (isset($childrenByPath[$path][$order])) {
                ++$order;
            }

            $childrenByPath[$path][$order] = $content;
        }

        foreach ($contents as $content) {
            if (!isset($childrenByPath[$content->getPath()])) {
                continue;
            }

            ksort($childrenByPath[$content->getPath()]);
            $content->setChildren(array_values($childrenByPath[$content->getPath()]));
        }

        if (!array_key_exists('/', $childrenByPath) || !is_array($childrenByPath['/'])) {
            return [];
        }

        ksort($childrenByPath['/']);

        return array_values($childrenByPath['/']);
    }

    /**
     * Resolve path for node with given uuid.
     *
     * @param string $uuid
     *
     * @return string
     *
     * @throws ItemNotFoundException
     */
    private function resolvePathByUuid($uuid)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'))
            ->where(
                $this->qomFactory->comparison(
                    $this->qomFactory->propertyValue('node', 'jcr:uuid'),
                    '=',
                    $this->qomFactory->literal($uuid)
                )
            );

        $rows = $queryBuilder->execute();

        if (count(iterator_to_array($rows->getRows())) !== 1) {
            throw new ItemNotFoundException();
        }

        return $rows->getRows()->current()->getPath();
    }

    /**
     * Resolves query results to content.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $locale
     * @param MappingInterface $mapping
     * @param UserInterface $user
     *
     * @return Content[]
     */
    private function resolveQueryBuilder(
        QueryBuilder $queryBuilder,
        $locale,
        $locales,
        MappingInterface $mapping,
        UserInterface $user = null
    ) {
        return array_values(
            array_filter(
                array_map(
                    function (Row $row) use ($mapping, $locale, $locales, $user) {
                        return $this->resolveContent($row, $locale, $locales, $mapping, $user);
                    },
                    iterator_to_array($queryBuilder->execute())
                )
            )
        );
    }

    /**
     * Returns QueryBuilder with basic select and where statements.
     *
     * @param string $locale
     * @param string[] $locales
     * @param UserInterface $user
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder($locale, $locales, UserInterface $user = null)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('nodeType', $locale), 'nodeType')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('internal_link', $locale), 'internalLink')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('state', $locale), 'state')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('shadow-on', $locale), 'shadowOn')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('shadow-base', $locale), 'shadowBase')
            ->addSelect('node', $this->propertyEncoder->systemName('order'), 'order')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'))
            ->orderBy($this->qomFactory->propertyValue('node', 'sulu:order'));

        $this->appendSingleMapping($queryBuilder, 'template', $locales);
        $this->appendSingleMapping($queryBuilder, 'shadow-on', $locales);
        $this->appendSingleMapping($queryBuilder, 'state', $locales);

        if (null !== $user) {
            foreach ($user->getRoleObjects() as $role) {
                $queryBuilder->addSelect(
                    'node',
                    sprintf('sec:%s', 'role-' . $role->getId()),
                    sprintf('role%s', $role->getId())
                );
            }
        }

        return $queryBuilder;
    }

    /**
     * Returns array of locales for given webspace key.
     *
     * @param string $webspaceKey
     *
     * @return string[]
     */
    private function getLocalesByWebspaceKey($webspaceKey)
    {
        $webspace = $this->webspaceManager->findWebspaceByKey($webspaceKey);

        return array_map(
            function (Localization $localization) {
                return $localization->getLocalization();
            },
            $webspace->getAllLocalizations()
        );
    }

    /**
     * Returns array of locales for given portal key.
     *
     * @param string $portalKey
     *
     * @return string[]
     */
    private function getLocalesByPortalKey($portalKey)
    {
        $portal = $this->webspaceManager->findPortalByKey($portalKey);

        return array_map(
            function (Localization $localization) {
                return $localization->getLocalization();
            },
            $portal->getLocalizations()
        );
    }

    /**
     * Returns array of locales for webspaces.
     *
     * @return string[]
     */
    private function getLocales()
    {
        return array_map(
            function (Localization $localization) {
                return $localization->getLocalization();
            },
            $this->webspaceManager->getAllLocalizations()
        );
    }

    /**
     * Append mapping selects to given query-builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param MappingInterface $mapping Includes array of property names
     * @param string $locale
     * @param string[] $locales
     */
    private function appendMapping(QueryBuilder $queryBuilder, MappingInterface $mapping, $locale, $locales)
    {
        if ($mapping->onlyPublished()) {
            $queryBuilder->andWhere(
                $this->qomFactory->comparison(
                    $this->qomFactory->propertyValue(
                        'node',
                        $this->propertyEncoder->localizedSystemName('state', $locale)
                    ),
                    '=',
                    $this->qomFactory->literal(WorkflowStage::PUBLISHED)
                )
            );
        }

        $properties = $mapping->getProperties();
        foreach ($properties as $propertyName) {
            $this->appendSingleMapping($queryBuilder, $propertyName, $locales);
        }

        if ($mapping->resolveUrl()) {
            $this->appendUrlMapping($queryBuilder, $locales);
        }
    }

    /**
     * Append mapping selects for a single property to given query-builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $propertyName
     * @param string[] $locales
     */
    private function appendSingleMapping(QueryBuilder $queryBuilder, $propertyName, $locales)
    {
        foreach ($locales as $locale) {
            $alias = sprintf('%s%s', $locale, str_replace('-', '_', ucfirst($propertyName)));

            $queryBuilder->addSelect(
                'node',
                $this->propertyEncoder->localizedContentName($propertyName, $locale),
                $alias
            );
        }
    }

    /**
     * Append mapping for url to given query-builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string[] $locales
     */
    private function appendUrlMapping(QueryBuilder $queryBuilder, $locales)
    {
        $structures = $this->structureManager->getStructures(Structure::TYPE_PAGE);
        $urlNames = [];

        foreach ($structures as $structure) {
            if (!$structure->hasTag('sulu.rlp')) {
                continue;
            }

            $propertyName = $structure->getPropertyByTagName('sulu.rlp')->getName();

            if (!in_array($propertyName, $urlNames)) {
                $this->appendSingleMapping($queryBuilder, $propertyName, $locales);
                $urlNames[] = $propertyName;
            }
        }
    }

    /**
     * Resolve a single result row to a content object.
     *
     * @param Row $row
     * @param string $locale
     * @param string $locales
     * @param MappingInterface $mapping Includes array of property names
     * @param UserInterface $user
     *
     * @return Content
     */
    private function resolveContent(
        Row $row,
        $locale,
        $locales,
        MappingInterface $mapping,
        UserInterface $user = null
    ) {
        $webspaceKey = $this->nodeHelper->extractWebspaceFromPath($row->getPath());

        $originalLocale = $locale;
        $ghostLocale = $this->localizationFinder->findAvailableLocale(
            $webspaceKey,
            $this->resolveAvailableLocales($row),
            $locale
        );

        $type = null;
        if ($row->getValue('shadowOn')) {
            if (!$mapping->shouldHydrateShadow()) {
                return;
            }
            $type = StructureType::getShadow($row->getValue('shadowBase'));
        } elseif ($ghostLocale !== null && $ghostLocale !== $originalLocale) {
            if (!$mapping->shouldHydrateGhost()) {
                return;
            }
            $locale = $ghostLocale;
            $type = StructureType::getGhost($locale);
        }

        if (
            $row->getValue('nodeType') === RedirectType::INTERNAL
            && $mapping->followInternalLink()
            && $row->getValue('internalLink') !== ''
            && $row->getValue('internalLink') !== $row->getValue('uuid')
        ) {
            // TODO collect all internal link contents and query once
            return $this->resolveInternalLinkContent($row, $locale, $webspaceKey, $mapping, $type, $user);
        }

        $shadowBase = null;
        if ($row->getValue('shadowOn')) {
            $shadowBase = $row->getValue('shadowBase');
        }

        $data = [];
        foreach ($mapping->getProperties() as $item) {
            $data[$item] = $this->resolveProperty($row, $item, $locale, $shadowBase);
        }

        $content = new Content(
            $originalLocale,
            $webspaceKey,
            $row->getValue('uuid'),
            $this->resolvePath($row, $webspaceKey),
            $row->getValue('state'),
            $row->getValue('nodeType'),
            $this->resolveHasChildren($row),
            $data,
            $this->resolvePermissions($row, $user),
            $type
        );
        $content->setRow($row);

        if ($mapping->resolveUrl()) {
            $url = $this->resolveUrl($row, $locale);
            $urls = [];
            array_walk(
                $locales,
                function ($item) use (&$urls, $row) {
                    $urls[$item] = $this->resolveUrl($row, $item);
                }
            );

            $content->setUrl($url);
            $content->setUrls($urls);
        }

        if ($mapping->resolveConcreteLocales()) {
            $locales = $this->resolveAvailableLocales($row);
            $content->setConcreteLanguages($locales);
        }

        return $content;
    }

    /**
     * Resolves all available localizations for given row.
     *
     * @param Row $row
     *
     * @return string[]
     */
    private function resolveAvailableLocales(Row $row)
    {
        $locales = [];
        foreach ($row->getValues() as $key => $value) {
            if (preg_match('/^node.([a-zA-Z_]*?)Template/', $key, $matches) && '' !== $value
                && !$row->getValue(sprintf('node.%sShadow_on', $matches[1]))
            ) {
                $locales[] = $matches[1];
            }
        }

        return $locales;
    }

    /**
     * Resolve a single result row which is an internal link to a content object.
     *
     * @param Row $row
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names
     * @param StructureType $type
     * @param UserInterface $user
     *
     * @return Content
     */
    public function resolveInternalLinkContent(
        Row $row,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        StructureType $type = null,
        UserInterface $user = null
    ) {
        $linkedContent = $this->find($row->getValue('internalLink'), $locale, $webspaceKey, $mapping);
        $data = $linkedContent->getData();

        // properties which are in the intersection of the data and non
        // fallback properties should be handled on the original row.
        $properties = array_intersect(self::$nonFallbackProperties, array_keys($data));
        foreach ($properties as $property) {
            $data[$property] = $this->resolveProperty($row, $property, $locale);
        }

        return new Content(
            $locale,
            $webspaceKey,
            $row->getValue('uuid'),
            $this->resolvePath($row, $webspaceKey),
            $row->getValue('state'),
            $row->getValue('nodeType'),
            $this->resolveHasChildren($row),
            $data,
            $this->resolvePermissions($row, $user),
            $type
        );
    }

    /**
     * Resolve a property and follow shadow locale if it has one.
     *
     * @param Row $row
     * @param string $name
     * @param string $locale
     * @param string $shadowLocale
     *
     * @return mixed
     */
    private function resolveProperty(Row $row, $name, $locale, $shadowLocale = null)
    {
        if (array_key_exists(sprintf('node.%s', $name), $row->getValues())) {
            return $row->getValue($name);
        }

        if (null !== $shadowLocale && !in_array($name, self::$nonFallbackProperties)) {
            $locale = $shadowLocale;
        }

        $name = sprintf('%s%s', $locale, str_replace('-', '_', ucfirst($name)));

        try {
            return $row->getValue($name);
        } catch (ItemNotFoundException $e) {
            // the default value of a non existing property in jackalope is an empty string
            return '';
        }
    }

    /**
     * Resolve url property.
     *
     * @param Row $row
     * @param string $locale
     *
     * @return string
     */
    private function resolveUrl(Row $row, $locale)
    {
        if ($this->resolveProperty($row, $locale . 'State', $locale) !== WorkflowStage::PUBLISHED) {
            return;
        }

        $template = $this->resolveProperty($row, 'template', $locale);

        if (empty($template)) {
            return;
        }

        $structure = $this->structureManager->getStructure($template);
        if (!$structure->hasTag('sulu.rlp')) {
            return;
        }

        $propertyName = $structure->getPropertyByTagName('sulu.rlp')->getName();

        return $this->resolveProperty($row, $propertyName, $locale);
    }

    /**
     * Resolves path for given row.
     *
     * @param Row $row
     * @param string $webspaceKey
     *
     * @return string
     */
    private function resolvePath(Row $row, $webspaceKey)
    {
        return '/' . ltrim(str_replace($this->sessionManager->getContentPath($webspaceKey), '', $row->getPath()), '/');
    }

    /**
     * Resolves permissions for given user.
     *
     * @param Row $row
     * @param UserInterface $user
     *
     * @return array
     */
    private function resolvePermissions(Row $row, UserInterface $user = null)
    {
        $permissions = [];
        if (null !== $user) {
            foreach ($user->getRoleObjects() as $role) {
                foreach (array_filter(explode(' ', $row->getValue(sprintf('role%s', $role->getId())))) as $permission) {
                    $permissions[$role->getId()][$permission] = true;
                }
            }
        }

        return $permissions;
    }

    /**
     * Resolve property has-children with given node.
     *
     * @param Row $row
     *
     * @return bool
     */
    private function resolveHasChildren(Row $row)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'))
            ->where($this->qomFactory->childNode('node', $row->getPath()))
            ->setMaxResults(1);

        $result = $queryBuilder->execute();

        return count(iterator_to_array($result->getRows())) > 0;
    }
}
