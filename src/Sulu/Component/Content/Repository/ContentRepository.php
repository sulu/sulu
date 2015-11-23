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
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
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

    public function __construct(
        SessionManagerInterface $sessionManager,
        PropertyEncoder $propertyEncoder,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->sessionManager = $sessionManager;
        $this->propertyEncoder = $propertyEncoder;
        $this->webspaceManager = $webspaceManager;

        $this->session = $sessionManager->getSession();
        $this->qomFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();
    }

    /**
     * {@inheritdoc}
     */
    public function find($uuid, $locale, $webspaceKey, $mapping = [])
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale);
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
            // TODO Exception
            throw new Exception();
        }

        return $this->resolveContent($rows->getRows()->current(), $locale, $webspaceKey, $mapping);
    }

    /**
     * {@inheritdoc}
     */
    public function findByParentUuid($uuid, $locale, $webspaceKey, $mapping = [])
    {
        // TODO only load needed data
        $node = $this->session->getNodeByIdentifier($uuid);

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale);
        $queryBuilder->where($this->qomFactory->descendantNode('node', $node->getPath()));
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return array_map(
            function (Row $row) use ($mapping, $webspaceKey, $locale) {
                return $this->resolveContent($row, $locale, $webspaceKey, $mapping);
            },
            iterator_to_array($queryBuilder->execute())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByWebspaceRoot($locale, $webspaceKey, $mapping = [])
    {
        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($locale);
        $queryBuilder->where(
            $this->qomFactory->descendantNode('node', $this->sessionManager->getContentPath($webspaceKey))
        );
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return array_map(
            function (Row $row) use ($mapping, $webspaceKey, $locale) {
                return $this->resolveContent($row, $locale, $webspaceKey, $mapping);
            },
            iterator_to_array($queryBuilder->execute())
        );
    }

    /**
     * Returns QueryBuilder with basic select and where statements.
     *
     * @param string $locale
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder($locale)
    {
        $queryBuilder = new QueryBuilder($this->qomFactory);

        return $queryBuilder
            ->select('node', 'jcr:uuid', 'uuid')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('nodeType', $locale), 'nodeType')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('internal_link', $locale), 'internalLink')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('state', $locale), 'state')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('shadow-on', $locale), 'shadowOn')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('shadow-base', $locale), 'shadowBase')
            ->from($this->qomFactory->selector('node', 'nt:unstructured'));
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
     * @param string[] $mapping array of property names.
     * @param string $locale
     * @param string[] $locales
     */
    private function appendMapping(QueryBuilder $queryBuilder, $mapping, $locale, $locales)
    {
        foreach ($mapping as $propertyName) {
            $this->appendSingleMapping($queryBuilder, $propertyName, $locale, $locales);
        }
    }

    /**
     * Append mapping selects for a single property to given query-builder.
     *
     * @param QueryBuilder $queryBuilder
     * @param string $propertyName
     * @param string $locale
     * @param string[] $locales
     */
    private function appendSingleMapping(QueryBuilder $queryBuilder, $propertyName, $locale, $locales)
    {
        foreach ($locales as $item) {
            $alias = sprintf('%s%s', $item, ucfirst($propertyName));

            if ($locale === $item) {
                $alias = $propertyName;
            }

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
     * @param string[] $mapping array of property names.
     *
     * @return Content
     */
    private function resolveContent(Row $row, $locale, $webspaceKey, $mapping)
    {
        if ($row->getValue('nodeType') === RedirectType::INTERNAL) {
            return $this->resolveInternalLinkContent($row, $locale, $webspaceKey, $mapping);
        }

        $data = [];
        foreach ($mapping as $item) {
            $data[$item] = $this->resolveProperty(
                $row,
                $item,
                $row->getValue('shadowOn') ? $row->getValue('shadowBase') : null
            );
        }

        return new Content(
            $row->getValue('uuid'),
            $this->resolvePath($row, $webspaceKey),
            $data
        );
    }

    /**
     * Resolve a single result row which is an internal link to a content object.
     *
     * @param Row $row
     * @param string $locale
     * @param string $webspaceKey
     * @param string[] $mapping array of property names.
     *
     * @return Content
     */
    public function resolveInternalLinkContent(Row $row, $locale, $webspaceKey, $mapping)
    {
        $linkedContent = $this->find($row->getValue('internalLink'), $locale, $webspaceKey, $mapping);
        $data = $linkedContent->getData();

        // properties which are in the intersection of the data and non
        // fallback properties should be handled on the original row.
        $properties = array_intersect(self::$nonFallbackProperties, array_keys($data));
        foreach ($properties as $property) {
            $data[$property] = $this->resolveProperty($row, $property);
        }

        return new Content($row->getValue('uuid'), $this->resolvePath($row, $webspaceKey), $data);
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
    private function resolveProperty(Row $row, $name, $shadowLocale = null)
    {
        if (null !== $shadowLocale && !in_array($name, self::$nonFallbackProperties)) {
            $name = sprintf('%s%s', $shadowLocale, ucfirst($name));
        }

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
}
