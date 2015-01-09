<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace DTL\Component\Content\EventSubscriber;

use Doctrine\ODM\PHPCR\Event;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Sulu\Bundle\StructureBundle\Document\Structure;
use DTL\Component\Content\Serializer\SerializerInterface;

class PhpcrOdmStructureSubscriber implements EventSubscriberInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var DocumentManager
     */
    private $documentManager;

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Event::postLoad => 'deserializeContent',
            Event::prePersist => 'serializeContent',
            Event::preUpdate => 'serializeContent',
        );
    }

    /**
     * @param DocumentManager $documentManager
     * @param SerializerInterface $serializer
     */
    public function __construct(DocumentManager $documentManager, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->documentManager = $documentManager;
    }

    /**
     * Deserialize the content data after loading the document
     *
     * @param LifecycleEventArgs $event
     */
    public function deserializeContent(LifecycleEventArgs $event)
    {
        $document = $event->getObject();

        if (false === $this->isStructure()) {
            return;
        }

        $node = $this->documentManager->getNodeForDocument($document);
        $data = $this->serializer->deserialize($node);
        $document->setContentData($data);
    }

    /**
     * Serialize the content data before persisting the document
     *
     * @param LifecycleEventArgs $event
     */
    public function serializeContent(LifecycleEventArgs $event)
    {
        $document = $event->getObject();

        if (false === $this->isStructure()) {
            return;
        }

        $node = $this->documentManager->getNodeForDocument($document);
        $this->serializer->serialize($document->getContentData(), $node);
    }

    private function isStructure($object)
    {
        return $object instanceof Structure;
    }
}
