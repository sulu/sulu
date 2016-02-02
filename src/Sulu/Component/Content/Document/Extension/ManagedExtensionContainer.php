<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\Content\Document\Extension;

use PHPCR\NodeInterface;
use Sulu\Component\Content\Extension\ExtensionManagerInterface;

/**
 * The managed extension container lazily loads data from the actual
 * extension classes. It serves a similar, but not identical, role to
 * the ManagedStructure.
 *
 * In contrast to the Structure, which is a container of properties and returns Property instances,
 * extensions return simple arrays.
 *
 * Note that we should remove this class as retrieving the processed data
 * for extensions should be done externally to the document.
 */
class ManagedExtensionContainer extends ExtensionContainer
{
    /**
     * @var ExtensionManagerInterface
     */
    private $extensionManager;

    /**
     * @var NodeInterface
     */
    private $node;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var string
     */
    private $internalPrefix;

    /**
     * @var string
     */
    private $structureType;

    /**
     * @var string
     */
    private $webspaceName;

    /**
     * @param string                    $structureType
     * @param ExtensionManagerInterface $extensionManager
     * @param NodeInterface             $node
     * @param string                    $locale
     * @param string                    $prefix
     * @param string                    $internalPrefix
     * @param string                    $webspaceName
     */
    public function __construct(
        $structureType,
        ExtensionManagerInterface $extensionManager,
        NodeInterface $node,
        $locale,
        $prefix,
        $internalPrefix,
        $webspaceName
    ) {
        $this->extensionManager = $extensionManager;
        $this->node = $node;
        $this->locale = $locale;
        $this->prefix = $prefix;
        $this->internalPrefix = $internalPrefix;
        $this->structureType = $structureType;
        $this->webspaceName = $webspaceName;
    }

    /**
     * Lazily evaluate the value for the given extension.
     *
     * @param string $extensionName
     *
     * @return mixed
     */
    public function offsetGet($extensionName)
    {
        if (isset($this->data[$extensionName])) {
            return $this->data[$extensionName];
        }

        $extension = $this->extensionManager->getExtension($this->structureType, $extensionName);

        // TODO: should not pass namespace here.
        //       and indeed this call should be removed and the extension should be
        //       passed the document.
        $extension->setLanguageCode($this->locale, $this->prefix, $this->internalPrefix);

        // passing the webspace and locale would also be unnecessary if we passed the
        // document
        $data = $extension->load($this->node, $this->webspaceName, $this->locale);

        $this->data[$extensionName] = $data;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($extensionName)
    {
        return $this->extensionManager->hasExtension($this->structureType, $extensionName);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = [];

        foreach ($this->extensionManager->getExtensions($this->structureType) as $extension) {
            $result[$extension->getName()] = $this->offsetGet($extension->getName());
        }

        return $result;
    }
}
