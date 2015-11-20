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

use Jackalope\Query\Row;
use PHPCR\SessionInterface;
use PHPCR\Util\QOM\QueryBuilder;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\Localization\Localization;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;

class ContentRepository
{
    const NON_SHADOW_PROPERTIES = [
        'created',
        'changed',
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

    public function __construct(
        SessionManagerInterface $sessionManager,
        PropertyEncoder $propertyEncoder,
        WebspaceManagerInterface $webspaceManager
    ) {
        $this->sessionManager = $sessionManager;
        $this->propertyEncoder = $propertyEncoder;
        $this->webspaceManager = $webspaceManager;
        $this->session = $sessionManager->getSession();
    }

    /**
     * @param string $uuid
     * @param string $locale
     * @param string $webspaceKey
     * @param array $mapping
     *
     * @return Content[]
     */
    public function findByParentUuid($uuid, $locale, $webspaceKey, $mapping = [])
    {
        // TODO only load needed data
        $node = $this->session->getNodeByIdentifier($uuid);

        $locales = $this->getLocalesByWebspaceKey($webspaceKey);
        $queryBuilder = $this->getQueryBuilder($node->getPath(), $locale);
        $this->appendMapping($queryBuilder, $mapping, $locale, $locales);

        return array_map(
            function (Row $row) use ($mapping, $webspaceKey) {
                return $this->resolveContent($row, $mapping, $webspaceKey);
            },
            iterator_to_array($queryBuilder->execute())
        );
    }

    /**
     * Returns QueryBuilder with basic select and where statements.
     *
     * @param string $parentPath
     * @param string $locale
     *
     * @return QueryBuilder
     */
    private function getQueryBuilder($parentPath, $locale)
    {
        $qomFactory = $this->session->getWorkspace()->getQueryManager()->getQOMFactory();

        $queryBuilder = new QueryBuilder($qomFactory);

        return $queryBuilder
            ->select('node', $this->propertyEncoder->localizedContentName('nodeType', $locale), 'nodeType')
            ->addSelect('node', 'jcr:uuid', 'uuid')
            ->addSelect('node', $this->propertyEncoder->localizedContentName('nodeType', $locale), 'nodeType')
            ->from($qomFactory->selector('node', 'nt:unstructured'))
            ->where($qomFactory->descendantNode('node', $parentPath));
    }

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

    private function appendMapping(QueryBuilder $queryBuilder, $mapping, $locale, $locales)
    {
        foreach ($mapping as $propertyName) {
            $this->appendSingleMapping($queryBuilder, $propertyName, $locale, $locales);
        }
    }

    private function appendSingleMapping(QueryBuilder $queryBuilder, $propertyName, $locale, $locales)
    {
        foreach ($locales as $item) {
            $name = sprintf('%s%s', $item, ucfirst($propertyName));

            if ($locale === $item) {
                $name = $propertyName;
            }

            $queryBuilder->addSelect('node', $this->propertyEncoder->localizedContentName($name, $item), $name);
        }
    }

    private function resolveContent(Row $row, $mapping, $webspaceKey)
    {
        $values = $row->getValues();

        $data = [];
        foreach ($mapping as $item) {
            $data[$item] = $this->resolveProperty($values, $item, null);
        }

        return new Content(
            $row->getValue('uuid'),
            str_replace($this->sessionManager->getContentPath($webspaceKey), '', $row->getPath()),
            $data
        );
    }

    private function resolveProperty($data, $name, $shadowLocale)
    {
        // TODO resolve shadow

        return $data[sprintf('node.%s', $name)];
    }
}
