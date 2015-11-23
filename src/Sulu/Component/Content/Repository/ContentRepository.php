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
use PHPCR\Query\QOM\QueryObjectModelFactoryInterface;
use PHPCR\SessionInterface;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Component\Content\Compat\LocalizationFinder;
use Sulu\Component\Content\Compat\StructureType;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Repository\Mapping\MappingInterface;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Security\Authentication\UserInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Content repository which query content with sql2 statements.
 */
class ContentRepository implements ContentRepositoryInterface
{
    // TODO bad name they should not be handled by redirects and shadow
    private static $nonFallbackProperties = [
        'uuid',
        'state',
        'order',
        'created',
        'creator',
        'changed',
        'changer',
        'shadowOn',
        'shadowBase',
        'url', // TODO non fix name in templates
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
     * @var LocalizationFinder
     */
    private $localizationFinder;

    public function __construct(
        SessionManagerInterface $sessionManager,
        PropertyEncoder $propertyEncoder,
        WebspaceManagerInterface $webspaceManager,
        LocalizationFinder $localizationFinder
    ) {
        $this->sessionManager = $sessionManager;
        $this->propertyEncoder = $propertyEncoder;
        $this->webspaceManager = $webspaceManager;
        $this->localizationFinder = $localizationFinder;

        $this->session = $sessionManager->getSession();
        $this->qomFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function find($uuid, $locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null)
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $user);
        $queryBuilder->where(
            $this->qomFactory->comparison(
                new PropertyValue('node', 'jcr:uuid'),
                '=',
                $this->qomFactory->literal($uuid)
            )
        );
        $this->appendMapping($queryBuilder, $mapping, $locales);

        $rows = $queryBuilder->execute();

        if (count(iterator_to_array($rows->getRows())) !== 1) {
            // TODO Exception
            throw new Exception();
        }

        return $this->resolveContent($rows->getRows()->current(), $locale, $webspaceKey, $mapping, $user);
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
            // TODO Exception
            throw new Exception();
        }

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $user);
        $queryBuilder->where($this->qomFactory->childNode('node', $rows->getRows()->current()->getPath()));
        $this->appendMapping($queryBuilder, $mapping, $locales);

        return array_map(
            function (Row $row) use ($mapping, $webspaceKey, $locale, $user) {
                return $this->resolveContent($row, $locale, $webspaceKey, $mapping, $user);
            },
            iterator_to_array($queryBuilder->execute())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByWebspaceRoot($locale, $webspaceKey, MappingInterface $mapping, UserInterface $user = null)
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale, $user);
        $queryBuilder->where(
            $this->qomFactory->childNode('node', $this->sessionManager->getContentPath($webspaceKey))
        );
        $this->appendMapping($queryBuilder, $mapping, $locales);

        return array_map(
            function (Row $row) use ($mapping, $webspaceKey, $locale, $user) {
                return $this->resolveContent($row, $locale, $webspaceKey, $mapping, $user);
            },
            iterator_to_array($queryBuilder->execute())
        );
    }

    /**
     * Returns QueryBuilder with basic select and where statements.
     *
     * @param string $locale
     * @param UserInterface $user
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder($locale, UserInterface $user = null)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('nodeType', $locale), 'nodeType')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('internal_link', $locale), 'internalLink')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('state', $locale), 'state')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('shadow-on', $locale), 'shadowOn')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('shadow-base', $locale), 'shadowBase')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('shadow-base', $locale), 'shadowBase')
            ->addSelect('node', $this->propertyEncoder->systemName('order'), 'order')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'))
            ->orderBy($this->qomFactory->propertyValue('node', 'sulu:order'));

        if (null !== $user) {
            foreach ($user->getRoleObjects() as $role) {
                $queryBuilder->addSelect('node', sprintf('sec:%s', 'role-' . $role->getId()), $role->getIdentifier());
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
     * Append mapping selects to given query-builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param MappingInterface $mapping Includes array of property names.
     * @param string[] $locales
     */
    private function appendMapping(QueryBuilder $queryBuilder, MappingInterface $mapping, $locales)
    {
        $properties = $mapping->getProperties();
        $properties[] = 'template';
        foreach ($properties as $propertyName) {
            $this->appendSingleMapping($queryBuilder, $propertyName, $locales);
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
        foreach ($locales as $item) {
            $alias = sprintf('%s%s', $item, ucfirst($propertyName));

            $queryBuilder->addSelect(
                'node',
                $this->propertyEncoder->localizedContentName($propertyName, $item),
                $alias
            );
        }
    }

    /**
     * Resolve a single result row to a content object.
     *
     * @param Row $row
     * @param string $locale
     * @param string $webspaceKey
     * @param MappingInterface $mapping Includes array of property names.
     * @param UserInterface $user
     *
     * @return Content
     */
    private function resolveContent(
        Row $row,
        $locale,
        $webspaceKey,
        MappingInterface $mapping,
        UserInterface $user = null
    ) {
        $originalLocale = $locale;
        $locale = $this->localizationFinder->findAvailableLocale(
            $webspaceKey,
            $this->resolveAvailableLocales($row),
            $locale
        );
        if (!$mapping->hydrateGhost() && $originalLocale !== $locale) {
            return;
        }

        $type = null;
        if ($locale !== $originalLocale) {
            $type = StructureType::getGhost($locale);
        } elseif ($row->getValue('shadowOn')) {
            if (!$mapping->hydrateShadow()) {
                return;
            }
            $type = StructureType::getShadow($row->getValue('shadowBase'));
        }

        if ($row->getValue('nodeType') === RedirectType::INTERNAL && $mapping->followInternalLink()) {
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
            if (preg_match('/^node.([a-zA-Z_]*?)Template/', $key, $matches) && '' !== $value) {
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
     * @param MappingInterface $mapping Includes array of property names.
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

        $name = sprintf('%s%s', $locale, ucfirst($name));

        return $row->getValue($name);
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
        return str_replace($this->sessionManager->getContentPath($webspaceKey), '', $row->getPath());
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
                foreach (array_filter(explode(' ', $row->getValue($role->getIdentifier()))) as $permission) {
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

        return (count(iterator_to_array($result->getRows())) > 0);
    }
}
