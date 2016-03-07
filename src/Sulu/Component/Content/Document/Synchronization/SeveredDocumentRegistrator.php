<?php

namespace Sulu\Component\Content\Document\Synchronization;

use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Sulu\Component\DocumentManager\DocumentManagerInterface;

/**
 * (DDM = Default Document Manager, TDM = Target Document Manager).
 *
 * This class takes the responsiblity of registering any documents from the DDM
 * that exist in the TDM but are not currently registered.
 */
class SeveredDocumentRegistrator
{
    private $defaultManager;

    public function __construct(
        DocumentManagerInterface $defaultManager
    )
    {
        $this->defaultManager = $defaultManager;
    }

    public function registerDocument(DocumentManagerInterface $targetManager, SynchronizeBehavior $document)
    {
        $metadata = $this->defaultManager->getMetadataFactory()->getMetadataForClass(get_class($document));
        $reflectionClass = $metadata->getReflectionClass();

        foreach (array_keys($metadata->getFieldMappings()) as $field) {
            $reflectionProperty = $reflectionClass->getProperty($field);
            $reflectionProperty->setAccessible(true);
            $propertyValue = $reflectionProperty->getValue($document);

            if (false === is_object($propertyValue)) {
                continue;
            }

            $this->registerObject($targetManager, $propertyValue);
        }
    }

    private function registerObject(DocumentManagerInterface $targetManager, $object)
    {
        $uuid = $this->defaultManager->getInspector()->getUUid($object);
        $locale = $this->defaultManager->getInspector()->getLocale($object);
        $registry = $targetManager->getRegistry();

        if ($targetManager->getNodeManager()->has($uuid)) {
            $node = $targetManager->getNodeManager()->find($uuid);

            $registry->registerDocument(
                $object,
                $node,
                $locale
            );
        }
    }
}
