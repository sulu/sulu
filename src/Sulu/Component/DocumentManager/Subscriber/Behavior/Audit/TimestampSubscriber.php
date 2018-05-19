<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Component\DocumentManager\Subscriber\Behavior\Audit;

use PHPCR\NodeInterface;
use Sulu\Component\DocumentManager\Behavior\Audit\LocalizedTimestampBehavior;
use Sulu\Component\DocumentManager\Behavior\Audit\TimestampBehavior;
use Sulu\Component\DocumentManager\DocumentAccessor;
use Sulu\Component\DocumentManager\Event\HydrateEvent;
use Sulu\Component\DocumentManager\Event\PersistEvent;
use Sulu\Component\DocumentManager\Event\PublishEvent;
use Sulu\Component\DocumentManager\Event\RestoreEvent;
use Sulu\Component\DocumentManager\Events;
use Sulu\Component\DocumentManager\PropertyEncoder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manage the timestamp (created, changed) fields on documents before they are persisted.
 */
class TimestampSubscriber implements EventSubscriberInterface
{
    const CREATED = 'created';

    const CHANGED = 'changed';

    /**
     * @var PropertyEncoder
     */
    private $propertyEncoder;

    public function __construct(PropertyEncoder $propertyEncoder)
    {
        $this->propertyEncoder = $propertyEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::PERSIST => 'setTimestampsOnNodeForPersist',
            Events::PUBLISH => 'setTimestampsOnNodeForPublish',
            Events::RESTORE => ['setChangedForRestore', -32],
            Events::HYDRATE => 'setTimestampsOnDocument',
        ];
    }

    /**
     * Sets the timestamps from the node to the document.
     *
     * @param HydrateEvent $event
     */
    public function setTimestampsOnDocument(HydrateEvent $event)
    {
        $document = $event->getDocument();
        if (!$this->supports($document)) {
            return;
        }

        $accessor = $event->getAccessor();
        $node = $event->getNode();
        $locale = $event->getLocale();

        $encoding = $this->getPropertyEncoding($document);

        $accessor->set(
            static::CHANGED,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, static::CHANGED, $locale),
                null
            )
        );
        $accessor->set(
            static::CREATED,
            $node->getPropertyValueWithDefault(
                $this->propertyEncoder->encode($encoding, static::CREATED, $locale),
                null
            )
        );
    }

    /**
     * Sets the timestamps on the nodes for the persist operation.
     *
     * @param PersistEvent $event
     */
    public function setTimestampsOnNodeForPersist(PersistEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->setTimestampsOnNode(
            $document,
            $event->getNode(),
            $event->getAccessor(),
            $event->getLocale(),
            new \DateTime()
        );
    }

    public function setTimestampsOnNodeForPublish(PublishEvent $event)
    {
        $document = $event->getDocument();

        if (!$this->supports($document)) {
            return;
        }

        $this->setTimestampsOnNode(
            $document,
            $event->getNode(),
            $event->getAccessor(),
            $event->getLocale()
        );
    }

    /**
     * Set the timestamps on the node.
     *
     * @param LocalizedTimestampBehavior $document
     * @param NodeInterface $node
     * @param DocumentAccessor $accessor
     * @param string $locale
     * @param \DateTime|null $timestamp The timestamp to set, will use the documents timestamps if null is provided
     */
    public function setTimestampsOnNode(
        LocalizedTimestampBehavior $document,
        NodeInterface $node,
        DocumentAccessor $accessor,
        $locale,
        $timestamp = null
    ) {
        if (!$document instanceof TimestampBehavior && !$locale) {
            return;
        }

        $encoding = $this->getPropertyEncoding($document);

        $createdPropertyName = $this->propertyEncoder->encode($encoding, static::CREATED, $locale);
        if (!$node->hasProperty($createdPropertyName)) {
            $createdTimestamp = $document->getCreated() ?: $timestamp;
            $accessor->set(static::CREATED, $createdTimestamp);
            $node->setProperty($createdPropertyName, $createdTimestamp);
        }

        $changedTimestamp = $timestamp ?: $document->getChanged();
        $accessor->set(static::CHANGED, $changedTimestamp);
        $node->setProperty($this->propertyEncoder->encode($encoding, static::CHANGED, $locale), $changedTimestamp);
    }

    /**
     * Sets the changed timestamp when restoring a document.
     *
     * @param RestoreEvent $event
     */
    public function setChangedForRestore(RestoreEvent $event)
    {
        $document = $event->getDocument();
        if (!$this->supports($document)) {
            return;
        }

        $encoding = $this->getPropertyEncoding($document);

        $event->getNode()->setProperty(
            $this->propertyEncoder->encode($encoding, static::CHANGED, $event->getLocale()),
            new \DateTime()
        );
    }

    /**
     * Returns the encoding for the given document.
     *
     * @param $document
     *
     * @return string
     */
    private function getPropertyEncoding($document)
    {
        $encoding = 'system_localized';
        if ($document instanceof TimestampBehavior) {
            $encoding = 'system';
        }

        return $encoding;
    }

    /**
     * Return true if document is supported by this subscriber.
     *
     * @param $document
     *
     * @return bool
     */
    private function supports($document)
    {
        return $document instanceof LocalizedTimestampBehavior;
    }
}
