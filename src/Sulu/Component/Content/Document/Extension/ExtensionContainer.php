<?php

namespace Sulu\Component\Content\Document\Extension;

use Sulu\Component\Content\Extension\ExtensionManager;
use PHPCR\NodeInterface;

class ExtensionContainer implements \ArrayAccess
{
    private $extensionManager;
    private $node;
    private $locale;
    private $data;
    private $prefix;
    private $internalPrefix;
    private $structureType;
    private $webspaceName;

    public function __construct(
        $structureType,
        ExtensionManager $extensionManager,
        NodeInterface $node,
        $locale,
        $prefix,
        $internalPrefix,
        $webspaceName
    )
    {
        $this->extensionManager = $extensionManager;
        $this->node = $node;
        $this->locale = $locale;
        $this->prefix = $prefix;
        $this->internalPrefix = $internalPrefix;
        $this->structureType = $structureType;
        $this->webspaceName = $webspaceName;
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

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

    public function offsetSet($extensionName, $data)
    {
        $this->data[$extensionName] = $data;
    }

    public function offsetUnset($extensionName)
    {
        unset($this->data[$extensionName]);
    }
}
