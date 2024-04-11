<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\PageBundle\Preview;

use JMS\Serializer\DeserializationContext;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Sulu\Bundle\DocumentManagerBundle\Bridge\DocumentInspector;
use Sulu\Bundle\PageBundle\Admin\PageAdmin;
use Sulu\Bundle\PageBundle\Document\BasePageDocument;
use Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface;
use Sulu\Component\Content\Document\Extension\ExtensionContainer;
use Sulu\Component\DocumentManager\DocumentManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Introduces page and home documents in the preview.
 */
class PageObjectProvider implements PreviewObjectProviderInterface
{
    /**
     * @var DocumentManagerInterface
     */
    private $documentManager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DocumentInspector
     */
    private $documentInspector;

    public function __construct(DocumentManagerInterface $documentManager, SerializerInterface $serializer, DocumentInspector $documentInspector)
    {
        $this->documentManager = $documentManager;
        $this->serializer = $serializer;
        $this->documentInspector = $documentInspector;
    }

    public function getObject($id, $locale)
    {
        return $this->documentManager->find($id, $locale);
    }

    /**
     * @param BasePageDocument $object
     */
    public function getId($object)
    {
        return $object->getUuid();
    }

    /**
     * @param BasePageDocument $object
     */
    public function setValues($object, $locale, array $data)
    {
        $propertyAccess = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();

        $structure = $object->getStructure();
        foreach ($data as $property => $value) {
            try {
                if ('ext' === $property) {
                    $object->setExtensionsData(new ExtensionContainer($value));
                    continue;
                }

                $propertyAccess->setValue($structure, $property, $value);
            } catch (\InvalidArgumentException $e) {
                //ignore not existing properties
            }
        }
    }

    /**
     * @param BasePageDocument $object
     */
    public function setContext($object, $locale, array $context)
    {
        if (\array_key_exists('template', $context)) {
            $object->setStructureType($context['template']);
        }

        return $object;
    }

    /**
     * @param BasePageDocument $object
     */
    public function serialize($object)
    {
        return $this->serializer->serialize(
            $object,
            'json',
            SerializationContext::create()->setSerializeNull(true)->setGroups(['preview'])
        );
    }

    public function deserialize($serializedObject, $objectClass)
    {
        return $this->serializer->deserialize(
            $serializedObject,
            $objectClass,
            'json',
            DeserializationContext::create()->setGroups(['preview'])
        );
    }

    public function getSecurityContext($id, $locale): ?string
    {
        $webspaceKey = $this->documentInspector->getWebspace($this->getObject($id, $locale));

        return PageAdmin::getPageSecurityContext($webspaceKey);
    }
}
