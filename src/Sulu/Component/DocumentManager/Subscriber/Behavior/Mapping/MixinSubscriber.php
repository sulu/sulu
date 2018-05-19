<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Mapping;

use PHPCR\Util\UUIDHelper;
use Sulu\Component\DocumentManager\Event\AbstractMappingEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\MetadataFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MixinSubscriber implements EventSubscriberInterface
{
    /**
     * @var MetadataFactoryInterface
     */
    private $metadataFactory;

    public function __construct(MetadataFactoryInterface $metadataFactory)
    {
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => ['setDocumentMixinsOnNode', 468],
            Events::PUBLISH => ['setDocumentMixinsOnNode', 468],
        ];
    }

    public function setDocumentMixinsOnNode(AbstractMappingEvent $event)
    {
        $node = $event->getNode();
        $document = $event->getDocument();

        $metadata = $this->metadataFactory->getMetadataForClass(get_class($document));

        $node->addMixin($metadata->getPhpcrType());

        if (!$node->hasProperty('jcr:uuid')) {
            $node->setProperty('jcr:uuid', UUIDHelper::generateUUID());
        }
    }
}
