<?php

/*
 * This file is part of the Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ContentBundle;

use PHPCR\Migrations\VersionInterface;
use PHPCR\NodeInterface;
use PHPCR\Query\QueryManagerInterface;
use PHPCR\SessionInterface;
use Sulu\Component\Content\Compat\Structure;
use Sulu\Component\Content\Document\RedirectType;
use Sulu\Component\Content\Metadata\Factory\StructureMetadataFactoryInterface;
use Sulu\Component\Content\Metadata\PropertyMetadata;
use Sulu\Component\Content\Metadata\StructureMetadata;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Sulu\Component\PHPCR\SessionManager\SessionManagerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Version201508081500 implements VersionInterface, ContainerAwareInterface
{
    /** @var SessionInterface $session */
    private $session;

    /**
     * @var SessionManagerInterface
     */
    private $sessionManager;

    /**
     * @var QueryManagerInterface
     */
    private $queryManager;

    /**
     * @var WebspaceManagerInterface
     */
    private $webspaceManager;

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    /**
     * @var StructureMetadataFactoryInterface
     */
    private $structureMetadataFactory;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
        $this->sessionManager = $this->container->get('sulu.phpcr.session');
        $this->session = $this->sessionManager->getSession();
        $this->queryManager = $this->session->getWorkspace()->getQueryManager();
        $this->webspaceManager = $this->container->get('sulu_core.webspace.webspace_manager');
        $this->propertyEncoder = $this->container->get('sulu_document_manager.property_encoder');
        $this->structureMetadataFactory = $this->container->get('sulu_content.structure.factory');
    }

    public function up(SessionInterface $session)
    {
        $this->iterateWebspaces([$this, 'upgradeNode']);

        $this->session->save();
    }

    public function down(SessionInterface $session)
    {
        $this->iterateWebspaces([$this, 'downgradeNode']);

        $this->session->save();
    }

    /**
     * Iterates over webspaces.
     *
     * @param callable $callback
     */
    protected function iterateWebspaces(callable $callback)
    {
        foreach ($this->webspaceManager->getWebspaceCollection()->getWebspaces() as $webspace) {
            foreach ($webspace->getAllLocalizations() as $localization) {
                $locale = $localization->getLocalization();
                $this->iterateStructures($locale, 'page', $callback);
                $this->iterateStructures($locale, 'snippet', $callback);

                $this->iterateExternalLink($locale, $callback);
            }
        }
    }

    /**
     * Iterates over structures.
     *
     * @param string $locale
     * @param $type
     * @param callable $callback
     */
    protected function iterateStructures($locale, $type, callable $callback)
    {
        foreach ($this->structureMetadataFactory->getStructures($type) as $metadata) {
            $properties = $this->getUrlProperties($metadata);
            if (count($properties) > 0) {
                $this->iterateProperties($metadata, $properties, $locale, $callback);
            }
        }
    }

    /**
     * Iterate over given properties.
     *
     * @param StructureMetadata $metadata
     * @param PropertyMetadata[] $properties
     * @param string $locale
     * @param callable $callback
     */
    private function iterateProperties(StructureMetadata $metadata, $properties, $locale, callable $callback)
    {
        $sql = <<<EOT
SELECT * FROM [nt:unstructured] WHERE [%s] = "%s"
EOT;

        $query = $this->queryManager->createQuery(
            sprintf(
                $sql,
                $this->propertyEncoder->localizedSystemName('template', $locale),
                $metadata->getName()
            ),
            'JCR-SQL2'
        );
        $rows = $query->execute();

        foreach ($properties as $property) {
            $name = $property->getName();
            if ($property->isLocalized()) {
                $name = $this->propertyEncoder->localizedContentName($name, $locale);
            }

            foreach ($rows->getNodes() as $node) {
                $callback($node, $name);
            }
        }
    }

    /**
     * @param string $locale
     * @param callable $callback
     */
    private function iterateExternalLink($locale, $callback)
    {
        $sql = <<<EOT
SELECT * FROM [nt:unstructured] WHERE [%s] = "%s"
EOT;

        $query = $this->queryManager->createQuery(
            sprintf(
                $sql,
                $this->propertyEncoder->localizedSystemName('nodeType', $locale),
                RedirectType::EXTERNAL
            ),
            'JCR-SQL2'
        );
        $rows = $query->execute();

        $name = $this->propertyEncoder->localizedSystemName('external', $locale);
        foreach ($rows->getNodes() as $node) {
            $callback($node, $name);
        }
    }

    /**
     * Returns url properties for given metadata.
     *
     * @param StructureMetadata $metadata
     *
     * @return PropertyMetadata[]
     */
    private function getUrlProperties(StructureMetadata $metadata)
    {
        $result = [];
        foreach ($metadata->getProperties() as $property) {
            if ($property->getType() === 'url') {
                $result[] = $property;
            }
        }

        return $result;
    }

    /**
     * Upgrades given node with property-name.
     *
     * @param NodeInterface $node
     * @param string $propertyName
     */
    private function upgradeNode(NodeInterface $node, $propertyName)
    {
        if (!$node->hasProperty($propertyName)) {
            return;
        }

        $value = $node->getPropertyValue($propertyName);
        if (!empty($value) && !strpos($value, '://')) {
            $value = 'http://' . $value;
        }
        $node->setProperty($propertyName, $value);
    }

    /**
     * Downgrades node with given property-name.
     *
     * @param NodeInterface $node
     * @param string $propertyName
     */
    private function downgradeNode(NodeInterface $node, $propertyName)
    {
        if (!$node->hasProperty($propertyName)) {
            return;
        }

        $value = $node->getPropertyValue($propertyName);
        if (!empty($value) && strpos($value, '://')) {
            $value = explode('://', $value)[1];
        }
        $node->setProperty($propertyName, $value);
    }
}
